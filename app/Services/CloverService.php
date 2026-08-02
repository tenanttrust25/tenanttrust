<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloverService
{
    private string $privateToken;
    private string $merchantId;

    public function __construct()
    {
        $this->privateToken = config("services.clover.private_token");
        $this->merchantId = config("services.clover.merchant_id");
    }

    /**
     * Create a charge using a Clover source token.
     *
     * @param string $sourceToken The single-use card token from the Clover iframe
     * @param int $amountInCents Amount in cents (e.g., $10.00 = 1000)
     * @param string $currency Currency code (e.g., "cad", "usd")
     * @return array{success: bool, charge_id?: string, message?: string}
     */
    public function createCharge(string $sourceToken, int $amountInCents, string $currency = "cad"): array
    {
        $isSandbox = config("services.clover.env") !== "production";
        $baseUrl = $isSandbox
            ? "https://scl-sandbox.dev.clover.com"
            : "https://scl.clover.com";

        $idempotencyKey = (string) Str::uuid();

        try {
            $response = Http::withHeaders([
                "accept" => "application/json",
                "authorization" => "Bearer " . $this->privateToken,
                "idempotency-key" => $idempotencyKey,
                "content-type" => "application/json",
            ])->post("{$baseUrl}/v1/charges", [
                "amount" => $amountInCents,
                "currency" => strtolower($currency),
                "Source" => $sourceToken,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                return [
                    "success" => true,
                    "charge_id" => $body["id"] ?? null,
                    "data" => $body,
                    "message" => "Payment approved",
                ];
            }

            $errorBody = $response->json();
            $errorMsg = $errorBody["error"]["message"]
                ?? $errorBody["message"]
                ?? $response->body();

            return [
                "success" => false,
                "message" => "Clover error: {$errorMsg}",
            ];

        } catch (\Exception $e) {
            return [
                "success" => false,
                "message" => "Connection error: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Get the public token for frontend iframe initialization.
     */
    public static function getPublicToken(): string
    {
        return config("services.clover.public_token");
    }

    /**
     * Get the merchant ID for frontend iframe initialization.
     */
    public static function getMerchantId(): string
    {
        return config("services.clover.merchant_id");
    }

    /**
     * Get the SDK URL for frontend iframe.
     */
    public static function getSdkUrl(): string
    {
        $isSandbox = config("services.clover.env") !== "production";
        return $isSandbox
            ? "https://checkout.sandbox.dev.clover.com/sdk.js"
            : "https://checkout.clover.com/sdk.js";
    }
}
