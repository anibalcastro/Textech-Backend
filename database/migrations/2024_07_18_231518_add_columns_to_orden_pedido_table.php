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
        Schema::table('orden_pedido', function (Blueprint $table) {
            //
            $table->integer('proforma2')->default(0);
            $table->integer('proforma3')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_pedido', function (Blueprint $table) {
            //
            $table->dropColumn('proforma2');
            $table->dropColumn('proforma3');
        });
    }
};
