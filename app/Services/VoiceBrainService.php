<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Jarvis's brain: turns a caller's words + live account facts into a natural,
 * attentive spoken reply using the DeepSeek chat API.
 */
class VoiceBrainService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = (string) env('DEEPSEEK_API_KEY', '');
        $this->baseUrl = rtrim((string) env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'), '/');
        $this->model = (string) env('DEEPSEEK_MODEL', 'deepseek-chat');
    }

    /**
     * Build the caller's transcript into a messages array.
     */
    protected function buildMessages(array $transcript, string $systemPrompt): array
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        // Only send the most recent exchanges — full transcripts slow every reply.
        $recent = array_slice($transcript, -6);
        foreach ($recent as $entry) {
            $speaker = strtolower((string) ($entry['speaker'] ?? ''));
            $text = (string) ($entry['text'] ?? '');
            if ($text === '') {
                continue;
            }
            $role = $speaker === 'caller' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $text];
        }

        return $messages;
    }

    /**
     * Ask Jarvis for a reply. Returns plain spoken text.
     */
    public function reply(array $transcript, string $facts, int $turn): string
    {
        $systemPrompt = $this->systemPrompt($facts, $turn);

        $messages = $this->buildMessages($transcript, $systemPrompt);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(20)
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'max_tokens' => 150,
                    'temperature' => 0.4,
                ]);

            if (!$response->successful()) {
                Log::warning('VoiceBrain: LLM request failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 300),
                ]);
                return $this->fallbackReply($turn);
            }

            $text = data_get($response->json('choices.0.message'), 'content', '');
            $text = trim((string) $text);

            if ($text === '') {
                return $this->fallbackReply($turn);
            }

            // Guard: strip anything that looks like SSML/XML before it hits <Say>.
            $text = preg_replace('/<[^>]+>/', '', $text);
            $text = preg_replace('/\s+/', ' ', $text);

            Log::info('VoiceBrain: LLM reply', ['turn' => $turn, 'reply' => substr($text, 0, 200)]);
            return $text;
        } catch (\Throwable $e) {
            Log::error('VoiceBrain: LLM exception: ' . $e->getMessage());
            return $this->fallbackReply($turn);
        }
    }

    protected function systemPrompt(string $facts, int $turn): string
    {
        return <<<PROMPT
You are Jarvis, the receptionist for Tenant Trust Property Management (tenanttrust.ca).
A tenant has called in. You are speaking to them over the phone, so:

- Be attentive and empathetic. Listen to their actual question.
- Answer using ONLY the verified facts below. NEVER invent amounts, dates, or account details.
- Keep replies conversational and SHORT (1-3 sentences). Speak like a person, not a script.
- If you can resolve the issue, explain it clearly and then ask "Is there anything else I can help you with?"
- If they have other questions, answer them too, and keep asking if there's anything else.
- If you cannot resolve something, apologize and offer: "Would you like me to connect you with Steve right now, or have him call you back within 24 hours?"
- If they ask to speak with Steve / the landlord / the owner / a manager, say you'll connect them and end your reply with exactly: [TRANSFER]
- If they are done (thanks, goodbye, that's all, no that's it), say goodbye warmly and end your reply with exactly: [END]
- If they want to report maintenance, first ask what the issue is. Do NOT use [MAINTENANCE] yet.
  Once the caller describes the specific problem, say you'll log it and that someone will follow up within 24 hours, and end your reply with exactly: [MAINTENANCE]
- If a payment cannot be found for an outstanding amount, ask if they have a reference number. End your reply with exactly: [ESCALATE] only after they have given a reference or said they don't have one.

Verified facts for this call (turn {$turn}):
{$facts}
PROMPT;
    }

    protected function fallbackReply(int $turn): string
    {
        if ($turn <= 1) {
            return 'I am sorry, I am having trouble right now. Could you repeat that please?';
        }
        return 'Thank you for holding. Would you like me to connect you with Steve, or have him call you back within 24 hours?';
    }
}
