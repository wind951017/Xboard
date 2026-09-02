<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComissionLogResource;
use App\Http\Resources\CommissionWithdrawalResource;
use App\Http\Resources\InviteCodeResource;
use App\Models\CommissionLog;
use App\Models\CommissionWithdrawal;
use App\Models\InviteCode;
use App\Models\User;
use App\Services\CommissionService;
use App\Utils\Helper;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function save(Request $request)
    {
        if (InviteCode::where('user_id', $request->user()->id)->where('status', 0)->count() >= admin_setting('invite_gen_limit', 5)) {
            return $this->fail([400,__('The maximum number of creations has been reached')]);
        }
        $inviteCode = new InviteCode();
        $inviteCode->user_id = $request->user()->id;
        $inviteCode->code = Helper::randomChar(8);
        return $this->success($inviteCode->save());
    }

    public function details(Request $request)
    {
        $current = $request->input('current') ? $request->input('current') : 1;
        $pageSize = $request->input('page_size') >= 10 ? $request->input('page_size') : 10;
        $builder = CommissionLog::where('invite_user_id', $request->user()->id)
            ->where('get_amount', '>', 0)
            ->with('user:id,email')
            ->orderBy('created_at', 'DESC');
        $total = $builder->count();
        $details = $builder->forPage($current, $pageSize)
            ->get();
        return response([
            'data' => ComissionLogResource::collection($details),
            'total' => $total
        ]);
    }

    public function fetch(Request $request)
    {
        $user = User::find($request->user()->id)
                ->load(['codes' => fn($query) => $query->where('status', 0)]);
        $stats = app(CommissionService::class)->buildInviteStats($user);
        $stat = [
            (int) $stats['registered_count'],
            (int) $stats['paid_commission'],
            (int) $stats['pending_commission'],
            (int) $stats['commission_rate'],
            (int) $stats['available_commission'],
        ];
        $data = [
            'codes' => InviteCodeResource::collection($user->codes),
            'stat' => $stat,
            'stats' => $stats,
            'distribution' => [
                'enable' => (bool) admin_setting('commission_distribution_enable', 0),
                'levels' => app(CommissionService::class)->getDistributionRates(),
            ],
        ];
        return $this->success($data);
    }

    public function team(Request $request)
    {
        $current = $request->input('current') ? $request->input('current') : 1;
        $pageSize = $request->input('page_size') >= 10 ? $request->input('page_size') : 10;

        $builder = User::select(['id', 'email', 'plan_id', 'created_at'])
            ->with('plan:id,name')
            ->where('invite_user_id', $request->user()->id)
            ->orderBy('created_at', 'DESC');

        $total = $builder->count();
        $users = $builder->forPage($current, $pageSize)->get();

        return response([
            'data' => $users,
            'total' => $total,
        ]);
    }

    public function withdrawals(Request $request)
    {
        $current = $request->input('current') ? $request->input('current') : 1;
        $pageSize = $request->input('page_size') >= 10 ? $request->input('page_size') : 10;

        $builder = CommissionWithdrawal::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'DESC');

        $total = $builder->count();
        $withdrawals = $builder->forPage($current, $pageSize)->get();

        return response([
            'data' => CommissionWithdrawalResource::collection($withdrawals),
            'total' => $total,
        ]);
    }
}
