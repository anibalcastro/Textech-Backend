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
        Schema::table('facturas', function (Blueprint $table) {
            $table->unsignedBigInteger('reparacion_id')->nullable();

            $table->foreign('reparacion_id')->references('id')->on('reparacion_prendas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('facturas', function (Blueprint $table){
            $table->dropColumn('reparacion_id');
        });
    }
};
