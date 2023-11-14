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
        //Se crea la tabla de categoria.
        Schema::create('categorias', function (Blueprint $table){
            $table->id();
            $table->string("nombre_categoria", 100)->unique();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //Si la tabla existe la eliminará.
        Schema::dropIfExists("categoria");
    }
};
