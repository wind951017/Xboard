<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentCommissionLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AgentPanelController extends Controller
{
    private const COOKIE = 'xboard_agent_token';

    public function loginPage(Request $request)
    {
        if ($this->currentAgent($request)) {
            return redirect('/agent/dashboard');
        }

        return view('agent.login', [
            'title' => '代理后台登录',
            'error' => $request->query('error'),
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return redirect('/agent/login?error=请填写正确的邮箱和密码');
        }

        $agent = Agent::active()->where('email', strtolower(trim($request->input('email'))))->first();
        if (!$agent || !Hash::check($request->input('password'), $agent->password)) {
            return redirect('/agent/login?error=账号或密码错误');
        }

        $token = hash('sha256', Str::random(80));
        $agent->login_token = $token;
        $agent->save();

        return redirect('/agent/dashboard')
            ->withCookie(cookie(self::COOKIE, $token, 60 * 24 * 7, null, null, false, true));
    }

    public function logout(Request $request)
    {
        if ($agent = $this->currentAgent($request)) {
            $agent->login_token = null;
            $agent->save();
        }

        return redirect('/agent/login')->withCookie(cookie()->forget(self::COOKIE));
    }

    public function dashboard(Request $request)
    {
        $agent = $this->requireAgent($request);

        return view('agent.dashboard', [
            'agent' => $agent,
            'stats' => [
                'users' => User::where('agent_id', $agent->id)->count(),
                'orders' => Order::where('agent_id', $agent->id)->count(),
                'paid_amount' => Order::where('agent_id', $agent->id)->where('status', Order::STATUS_COMPLETED)->sum('total_amount'),
                'pending_commission' => AgentCommissionLog::where('agent_id', $agent->id)->where('status', AgentCommissionLog::STATUS_PENDING)->sum('commission_amount'),
                'settled_commission' => AgentCommissionLog::where('agent_id', $agent->id)->where('status', AgentCommissionLog::STATUS_SETTLED)->sum('commission_amount'),
            ],
        ]);
    }

    public function users(Request $request)
    {
        $agent = $this->requireAgent($request);

        return view('agent.users', [
            'agent' => $agent,
            'users' => User::where('agent_id', $agent->id)->latest('id')->paginate(20),
        ]);
    }

    public function orders(Request $request)
    {
        $agent = $this->requireAgent($request);

        return view('agent.orders', [
            'agent' => $agent,
            'orders' => Order::with(['user', 'plan'])->where('agent_id', $agent->id)->latest('id')->paginate(20),
        ]);
    }

    public function commissions(Request $request)
    {
        $agent = $this->requireAgent($request);

        return view('agent.commissions', [
            'agent' => $agent,
            'logs' => AgentCommissionLog::with(['user', 'order'])
                ->where('agent_id', $agent->id)
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function settings(Request $request)
    {
        $agent = $this->requireAgent($request);

        return view('agent.settings', [
            'agent' => $agent,
            'saved' => $request->query('saved'),
            'error' => $request->query('error'),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $agent = $this->requireAgent($request);
        $validator = Validator::make($request->all(), [
            'site_name' => 'nullable|string|max:100',
            'logo' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|max:64',
        ]);
        if ($validator->fails()) {
            return redirect('/agent/settings?error=参数不正确，密码至少 8 位');
        }

        $agent->site_name = $request->input('site_name');
        $agent->logo = $request->input('logo');
        $agent->contact = $request->input('contact');
        if ($request->filled('password')) {
            $agent->password = Hash::make($request->input('password'));
        }
        $agent->save();

        return redirect('/agent/settings?saved=1');
    }

    private function currentAgent(Request $request): ?Agent
    {
        $token = $request->cookie(self::COOKIE);
        if (!$token) {
            return null;
        }

        return Agent::active()->where('login_token', $token)->first();
    }

    private function requireAgent(Request $request): Agent
    {
        $agent = $this->currentAgent($request);
        if (!$agent) {
            throw new HttpResponseException(redirect('/agent/login'));
        }

        return $agent;
    }
}
