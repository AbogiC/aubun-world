<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class PayPalOrderService
{
    private ?string $accessToken = null;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $baseUrl,
        private readonly string $currency
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function createOrder(array $checkout): array
    {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $this->currency,
                    'value' => $this->formatAmount((float) $checkout['total']),
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => $this->currency,
                            'value' => $this->formatAmount((float) $checkout['subtotal']),
                        ],
                        'shipping' => [
                            'currency_code' => $this->currency,
                            'value' => $this->formatAmount((float) $checkout['shipping']),
                        ],
                        'discount' => [
                            'currency_code' => $this->currency,
                            'value' => $this->formatAmount((float) $checkout['discount']),
                        ],
                    ],
                ],
                'description' => sprintf(
                    'AUBUN WORLD checkout for %s',
                    (string) $checkout['customer_name']
                ),
            ]],
        ];

        return $this->request('POST', '/v2/checkout/orders', $payload, [
            'Prefer: return=representation',
        ]);
    }

    public function captureOrder(string $orderId): array
    {
        return $this->request(
            'POST',
            sprintf('/v2/checkout/orders/%s/capture', rawurlencode($orderId)),
            new \stdClass(),
            ['Prefer: return=representation']
        );
    }

    public function getOrder(string $orderId): array
    {
        return $this->request(
            'GET',
            sprintf('/v2/checkout/orders/%s', rawurlencode($orderId))
        );
    }

    private function request(string $method, string $path, array|\stdClass|null $body = null, array $headers = []): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PayPal is not configured on the server.', 500);
        }

        $curl = curl_init(rtrim($this->baseUrl, '/') . $path);

        if ($curl === false) {
            throw new RuntimeException('Unable to initialize PayPal request.', 500);
        }

        $requestHeaders = array_merge([
            'Authorization: Bearer ' . $this->accessToken(),
            'Content-Type: application/json',
        ], $headers);

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        }

        $responseBody = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);

        if ($responseBody === false) {
            throw new RuntimeException('Failed to contact PayPal: ' . $curlError, 502);
        }

        $decoded = json_decode($responseBody, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Unexpected PayPal response.', 502);
        }

        if ($statusCode >= 400) {
            $message = $decoded['message'] ?? $decoded['details'][0]['description'] ?? 'PayPal request failed.';
            throw new RuntimeException($message, $statusCode);
        }

        return $decoded;
    }

    private function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $curl = curl_init(rtrim($this->baseUrl, '/') . '/v1/oauth2/token');

        if ($curl === false) {
            throw new RuntimeException('Unable to initialize PayPal authentication.', 500);
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERPWD => $this->clientId . ':' . $this->clientSecret,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US',
            ],
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        ]);

        $responseBody = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);

        if ($responseBody === false) {
            throw new RuntimeException('Failed to authenticate with PayPal: ' . $curlError, 502);
        }

        $decoded = json_decode($responseBody, true);

        if (!is_array($decoded) || $statusCode >= 400 || empty($decoded['access_token'])) {
            $message = is_array($decoded) ? ($decoded['error_description'] ?? $decoded['error'] ?? 'Unable to authenticate with PayPal.') : 'Unable to authenticate with PayPal.';
            throw new RuntimeException($message, $statusCode >= 400 ? $statusCode : 502);
        }

        $this->accessToken = (string) $decoded['access_token'];

        return $this->accessToken;
    }

    private function formatAmount(float $amount): string
    {
        return number_format(max($amount, 0), 2, '.', '');
    }
}
