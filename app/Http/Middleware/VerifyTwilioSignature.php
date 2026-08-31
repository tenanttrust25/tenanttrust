<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyTwilioSignature
{
    protected string $authToken;

    public function __construct()
    {
        $this->authToken = (string) env('TWILIO_AUTH_TOKEN', '');
    }

    public function handle(Request $request, Closure $next): Response
    {
        $signature = (string) $request->header('X-Twilio-Signature', '');

        if ($this->authToken === '' || $signature === '') {
            abort(403, 'Forbidden');
        }

        // Build the URL exactly as Twilio sees it (https://tenanttrust.ca/...).
        // Using APP_URL avoids proxy/scheme mismatches behind nginx.
        $url = rtrim((string) config('app.url'), '/') . '/' . ltrim($request->path(), '/');
        $query = $request->getQueryString();
        if ($query) {
            $url .= '?' . $query;
        }

        // Twilio signature: HMAC-SHA1(authToken) over URL + sorted POST params.
        $params = $request->except(['X-Twilio-Signature']);
        ksort($params);

        $payload = $url;
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $payload .= $key . $value;
        }

        $expected = base64_encode(hash_hmac('sha1', $payload, $this->authToken, true));

        if (!hash_equals($expected, $signature)) {
            Log::warning('Twilio signature verification failed', [
                'url' => $url,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
