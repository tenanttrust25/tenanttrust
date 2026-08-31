<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TwilioVoiceController extends Controller
{
    protected string $steveNumber;
    protected string $businessNumber;
    protected string $notifyEmail;
    protected string $escalationEmail;
    protected string $voice;
    protected string $voiceLang;

    public function __construct()
    {
        $this->steveNumber = env('TWILIO_STEVE_NUMBER', '+17056269246');
        $this->businessNumber = env('TWILIO_BUSINESS_NUMBER', '+12495233331');
        $this->notifyEmail = env('TWILIO_NOTIFY_EMAIL', 'info@tenanttrust.ca');
        $this->escalationEmail = env('TWILIO_ESCALATION_EMAIL', 'steve.ouimette@icloud.com');
        $this->voice = env('TWILIO_VOICE', 'Polly.Matthew-Generative');
        $this->voiceLang = env('TWILIO_VOICE_LANG', 'en-CA');
    }

    /* ================================================================
     * ENTRY POINT
     * ================================================================ */

    public function voice(Request $request)
    {
        $callSid = (string) $request->input('CallSid', '');
        $from = (string) $request->input('From', '');
        $to = (string) $request->input('To', '');

        $log = CallLog::updateOrCreate(
            ['call_sid' => $callSid],
            [
                'from_number' => $from,
                'to_number' => $to,
                'state' => 'greet',
                'status' => 'in_progress',
            ]
        );

        Log::info('Twilio call started', ['call_sid' => $callSid, 'from' => $from]);

        $twiml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Thank you for calling Tenant Trust. <break time="300ms"/> Jarvis speaking. <break time="200ms"/> How can I help you?') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
                'Steve, balance, payment, portal, outstanding, rent, maintenance, repair, leak, office, hours') .
            $this->say('Sorry, I did not catch that. <break time=\"200ms\"/> Go ahead and leave a message now.') .
            $this->recordVerbs() .
            '</Response>';

        return $this->twiml($twiml);
    }

    /* ================================================================
     * MAIN DISPATCHER (state machine)
     * ================================================================ */

    public function gather(Request $request)
    {
        $callSid = (string) $request->input('CallSid', '');
        $log = CallLog::where('call_sid', $callSid)->first();

        if (!$log) {
            return $this->twiml($this->fallbackResponse());
        }

        $digits = trim((string) $request->input('Digits', ''));
        $speech = trim((string) $request->input('SpeechResult', ''));
        $input = $digits !== '' ? $digits : strtolower($speech);

        // Log what the caller said.
        $log->appendTranscript('caller', $speech !== '' ? $speech : ($digits !== '' ? 'DTMF: ' . $digits : '(silence)'));

        switch ($log->state) {
            case 'greet':
                return $this->handleGreet($log, $input, $speech, $digits);

            case 'ask_name':
                return $this->handleAskName($log, $input);

            case 'ask_address':
                return $this->handleAskAddress($log, $input);

            case 'explain':
                return $this->handleExplain($log, $input);

            case 'ask_reference':
                return $this->handleAskReference($log, $input);

            case 'escalate':
                return $this->handleEscalate($log, $input);

            case 'maintenance_issue':
                return $this->handleMaintenanceIssue($log, $input);

            case 'maintenance_confirm':
                return $this->handleMaintenanceConfirm($log, $input);

            case 'info':
                return $this->handleInfo($log, $input);

            default:
                return $this->twiml($this->fallbackResponse());
        }
    }

    /* ================================================================
     * STATE HANDLERS
     * ================================================================ */

    protected function handleGreet(CallLog $log, string $input, string $speech, string $digits)
    {
        // DTMF 1 or explicit request to talk to Steve.
        if ($digits === '1' || $this->containsAny($input, ['steve', 'landlord', 'owner', 'manager', 'human', 'person', 'talk to'])) {
            $log->update(['intent' => 'steve', 'state' => 'done']);
            Log::info('Twilio call: transferring to Steve', ['call_sid' => $log->call_sid, 'speech' => $speech]);
            return $this->twiml($this->transferToSteveResponse());
        }

        if ($this->containsAny($input, ['balance', 'outstanding', 'portal', 'payment', 'paid', 'owe', 'money', 'rent', 'invoice', 'amount', 'due'])) {
            $log->update(['intent' => 'balance', 'state' => 'ask_name']);
            return $this->twiml($this->askNameResponse());
        }

        if ($this->containsAny($input, ['maintenance', 'repair', 'leak', 'broken', 'plumbing', 'heating', 'electrical', 'appliance', 'fridge', 'stove', 'toilet', 'water', 'door', 'window', 'fix'])) {
            $log->update(['intent' => 'maintenance', 'state' => 'ask_name']);
            return $this->twiml($this->askNameResponse());
        }

        if ($this->containsAny($input, ['hour', 'address', 'where', 'office', 'contact', 'pay', 'method', 'portal access', 'website'])) {
            $log->update(['intent' => 'info', 'state' => 'info']);
            return $this->twiml($this->infoResponse());
        }

        // Didn't understand — ask once more, then route to voicemail.
        $retries = (int) ($log->data['greet_retries'] ?? 0) + 1;
        $log->update(['data' => array_merge($log->data ?? [], ['greet_retries' => $retries])]);
        if ($retries >= 2) {
            return $this->twiml($this->fallbackResponse());
        }
        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Sorry, I did not quite catch that. Are you calling about your balance or a payment, a maintenance issue, or would you like to speak with Steve?') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
                'Steve, balance, payment, portal, outstanding, rent, maintenance, repair, leak') .
            '</Response>'
        );
    }

    protected function handleAskName(CallLog $log, string $input)
    {
        // This is the caller's NAME — never route on "steve" etc. here.
        $log->update([
            'tenant_name' => $this->titleCase($input),
            'state' => 'ask_address',
        ]);

        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Thank you, <break time="200ms"/> ' . $this->esc($log->tenant_name) . '. And can I have the address on the account, please?') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', null,
                'street, avenue, road, unit, apartment, lane, boulevard, drive, court, crescent') .
            '</Response>'
        );
    }

    protected function handleAskAddress(CallLog $log, string $input)
    {
        $log->update(['tenant_address' => $this->titleCase($input)]);

        // Look up the tenant: caller ID first, then fuzzy name + address.
        $tenant = $this->lookupTenant($log->from_number, $log->tenant_name, $log->tenant_address);

        if ($tenant) {
            $log->update(['tenant_id' => $tenant->id, 'state' => 'working']);
            Log::info('Twilio call: tenant matched', [
                'call_sid' => $log->call_sid,
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
            ]);
        }

        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Thank you. Please give me a moment to look into this for you. <break time="500ms"/>') .
            $this->say($this->resolveForIntent($log)) .
            $this->followUpGather($log) .
            '</Response>'
        );
    }

    protected function resolveForIntent(CallLog $log): string
    {
        $tenant = $log->tenant_id ? User::find($log->tenant_id) : null;

        if (!$tenant) {
            $log->update(['state' => 'escalate']);
            return 'I apologize, but I was not able to find an account with that name and address. ' .
                'I can connect you with Steve right now if you would like. Press 1, or say yes. ' .
                'Otherwise, press 2 and Steve will call you back within 24 hours.';
        }

        if ($log->intent === 'maintenance') {
            $log->update(['state' => 'maintenance_issue']);
            return 'I have your account on file. What is the maintenance issue you are experiencing?';
        }

        // Balance / payment flow: explain, or ask for a reference if no payment found.
        $explanation = $this->buildBalanceExplanation($tenant);
        $log->update(['state' => str_contains($explanation, 'reference number') ? 'ask_reference' : 'explain']);
        return $explanation;
    }

    protected function handleExplain(CallLog $log, string $input)
    {
        if ($this->wantsSteve($input)) {
            $log->update(['state' => 'done']);
            return $this->twiml($this->transferToSteveResponse());
        }
        if ($this->wantsDone($input)) {
            return $this->twiml($this->goodbyeResponse($log));
        }

        // Otherwise loop back to the main menu.
        $log->update(['state' => 'greet']);
        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Is there anything else I can help you with?') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
                'Steve, balance, payment, portal, outstanding, rent, maintenance, repair, leak') .
            '</Response>'
        );
    }

    protected function handleAskReference(CallLog $log, string $input)
    {
        $log->update(['reference' => $input, 'state' => 'escalate']);

        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Thank you. I will escalate this to Steve. Would you like me to connect you with him right now? Press 1, or say yes. Otherwise press 2, and Steve will reach out to you within the next 24 hours.') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1, 'Steve, yes, no, callback, call back, 24') .
            '</Response>'
        );
    }

    protected function handleEscalate(CallLog $log, string $input)
    {
        if ($this->wantsSteve($input)
            || $this->containsAny($input, ['yes', 'yeah', 'right now', 'now', 'connect'])
        ) {
            $log->update(['state' => 'done']);
            return $this->twiml($this->transferToSteveResponse());
        }

        // 24h route: email Steve the call log.
        $log->update(['state' => 'done', 'status' => 'escalated', 'ended_at' => now()]);
        $this->sendEscalationEmail($log);

        return $this->twiml($this->goodbyeResponse($log, true));
    }

    protected function handleMaintenanceIssue(CallLog $log, string $input)
    {
        if ($this->containsAny($input, ['steve', 'landlord', 'owner', 'manager', 'human', 'person'])) {
            $log->update(['state' => 'done']);
            return $this->twiml($this->transferToSteveResponse());
        }

        $log->update(['issue' => $input, 'state' => 'maintenance_confirm']);

        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Got it. Let me log that for you.') .
            $this->say($this->createMaintenanceTicket($log)) .
            $this->followUpGather($log) .
            '</Response>'
        );
    }

    protected function handleMaintenanceConfirm(CallLog $log, string $input)
    {
        if ($this->wantsSteve($input)) {
            $log->update(['state' => 'done']);
            return $this->twiml($this->transferToSteveResponse());
        }
        if ($this->wantsDone($input)) {
            return $this->twiml($this->goodbyeResponse($log));
        }

        $log->update(['state' => 'greet']);
        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Is there anything else I can help you with?') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
                'Steve, balance, payment, portal, outstanding, rent, maintenance, repair, leak') .
            '</Response>'
        );
    }

    protected function handleInfo(CallLog $log, string $input)
    {
        if ($this->wantsSteve($input)) {
            $log->update(['state' => 'done']);
            return $this->twiml($this->transferToSteveResponse());
        }
        if ($this->wantsDone($input)) {
            return $this->twiml($this->goodbyeResponse($log));
        }

        $log->update(['state' => 'greet']);
        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Is there anything else I can help you with?') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
                'Steve, balance, payment, portal, outstanding, rent, maintenance, repair, leak') .
            '</Response>'
        );
    }

    /* ================================================================
     * BALANCE / PAYMENT EXPLANATION
     * ================================================================ */

    protected function buildBalanceExplanation(User $tenant): string
    {
        $outstanding = $tenant->outstandingBalance();

        $unpaidInvoices = $tenant->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->with(['payments' => fn ($q) => $q->where('status', 'completed')])
            ->orderBy('due_date')
            ->get();

        if ($outstanding <= 0.0 || $unpaidInvoices->isEmpty()) {
            return 'Good news. Your account is fully up to date, with no outstanding balance. ' .
                'If the portal is still showing an amount, it may take up to 24 hours to refresh.';
        }

        // Check for the classic "I paid but it still shows outstanding" case.
        $explanations = [];
        $foundProblem = false;
        foreach ($unpaidInvoices as $invoice) {
            $paidSum = (float) $invoice->payments->sum('amount');
            $amount = (float) $invoice->amount;

            if ($paidSum > 0) {
                $lastPayment = $invoice->payments->sortByDesc('paid_at')->first();
                $paidDate = $lastPayment && $lastPayment->paid_at
                    ? $lastPayment->paid_at->setTimezone('America/Toronto')->format('F j, Y')
                    : 'recently';

                if ($paidSum >= $amount) {
                    $explanations[] = 'Your payment of ' . $this->money($amount) .
                        ' for invoice ' . $invoice->invoice_number . ' was received on ' . $paidDate . '. ' .
                        'The portal can take up to 24 hours to update, so it will show as paid shortly.';
                    $foundProblem = true;
                } else {
                    $explanations[] = 'A partial payment of ' . $this->money($paidSum) .
                        ' was received on ' . $paidDate . ' for invoice ' . $invoice->invoice_number .
                        ', but ' . $this->money($amount - $paidSum) . ' remains outstanding.';
                    $foundProblem = true;
                }
            } else {
                $due = $invoice->due_date ? $invoice->due_date->setTimezone('America/Toronto')->format('F j, Y') : 'recently';
                if ($invoice->status === 'pending') {
                    $explanations[] = 'Invoice ' . $invoice->invoice_number . ' for ' . $this->money($amount) .
                        ' is currently pending and not yet due. It is scheduled for ' . $due .
                        ', so no payment is needed at this time. If the portal is showing it as an amount due, ' .
                        'it is your upcoming invoice.';
                    $foundProblem = true;
                } else {
                    $explanations[] = 'Invoice ' . $invoice->invoice_number . ' for ' . $this->money($amount) .
                        ' was due on ' . $due . ' and I do not see a payment for it on file.';
                }
            }
        }

        $outstandingText = 'Your current outstanding balance is ' . $this->money($outstanding) . '.';

        // If we explained the situation (paid-but-showing or upcoming invoice), great.
        // Otherwise offer the reference-number escalation path.
        if (!$foundProblem) {
            return 'Thank you for holding. Unfortunately, I cannot find a payment for that amount on your account. ' .
                'Do you have a reference number for the payment?';
        }

        return 'Thank you for holding. I have found the problem. ' .
            implode(' ', $explanations) . ' ' . $outstandingText;
    }

    /* ================================================================
     * TENANT LOOKUP
     * ================================================================ */

    protected function lookupTenant(?string $fromNumber, ?string $name, ?string $address): ?User
    {
        // 1) Caller ID match (most reliable).
        if ($fromNumber) {
            $phone = $this->normalizePhone($fromNumber);
            if ($phone !== '') {
                $user = User::where('role', 'tenant')->get()
                    ->first(fn ($u) => $this->normalizePhone($u->phone) === $phone);
                if ($user) {
                    return $user;
                }
            }
        }

        // 2) Fuzzy name + address match.
        if ($name) {
            $nameTokens = $this->nameTokens($name);
            if (!empty($nameTokens)) {
                $candidates = User::where('role', 'tenant')->get();
                $best = null;
                $bestScore = 0;
                foreach ($candidates as $user) {
                    $score = $this->matchName($nameTokens, $user->name);
                    if ($score > 0 && $address) {
                        $score += $this->matchAddress($address, $user->address);
                    }
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $best = $user;
                    }
                }
                if ($best && $bestScore >= 2) {
                    return $best;
                }
            }
        }

        return null;
    }

    protected function normalizePhone(?string $phone): string
    {
        if (!$phone) {
            return '';
        }
        $digits = preg_replace('/\D/', '', (string) $phone);
        // Keep last 10 digits (NA format).
        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    protected function nameTokens(string $name): array
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z\s-]/', ' ', $name);
        $tokens = preg_split('/\s+/', trim($name)) ?: [];
        return array_values(array_filter($tokens, fn ($t) => strlen($t) >= 2));
    }

    protected function matchName(array $tokens, string $userName): int
    {
        $userTokens = $this->nameTokens($userName);
        if (empty($userTokens)) {
            return 0;
        }
        $score = 0;
        foreach ($tokens as $t) {
            foreach ($userTokens as $ut) {
                if ($t === $ut) {
                    $score += 1;
                } elseif (str_starts_with($ut, $t) || str_starts_with($t, $ut)) {
                    $score += 1;
                }
            }
        }
        // Require at least a first name or last name to match.
        return $score;
    }

    protected function matchAddress(string $spoken, string $stored): int
    {
        $spoken = strtolower($this->normalizeAddress($spoken));
        $stored = strtolower($this->normalizeAddress($stored));

        $spokenTokens = $this->addressTokens($spoken);
        $storedTokens = $this->addressTokens($stored);

        if (empty($spokenTokens) || empty($storedTokens)) {
            return 0;
        }

        // Street number must match.
        $spokenNumber = $this->extractStreetNumber($spokenTokens);
        $storedNumber = $this->extractStreetNumber($storedTokens);
        if ($spokenNumber && $storedNumber && $spokenNumber !== $storedNumber) {
            return 0;
        }

        $score = 0;
        foreach ($spokenTokens as $t) {
            if (in_array($t, $storedTokens, true)) {
                $score += 1;
            }
        }
        return $score;
    }

    protected function normalizeAddress(string $address): string
    {
        $address = strtolower(trim($address));
        $address = preg_replace('/[^a-z0-9\s-]/', ' ', $address);
        $address = preg_replace('/\s+/', ' ', $address);
        return $address;
    }

    protected function addressTokens(string $address): array
    {
        $tokens = preg_split('/\s+/', trim($address)) ?: [];
        $skip = ['street', 'st', 'avenue', 'ave', 'road', 'rd', 'unit', 'apt', 'apartment',
            'suite', 'lane', 'boulevard', 'blvd', 'drive', 'dr', 'court', 'crescent', 'cres',
            'north', 'south', 'east', 'west', 'n', 's', 'e', 'w', 'sudbury', 'ontario', 'on',
            'p3c', 'p3b', 'p3e', 'p3g', 'canada'];
        return array_values(array_filter($tokens, fn ($t) => !in_array($t, $skip, true)));
    }

    protected function extractStreetNumber(array $tokens): ?string
    {
        foreach ($tokens as $t) {
            if (ctype_digit($t) && strlen($t) >= 2) {
                return $t;
            }
        }
        return null;
    }

    /* ================================================================
     * MAINTENANCE TICKET
     * ================================================================ */

    protected function createMaintenanceTicket(CallLog $log): string
    {
        $tenant = $log->tenant_id ? User::find($log->tenant_id) : null;
        if (!$tenant || !$log->issue) {
            $log->update(['state' => 'escalate']);
            return 'I apologize, I was not able to log that. I will escalate this to Steve and he will call you back within 24 hours.';
        }

        $tenancy = $tenant->tenancies()->orderByDesc('id')->first();

        $ticket = MaintenanceTicket::create([
            'tenant_id' => $tenant->id,
            'property_id' => $tenancy->property_id ?? null,
            'property_unit_id' => $tenancy->property_unit_id ?? null,
            'ticket_number' => MaintenanceTicket::generateTicketNumber(),
            'title' => substr($log->issue, 0, 100),
            'description' => 'Logged via phone (Jarvis receptionist). Caller: ' . $log->from_number .
                ' | Issue: ' . $log->issue,
            'priority' => 'normal',
            'status' => 'open',
            'category' => 'phone',
        ]);

        $log->update(['reference' => $ticket->ticket_number]);

        Log::info('Maintenance ticket created via phone', [
            'call_sid' => $log->call_sid,
            'tenant_id' => $tenant->id,
            'ticket_number' => $ticket->ticket_number,
        ]);

        return 'I have logged maintenance ticket ' . $ticket->ticket_number . ' for your address. ' .
            'Our team will follow up with you within 24 hours.';
    }

    /* ================================================================
     * ESCALATION EMAIL (24h route)
     * ================================================================ */

    protected function sendEscalationEmail(CallLog $log): void
    {
        $tenant = $log->tenant_id ? User::find($log->tenant_id) : null;
        $transcript = $log->transcript ?? [];
        $lines = [];
        foreach ($transcript as $entry) {
            $lines[] = strtoupper($entry['speaker'] ?? '?') . ': ' . ($entry['text'] ?? '');
        }

        $subject = '📞 Escalation needed — ' . ($tenant->name ?? $log->tenant_name ?? 'unknown tenant');
        $body = "A caller needs follow-up (24h route).\n\n"
            . "Call SID: {$log->call_sid}\n"
            . "Caller: {$log->from_number}\n"
            . "Time: " . now()->setTimezone('America/Toronto')->format('Y-m-d H:i T') . "\n"
            . "Tenant: " . ($tenant->name ?? $log->tenant_name ?? 'unknown') . "\n"
            . "Address: " . ($tenant->address ?? $log->tenant_address ?? 'unknown') . "\n"
            . "Intent: {$log->intent}\n"
            . "Reference: " . ($log->reference ?? 'n/a') . "\n"
            . "Issue: " . ($log->issue ?? 'n/a') . "\n\n"
            . "Transcript:\n" . implode("\n", $lines) . "\n";

        try {
            Mail::raw($body, function ($message) use ($subject) {
                $message->to($this->escalationEmail)
                    ->cc($this->notifyEmail)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            Log::info('Twilio escalation email sent', ['to' => $this->escalationEmail, 'cc' => $this->notifyEmail, 'call_sid' => $log->call_sid]);
        } catch (\Throwable $e) {
            Log::error('Twilio escalation email failed: ' . $e->getMessage(), ['call_sid' => $log->call_sid]);
        }
    }

    /* ================================================================
     * RESPONSE BUILDERS
     * ================================================================ */

    protected function askNameResponse(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('My apologies, give me a moment to look into it for you. ' .
                'Can I please have your name and the address on the account?') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', null,
                'Steve, Ouimette, Sugrim, tenant, resident, first name, last name') .
            '</Response>';
    }

    protected function infoResponse(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('For portal access, payments, and general questions, you can also visit our website at tenanttrust.ca, ' .
                'or speak with Steve directly. Would you like me to connect you with Steve now? Press 1, or say yes. ' .
                'Otherwise, is there anything else I can help you with?') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1, 'Steve, yes, no') .
            '</Response>';
    }

    protected function followUpGather(CallLog $log): string
    {
        return $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
            'Steve, yes, no, thank you, goodbye, nothing else, balance, maintenance');
    }

    protected function transferToSteveResponse(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Sure thing. Connecting you with Steve now. <break time="300ms"/> One moment, please.') .
            '<Dial callerId="' . $this->businessNumber . '" timeout="25" ' .
            'action="' . $this->appUrl('/twilio/dial-result') . '" method="POST">' .
            $this->steveNumber . '</Dial>' .
            '</Response>';
    }

    protected function goodbyeResponse(CallLog $log, bool $escalated = false): string
    {
        $log->update(['state' => 'done', 'status' => $escalated ? 'escalated' : 'resolved', 'ended_at' => now()]);

        $text = $escalated
            ? 'I have escalated this to Steve and he will reach out to you within the next 24 hours. ' .
              'Thank you for calling Tenant Trust. Have a great day.'
            : 'Thank you for calling Tenant Trust. Have a great day.';

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' . $this->say($text) . '<Hangup/>' . '</Response>';
    }

    protected function fallbackResponse(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Sorry, I did not catch that. Go ahead and leave a message after the beep.') .
            $this->recordVerbs() .
            '</Response>';
    }

    /* ================================================================
     * UTILITIES
     * ================================================================ */

    protected function gatherTag(string $action, string $input, ?int $numDigits, string $hints): string
    {
        $nd = $numDigits !== null ? ' numDigits="' . $numDigits . '"' : '';
        return '<Gather input="' . $input . '" language="en-CA" speechTimeout="auto" timeout="6"' . $nd . ' ' .
            'hints="' . $hints . '" ' .
            'action="' . $action . '" method="POST"/>';
    }

    protected function say(string $text): string
    {
        return '<Say voice="' . $this->voice . '" language="' . $this->voiceLang . '">' .
            $this->esc($text) . '</Say>';
    }

    protected function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected function containsAny(string $haystack, array $needles): bool
    {
        $haystack = strtolower($haystack);
        foreach ($needles as $needle) {
            if (str_contains($haystack, strtolower($needle))) {
                return true;
            }
        }
        return false;
    }

    protected function wantsSteve(string $input): bool
    {
        return $this->containsAny($input, ['steve', 'landlord', 'owner', 'manager', 'human', 'person', 'talk to', 'connect', '1']);
    }

    protected function wantsDone(string $input): bool
    {
        return $this->containsAny($input, ['no', 'nothing', 'goodbye', 'bye', 'thank', 'great day', 'done', 'that is all', 'that\'s all', '2']);
    }

    protected function titleCase(string $input): string
    {
        return mb_convert_case(trim($input), MB_CASE_TITLE, 'UTF-8');
    }

    protected function money(float $amount): string
    {
        $amount = round($amount, 2);
        $dollars = floor($amount);
        $cents = round(($amount - $dollars) * 100);

        $text = $this->numberToWords((int) $dollars) . ($dollars == 1 ? ' dollar' : ' dollars');
        if ($cents > 0) {
            $text .= ' and ' . $this->numberToWords($cents) . ($cents == 1 ? ' cent' : ' cents');
        }
        return $text;
    }

    protected function numberToWords(int $number): string
    {
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
            'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
            'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        if ($number < 20) {
            return $ones[$number];
        }
        if ($number < 100) {
            return $tens[intdiv($number, 10)] . ($number % 10 ? '-' . $ones[$number % 10] : '');
        }
        if ($number < 1000) {
            return $ones[intdiv($number, 100)] . ' hundred' . ($number % 100 ? ' ' . $this->numberToWords($number % 100) : '');
        }
        if ($number < 1000000) {
            return $this->numberToWords(intdiv($number, 1000)) . ' thousand' .
                ($number % 1000 ? ' ' . $this->numberToWords($number % 1000) : '');
        }
        return (string) $number;
    }

    protected function appUrl(string $path): string
    {
        return rtrim(config('app.url'), '/') . $path;
    }

    protected function twiml(string $xml)
    {
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    protected function recordVerbs(): string
    {
        return '<Record maxLength="120" timeout="10" transcribe="true" ' .
            'transcribeCallback="' . $this->appUrl('/twilio/record') . '" ' .
            'recordingStatusCallback="' . $this->appUrl('/twilio/record') . '" ' .
            'action="' . $this->appUrl('/twilio/record') . '" method="POST"/>' .
            $this->say('Thank you. Goodbye.');
    }

    /* ================================================================
     * LEGACY: voicemail email + dial result (kept from original build)
     * ================================================================ */

    public function dialResult(Request $request)
    {
        $status = (string) $request->input('DialCallStatus', '');

        Log::info('Twilio call: dial result', [
            'from' => $request->input('From'),
            'call_sid' => $request->input('CallSid'),
            'dial_status' => $status,
        ]);

        if (in_array($status, ['completed', 'answered'], true)) {
            return $this->twiml('<?xml version="1.0" encoding="UTF-8"?>' . "\n<Response/>");
        }

        $twiml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Steve is not available right now. <break time="300ms"/> Go ahead and leave a message after the beep.') .
            $this->recordVerbs() .
            '</Response>';

        return $this->twiml($twiml);
    }

    public function record(Request $request)
    {
        $recordingSid = (string) $request->input('RecordingSid', '');
        $recordingUrl = (string) $request->input('RecordingUrl', '');
        $transcript = (string) $request->input('TranscriptionText', '');
        $from = (string) $request->input('From', '');
        $callSid = (string) $request->input('CallSid', '');
        $duration = (string) $request->input('RecordingDuration', '');
        $isTranscription = (bool) $request->input('TranscriptionSid', '');

        $entry = json_encode([
            'ts' => now()->toIso8601String(),
            'recording_sid' => $recordingSid,
            'recording_url' => $recordingUrl,
            'transcript' => $transcript,
            'from' => $from,
            'call_sid' => $callSid,
            'duration' => $duration,
            'callback' => $isTranscription ? 'transcription' : 'recording',
        ], JSON_UNESCAPED_SLASHES);

        file_put_contents(
            storage_path('logs/twilio-voicemail.jsonl'),
            $entry . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($recordingUrl !== '' && !$this->wasEmailed($recordingSid)) {
            $this->markEmailed($recordingSid);
            $this->sendVoicemailEmail($from, $recordingUrl, $transcript, $duration, $recordingSid);
        }

        return $this->twiml('<?xml version="1.0" encoding="UTF-8"?>' . "\n<Response/>");
    }

    protected function sendVoicemailEmail(string $from, string $recordingUrl, string $transcript, string $duration, string $recordingSid): void
    {
        $mediaUrl = preg_match('/\.(mp3|wav)$/i', $recordingUrl)
            ? $recordingUrl
            : rtrim($recordingUrl, '/') . '.mp3';

        $subject = '📞 Voicemail from ' . ($from ?: 'unknown caller');

        $body = "New voicemail received.\n\n"
            . "Caller: {$from}\n"
            . "Duration: {$duration}s\n"
            . "Time: " . now()->setTimezone('America/Toronto')->format('Y-m-d H:i T') . "\n"
            . "Recording: {$mediaUrl}\n"
            . "Recording SID: {$recordingSid}\n";

        if ($transcript !== '') {
            $body .= "\nTranscript:\n{$transcript}\n";
        } else {
            $body .= "\n(Transcript may follow in a moment — it's processed separately by Twilio.)\n";
        }

        $attachment = $this->downloadRecording($mediaUrl);
        if ($attachment !== null) {
            $body .= "\n(Recording attached to this email — no login required.)\n";
        }

        try {
            Mail::raw($body, function ($message) use ($subject, $attachment) {
                $message->to($this->notifyEmail)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
                if ($attachment !== null) {
                    $message->attach($attachment, [
                        'as' => 'voicemail.mp3',
                        'mime' => 'audio/mpeg',
                    ]);
                }
            });
            Log::info('Twilio voicemail email sent', ['to' => $this->notifyEmail, 'recording_sid' => $recordingSid]);
        } catch (\Throwable $e) {
            Log::error('Twilio voicemail email failed: ' . $e->getMessage(), ['recording_sid' => $recordingSid]);
        } finally {
            if ($attachment !== null && is_file($attachment)) {
                @unlink($attachment);
            }
        }
    }

    protected function downloadRecording(string $url): ?string
    {
        $sid = (string) env('TWILIO_ACCOUNT_SID', '');
        $token = (string) env('TWILIO_AUTH_TOKEN', '');
        if ($sid === '' || $token === '') {
            return null;
        }

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERPWD => $sid . ':' . $token,
            ]);
            $data = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($code !== 200 || $data === false || strlen($data) < 100) {
                return null;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'vm_');
            file_put_contents($tmp, $data);
            return $tmp;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function wasEmailed(string $recordingSid): bool
    {
        $logFile = storage_path('logs/twilio-voicemail-emailed.jsonl');
        if (!is_file($logFile)) {
            return false;
        }
        foreach (file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_contains($line, $recordingSid)) {
                return true;
            }
        }
        return false;
    }

    protected function markEmailed(string $recordingSid): void
    {
        file_put_contents(
            storage_path('logs/twilio-voicemail-emailed.jsonl'),
            json_encode(['ts' => now()->toIso8601String(), 'recording_sid' => $recordingSid]) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
