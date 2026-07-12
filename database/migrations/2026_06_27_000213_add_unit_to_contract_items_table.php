<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            // 🎯 核心补齐：在数量字段前面，追加 20 位长度的单位快照字段
            $table->string('unit', 20)->nullable()->after('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
