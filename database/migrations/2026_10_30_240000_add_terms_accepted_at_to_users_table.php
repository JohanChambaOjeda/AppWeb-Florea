<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuándo aceptó cada persona los términos y la política de privacidad.
 *
 * La Ley Orgánica de Protección de Datos Personales del Ecuador pide poder
 * demostrar el consentimiento, así que no basta con un checkbox en la
 * pantalla: hay que guardar la fecha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('push_window_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });
    }
};
