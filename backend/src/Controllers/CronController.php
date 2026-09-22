<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\OrderRepository;

final class CronController
{
    public function __construct(
        private readonly OrderRepository $orders
    )
    {
    }

    public function cancelExpiredOrders(Request $request): array
    {
        // Verify cron secret to prevent unauthorized access
        $providedSecret = $request->header('X-Cron-Secret');
        $expectedSecret = $_ENV['CRON_SECRET'] ?? '';

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