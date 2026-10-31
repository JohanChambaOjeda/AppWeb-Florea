<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reputación de cada empresa, precalculada.
 *
 * Va denormalizada a propósito: estos números salen en CADA tarjeta de las
 * listas del inicio, y calcularlos al vuelo sería una consulta por tarjeta.
 * Se recalculan con Company::recomputeReputation() cuando llega una
 * calificación o se cierra un trato, que pasa pocas veces al día.
 *
 * Y la tabla de recordatorios, para no pedir la calificación dos veces de
 * más: al día siguiente del trato y otra vez a los 3 días.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('ratings_count')->default(0)->after('plan_until');
            $table->decimal('ratings_avg', 3, 2)->nullable()->after('ratings_count');
            // Solo para vendedores: % de entregas a tiempo.
            $table->unsignedTinyInteger('on_time_pct')->nullable()->after('ratings_avg');
            $table->unsignedInteger('deals_count')->default(0)->after('on_time_pct');
        });

        Schema::create('rating_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sent_count')->default(0);
            $table->timestamp('last_sent_at')->nullable();

            $table->unique(['deal_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_reminders');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['ratings_count', 'ratings_avg', 'on_time_pct', 'deals_count']);
        });
    }
};
