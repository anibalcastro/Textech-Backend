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
            //
            $table->decimal('altura_cadera_inferior', 10,2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            //
            $table->dropColumn('altura_cadera_inferior');
        });
    }
};
