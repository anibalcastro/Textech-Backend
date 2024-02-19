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
            $table->boolean('pizarra')->default(false);
            $table->boolean('tela')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_pedido', function (Blueprint $table) {
            //
            $table->dropColumn('pizarra');
            $table->dropColumn('tela');
        });
    }
};
