<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCommissionLog extends Model
{
    protected $table = 'v2_agent_commission_log';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'commission_rate' => 'float',
        'order_amount' => 'integer',
        'commission_amount' => 'integer',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'settled_at' => 'timestamp',
    ];

    public const STATUS_PENDING = 0;
    public const STATUS_SETTLED = 1;
    public const STATUS_VOID = 2;

    public static array $statusMap = [
        self::STATUS_PENDING => '待结算',
        self::STATUS_SETTLED => '已结算',
        self::STATUS_VOID => '已作废',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
