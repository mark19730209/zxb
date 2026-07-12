<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // 🎯 核心补齐：大货具体款式 SKU 的物理影像存根路径
            if (!Schema::hasColumn('items', 'image_path')) {
                $table->string('image_path', 500)->nullable()->after('purchase_price')->comment('款式图片物理托管路径');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
