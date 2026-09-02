<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionWithdrawalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'user_id' => $this['user_id'],
            'ticket_id' => $this['ticket_id'],
            'withdraw_method' => $this['withdraw_method'],
            'withdraw_account' => $this['withdraw_account'],
            'amount' => $this['amount'],
            'fee_amount' => $this['fee_amount'],
            'actual_amount' => $this['actual_amount'],
            'status' => $this['status'],
            'status_text' => \App\Models\CommissionWithdrawal::$statusMap[$this['status']] ?? '未知',
            'processed_by' => $this['processed_by'],
            'processed_at' => $this['processed_at'],
            'remark' => $this['remark'],
            'created_at' => $this['created_at'],
            'updated_at' => $this['updated_at'],
            'user' => $this->whenLoaded('user'),
            'ticket' => $this->whenLoaded('ticket'),
        ];
    }
}
