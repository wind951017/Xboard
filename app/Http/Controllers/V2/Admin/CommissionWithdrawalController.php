<?php

namespace App\Http\Controllers\V2\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommissionWithdrawalResource;
use App\Models\CommissionWithdrawal;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionWithdrawalController extends Controller
{
    public function fetch(Request $request)
    {
        $builder = CommissionWithdrawal::with(['user:id,email,commission_balance', 'ticket:id,status'])
            ->latest('created_at');

        $this->applyFilters($request, $builder);

        $page = $builder->paginate(
            perPage: $request->integer('pageSize', 10),
            page: $request->integer('current', 1)
        );

        $page->getCollection()->transform(fn($item) => CommissionWithdrawalResource::make($item));

        return $this->paginate($page);
    }

    public function approve(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'remark' => 'nullable|string|max:255',
        ]);

        $withdrawal = $this->finishWithdrawal(
            $request,
            CommissionWithdrawal::STATUS_APPROVED,
            '提现申请已通过'
        );

        return $this->success(CommissionWithdrawalResource::make($withdrawal));
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'remark' => 'nullable|string|max:255',
        ]);

        $withdrawal = $this->finishWithdrawal(
            $request,
            CommissionWithdrawal::STATUS_REJECTED,
            '提现申请已拒绝，佣金已退回账户'
        );

        return $this->success(CommissionWithdrawalResource::make($withdrawal));
    }

    private function finishWithdrawal(Request $request, int $status, string $ticketMessage): CommissionWithdrawal
    {
        return DB::transaction(function () use ($request, $status, $ticketMessage): CommissionWithdrawal {
            $withdrawal = CommissionWithdrawal::whereKey($request->input('id'))->lockForUpdate()->first();

            if (!$withdrawal) {
                throw new ApiException('提现申请不存在', 404);
            }

            if ((int) $withdrawal->status !== CommissionWithdrawal::STATUS_PENDING) {
                throw new ApiException('提现申请已处理', 422);
            }

            if ($status === CommissionWithdrawal::STATUS_REJECTED) {
                $user = User::whereKey($withdrawal->user_id)->lockForUpdate()->first();
                if ($user) {
                    $user->commission_balance += $withdrawal->amount;
                    if (!$user->save()) {
                        throw new \RuntimeException('Failed to refund commission balance.');
                    }
                }
            }

            $withdrawal->status = $status;
            $withdrawal->remark = $request->input('remark');
            $withdrawal->processed_by = $request->user()->id;
            $withdrawal->processed_at = time();
            if (!$withdrawal->save()) {
                throw new \RuntimeException('Failed to save withdrawal status.');
            }

            if ($withdrawal->ticket_id) {
                $message = $ticketMessage;
                if ($withdrawal->remark) {
                    $message .= "\r\n备注：" . $withdrawal->remark;
                }
                app(TicketService::class)->replyByAdmin($withdrawal->ticket_id, $message, $request->user()->id);

                Ticket::whereKey($withdrawal->ticket_id)->update(['status' => Ticket::STATUS_CLOSED]);
            }

            return $withdrawal->refresh();
        });
    }

    private function applyFilters(Request $request, Builder $builder): void
    {
        if ($request->filled('status')) {
            $builder->where('status', $request->integer('status'));
        }

        if ($request->filled('email')) {
            $builder->whereHas('user', function (Builder $query) use ($request) {
                $query->where('email', 'like', '%' . $request->input('email') . '%');
            });
        }

        if ($request->filled('withdraw_method')) {
            $builder->where('withdraw_method', $request->input('withdraw_method'));
        }
    }
}
