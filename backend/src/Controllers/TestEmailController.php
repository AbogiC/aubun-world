<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\EmailService;
use RuntimeException;

final class TestEmailController
{
    public function __construct(
        private readonly EmailService $email
    ) {
    }

    public function send(Request $request): array
    {
        $to = trim((string) $request->input('to'));
        $subject = trim((string) $request->input('subject')) ?: 'GoDaddy SMTP Test';
        $message = (string) $request->input('message') ?: 'This is a GoDaddy SMTP test message from the Aubun World backend.';

        if ($to === '') {
            throw new RuntimeException('Missing "to" email address.', 400);
        }

        $this->email->sendTestEmail($to, $message, $subject);

        return [
            'success' => true,
            'to' => $to,
            'subject' => $subject,
            'message' => 'SMTP test email sent successfully.',
        ];
    }
}
