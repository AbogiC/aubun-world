<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use RuntimeException;

final class TestEmailController
{
    public function __construct(
        private readonly string $clientId = '',
        private readonly string $tenantId = '',
        private readonly string $clientSecret = '',
        private readonly string $senderEmail = ''
    ) {
    }

    public function send(Request $request): array
    {
        $to = trim((string) $request->input('to'));
        $subject = trim((string) $request->input('subject')) ?: 'AUBUN WORLD - Microsoft Graph Test';
        $message = (string) $request->input('message') ?: 'This is a test email sent from the AUBUN WORLD PHP backend via Microsoft Graph API.';

        if ($to === '') {
            throw new RuntimeException('Missing "to" email address.', 400);
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid "to" email address.', 400);
        }

        $clientId = trim($this->clientId) !== '' ? trim($this->clientId) : trim((string) getenv('MICROSOFT_CLIENT_ID'));
        $tenantId = trim($this->tenantId) !== '' ? trim($this->tenantId) : trim((string) getenv('MICROSOFT_TENANT_ID'));
        $clientSecret = $this->clientSecret !== '' ? $this->clientSecret : (string) getenv('MICROSOFT_CLIENT_SECRET');
        $senderEmail = trim($this->senderEmail) !== '' ? trim($this->senderEmail) : trim((string) getenv('MICROSOFT_SENDER_EMAIL'));

        if ($clientId === '' || $tenantId === '' || $clientSecret === '' || $senderEmail === '') {
            throw new RuntimeException(
                'Microsoft Graph email is not configured. Missing MICROSOFT_CLIENT_ID, MICROSOFT_TENANT_ID, MICROSOFT_CLIENT_SECRET, or MICROSOFT_SENDER_EMAIL.',
                500
            );
        }

        $accessToken = $this->getAccessToken($clientId, $tenantId, $clientSecret);
        $this->sendViaGraph($accessToken, $senderEmail, $to, $subject, $message);

        return [
            'success' => true,
            'from' => $senderEmail,
            'to' => $to,
            'subject' => $subject,
            'message' => 'Microsoft Graph test email sent successfully.',
        ];
    }

    private function getAccessToken(string $clientId, string $tenantId, string $clientSecret): string
    {
        $tokenUrl = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';

        $tokenData = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);

        $ch = curl_init($tokenUrl);

        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL for Microsoft token request.', 500);
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $tokenData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $tokenResponse = curl_exec($ch);

        if ($tokenResponse === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Microsoft token request failed: ' . $error, 500);
        }

        $tokenHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $token = json_decode((string) $tokenResponse, true);

        if ($tokenHttpCode !== 200 || empty($token['access_token'])) {
            throw new RuntimeException(
                'Failed to get Microsoft access token (HTTP ' . $tokenHttpCode . '): ' . (string) $tokenResponse,
                500
            );
        }

        return (string) $token['access_token'];
    }

    private function sendViaGraph(string $accessToken, string $senderEmail, string $to, string $subject, string $message): void
    {
        $htmlBody = '<h1>Hello from AUBUN WORLD!</h1>'
            . '<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>'
            . '<p>This email was sent using <strong>Microsoft Graph API</strong>.</p>';

        $email = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlBody,
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $to,
                        ],
                    ],
                ],
            ],
            'saveToSentItems' => true,
        ];

        $sendUrl = 'https://graph.microsoft.com/v1.0/users/'
            . rawurlencode($senderEmail)
            . '/sendMail';

        $ch = curl_init($sendUrl);

        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL for Microsoft Graph sendMail request.', 500);
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($email),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
        ]);

        $sendResponse = curl_exec($ch);

        if ($sendResponse === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Microsoft Graph send request failed: ' . $error, 500);
        }

        $sendHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($sendHttpCode !== 202) {
            throw new RuntimeException(
                'Failed to send email via Microsoft Graph (HTTP ' . $sendHttpCode . '): ' . (string) $sendResponse,
                500
            );
        }
    }
}
