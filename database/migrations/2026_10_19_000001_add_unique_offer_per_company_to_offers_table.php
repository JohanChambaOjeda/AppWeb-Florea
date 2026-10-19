<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una oferta por empresa y publicación (regla 3).
 *
 * Nunca hay dos filas para el mismo par: retirar una oferta la deja en estado
 * "withdrawn" y volver a ofertar reutiliza esa misma fila. Así el índice único
 * vale como red de seguridad contra un doble envío, sin necesidad de un índice
 * parcial (MySQL 8 no los tiene).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->unique(['listing_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropUnique(['listing_id', 'company_id']);
        });
    }
};
