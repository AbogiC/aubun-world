<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\OrderRepository;

final class CronController
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly string $cronSecret = ''
    )
    {
    }

    public function cancelExpiredOrders(Request $request): array
    {
        // Verify cron secret to prevent unauthorized access
        $providedSecret = (string) $request->header('X-Cron-Secret');
        $expectedSecret = $this->cronSecret !== '' ? $this->cronSecret : ($_ENV['CRON_SECRET'] ?? (string) getenv('CRON_SECRET'));

        if ($expectedSecret === '' || !hash_equals($expectedSecret, $providedSecret)) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }

        $cancelled = $this->orders->cancelExpiredOrders(1);

        return [
            'message' => 'Expired orders processed',
            'cancelledCount' => count($cancelled),
            'cancelledOrders' => $cancelled,
        ];
    }
}