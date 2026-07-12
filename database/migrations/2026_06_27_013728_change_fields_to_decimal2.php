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
            $table->decimal('purchase_price', 15, 2)->change(); // 默认含税进货价
        });
        Schema::table('contract_items', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->change();      // 外币外销单价
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    //
    }
};
