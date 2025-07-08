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
        Schema::table('mediciones', function (Blueprint $table) {
            $table->decimal('largo_costado_superior', 8, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            $table->dropColumn([
                'largo_costado_superior'
            ]);
        });
    }
};
