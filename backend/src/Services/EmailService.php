<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class EmailService
{
    public function __construct(
        private readonly string $fromEmail = 'noreply@aubunworld.com',
        private readonly string $fromName = 'AUBUN WORLD',
        private readonly string $baseUrl = 'http://localhost:5174'
    ) {
    }

    public function sendVerificationEmail(string $toEmail, string $toName, string $verificationToken): void
    {
        $verifyUrl = sprintf(
            '%s/api/auth/verify-email?token=%s',
            rtrim($this->baseUrl, '/'),
            urlencode($verificationToken)
        );

        $subject = 'Verify your AUBUN WORLD email address';
        $body = $this->buildVerificationEmailBody($toName, $verifyUrl);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
        ];

        $sent = mail($toEmail, $subject, $body, implode("\r\n", $headers));

        if (!$sent) {
            throw new RuntimeException('Failed to send verification email.', 500);
        }
    }

    private function buildVerificationEmailBody(string $name, string $verifyUrl): string
    {
        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Verify Email</title></head><body style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; padding: 40px; background: #f7f7f5;">' .
            '<div style="background: white; padding: 48px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08);">' .
            '<h1 style="color: #0b0b0c; margin-bottom: 24px; letter-spacing: 0.08em; text-transform: uppercase; font-size: 1.4rem;">Welcome to AUBUN WORLD</h1>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Thank you for creating your account. Please verify your email address by clicking the button below:</p>' .
            '<div style="text-align: center; margin: 40px 0;">' .
            '<a href="%s" style="background: #0b0b0c; color: white; padding: 16px 48px; text-decoration: none; text-transform: uppercase; letter-spacing: 0.18em; font-size: 0.78rem; border-radius: 999px; display: inline-block; box-shadow: 0 12px 32px rgba(0,0,0,0.18);">Verify Email</a>' .
            '</div>' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 32px;">If you did not create an account, you can safely ignore this email.</p>' .
            '<p style="color: #6f6f74; font-size: 0.85rem; margin-top: 48px; border-top: 1px solid rgba(11,11,12,0.08); padding-top: 24px;">Best regards,<br>AUBUN WORLD Team</p>' .
            '</div></body></html>',
            htmlspecialchars($name, ENT_QUOTES),
            $verifyUrl
        );
    }
}
