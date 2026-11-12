<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El plazo de pago acordado y las calificaciones en las dos direcciones.
 *
 * Todo nullable: hay tratos cerrados antes de esta migración que no tienen
 * plazo acordado, y calificaciones que solo iban en un sentido. Ninguna
 * tiene por qué desaparecer del historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Solo cuando quien oferta es quien paga, o sea la poscosecha
            // ofertando en un "Disponible". En un "Busco" lo pone la
            // poscosecha al aceptar, y va directo al trato.
            $table->string('payment_term', 20)->nullable()->after('delivery');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->string('payment_term', 20)->nullable()->after('closed_at');
        });

        Schema::table('ratings', function (Blueprint $table) {
            // Las dos preguntas del productor a la poscosecha.
            $table->string('paid_as_agreed', 10)->nullable()->after('on_time');
            $table->boolean('honored_agreement')->nullable()->after('paid_as_agreed');
        });

        Schema::table('companies', function (Blueprint $table) {
            // El espejo de on_time_pct para la poscosecha: qué parte de sus
            // pagos llegaron como se habían acordado.
            $table->unsignedTinyInteger('paid_pct')->nullable()->after('on_time_pct');
        });
    }

    public function down(): void
    {
        Schema::table('offers', fn (Blueprint $table) => $table->dropColumn('payment_term'));
        Schema::table('deals', fn (Blueprint $table) => $table->dropColumn('payment_term'));
        Schema::table('ratings', fn (Blueprint $table) => $table->dropColumn(['paid_as_agreed', 'honored_agreement']));
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('paid_pct'));
    }
};
