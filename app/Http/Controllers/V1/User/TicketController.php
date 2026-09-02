<?php

namespace App\Http\Controllers\V1\User;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\TicketSave;
use App\Http\Requests\User\TicketWithdraw;
use App\Http\Resources\TicketResource;
use App\Models\CommissionWithdrawal;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketService;
use App\Utils\Dict;
use Illuminate\Http\Request;
use App\Services\Plugin\HookManager;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function fetch(Request $request)
    {
        if ($request->input('id')) {
            $ticket = Ticket::where('id', $request->input('id'))
                ->where('user_id', $request->user()->id)
                ->first()
                ->load('message');
            if (!$ticket) {
                return $this->fail([400, __('Ticket does not exist')]);
            }
            $ticket['message'] = TicketMessage::where('ticket_id', $ticket->id)->get();
            $ticket['message']->each(function ($message) use ($ticket) {
                $message['is_me'] = ($message['user_id'] == $ticket->user_id);
            });
            return $this->success(TicketResource::make($ticket)->additional(['message' => true]));
        }
        $ticket = Ticket::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'DESC')
            ->get();
        return $this->success(TicketResource::collection($ticket));
    }

    public function save(TicketSave $request)
    {
        $ticketService = new TicketService();
        $ticket = $ticketService->createTicket(
            $request->user()->id,
            $request->input('subject'),
            $request->input('level'),
            $request->input('message')
        );
        HookManager::call('ticket.create.after', $ticket);
        return $this->success(true);

    }

    public function reply(Request $request)
    {
        if (empty($request->input('id'))) {
            return $this->fail([400, __('Invalid parameter')]);
        }
        if (empty($request->input('message'))) {
            return $this->fail([400, __('Message cannot be empty')]);
        }
        $ticket = Ticket::where('id', $request->input('id'))
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$ticket) {
            return $this->fail([400, __('Ticket does not exist')]);
        }
        if ($ticket->status) {
            return $this->fail([400, __('The ticket is closed and cannot be replied')]);
        }
        if ((int) admin_setting('ticket_must_wait_reply', 0) && $request->user()->id == $this->getLastMessage($ticket->id)->user_id) {
            return $this->fail(codeResponse: [400, __('Please wait for the technical enginneer to reply')]);
        }
        $ticketService = new TicketService();
        if (
            !$ticketService->reply(
                $ticket,
                $request->input('message'),
                $request->user()->id
            )
        ) {
            return $this->fail([400, __('Ticket reply failed')]);
        }
        HookManager::call('ticket.reply.user.after', $ticket);
        return $this->success(true);
    }


    public function close(Request $request)
    {
        if (empty($request->input('id'))) {
            return $this->fail([422, __('Invalid parameter')]);
        }
        $ticket = Ticket::where('id', $request->input('id'))
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$ticket) {
            return $this->fail([400, __('Ticket does not exist')]);
        }
        $ticket->status = Ticket::STATUS_CLOSED;
        if (!$ticket->save()) {
            return $this->fail([500, __('Close failed')]);
        }
        return $this->success(true);
    }

    private function getLastMessage($ticketId)
    {
        return TicketMessage::where('ticket_id', $ticketId)
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function withdraw(TicketWithdraw $request)
    {
        if ((int) admin_setting('withdraw_close_enable', 0)) {
            return $this->fail([400, __('Unsupported withdrawal')]);
        }

        if (
            !in_array(
                $request->input('withdraw_method'),
                admin_setting('commission_withdraw_method', Dict::WITHDRAW_METHOD_WHITELIST_DEFAULT)
            )
        ) {
            return $this->fail([422, __('Unsupported withdrawal method')]);
        }

        try {
            $ticket = DB::transaction(function () use ($request) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->first();
                if (!$user) {
                    throw new ApiException(__('The user does not exist'), 404);
                }

                if (Ticket::where('status', Ticket::STATUS_OPENING)->where('user_id', $user->id)->lockForUpdate()->first()) {
                    throw new ApiException('存在未关闭的工单');
                }

                $amount = (int) ($request->input('withdraw_amount') ?: $request->input('amount') ?: $user->commission_balance);
                $limit = (int) admin_setting('commission_withdraw_limit', 100) * 100;

                if ($amount < $limit) {
                    throw new ApiException(__('The current required minimum withdrawal commission is :limit', ['limit' => $limit / 100]), 422);
                }

                if ($amount > $user->commission_balance) {
                    throw new ApiException(__('Insufficient commission balance'), 422);
                }

                $feeRate = (float) admin_setting('commission_withdraw_fee_rate', admin_setting('app_withdraw_fee_rate', 0));
                $feeAmount = (int) floor($amount * $feeRate);
                $actualAmount = max(0, $amount - $feeAmount);

                $subject = __('[Commission Withdrawal Request] This ticket is opened by the system');
                $message = sprintf(
                    "%s\r\n%s\r\n%s\r\n%s",
                    __('Withdrawal method') . "：" . $request->input('withdraw_method'),
                    __('Withdrawal account') . "：" . $request->input('withdraw_account'),
                    '提现金额：' . number_format($amount / 100, 2),
                    '到账金额：' . number_format($actualAmount / 100, 2)
                );

                $ticket = Ticket::create([
                    'user_id' => $user->id,
                    'subject' => $subject,
                    'level' => 2,
                    'reply_status' => Ticket::REPLY_STATUS_WAITING,
                    'last_reply_user_id' => $user->id,
                ]);

                if (!$ticket) {
                    throw new ApiException('工单创建失败');
                }

                $ticketMessage = TicketMessage::create([
                    'user_id' => $user->id,
                    'ticket_id' => $ticket->id,
                    'message' => $message,
                ]);

                if (!$ticketMessage) {
                    throw new ApiException('工单消息创建失败');
                }

                $user->commission_balance -= $amount;
                if ($user->commission_balance < 0 || !$user->save()) {
                    throw new ApiException(__('Insufficient commission balance'), 422);
                }

                CommissionWithdrawal::create([
                    'user_id' => $user->id,
                    'ticket_id' => $ticket->id,
                    'withdraw_method' => $request->input('withdraw_method'),
                    'withdraw_account' => $request->input('withdraw_account'),
                    'amount' => $amount,
                    'fee_amount' => $feeAmount,
                    'actual_amount' => $actualAmount,
                    'status' => CommissionWithdrawal::STATUS_PENDING,
                ]);

                return $ticket;
            });
        } catch (\Exception $e) {
            throw $e;
        }

        HookManager::call('ticket.create.after', $ticket);
        return $this->success(true);
    }
}
