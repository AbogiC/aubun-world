<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class EmailService
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $smtpEncryption;
    private $fromEmail;
    private $fromName;
    private $baseUrl;

    public function __construct(
        string $fromEmail = 'noreply@aubunworld.com',
        string $fromName = 'AUBUN WORLD',
        string $baseUrl = 'http://localhost:5173',
        string $smtpHost = 'smtpout.secureserver.net',
        int $smtpPort = 587,
        string $smtpUsername = '',
        string $smtpPassword = '',
        string $smtpEncryption = 'tls'
    ) {
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->baseUrl = $baseUrl;
        $this->smtpHost = $smtpHost;
        $this->smtpPort = $smtpPort;
        $this->smtpUsername = $smtpUsername ?: $fromEmail;
        $this->smtpPassword = $smtpPassword;
        $this->smtpEncryption = $smtpEncryption;
    }

    public function sendVerificationEmail(string $toEmail, string $toName, string $verificationToken): void
    {
        $verifyUrl = sprintf(
            '%s/verify-email?token=%s',
            rtrim($this->baseUrl, '/'),
            urlencode($verificationToken)
        );

        $subject = 'Verify your AUBUN WORLD email address';
        $body = $this->buildVerificationEmailBody($toName, $verifyUrl);

        $this->send($toEmail, $subject, $body);
    }

    public function sendNewsletterSubscriptionEmail(string $toEmail): void
    {
        $subject = 'You are subscribed to the AUBUN WORLD newsletter';
        $body = $this->buildNewsletterSubscriptionBody($toEmail);

        $this->send($toEmail, $subject, $body);
    }

    public function sendTestEmail(string $toEmail, string $message, string $subject = 'GoDaddy SMTP Test'): void
    {
        $body = $this->wrapEmail(
            'SMTP Test',
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 20px;">This is a GoDaddy SMTP test email.</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 20px;"><strong>Message:</strong></p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 0;">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>'
        );

        $this->send($toEmail, $subject, $body);
    }

    public function sendOrderConfirmation(string $toEmail, string $toName, array $order): void
    {
        $subject = 'Your AUBUN WORLD Order Confirmation';
        $body = $this->buildOrderConfirmationBody($toName, $order);

        $this->send($toEmail, $subject, $body);
    }

    public function sendPaymentPendingEmail(string $toEmail, string $toName, array $order): void
    {
        $subject = 'AUBUN WORLD - Order Received: Awaiting Payment';
        $body = $this->buildPaymentPendingBody($toName, $order);

        $this->send($toEmail, $subject, $body);
    }

    public function sendPaymentConfirmedEmail(string $toEmail, string $toName, array $order): void
    {
        $subject = 'AUBUN WORLD - Payment Confirmed: Your Order is Being Processed';
        $body = $this->buildPaymentConfirmedBody($toName, $order);

        $this->send($toEmail, $subject, $body);
    }

    public function sendShippingUpdateEmail(string $toEmail, string $toName, array $order): void
    {
        $subject = 'AUBUN WORLD - Shipping Update: ' . ucfirst($order['status']);
        $body = $this->buildShippingUpdateBody($toName, $order);

        $this->send($toEmail, $subject, $body);
    }

    public function sendOrderCancelledEmail(string $toEmail, string $toName, array $order): void
    {
        $subject = 'AUBUN WORLD - Order Cancelled: Payment Timeout';
        $body = $this->buildOrderCancelledBody($toName, $order);

        $this->send($toEmail, $subject, $body);
    }

    private function send(string $toEmail, string $subject, string $body): void
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
        ];

        $sent = false;

        if ($this->smtpPassword) {
            $sent = $this->sendViaSmtp($toEmail, $subject, $body, $headers);
        }

        if (!$sent) {
            $sent = mail($toEmail, $subject, $body, implode("\r\n", $headers));
        }

        if (!$sent) {
            throw new RuntimeException('Failed to send email to ' . $toEmail, 500);
        }
    }

    private function sendViaSmtp(string $toEmail, string $subject, string $body, array $headers): bool
    {
        $host = trim((string) $this->smtpHost);
        $encryption = strtolower(trim((string) $this->smtpEncryption));

        if ($host === '') {
            return false;
        }

        if (str_starts_with(strtolower($host), 'ssl://')) {
            $host = substr($host, 6);
        }

        if ($encryption === 'ssl' || $encryption === 'smtps') {
            $host = 'ssl://' . ltrim($host, '/');
        } elseif ($encryption === 'tls' || $encryption === 'starttls') {
            $host = preg_replace('#^ssl://#i', '', $host) ?? $host;
        }

        $socket = @fsockopen(
            $host,
            $this->smtpPort,
            $errno,
            $errstr,
            30
        );

        if (!$socket) {
            error_log("SMTP connection failed: $errstr ($errno)");
            return false;
        }

        stream_set_timeout($socket, 30);

        $response = $this->smtpReadResponse($socket);
        if (!str_starts_with($response, '220')) {
            fclose($socket);
            error_log("SMTP greeting failed: $response");
            return false;
        }

        if (!$this->smtpCommand($socket, 'EHLO ' . gethostname())) {
            fclose($socket);
            return false;
        }

        if ($encryption === 'tls' || $encryption === 'starttls') {
            if (!$this->smtpCommand($socket, 'STARTTLS')) {
                fclose($socket);
                return false;
            }

            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                fclose($socket);
                error_log("SMTP TLS handshake failed");
                return false;
            }

            if (!$this->smtpCommand($socket, 'EHLO ' . gethostname())) {
                fclose($socket);
                return false;
            }
        }

        if (!$this->smtpCommand($socket, 'AUTH LOGIN')) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpCommand($socket, base64_encode($this->smtpUsername), 'AUTH username')) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpCommand($socket, base64_encode($this->smtpPassword), 'AUTH password')) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpCommand($socket, 'MAIL FROM:<' . $this->fromEmail . '>')) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpCommand($socket, 'RCPT TO:<' . $toEmail . '>')) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpCommand($socket, 'DATA')) {
            fclose($socket);
            return false;
        }

        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'Date: ' . date(DATE_RFC2822);

        $message = implode("\r\n", $headers) . "\r\n" .
                   "\r\n" .
                   $this->escapeSmtpBody($body) . "\r\n" .
                   '.';

        fwrite($socket, $message . "\r\n");
        $response = $this->smtpReadResponse($socket);
        if (!str_starts_with($response, '250')) {
            fclose($socket);
            error_log("SMTP DATA failed: $response");
            return false;
        }

        $this->smtpCommand($socket, 'QUIT');
        fclose($socket);

        return true;
    }

    private function smtpCommand($socket, string $command, ?string $logCommand = null): bool
    {
        fwrite($socket, $command . "\r\n");
        $response = $this->smtpReadResponse($socket);

        // Check for successful response codes
        if (str_starts_with($response, '2') || str_starts_with($response, '3')) {
            return true;
        }

        error_log('SMTP command failed: ' . ($logCommand ?? $command) . " -> $response");
        return false;
    }

    private function smtpReadResponse($socket): string
    {
        $response = '';

        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;

            if (preg_match('/^\d{3}\s/', $line) === 1) {
                break;
            }
        }

        return $response;
    }

    private function escapeSmtpBody(string $body): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = preg_replace('/^\./m', '..', $body) ?? $body;

        return str_replace("\n", "\r\n", $body);
    }

    private function buildOrderItemsRows(array $order): string
    {
        $itemsHtml = '';

        foreach ($order['items'] ?? [] as $item) {
            $itemsHtml .= sprintf(
                '<tr><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:center;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:right;">$%s</td></tr>',
                htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES),
                htmlspecialchars((string) ($item['size'] ?? ''), ENT_QUOTES) . ' / ' . htmlspecialchars((string) ($item['color'] ?? ''), ENT_QUOTES) . ' x ' . (int) ($item['quantity'] ?? 0),
                htmlspecialchars((string) ($item['unit_price'] ?? ''), ENT_QUOTES),
                htmlspecialchars(number_format((float) ($item['line_total'] ?? 0), 2), ENT_QUOTES)
            );
        }

        return $itemsHtml;
    }

    private function buildOrderSummary(array $order, string $totalLabel = 'Total'): string
    {
        return sprintf(
            '<div style="background:#fafafa; padding:16px; border-radius:8px; margin-bottom:24px;">' .
            '<p style="margin:4px 0;"><strong>Order Number:</strong> %s</p>' .
            '<p style="margin:4px 0;"><strong>Subtotal:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>Shipping:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>%s:</strong> <strong>$%s</strong></p>' .
            '</div>',
            htmlspecialchars((string) ($order['orderNumber'] ?? ''), ENT_QUOTES),
            htmlspecialchars(number_format((float) ($order['subtotal'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars(number_format((float) ($order['shipping'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars($totalLabel, ENT_QUOTES),
            htmlspecialchars(number_format((float) ($order['total'] ?? 0), 2), ENT_QUOTES)
        );
    }

    private function buildOrderTable(array $order): string
    {
        return '<table style="width:100%; border-collapse:collapse; margin-bottom:24px;">' .
            '<tr style="background:#fafafa;"><th style="text-align:left; padding:8px; border-bottom:2px solid #eee;">Product</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Details</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Unit Price</th><th style="text-align:right; padding:8px; border-bottom:2px solid #eee;">Total</th></tr>' .
            $this->buildOrderItemsRows($order) .
            '</table>';
    }

    private function wrapEmail(string $title, string $content, string $titleColor = '#0b0b0c'): string
    {
        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>%s</title></head><body style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; padding: 40px; background: #f7f7f5;">' .
            '<div style="background: white; padding: 48px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08);">' .
            '<h1 style="color: %s; margin-bottom: 24px; letter-spacing: 0.08em; text-transform: uppercase; font-size: 1.4rem;">%s</h1>' .
            '%s' .
            '<p style="color: #6f6f74; font-size: 0.85rem; margin-top: 48px; border-top: 1px solid rgba(11,11,12,0.08); padding-top: 24px;">Best regards,<br>AUBUN WORLD Team</p>' .
            '</div></body></html>',
            htmlspecialchars($title, ENT_QUOTES),
            $titleColor,
            htmlspecialchars($title, ENT_QUOTES),
            $content
        );
    }

    private function buildOrderConfirmationBody(string $name, array $order): string
    {
        $content = sprintf(
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Thank you for your purchase! Here are your order details:</p>' .
            '%s%s' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">If you have any questions, please contact us.</p>',
            htmlspecialchars($name, ENT_QUOTES),
            $this->buildOrderTable($order),
            $this->buildOrderSummary($order)
        );

        return $this->wrapEmail('Order Confirmed', $content);
    }

    private function buildVerificationEmailBody(string $name, string $verifyUrl): string
    {
        $content = sprintf(
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Thank you for creating your account. Please verify your email address by clicking the button below:</p>' .
            '<div style="text-align: center; margin: 40px 0;">' .
            '<a href="%s" style="background: #0b0b0c; color: white; padding: 16px 48px; text-decoration: none; text-transform: uppercase; letter-spacing: 0.18em; font-size: 0.78rem; border-radius: 999px; display: inline-block; box-shadow: 0 12px 32px rgba(0,0,0,0.18);">Verify Email</a>' .
            '</div>' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 32px;">If you did not create an account, you can safely ignore this email.</p>',
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars($verifyUrl, ENT_QUOTES)
        );

        return $this->wrapEmail('Welcome to AUBUN WORLD', $content);
    }

    private function buildNewsletterSubscriptionBody(string $email): string
    {
        $content = sprintf(
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 20px;">Your email <strong>%s</strong> is now subscribed.</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">You will receive early access to new collections, private events, and tailored styling notes.</p>',
            htmlspecialchars($email, ENT_QUOTES)
        );

        return $this->wrapEmail('AUBUN WORLD Newsletter', $content);
    }

    private function buildPaymentPendingBody(string $name, array $order): string
    {
        $orderNumber = (string) ($order['orderNumber'] ?? '');
        $paymentLink = $this->baseUrl !== ''
            ? rtrim($this->baseUrl, '/') . '/checkout?resumePayment=1&order=' . urlencode($orderNumber)
            : '#';
        $paymentMethod = (string) (($order['paymentMethodLabel'] ?? $order['paymentMethod'] ?? 'PayPal') ?: 'PayPal');

        $content = sprintf(
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Thank you for your order! We have received your order and it is currently <strong>awaiting payment</strong>. Your order will be processed once payment is confirmed.</p>' .
            '<div style="background: #fff9e6; border: 1px solid #f5a623; border-radius: 8px; padding: 16px; margin-bottom: 24px;"><p style="margin: 0; color: #8a6d00;"><strong>Status:</strong> Pending Payment</p><p style="margin: 8px 0 0; color: #8a6d00;"><strong>Payment Method:</strong> %s</p></div>' .
            '<div style="text-align: center; margin: 32px 0;">' .
            '<a href="%s" style="background: #0b0b0c; color: #ffffff; padding: 16px 28px; text-decoration: none; text-transform: uppercase; letter-spacing: 0.14em; font-size: 0.74rem; border-radius: 999px; display: inline-block;">Pay with PayPal / Card</a>' .
            '</div>' .
            '%s%s' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">If you have already completed payment, please allow a few minutes for processing. If you have any questions, please contact us.</p>',
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars($paymentMethod, ENT_QUOTES),
            htmlspecialchars($paymentLink, ENT_QUOTES),
            $this->buildOrderTable($order),
            $this->buildOrderSummary($order)
        );

        return $this->wrapEmail('Order Received - Awaiting Payment', $content, '#f5a623');
    }

    private function buildPaymentConfirmedBody(string $name, array $order): string
    {
        $paymentMethod = (string) (($order['paymentMethodLabel'] ?? $order['paymentMethod'] ?? 'PayPal') ?: 'PayPal');

        $content = sprintf(
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Great news! We have successfully received your payment for order <strong>%s</strong> using <strong>%s</strong>. Your order is now being processed and will be shipped soon.</p>' .
            '<div style="background: #e8f5e9; border: 1px solid #28a745; border-radius: 8px; padding: 16px; margin-bottom: 24px;"><p style="margin: 0; color: #1e7e34;"><strong>Status:</strong> Payment Received - Processing</p><p style="margin: 8px 0 0; color: #1e7e34;"><strong>Payment Method:</strong> %s</p></div>' .
            '%s%s' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">You will receive another notification when your order ships with tracking information. If you have any questions, please contact us.</p>',
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars((string) ($order['orderNumber'] ?? ''), ENT_QUOTES),
            htmlspecialchars($paymentMethod, ENT_QUOTES),
            htmlspecialchars($paymentMethod, ENT_QUOTES),
            $this->buildOrderTable($order),
            $this->buildOrderSummary($order, 'Total Paid')
        );

        return $this->wrapEmail('Payment Confirmed', $content, '#28a745');
    }

    private function buildShippingUpdateBody(string $name, array $order): string
    {
        $statusLabels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'paid' => 'Paid',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ];
        $statusColors = [
            'pending' => '#f5a623',
            'processing' => '#007bff',
            'paid' => '#28a745',
            'shipped' => '#6f42c1',
            'delivered' => '#28a745',
            'cancelled' => '#dc3545',
            'refunded' => '#6c757d',
        ];
        $status = (string) ($order['status'] ?? 'pending');
        $statusLabel = $statusLabels[$status] ?? ucfirst($status);
        $statusColor = $statusColors[$status] ?? '#0b0b0c';
        $trackingHtml = '';

        if (!empty($order['tracking_number'])) {
            $trackingHtml = sprintf(
                '<div style="background:#e8f5e9; border: 1px solid #28a745; border-radius: 8px; padding: 16px; margin-bottom: 24px;"><p style="margin: 4px 0;"><strong>Tracking Number:</strong> %s</p><p style="margin: 4px 0;"><strong>Carrier:</strong> %s</p><p style="margin: 4px 0;"><a href="%s" style="color: #28a745;">Track your shipment</a></p></div>',
                htmlspecialchars((string) $order['tracking_number'], ENT_QUOTES),
                htmlspecialchars((string) ($order['tracking_carrier'] ?? 'Carrier'), ENT_QUOTES),
                htmlspecialchars((string) ($order['tracking_url'] ?? '#'), ENT_QUOTES)
            );
        }

        $content = sprintf(
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Your order <strong>%s</strong> status has been updated to: <strong style="color: %s;">%s</strong>.</p>' .
            '<div style="background: #fafafa; border: 1px solid %s; border-radius: 8px; padding: 16px; margin-bottom: 24px;"><p style="margin: 0; color: %s;"><strong>Current Status:</strong> %s</p></div>' .
            '%s%s' .
            '<div style="background:#fafafa; padding:16px; border-radius:8px; margin-bottom:24px;"><p style="margin:4px 0;"><strong>Order Number:</strong> %s</p><p style="margin:4px 0;"><strong>Total:</strong> <strong>$%s</strong></p></div>' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">If you have any questions about your order, please contact us.</p>',
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars((string) ($order['orderNumber'] ?? ''), ENT_QUOTES),
            $statusColor,
            htmlspecialchars($statusLabel, ENT_QUOTES),
            $statusColor,
            $statusColor,
            htmlspecialchars($statusLabel, ENT_QUOTES),
            $trackingHtml,
            $this->buildOrderTable($order),
            htmlspecialchars((string) ($order['orderNumber'] ?? ''), ENT_QUOTES),
            htmlspecialchars(number_format((float) ($order['total'] ?? 0), 2), ENT_QUOTES)
        );

        return $this->wrapEmail('Order Status Update: ' . $statusLabel, $content, $statusColor);
    }

    private function buildOrderCancelledBody(string $name, array $order): string
    {
        $content = sprintf(
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">We regret to inform you that your order <strong>%s</strong> has been <strong>automatically cancelled</strong> because payment was not received within 1 hour of placing the order.</p>' .
            '<div style="background: #fde8e8; border: 1px solid #dc3545; border-radius: 8px; padding: 16px; margin-bottom: 24px;"><p style="margin: 0; color: #a71d2a;"><strong>Status:</strong> Cancelled - Payment Not Received</p></div>' .
            '%s%s' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">The items have been returned to stock and are available for purchase again. If you still wish to purchase these items, please place a new order.</p>',
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars((string) ($order['orderNumber'] ?? ''), ENT_QUOTES),
            $this->buildOrderTable($order),
            $this->buildOrderSummary($order)
        );

        return $this->wrapEmail('Order Cancelled - Payment Timeout', $content, '#dc3545');
    }
}
