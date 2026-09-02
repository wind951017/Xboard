<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentCommissionLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentService
{
    public function resolveFromRequest(Request $request): ?Agent
    {
        $agentCode = $request->input('agent_code') ?: $request->query('agent_code') ?: $request->cookie('xboard_agent_code');
        if ($agentCode) {
            return Agent::active()->where('code', trim((string) $agentCode))->first();
        }

        return $this->resolveByHost($request->getHost());
    }

    public function resolveByHost(?string $host): ?Agent
    {
        $host = $this->normalizeHost($host);
        if ($host === '') {
            return null;
        }

        return Agent::active()
            ->where(function ($query) use ($host) {
                $query->where('domain', $host)
                    ->orWhere('domains', 'like', '%'.$host.'%');
            })
            ->get()
            ->first(function (Agent $agent) use ($host) {
                return in_array($host, $this->agentDomains($agent), true);
            });
    }

    public function agentDomains(Agent $agent): array
    {
        $domains = [$agent->domain];
        if ($agent->domains) {
            $domains = array_merge($domains, preg_split('/[\s,]+/', (string) $agent->domains) ?: []);
        }

        return collect($domains)
            ->map(fn($domain) => $this->normalizeHost($domain))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function recordOrderCommission(Order $order): ?AgentCommissionLog
    {
        if (!$order->agent_id || $order->total_amount <= 0 || (int) $order->status !== Order::STATUS_COMPLETED) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            if (AgentCommissionLog::where('order_id', $order->id)->exists()) {
                return AgentCommissionLog::where('order_id', $order->id)->first();
            }

            $agent = Agent::lockForUpdate()->find($order->agent_id);
            if (!$agent || !$agent->status) {
                return null;
            }

            $rate = (float) ($order->agent_commission_rate ?? $agent->commission_rate);
            $amount = (int) floor($order->total_amount * ($rate / 100));

            if ($amount <= 0) {
                return null;
            }

            $log = AgentCommissionLog::create([
                'agent_id' => $agent->id,
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'trade_no' => $order->trade_no,
                'order_amount' => $order->total_amount,
                'commission_rate' => $rate,
                'commission_amount' => $amount,
                'status' => AgentCommissionLog::STATUS_PENDING,
            ]);

            $agent->balance = (int) $agent->balance + $amount;
            $agent->save();

            $order->agent_commission_amount = $amount;
            $order->agent_settlement_status = AgentCommissionLog::STATUS_PENDING;
            $order->save();

            return $log;
        });
    }

    public function normalizeHost(?string $host): string
    {
        $host = strtolower(trim((string) $host));
        $host = preg_replace('#^https?://#', '', $host);
        $host = explode('/', $host)[0] ?? '';
        $host = explode(':', $host)[0] ?? '';
        return trim($host);
    }

    public function publicConfig(?Agent $agent): ?array
    {
        if (!$agent) {
            return null;
        }

        return [
            'id' => $agent->id,
            'code' => $agent->code,
            'name' => $agent->name,
            'domain' => $agent->domain,
            'site_name' => $agent->site_name ?: $agent->name,
            'logo' => $agent->logo,
            'contact' => $agent->contact,
        ];
    }
}
