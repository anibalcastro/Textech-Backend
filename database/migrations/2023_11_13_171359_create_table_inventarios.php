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
        Schema::create('inventario', function (Blueprint $table){
            $table->id();
            $table->string('nombre_producto',100);
            $table->decimal('cantidad',65, 2);
            $table->string('color',20)->nullable();
            $table->unsignedBigInteger('id_categoria')->nullable();
            $table->unsignedBigInteger('id_proveedor')->nullable();
            $table->longText('comentario')->nullable();
            $table->timestamps();

            $table->foreign('id_categoria')->references('id')->on('categorias');
            $table->foreign('id_proveedor')->references('id')->on('proveedores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("inventario");
    }
};
