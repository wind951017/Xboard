<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('v2_commission_withdrawal')) {
            Schema::create('v2_commission_withdrawal', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('user_id')->index();
                $table->integer('ticket_id')->nullable()->index();
                $table->string('withdraw_method', 32);
                $table->string('withdraw_account', 255);
                $table->integer('amount');
                $table->integer('fee_amount')->default(0);
                $table->integer('actual_amount');
                $table->unsignedTinyInteger('status')->default(0)->index()->comment('0 pending, 1 approved, 2 rejected');
                $table->integer('processed_by')->nullable();
                $table->integer('processed_at')->nullable();
                $table->string('remark')->nullable();
                $table->integer('created_at');
                $table->integer('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('v2_commission_withdrawal');
    }
};
