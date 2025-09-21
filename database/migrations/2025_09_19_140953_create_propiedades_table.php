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
            $table->foreignId('ciudad_id')
                ->constrained('ciudades')
                ->onDelete('restrict');
            $table->integer('habitaciones');
            $table->integer('banios');
            $table->enum('tipo_transaccion', ['arriendo', 'venta']);
            $table->decimal('precio_arriendo', 12, 2)->nullable();
            $table->decimal('precio_venta', 12, 2)->nullable();
            $table->decimal('latitud', 10, 8)->nullable()->after('precio_venta');
            $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
            $table->string('direccion_completa')->nullable()->after('longitud');
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
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
