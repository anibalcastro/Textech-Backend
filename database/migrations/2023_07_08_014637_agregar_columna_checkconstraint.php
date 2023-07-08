<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AgregarColumnaCheckConstraint extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            $table->string('sastre')->nullable();
        });

        // Realizar la verificación manualmente
        $mediciones = DB::table('mediciones')
            ->select('id_cliente', 'articulo')
            ->groupBy('id_cliente', 'articulo')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($mediciones as $medicion) {
            $duplicados = DB::table('mediciones')
                ->where('id_cliente', $medicion->id_cliente)
                ->where('articulo', $medicion->articulo)
                ->orderBy('id', 'asc')
                ->skip(1)
                ->pluck('id');

            DB::table('mediciones')
                ->whereIn('id', $duplicados)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mediciones', function (Blueprint $table) {
            $table->dropColumn('sastre');
        });
    }
}
