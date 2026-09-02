<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    protected $table = 'v2_agent';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'login_token'];
    protected $casts = [
        'status' => 'boolean',
        'commission_rate' => 'float',
        'balance' => 'integer',
        'settled_amount' => 'integer',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'agent_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'agent_id', 'id');
    }

    public function commissionLogs(): HasMany
    {
        return $this->hasMany(AgentCommissionLog::class, 'agent_id', 'id');
    }
}
