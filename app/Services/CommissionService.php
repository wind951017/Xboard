<?php

namespace App\Services;

use App\Models\CommissionLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * @return array<int, float>
     */
    public function getDistributionRates(): array
    {
        if (!(int) admin_setting('commission_distribution_enable', 0)) {
            return [1 => 100.0];
        }

        $rates = [
            1 => (float) admin_setting('commission_distribution_l1', 100),
            2 => (float) admin_setting('commission_distribution_l2', 0),
            3 => (float) admin_setting('commission_distribution_l3', 0),
        ];

        return array_filter($rates, fn(float $rate): bool => $rate > 0);
    }

    public function resolveCommissionRate(User $inviter): float
    {
        return $inviter->commission_rate
            ? (float) $inviter->commission_rate
            : (float) admin_setting('invite_commission', 10);
    }

    public function buildInviteStats(User $user): array
    {
        $pendingOrders = Order::where('status', Order::STATUS_COMPLETED)
            ->where('commission_status', 0)
            ->whereNotNull('invite_user_id')
            ->where('commission_balance', '>', 0)
            ->get(['id', 'user_id', 'invite_user_id', 'commission_balance']);

        $pendingAmount = 0;
        foreach ($pendingOrders as $order) {
            $pendingAmount += $this->calculatePendingAmountForUser($order, $user->id);
        }

        return [
            'registered_count' => (int) User::where('invite_user_id', $user->id)->count(),
            'paid_commission' => (int) CommissionLog::where('invite_user_id', $user->id)->sum('get_amount'),
            'pending_commission' => $pendingAmount,
            'commission_rate' => $this->resolveCommissionRate($user),
            'available_commission' => (int) $user->commission_balance,
        ];
    }

    private function calculatePendingAmountForUser(Order $order, int $userId): int
    {
        $inviteUserId = $order->invite_user_id;
        $visitedUserIds = [];

        foreach ($this->getDistributionRates() as $level => $distributionRate) {
            if (!$inviteUserId || in_array($inviteUserId, $visitedUserIds, true)) {
                break;
            }

            $visitedUserIds[] = $inviteUserId;
            $inviter = User::find($inviteUserId);
            if (!$inviter) {
                break;
            }

            if ($inviter->id === $userId) {
                return (int) floor($order->commission_balance * ($distributionRate / 100));
            }

            $inviteUserId = $inviter->invite_user_id;
        }

        return 0;
    }

    public function payOrder(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (!$order || (int) $order->commission_status !== 1 || !$order->invite_user_id || $order->commission_balance <= 0) {
                return false;
            }

            $inviteUserId = $order->invite_user_id;
            $actualCommissionBalance = 0;
            $commissionRate = (float) ($order->commission_rate ?: admin_setting('invite_commission', 10));
            $visitedUserIds = [];

            foreach ($this->getDistributionRates() as $level => $distributionRate) {
                if (!$inviteUserId || in_array($inviteUserId, $visitedUserIds, true)) {
                    break;
                }

                $visitedUserIds[] = $inviteUserId;
                $inviter = User::whereKey($inviteUserId)->lockForUpdate()->first();
                if (!$inviter) {
                    break;
                }

                if ($inviter->id === $order->user_id) {
                    break;
                }

                $commissionBalance = (int) floor($order->commission_balance * ($distributionRate / 100));
                if ($commissionBalance > 0) {
                    if ((int) admin_setting('withdraw_close_enable', 0)) {
                        $inviter->balance += $commissionBalance;
                    } else {
                        $inviter->commission_balance += $commissionBalance;
                    }

                    if (!$inviter->save()) {
                        throw new \RuntimeException('Failed to save inviter commission balance.');
                    }

                    CommissionLog::create([
                        'invite_user_id' => $inviter->id,
                        'user_id' => $order->user_id,
                        'level' => $level,
                        'commission_rate' => $commissionRate,
                        'distribution_rate' => $distributionRate,
                        'trade_no' => $order->trade_no,
                        'order_amount' => $order->total_amount,
                        'get_amount' => $commissionBalance,
                        'status' => 1,
                    ]);

                    $actualCommissionBalance += $commissionBalance;
                }

                $inviteUserId = $inviter->invite_user_id;
            }

            $order->actual_commission_balance = $actualCommissionBalance;
            $order->commission_status = 2;

            if (!$order->save()) {
                throw new \RuntimeException('Failed to save order commission status.');
            }

            return true;
        });
    }
}
