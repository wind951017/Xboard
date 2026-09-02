<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComissionLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this['id'],
            "user_id" => $this['user_id'],
            "level" => $this['level'] ?? 1,
            "commission_rate" => $this['commission_rate'],
            "distribution_rate" => $this['distribution_rate'],
            "order_amount" => $this['order_amount'],
            "trade_no" => $this['trade_no'],
            "get_amount" => $this['get_amount'],
            "status" => $this['status'] ?? 1,
            "created_at" => $this['created_at'],
            "user" => $this->whenLoaded('user'),
        ];
    }
}
