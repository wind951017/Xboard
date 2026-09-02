<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('v2_agent')) {
            Schema::create('v2_agent', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('domain')->nullable()->index();
                $table->text('domains')->nullable();
                $table->string('site_name')->nullable();
                $table->string('logo')->nullable();
                $table->string('contact')->nullable();
                $table->decimal('commission_rate', 8, 2)->default(0);
                $table->integer('balance')->default(0);
                $table->integer('settled_amount')->default(0);
                $table->boolean('status')->default(1);
                $table->string('login_token', 128)->nullable()->index();
                $table->integer('created_at');
                $table->integer('updated_at');
            });
        }

        if (!Schema::hasTable('v2_agent_commission_log')) {
            Schema::create('v2_agent_commission_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agent_id')->index();
                $table->integer('user_id')->index();
                $table->integer('order_id')->index();
                $table->string('trade_no')->index();
                $table->integer('order_amount')->default(0);
                $table->decimal('commission_rate', 8, 2)->default(0);
                $table->integer('commission_amount')->default(0);
                $table->tinyInteger('status')->default(0)->index();
                $table->integer('settled_at')->nullable();
                $table->integer('created_at');
                $table->integer('updated_at');
            });
        }

        if (Schema::hasTable('v2_user')) {
            Schema::table('v2_user', function (Blueprint $table) {
                if (!Schema::hasColumn('v2_user', 'agent_id')) {
                    $table->unsignedBigInteger('agent_id')->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('v2_order')) {
            Schema::table('v2_order', function (Blueprint $table) {
                if (!Schema::hasColumn('v2_order', 'agent_id')) {
                    $table->unsignedBigInteger('agent_id')->nullable()->index();
                }
                if (!Schema::hasColumn('v2_order', 'agent_commission_rate')) {
                    $table->decimal('agent_commission_rate', 8, 2)->nullable();
                }
                if (!Schema::hasColumn('v2_order', 'agent_commission_amount')) {
                    $table->integer('agent_commission_amount')->default(0);
                }
                if (!Schema::hasColumn('v2_order', 'agent_settlement_status')) {
                    $table->tinyInteger('agent_settlement_status')->nullable()->index();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('v2_order')) {
            Schema::table('v2_order', function (Blueprint $table) {
                foreach (['agent_settlement_status', 'agent_commission_amount', 'agent_commission_rate', 'agent_id'] as $column) {
                    if (Schema::hasColumn('v2_order', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('v2_user')) {
            Schema::table('v2_user', function (Blueprint $table) {
                if (Schema::hasColumn('v2_user', 'agent_id')) {
                    $table->dropColumn('agent_id');
                }
            });
        }

        Schema::dropIfExists('v2_agent_commission_log');
        Schema::dropIfExists('v2_agent');
    }
}
