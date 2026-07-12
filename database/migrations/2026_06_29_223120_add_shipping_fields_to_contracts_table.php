<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // 🎯 核心补齐：海关标准的运输方式代码、启运港代码、以及目的港描述
            $table->string('transport_mode', 10)->default('2')->comment('海关标准运输方式：2代表水路运输');
            $table->string('port_of_loading', 50)->default('SHA')->comment('启运港代码/港口：如上海港');
            $table->string('port_of_destination', 100)->nullable()->comment('最终目的港口描述');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['transport_mode', 'port_of_loading', 'port_of_destination']);
        });
    }
};
