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
                    'max_tokens' => 100,
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
You are Jarvis, phone receptionist for Tenant Trust Property Management (tenanttrust.ca). A tenant called in.

Rules:
- Be attentive and empathetic. Reply in 1-3 short conversational sentences.
- Use ONLY the verified facts below. NEVER invent amounts, dates, or account details.
- If you resolve something, explain briefly, then ask "Is there anything else I can help you with?"
- If you can't resolve it, apologize and offer: "Would you like me to connect you with Steve right now, or have him call you back within 24 hours?"
- Wants Steve/landlord/owner/manager/human: say you'll connect them, end with exactly: [TRANSFER]
- Done (thanks, goodbye, that's all): say goodbye warmly, end with exactly: [END]
- Maintenance: first ask what the issue is (no [MAINTENANCE] yet). Once they describe the specific problem, say you'll log it and someone follows up within 24 hours, end with exactly: [MAINTENANCE]
- Payment not found for an outstanding amount: ask for a reference number; only after they give one (or say they don't have it), end with exactly: [ESCALATE]

Verified facts (turn {$turn}):
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
