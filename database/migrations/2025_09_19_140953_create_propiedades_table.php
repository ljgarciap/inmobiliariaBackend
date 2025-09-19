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
        Schema::create('propiedades', function (Blueprint $table) {
            $table->id();
            $table->string('detalle');
            $table->text('descripcion');
            $table->foreignId('ciudad_id')->constrained();
            $table->integer('habitaciones');
            $table->integer('banios');
            $table->enum('tipo_transaccion', ['renta', 'venta']);
            $table->decimal('precio_renta', 12, 2)->nullable();
            $table->decimal('precio_venta', 12, 2)->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propiedades');
    }
};
