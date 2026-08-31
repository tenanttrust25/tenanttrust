<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Services\VoiceBrainService;
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
    protected VoiceBrainService $brain;

    public function __construct()
    {
        $this->steveNumber = env('TWILIO_STEVE_NUMBER', '+17056269246');
        $this->businessNumber = env('TWILIO_BUSINESS_NUMBER', '+12495233331');
        $this->notifyEmail = env('TWILIO_NOTIFY_EMAIL', 'info@tenanttrust.ca');
        $this->escalationEmail = env('TWILIO_ESCALATION_EMAIL', 'steve.ouimette@icloud.com');
        $this->voice = env('TWILIO_VOICE', 'Polly.Matthew-Generative');
        $this->voiceLang = env('TWILIO_VOICE_LANG', 'en-CA');
        $this->brain = new VoiceBrainService();
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
                'data' => ['turns' => 0],
            ]
        );

        Log::info('Twilio call started', ['call_sid' => $callSid, 'from' => $from]);

        $twiml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Thank you for calling Tenant Trust. Jarvis speaking. How can I help you?') .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
                'Steve, balance, payment, portal, outstanding, rent, maintenance, repair, leak, office, hours, hello, help') .
            $this->say('Sorry, I did not catch that. Go ahead and leave a message now.') .
            $this->recordVerbs() .
            '</Response>';

        return $this->twiml($twiml);
    }

    /* ================================================================
     * MAIN DISPATCHER (LLM-driven conversation, deterministic routing)
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
        $data = $log->data ?? [];
        $data['turns'] = (int) ($data['turns'] ?? 0) + 1;
        $log->update(['data' => $data]);

        // ---- Deterministic routing that MUST never misfire ----
        // Transfer to Steve (always wins).
        if ($digits === '1'
            || $this->containsAny($input, [
                'talk to steve', 'speak to steve', 'speak with steve', 'connect', 'with steve',
                'landlord', 'owner', 'manager', 'a human', 'a person', 'someone',
            ])) {
            $log->update(['state' => 'done', 'intent' => 'steve']);
            Log::info('Twilio call: transferring to Steve', ['call_sid' => $log->call_sid, 'speech' => $speech]);
            return $this->twiml($this->transferToSteveResponse());
        }

        // Hang up on clear goodbye (only if we're past the first turn).
        if ($data['turns'] > 1 && $this->wantsDone($input)) {
            return $this->twiml($this->goodbyeResponse($log));
        }

        // Turn cap: after 10 turns, wrap up politely.
        if ($data['turns'] > 10) {
            $log->update(['state' => 'escalate']);
            return $this->twiml($this->escalatePrompt());
        }

        // ---- LLM-driven conversation ----
        $facts = $this->buildFacts($log, $input);
        $reply = $this->brain->reply($log->transcript ?? [], $facts, $data['turns']);

        // Interpret LLM action markers for state transitions.
        $marker = null;
        if (str_contains($reply, '[TRANSFER]')) {
            $marker = 'transfer';
            $reply = str_replace('[TRANSFER]', '', $reply);
        } elseif (str_contains($reply, '[ESCALATE]')) {
            $marker = 'escalate';
            $reply = str_replace('[ESCALATE]', '', $reply);
        } elseif (str_contains($reply, '[MAINTENANCE]')) {
            $marker = 'maintenance';
            $reply = str_replace('[MAINTENANCE]', '', $reply);
        } elseif (str_contains($reply, '[END]')) {
            $marker = 'end';
            $reply = str_replace('[END]', '', $reply);
        }
        $reply = trim($reply);

        if ($marker === 'transfer') {
            $log->update(['state' => 'done']);
            return $this->twiml(
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                '<Response>' . $this->say($reply !== '' ? $reply : 'Connecting you with Steve now. One moment, please.') .
                $this->transferToSteveDial() .
                '</Response>'
            );
        }

        if ($marker === 'escalate') {
            $log->update(['state' => 'escalate']);
            return $this->twiml(
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                '<Response>' .
                $this->say($reply !== '' ? $reply : 'I will escalate this to Steve.') .
                $this->escalatePrompt() .
                '</Response>'
            );
        }

        if ($marker === 'maintenance') {
            // The caller just described the issue in $speech — log it now.
            $ticket = $this->createTicketFromSpeech($log, $speech);
            $log->appendTranscript('jarvis', $reply);
            $log->update(['state' => 'converse', 'reference' => $ticket ? $ticket->ticket_number : null]);
            return $this->twiml(
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                '<Response>' .
                $this->say($reply !== '' ? $reply : 'I have logged that for you. Our team will follow up within 24 hours.') .
                $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
                    'Steve, yes, no, thank you, goodbye, anything else') .
                $this->say('Sorry, I did not catch that. Go ahead and leave a message now.') .
                $this->recordVerbs() .
                '</Response>'
            );
        }

        if ($marker === 'end') {
            return $this->twiml($this->goodbyeResponse($log));
        }

        // Default: speak the LLM reply and keep listening (interactive loop).
        $log->appendTranscript('jarvis', $reply);
        $log->update(['state' => 'converse']);

        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say($reply) .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
                'Steve, yes, no, thank you, goodbye, balance, payment, maintenance, repair, anything else') .
            $this->say('Sorry, I did not catch that. Go ahead and leave a message now.') .
            $this->recordVerbs() .
            '</Response>'
        );
    }

    /* ================================================================
     * MAINTENANCE ISSUE CAPTURE (after LLM asks what's wrong)
     * ================================================================ */

    public function maintenanceIssue(Request $request)
    {
        $callSid = (string) $request->input('CallSid', '');
        $log = CallLog::where('call_sid', $callSid)->first();

        if (!$log) {
            return $this->twiml($this->fallbackResponse());
        }

        $speech = trim((string) $request->input('SpeechResult', ''));
        $digits = trim((string) $request->input('Digits', ''));
        $input = $digits !== '' ? $digits : strtolower($speech);

        $log->appendTranscript('caller', $speech !== '' ? $speech : ($digits !== '' ? 'DTMF: ' . $digits : '(silence)'));

        if ($this->wantsSteve($input)) {
            $log->update(['state' => 'done']);
            return $this->twiml($this->transferToSteveResponse());
        }

        if ($speech === '') {
            return $this->twiml(
                '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                '<Response>' .
                $this->say('Sorry, I did not catch the issue. Could you describe what needs repair?') .
                $this->gatherTag($this->appUrl('/twilio/maintenance'), 'speech dtmf', null,
                    'leak, water, heat, electrical, appliance, fridge, stove, toilet, door, window, broken, repair') .
                '</Response>'
            );
        }

        // Create the ticket from the issue text (tenant may be matched already).
        $tenant = $log->tenant_id ? User::find($log->tenant_id) : null;
        $log->update(['issue' => $speech]);

        $ticket = MaintenanceTicket::create([
            'tenant_id' => $tenant->id ?? null,
            'property_id' => $tenant ? ($tenant->tenancies()->orderByDesc('id')->first()->property_id ?? null) : null,
            'property_unit_id' => $tenant ? ($tenant->tenancies()->orderByDesc('id')->first()->property_unit_id ?? null) : null,
            'ticket_number' => MaintenanceTicket::generateTicketNumber(),
            'title' => substr($speech, 0, 100),
            'description' => 'Logged via phone (Jarvis receptionist). Caller: ' . $log->from_number .
                ' | Issue: ' . $speech,
            'priority' => 'normal',
            'status' => 'open',
            'category' => 'phone',
        ]);

        $log->update(['reference' => $ticket->ticket_number]);
        Log::info('Maintenance ticket created via phone', [
            'call_sid' => $log->call_sid,
            'tenant_id' => $tenant->id ?? null,
            'ticket_number' => $ticket->ticket_number,
        ]);
        $this->sendMaintenanceEmail($log, $ticket);

        $confirm = 'I have logged maintenance ticket ' . $ticket->ticket_number . ' for your address. Our team will follow up with you within 24 hours. Is there anything else I can help you with?';

        $log->appendTranscript('jarvis', $confirm);
        $log->update(['state' => 'converse']);

        return $this->twiml(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say($confirm) .
            $this->gatherTag($this->appUrl('/twilio/gather'), 'speech dtmf', 1,
                'Steve, yes, no, thank you, goodbye, balance, payment, maintenance, anything else') .
            $this->say('Sorry, I did not catch that. Go ahead and leave a message now.') .
            $this->recordVerbs() .
            '</Response>'
        );
    }

    protected function createTicketFromSpeech(CallLog $log, string $issue): ?MaintenanceTicket
    {
        if ($issue === '') {
            return null;
        }
        $tenant = $log->tenant_id ? User::find($log->tenant_id) : null;
        $tenancy = $tenant ? $tenant->tenancies()->orderByDesc('id')->first() : null;

        $ticket = MaintenanceTicket::create([
            'tenant_id' => $tenant->id ?? null,
            'property_id' => $tenancy->property_id ?? null,
            'property_unit_id' => $tenancy->property_unit_id ?? null,
            'ticket_number' => MaintenanceTicket::generateTicketNumber(),
            'title' => substr($issue, 0, 100),
            'description' => 'Logged via phone (Jarvis receptionist). Caller: ' . $log->from_number .
                ' | Issue: ' . $issue,
            'priority' => 'normal',
            'status' => 'open',
            'category' => 'phone',
        ]);

        Log::info('Maintenance ticket created via phone (LLM flow)', [
            'call_sid' => $log->call_sid,
            'tenant_id' => $tenant->id ?? null,
            'ticket_number' => $ticket->ticket_number,
        ]);
        $this->sendMaintenanceEmail($log, $ticket);
        return $ticket;
    }

    /* ================================================================
     * ESCALATION (Steve now, or 24h route)
     * ================================================================ */

    public function escalate(Request $request)
    {
        $callSid = (string) $request->input('CallSid', '');
        $log = CallLog::where('call_sid', $callSid)->first();

        if (!$log) {
            return $this->twiml($this->fallbackResponse());
        }

        $digits = trim((string) $request->input('Digits', ''));
        $speech = trim((string) $request->input('SpeechResult', ''));
        $input = $digits !== '' ? $digits : strtolower($speech);

        $log->appendTranscript('caller', $speech !== '' ? $speech : ($digits !== '' ? 'DTMF: ' . $digits : '(silence)'));

        if ($digits === '1' || $this->containsAny($input, ['steve', 'yes', 'right now', 'now', 'connect', '1'])) {
            $log->update(['state' => 'done', 'status' => 'transferred']);
            return $this->twiml($this->transferToSteveResponse());
        }

        // 24h route: email Steve the call log.
        $log->update(['state' => 'done', 'status' => 'escalated', 'ended_at' => now()]);
        $this->sendEscalationEmail($log);

        return $this->twiml($this->goodbyeResponse($log, true));
    }

    /* ================================================================
     * FACTS BUILDER (server-verified, LLM may only speak from these)
     * ================================================================ */

    protected function buildFacts(CallLog $log, string $input): string
    {
        // Caller ID match first.
        $tenant = $log->tenant_id ? User::find($log->tenant_id) : null;
        if (!$tenant && $log->from_number) {
            $phone = $this->normalizePhone($log->from_number);
            $tenant = User::where('role', 'tenant')->get()
                ->first(fn ($u) => $this->normalizePhone($u->phone) === $phone);
            if ($tenant) {
                $log->update(['tenant_id' => $tenant->id]);
            }
        }

        if (!$tenant) {
            // Try name + address from the transcript.
            $transcript = $log->transcript ?? [];
            $texts = array_column($transcript, 'text');
            $all = implode(' ', $texts);
            $tenant = $this->lookupFromText($all, $log->from_number);
            if ($tenant) {
                $log->update(['tenant_id' => $tenant->id]);
            }
        }

        $lines = [];
        $lines[] = 'Caller phone: ' . ($log->from_number ?? 'unknown');
        $lines[] = 'Caller said (most recent): ' . ($input !== '' ? $input : '(nothing captured)');

        if (!$tenant) {
            $lines[] = 'ACCOUNT: Not identified yet. Ask for their name and address if you need to look something up.';
            return implode("\n", $lines);
        }

        $lines[] = 'ACCOUNT: ' . $tenant->name . ' — ' . ($tenant->address ?? 'no address on file') . ' (tenant id ' . $tenant->id . ')';
        $lines[] = 'OUTSTANDING BALANCE: $' . number_format($tenant->outstandingBalance(), 2);

        $invoices = $tenant->invoices()
            ->orderBy('due_date')
            ->get()
            ->map(fn ($i) => sprintf(
                '%s ($%.2f, status %s, due %s)',
                $i->invoice_number,
                (float) $i->amount,
                $i->status,
                $i->due_date ? $i->due_date->format('Y-m-d') : 'n/a'
            ))->implode('; ');

        $lines[] = 'INVOICES: ' . ($invoices !== '' ? $invoices : 'none on file');


        $payments = $tenant->payments()
            ->where('status', 'completed')
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get()
            ->map(fn ($p) => sprintf(
                '$%.2f on %s (invoice %s)',
                (float) $p->amount,
                $p->paid_at ? $p->paid_at->format('Y-m-d') : 'n/a',
                $p->invoice_id ?? 'n/a'
            ))->implode('; ');

        $lines[] = 'RECENT PAYMENTS: ' . ($payments !== '' ? $payments : 'none');

        return implode("\n", $lines);
    }

    protected function lookupFromText(string $text, ?string $fromNumber): ?User
    {
        $text = strtolower($text);

        // Name match: look for two consecutive capitalized-ish words.
        if (preg_match('/([a-z]+)\s+([a-z]+)/i', $text, $m)) {
            $name = ucwords($m[1] . ' ' . $m[2]);
            $candidates = User::where('role', 'tenant')->get();
            $best = null;
            $bestScore = 0;
            foreach ($candidates as $user) {
                $score = $this->matchName($this->nameTokens($name), $user->name);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $user;
                }
            }
            if ($best && $bestScore >= 2) {
                return $best;
            }
        }

        // Address match: street number + street name.
        if (preg_match('/(\d{2,5})\s+([a-z]+)/i', $text, $m)) {
            $spokenNumber = $m[1];
            $spokenStreet = strtolower($m[2]);
            $candidates = User::where('role', 'tenant')->get();
            foreach ($candidates as $user) {
                $addr = strtolower($user->address ?? '');
                if (str_contains($addr, $spokenNumber) && str_contains($addr, $spokenStreet)) {
                    return $user;
                }
            }
        }

        return null;
    }

    /* ================================================================
     * RESPONSE BUILDERS
     * ================================================================ */

    protected function escalatePrompt(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Would you like me to connect you with Steve right now? Press 1, or say yes. ' .
                'Otherwise press 2, and Steve will call you back within 24 hours.') .
            $this->gatherTag($this->appUrl('/twilio/escalate'), 'speech dtmf', 1, 'Steve, yes, no, callback, 24') .
            $this->say('Sorry, I did not catch that.') .
            $this->escalatePromptRetry() .
            '</Response>';
    }

    protected function escalatePromptRetry(): string
    {
        return $this->gatherTag($this->appUrl('/twilio/escalate'), 'speech dtmf', 1, 'Steve, yes, no, callback, 24');
    }

    protected function transferToSteveResponse(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Response>' .
            $this->say('Sure thing. Connecting you with Steve now. One moment, please.') .
            $this->transferToSteveDial() .
            '</Response>';
    }

    protected function transferToSteveDial(): string
    {
        return '<Dial callerId="' . $this->businessNumber . '" timeout="25" ' .
            'action="' . $this->appUrl('/twilio/dial-result') . '" method="POST">' .
            $this->steveNumber . '</Dial>';
    }

    protected function goodbyeResponse(CallLog $log, bool $escalated = false): string
    {
        $log->update(['state' => 'done', 'status' => $escalated ? 'escalated' : 'resolved', 'ended_at' => now()]);

        $text = $escalated
            ? 'I have escalated this to Steve and he will reach out to you within the next 24 hours. Thank you for calling Tenant Trust. Have a great day.'
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
     * ESCALATION EMAIL (24h route — emails Steve the call log)
     * ================================================================ */

    protected function sendMaintenanceEmail(CallLog $log, MaintenanceTicket $ticket): void
    {
        $tenant = $log->tenant_id ? User::find($log->tenant_id) : null;

        $subject = '🔧 Maintenance request — ' . $ticket->ticket_number . ($tenant ? ' — ' . $tenant->name : '');
        $body = "A tenant reported a maintenance issue via phone (Jarvis receptionist).\n\n"
            . "Ticket: {$ticket->ticket_number}\n"
            . "Status: open\n"
            . "Time: " . now()->setTimezone('America/Toronto')->format('Y-m-d H:i T') . "\n"
            . "Caller: {$log->from_number}\n"
            . "Call SID: {$log->call_sid}\n"
            . "Tenant: " . ($tenant->name ?? 'not matched') . "\n"
            . "Address: " . ($tenant->address ?? 'not matched') . "\n\n"
            . "Issue:\n" . ($ticket->description ?? $log->issue ?? 'n/a') . "\n";

        try {
            Mail::raw($body, function ($message) use ($subject) {
                $message->to($this->escalationEmail)
                    ->cc($this->notifyEmail)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            Log::info('Twilio maintenance email sent', [
                'to' => $this->escalationEmail,
                'cc' => $this->notifyEmail,
                'ticket' => $ticket->ticket_number,
                'call_sid' => $log->call_sid,
            ]);
        } catch (\Throwable $e) {
            Log::error('Twilio maintenance email failed: ' . $e->getMessage(), [
                'ticket' => $ticket->ticket_number,
                'call_sid' => $log->call_sid,
            ]);
        }
    }

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
            $text . '</Say>';
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
        return $this->containsAny($input, [
            'talk to steve', 'speak to steve', 'speak with steve', 'connect', 'with steve',
            'landlord', 'owner', 'manager', 'a human', 'a person', 'someone',
        ]);
    }

    protected function wantsDone(string $input): bool
    {
        return $this->containsAny($input, [
            'goodbye', 'bye', 'thank you', 'thanks', 'great day', 'have a great',
            'that is all', 'that\'s all', 'nothing else', 'no that is it', 'no that\'s it', 'done',
        ]);
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
                if ($t === $ut || str_starts_with($ut, $t) || str_starts_with($t, $ut)) {
                    $score += 1;
                }
            }
        }
        return $score;
    }

    protected function normalizePhone(?string $phone): string
    {
        if (!$phone) {
            return '';
        }
        $digits = preg_replace('/\D/', '', (string) $phone);
        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
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
     * LEGACY: voicemail + dial result
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
            $this->say('Steve is not available right now. Go ahead and leave a message after the beep.') .
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
