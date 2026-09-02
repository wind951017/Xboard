<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('v2_commission_log')) {
            Schema::table('v2_commission_log', function (Blueprint $table) {
                if (!Schema::hasColumn('v2_commission_log', 'level')) {
                    $table->unsignedTinyInteger('level')->default(1)->after('user_id');
                }
                if (!Schema::hasColumn('v2_commission_log', 'commission_rate')) {
                    $table->decimal('commission_rate', 8, 2)->nullable()->after('level');
                }
                if (!Schema::hasColumn('v2_commission_log', 'distribution_rate')) {
                    $table->decimal('distribution_rate', 8, 2)->nullable()->after('commission_rate');
                }
                if (!Schema::hasColumn('v2_commission_log', 'status')) {
                    $table->unsignedTinyInteger('status')->default(1)->after('get_amount');
                }
            });
        }

        if (Schema::hasTable('v2_order')) {
            Schema::table('v2_order', function (Blueprint $table) {
                if (!Schema::hasColumn('v2_order', 'commission_rate')) {
                    $table->decimal('commission_rate', 8, 2)->nullable()->after('commission_balance');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('v2_commission_log')) {
            Schema::table('v2_commission_log', function (Blueprint $table) {
                foreach (['status', 'distribution_rate', 'commission_rate', 'level'] as $column) {
                    if (Schema::hasColumn('v2_commission_log', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('v2_order') && Schema::hasColumn('v2_order', 'commission_rate')) {
            Schema::table('v2_order', function (Blueprint $table) {
                $table->dropColumn('commission_rate');
            });
        }
    }
};
