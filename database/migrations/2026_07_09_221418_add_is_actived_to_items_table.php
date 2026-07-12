<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // 🎯 核心补齐：默认为 true (启用/上架)，设为 false 代表逻辑下架/封存
            if (!Schema::hasColumn('items', 'is_actived')) {
                $table->boolean('is_actived')->default(true)->after('image_path')->comment('商品激活状态');
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('is_actived');
        });
    }
};
