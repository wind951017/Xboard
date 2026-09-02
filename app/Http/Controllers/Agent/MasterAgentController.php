<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentCommissionLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MasterAgentController extends Controller
{
    private const COOKIE = 'xboard_agent_master';

    public function loginPage(Request $request)
    {
        return view('agent.master-login', [
            'title' => '总代管理登录',
            'error' => $request->query('error'),
            'configured' => (bool) $this->masterKey(),
        ]);
    }

    public function login(Request $request)
    {
        $key = $this->masterKey();
        if (!$key || !hash_equals($key, (string) $request->input('password'))) {
            return redirect('/agent/master/login?error=管理密钥错误或未设置');
        }

        return redirect('/agent/master')
            ->withCookie(cookie(self::COOKIE, hash('sha256', $key), 60 * 12, null, null, false, true));
    }

    public function logout()
    {
        return redirect('/agent/master/login')->withCookie(cookie()->forget(self::COOKIE));
    }

    public function index(Request $request)
    {
        $this->requireMaster($request);

        return view('agent.master', [
            'agents' => Agent::latest('id')->paginate(20),
            'stats' => [
                'agents' => Agent::count(),
                'users' => User::whereNotNull('agent_id')->count(),
                'orders' => Order::whereNotNull('agent_id')->count(),
                'pending_commission' => AgentCommissionLog::where('status', AgentCommissionLog::STATUS_PENDING)->sum('commission_amount'),
            ],
            'saved' => $request->query('saved'),
            'password' => $request->query('password'),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireMaster($request);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'domain' => 'nullable|string|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);
        if ($validator->fails()) {
            return redirect('/agent/master?error=参数不正确');
        }

        $password = Str::random(12);
        Agent::create([
            'code' => Str::lower(Str::random(8)),
            'name' => $request->input('name'),
            'email' => strtolower(trim($request->input('email'))),
            'password' => Hash::make($password),
            'domain' => app(\App\Services\AgentService::class)->normalizeHost($request->input('domain')),
            'site_name' => $request->input('name'),
            'commission_rate' => $request->input('commission_rate'),
            'status' => 1,
        ]);

        return redirect('/agent/master?saved=1&password='.$password);
    }

    public function update(Request $request, $agentId)
    {
        $this->requireMaster($request);
        $agent = Agent::findOrFail($agentId);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'domain' => 'nullable|string|max:255',
            'domains' => 'nullable|string|max:1000',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'nullable|integer|in:0,1',
            'password' => 'nullable|string|min:8|max:64',
        ]);
        if ($validator->fails()) {
            return redirect('/agent/master?error=参数不正确，密码至少 8 位');
        }

        $agent->name = $request->input('name');
        $agent->email = strtolower(trim($request->input('email')));
        $agent->domain = app(\App\Services\AgentService::class)->normalizeHost($request->input('domain'));
        $agent->domains = $request->input('domains');
        $agent->commission_rate = $request->input('commission_rate');
        $agent->status = (int) $request->input('status', 0);
        if ($request->filled('password')) {
            $agent->password = Hash::make($request->input('password'));
        }
        $agent->save();

        return redirect('/agent/master?saved=1');
    }

    public function commissions(Request $request)
    {
        $this->requireMaster($request);

        return view('agent.master-commissions', [
            'logs' => AgentCommissionLog::with(['agent', 'user', 'order'])->latest('id')->paginate(30),
            'saved' => $request->query('saved'),
        ]);
    }

    public function settle(Request $request)
    {
        $this->requireMaster($request);
        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        DB::transaction(function () use ($ids) {
            $logs = AgentCommissionLog::whereIn('id', $ids)
                ->where('status', AgentCommissionLog::STATUS_PENDING)
                ->lockForUpdate()
                ->get();

            foreach ($logs as $log) {
                $agent = Agent::lockForUpdate()->find($log->agent_id);
                if (!$agent) {
                    continue;
                }

                $log->status = AgentCommissionLog::STATUS_SETTLED;
                $log->settled_at = time();
                $log->save();

                $agent->balance = max(0, (int) $agent->balance - (int) $log->commission_amount);
                $agent->settled_amount = (int) $agent->settled_amount + (int) $log->commission_amount;
                $agent->save();

                Order::where('id', $log->order_id)->update([
                    'agent_settlement_status' => AgentCommissionLog::STATUS_SETTLED,
                ]);
            }
        });

        return redirect('/agent/master/commissions?saved=1');
    }

    private function masterKey(): ?string
    {
        $key = admin_setting('agent_master_key') ?: env('AGENT_MASTER_KEY');
        return $key ? (string) $key : null;
    }

    private function requireMaster(Request $request): void
    {
        $key = $this->masterKey();
        if (!$key || $request->cookie(self::COOKIE) !== hash('sha256', $key)) {
            throw new HttpResponseException(redirect('/agent/master/login'));
        }
    }
}
