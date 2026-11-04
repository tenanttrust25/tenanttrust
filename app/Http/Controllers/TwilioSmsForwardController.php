<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Forwards inbound SMS on the Twilio business number to Steve's mobile.
 *
 * Twilio hits this endpoint (configured as the number's messaging webhook) for
 * every inbound message; we re-send it to TWILIO_STEVE_NUMBER through the REST
 * API. Signature validation is handled by the `twilio.signature` middleware, so
 * by the time this runs the request is known to come from Twilio.
 */
class TwilioSmsForwardController extends Controller
{
    public function incoming(Request $request)
    {
        $sid = (string) env('TWILIO_ACCOUNT_SID', '');
        $token = (string) env('TWILIO_AUTH_TOKEN', '');
        $from = (string) env('TWILIO_BUSINESS_NUMBER', '');
        $to = (string) env('TWILIO_SMS_FORWARD_TO', env('TWILIO_STEVE_NUMBER', ''));

        $sender = (string) $request->input('From', '');
        $Body = (string) $request->input('Body', '');
        $messageSid = (string) $request->input('MessageSid', '');
        $media = (int) $request->input('NumMedia', 0);

        // Loop guard: never forward a message we sent ourselves.
        if ($sender === $from || $sender === '') {
            return $this->twiml();
        }

        if ($sid === '' || $token === '' || $from === '' || $to === '') {
            Log::error('Twilio SMS forward: missing configuration', [
                'has_sid' => $sid !== '',
                'has_token' => $token !== '',
                'has_from' => $from !== '',
                'has_to' => $to !== '',
            ]);

            return $this->twiml();
        }

        $text = "SMS from {$sender}:\n" . ($Body !== '' ? $Body : '(no text)');
        if ($media > 0) {
            $text .= "\n[{$media} media attachment" . ($media === 1 ? '' : 's') . ' - not forwarded]';
        }
        $text = mb_substr($text, 0, 1500);

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $text,
                ]);

            if (! $response->successful()) {
                Log::error('Twilio SMS forward failed', [
                    'message_sid' => $messageSid,
                    'status' => $response->status(),
                    'error_code' => $response->json('code'),
                    'error' => $response->json('message'),
                ]);
            } else {
                // Never log the message body: it is tenant PII.
                Log::info('Twilio SMS forwarded', [
                    'inbound_sid' => $messageSid,
                    'outbound_sid' => $response->json('sid'),
                    'from_last4' => substr($sender, -4),
                    'length' => mb_strlen($Body),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Twilio SMS forward exception', [
                'message_sid' => $messageSid,
                'error' => $e->getMessage(),
            ]);
        }

        // Always answer with empty TwiML so Twilio does not retry the webhook.
        return $this->twiml();
    }

    private function twiml()
    {
        return response(
            '<?xml version="1.0" encoding="UTF-8"?><Response></Response>',
            200,
            ['Content-Type' => 'text/xml']
        );
    }
}
