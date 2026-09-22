<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class EmailService
{
    public function __construct(
        private readonly string $fromEmail = 'noreply@aubunworld.com',
        private readonly string $fromName = 'AUBUN WORLD',
        private readonly string $baseUrl = 'http://localhost:5173'
    ) {
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

    public function sendNewsletterSubscriptionEmail(string $toEmail): void
    {
        $subject = 'You are subscribed to the AUBUN WORLD newsletter';
        $body = $this->buildNewsletterSubscriptionBody($toEmail);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
        ];

        $sent = mail($toEmail, $subject, $body, implode("\r\n", $headers));

        if (!$sent) {
            throw new RuntimeException('Failed to send newsletter subscription email.', 500);
        }
    }

    public function sendOrderConfirmation(string $toEmail, string $toName, array $order): void
    {
        $subject = 'Your AUBUN WORLD Order Confirmation';
        $body = $this->buildOrderConfirmationBody($toName, $order);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
        ];

        mail($toEmail, $subject, $body, implode("\r\n", $headers));
    }

    public function sendPaymentPendingEmail(string $toEmail, string $toName, array $order): void
    {
        $subject = 'AUBUN WORLD - Order Received: Awaiting Payment';
        $body = $this->buildPaymentPendingBody($toName, $order);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
        ];

        mail($toEmail, $subject, $body, implode("\r\n", $headers));
    }

    public function sendPaymentConfirmedEmail(string $toEmail, string $toName, array $order): void
    {
        $subject = 'AUBUN WORLD - Payment Confirmed: Your Order is Being Processed';
        $body = $this->buildPaymentConfirmedBody($toName, $order);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
        ];

        mail($toEmail, $subject, $body, implode("\r\n", $headers));
    }

    public function sendShippingUpdateEmail(string $toEmail, string $toName, array $order): void
    {
        $subject = 'AUBUN WORLD - Shipping Update: ' . ucfirst($order['status']);
        $body = $this->buildShippingUpdateBody($toName, $order);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
        ];

        mail($toEmail, $subject, $body, implode("\r\n", $headers));
    }

    public function sendOrderCancelledEmail(string $toEmail, string $toName, array $order): void
    {
        $subject = 'AUBUN WORLD - Order Cancelled: Payment Timeout';
        $body = $this->buildOrderCancelledBody($toName, $order);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
        ];

        mail($toEmail, $subject, $body, implode("\r\n", $headers));
    }

    private function buildOrderConfirmationBody(string $name, array $order): string
    {
        $itemsHtml = '';
        foreach ($order['items'] ?? [] as $item) {
            $itemsHtml .= sprintf(
                '<tr><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:center;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:right;">$%s</td></tr>',
                htmlspecialchars($item['name'] ?? '', ENT_QUOTES),
                htmlspecialchars($item['size'] ?? '', ENT_QUOTES) . ' / ' . htmlspecialchars($item['color'] ?? '', ENT_QUOTES) . ' x ' . (int)($item['quantity'] ?? 0),
                htmlspecialchars($item['unit_price'] ?? '', ENT_QUOTES),
                htmlspecialchars(number_format((float)($item['line_total'] ?? 0), 2), ENT_QUOTES)
            );
        }

        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Confirmation</title></head><body style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; padding: 40px; background: #f7f7f5;">' .
            '<div style="background: white; padding: 48px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08);">' .
            '<h1 style="color: #0b0b0c; margin-bottom: 24px; letter-spacing: 0.08em; text-transform: uppercase; font-size: 1.4rem;">Order Confirmed</h1>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Thank you for your purchase! Here are your order details:</p>' .
            '<table style="width:100%%; border-collapse:collapse; margin-bottom:24px;">' .
            '<tr style="background:#fafafa;"><th style="text-align:left; padding:8px; border-bottom:2px solid #eee;">Product</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Details</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Unit Price</th><th style="text-align:right; padding:8px; border-bottom:2px solid #eee;">Total</th></tr>' .
            '%s' .
            '</table>' .
            '<div style="background:#fafafa; padding:16px; border-radius:8px; margin-bottom:24px;">' .
            '<p style="margin:4px 0;"><strong>Order Number:</strong> %s</p>' .
            '<p style="margin:4px 0;"><strong>Subtotal:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>Shipping:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>Total:</strong> <strong>$%s</strong></p>' .
            '</div>' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">If you have any questions, please contact us.</p>' .
            '<p style="color: #6f6f74; font-size: 0.85rem; margin-top: 48px; border-top: 1px solid rgba(11,11,12,0.08); padding-top: 24px;">Best regards,<br>AUBUN WORLD Team</p>' .
            '</div></body></html>',
            htmlspecialchars($name, ENT_QUOTES),
            $itemsHtml,
            htmlspecialchars($order['orderNumber'] ?? '', ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['subtotal'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['shipping'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['total'] ?? 0), 2), ENT_QUOTES)
        );
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

    private function buildNewsletterSubscriptionBody(string $email): string
    {
        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Newsletter Subscription</title></head><body style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; padding: 40px; background: #f7f7f5;">' .
            '<div style="background: white; padding: 48px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08);">' .
            '<h1 style="color: #0b0b0c; margin-bottom: 24px; letter-spacing: 0.08em; text-transform: uppercase; font-size: 1.4rem;">AUBUN WORLD Newsletter</h1>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 20px;">Your email <strong>%s</strong> is now subscribed.</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">You will receive early access to new collections, private events, and tailored styling notes.</p>' .
            '<p style="color: #6f6f74; font-size: 0.85rem; margin-top: 48px; border-top: 1px solid rgba(11,11,12,0.08); padding-top: 24px;">Best regards,<br>AUBUN WORLD Team</p>' .
            '</div></body></html>',
            htmlspecialchars($email, ENT_QUOTES)
        );
    }

    private function buildPaymentPendingBody(string $name, array $order): string
    {
        $itemsHtml = '';
        foreach ($order['items'] ?? [] as $item) {
            $itemsHtml .= sprintf(
                '<tr><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:center;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:right;">$%s</td></tr>',
                htmlspecialchars($item['name'] ?? '', ENT_QUOTES),
                htmlspecialchars($item['size'] ?? '', ENT_QUOTES) . ' / ' . htmlspecialchars($item['color'] ?? '', ENT_QUOTES) . ' x ' . (int)($item['quantity'] ?? 0),
                htmlspecialchars($item['unit_price'] ?? '', ENT_QUOTES),
                htmlspecialchars(number_format((float)($item['line_total'] ?? 0), 2), ENT_QUOTES)
            );
        }

        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Received - Awaiting Payment</title></head><body style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; padding: 40px; background: #f7f7f5;">' .
            '<div style="background: white; padding: 48px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08);">' .
            '<h1 style="color: #f5a623; margin-bottom: 24px; letter-spacing: 0.08em; text-transform: uppercase; font-size: 1.4rem;">Order Received - Awaiting Payment</h1>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Thank you for your order! We have received your order and it is currently <strong>awaiting payment</strong>. Your order will be processed once payment is confirmed.</p>' .
            '<div style="background: #fff9e6; border: 1px solid #f5a623; border-radius: 8px; padding: 16px; margin-bottom: 24px;">' .
            '<p style="margin: 0; color: #8a6d00;"><strong>Status:</strong> Pending Payment</p>' .
            '</div>' .
            '<table style="width:100%%; border-collapse:collapse; margin-bottom:24px;">' .
            '<tr style="background:#fafafa;"><th style="text-align:left; padding:8px; border-bottom:2px solid #eee;">Product</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Details</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Unit Price</th><th style="text-align:right; padding:8px; border-bottom:2px solid #eee;">Total</th></tr>' .
            '%s' .
            '</table>' .
            '<div style="background:#fafafa; padding:16px; border-radius:8px; margin-bottom:24px;">' .
            '<p style="margin:4px 0;"><strong>Order Number:</strong> %s</p>' .
            '<p style="margin:4px 0;"><strong>Subtotal:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>Shipping:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>Total:</strong> <strong>$%s</strong></p>' .
            '</div>' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">If you have already completed payment, please allow a few minutes for processing. If you have any questions, please contact us.</p>' .
            '<p style="color: #6f6f74; font-size: 0.85rem; margin-top: 48px; border-top: 1px solid rgba(11,11,12,0.08); padding-top: 24px;">Best regards,<br>AUBUN WORLD Team</p>' .
            '</div></body></html>',
            htmlspecialchars($name, ENT_QUOTES),
            $itemsHtml,
            htmlspecialchars($order['orderNumber'] ?? '', ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['subtotal'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['shipping'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['total'] ?? 0), 2), ENT_QUOTES)
        );
    }

    private function buildPaymentConfirmedBody(string $name, array $order): string
    {
        $itemsHtml = '';
        foreach ($order['items'] ?? [] as $item) {
            $itemsHtml .= sprintf(
                '<tr><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:center;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:right;">$%s</td></tr>',
                htmlspecialchars($item['name'] ?? '', ENT_QUOTES),
                htmlspecialchars($item['size'] ?? '', ENT_QUOTES) . ' / ' . htmlspecialchars($item['color'] ?? '', ENT_QUOTES) . ' x ' . (int)($item['quantity'] ?? 0),
                htmlspecialchars($item['unit_price'] ?? '', ENT_QUOTES),
                htmlspecialchars(number_format((float)($item['line_total'] ?? 0), 2), ENT_QUOTES)
            );
        }

        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Payment Confirmed</title></head><body style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; padding: 40px; background: #f7f7f5;">' .
            '<div style="background: white; padding: 48px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08);">' .
            '<h1 style="color: #28a745; margin-bottom: 24px; letter-spacing: 0.08em; text-transform: uppercase; font-size: 1.4rem;">Payment Confirmed</h1>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Great news! We have successfully received your payment for order <strong>%s</strong>. Your order is now being processed and will be shipped soon.</p>' .
            '<div style="background: #e8f5e9; border: 1px solid #28a745; border-radius: 8px; padding: 16px; margin-bottom: 24px;">' .
            '<p style="margin: 0; color: #1e7e34;"><strong>Status:</strong> Payment Received - Processing</p>' .
            '</div>' .
            '<table style="width:100%%; border-collapse:collapse; margin-bottom:24px;">' .
            '<tr style="background:#fafafa;"><th style="text-align:left; padding:8px; border-bottom:2px solid #eee;">Product</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Details</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Unit Price</th><th style="text-align:right; padding:8px; border-bottom:2px solid #eee;">Total</th></tr>' .
            '%s' .
            '</table>' .
            '<div style="background:#fafafa; padding:16px; border-radius:8px; margin-bottom:24px;">' .
            '<p style="margin:4px 0;"><strong>Order Number:</strong> %s</p>' .
            '<p style="margin:4px 0;"><strong>Subtotal:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>Shipping:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>Total Paid:</strong> <strong>$%s</strong></p>' .
            '</div>' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">You will receive another notification when your order ships with tracking information. If you have any questions, please contact us.</p>' .
            '<p style="color: #6f6f74; font-size: 0.85rem; margin-top: 48px; border-top: 1px solid rgba(11,11,12,0.08); padding-top: 24px;">Best regards,<br>AUBUN WORLD Team</p>' .
            '</div></body></html>',
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars($order['orderNumber'] ?? '', ENT_QUOTES),
            $itemsHtml,
            htmlspecialchars($order['orderNumber'] ?? '', ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['subtotal'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['shipping'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['total'] ?? 0), 2), ENT_QUOTES)
        );
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

        $status = $order['status'] ?? 'pending';
        $statusLabel = $statusLabels[$status] ?? ucfirst($status);
        $statusColor = $statusColors[$status] ?? '#0b0b0c';

        $itemsHtml = '';
        foreach ($order['items'] ?? [] as $item) {
            $itemsHtml .= sprintf(
                '<tr><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:center;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:right;">$%s</td></tr>',
                htmlspecialchars($item['name'] ?? '', ENT_QUOTES),
                htmlspecialchars($item['size'] ?? '', ENT_QUOTES) . ' / ' . htmlspecialchars($item['color'] ?? '', ENT_QUOTES) . ' x ' . (int)($item['quantity'] ?? 0),
                htmlspecialchars($item['unit_price'] ?? '', ENT_QUOTES),
                htmlspecialchars(number_format((float)($item['line_total'] ?? 0), 2), ENT_QUOTES)
            );
        }

        $trackingHtml = '';
        if (!empty($order['tracking_number'])) {
            $trackingHtml = sprintf(
                '<div style="background:#e8f5e9; border: 1px solid #28a745; border-radius: 8px; padding: 16px; margin-bottom: 24px;">' .
                '<p style="margin: 4px 0;"><strong>Tracking Number:</strong> %s</p>' .
                '<p style="margin: 4px 0;"><strong>Carrier:</strong> %s</p>' .
                '<p style="margin: 4px 0;"><a href="%s" style="color: #28a745;">Track your shipment</a></p>' .
                '</div>',
                htmlspecialchars($order['tracking_number'], ENT_QUOTES),
                htmlspecialchars($order['tracking_carrier'] ?? 'Carrier', ENT_QUOTES),
                htmlspecialchars($order['tracking_url'] ?? '#', ENT_QUOTES)
            );
        }

        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Shipping Update</title></head><body style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; padding: 40px; background: #f7f7f5;">' .
            '<div style="background: white; padding: 48px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08);">' .
            '<h1 style="color: %s; margin-bottom: 24px; letter-spacing: 0.08em; text-transform: uppercase; font-size: 1.4rem;">Order Status Update: %s</h1>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Your order <strong>%s</strong> status has been updated to: <strong style="color: %s;">%s</strong>.</p>' .
            '<div style="background: #fafafa; border: 1px solid %s; border-radius: 8px; padding: 16px; margin-bottom: 24px;">' .
            '<p style="margin: 0; color: %s;"><strong>Current Status:</strong> %s</p>' .
            '</div>' .
            '%s' .
            '<table style="width:100%%; border-collapse:collapse; margin-bottom:24px;">' .
            '<tr style="background:#fafafa;"><th style="text-align:left; padding:8px; border-bottom:2px solid #eee;">Product</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Details</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Unit Price</th><th style="text-align:right; padding:8px; border-bottom:2px solid #eee;">Total</th></tr>' .
            '%s' .
            '</table>' .
            '<div style="background:#fafafa; padding:16px; border-radius:8px; margin-bottom:24px;">' .
            '<p style="margin:4px 0;"><strong>Order Number:</strong> %s</p>' .
            '<p style="margin:4px 0;"><strong>Total:</strong> <strong>$%s</strong></p>' .
            '</div>' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">If you have any questions about your order, please contact us.</p>' .
            '<p style="color: #6f6f74; font-size: 0.85rem; margin-top: 48px; border-top: 1px solid rgba(11,11,12,0.08); padding-top: 24px;">Best regards,<br>AUBUN WORLD Team</p>' .
            '</div></body></html>',
            $statusColor,
            $statusLabel,
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars($order['orderNumber'] ?? '', ENT_QUOTES),
            $statusColor,
            $statusLabel,
            $statusColor,
            $statusColor,
            $statusLabel,
            $trackingHtml,
            $itemsHtml,
            htmlspecialchars($order['orderNumber'] ?? '', ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['total'] ?? 0), 2), ENT_QUOTES)
        );
    }

    private function buildOrderCancelledBody(string $name, array $order): string
    {
        $itemsHtml = '';
        foreach ($order['items'] ?? [] as $item) {
            $itemsHtml .= sprintf(
                '<tr><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:center;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee;">%s</td><td style="padding:10px 0; border-bottom:1px solid #eee; text-align:right;">$%s</td></tr>',
                htmlspecialchars($item['name'] ?? '', ENT_QUOTES),
                htmlspecialchars($item['size'] ?? '', ENT_QUOTES) . ' / ' . htmlspecialchars($item['color'] ?? '', ENT_QUOTES) . ' x ' . (int)($item['quantity'] ?? 0),
                htmlspecialchars($item['unit_price'] ?? '', ENT_QUOTES),
                htmlspecialchars(number_format((float)($item['line_total'] ?? 0), 2), ENT_QUOTES)
            );
        }

        return sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Cancelled</title></head><body style="font-family: Georgia, serif; max-width: 600px; margin: 0 auto; padding: 40px; background: #f7f7f5;">' .
            '<div style="background: white; padding: 48px; border-radius: 16px; box-shadow: 0 12px 40px rgba(0,0,0,0.08);">' .
            '<h1 style="color: #dc3545; margin-bottom: 24px; letter-spacing: 0.08em; text-transform: uppercase; font-size: 1.4rem;">Order Cancelled - Payment Timeout</h1>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">Dear %s,</p>' .
            '<p style="color: #6f6f74; font-size: 1rem; line-height: 1.7; margin-bottom: 28px;">We regret to inform you that your order <strong>%s</strong> has been <strong>automatically cancelled</strong> because payment was not received within 1 hour of placing the order.</p>' .
            '<div style="background: #fde8e8; border: 1px solid #dc3545; border-radius: 8px; padding: 16px; margin-bottom: 24px;">' .
            '<p style="margin: 0; color: #a71d2a;"><strong>Status:</strong> Cancelled - Payment Not Received</p>' .
            '</div>' .
            '<table style="width:100%%; border-collapse:collapse; margin-bottom:24px;">' .
            '<tr style="background:#fafafa;"><th style="text-align:left; padding:8px; border-bottom:2px solid #eee;">Product</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Details</th><th style="text-align:center; padding:8px; border-bottom:2px solid #eee;">Unit Price</th><th style="text-align:right; padding:8px; border-bottom:2px solid #eee;">Total</th></tr>' .
            '%s' .
            '</table>' .
            '<div style="background:#fafafa; padding:16px; border-radius:8px; margin-bottom:24px;">' .
            '<p style="margin:4px 0;"><strong>Order Number:</strong> %s</p>' .
            '<p style="margin:4px 0;"><strong>Subtotal:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>Shipping:</strong> $%s</p>' .
            '<p style="margin:4px 0;"><strong>Total:</strong> <strong>$%s</strong></p>' .
            '</div>' .
            '<p style="color: #6f6f74; font-size: 0.9rem; line-height: 1.6; margin-top: 24px;">The items have been returned to stock and are available for purchase again. If you still wish to purchase these items, please place a new order.</p>' .
            '<p style="color: #6f6f74; font-size: 0.85rem; margin-top: 48px; border-top: 1px solid rgba(11,11,12,0.08); padding-top: 24px;">Best regards,<br>AUBUN WORLD Team</p>' .
            '</div></body></html>',
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars($order['orderNumber'] ?? '', ENT_QUOTES),
            $itemsHtml,
            htmlspecialchars($order['orderNumber'] ?? '', ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['subtotal'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['shipping'] ?? 0), 2), ENT_QUOTES),
            htmlspecialchars(number_format((float)($order['total'] ?? 0), 2), ENT_QUOTES)
        );
    }
}
