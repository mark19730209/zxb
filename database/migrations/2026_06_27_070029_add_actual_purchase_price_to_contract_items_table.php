<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 执行迁移：追加本次实际下单的国内含税采购单价字段
     */
    public function up(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            // 🎯 核心更新：使用 decimal(15, 4) 高精度小数存储本次跟工厂敲定的【实际下单进货价】
            if (!Schema::hasColumn('contract_items', 'purchase_price_snapshot')) {
                $table->decimal('purchase_price_snapshot', 15, 2)->nullable()->after('unit_price');
            }
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropColumn('purchase_price_snapshot');
        });
    }
};
