<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "¿Algo no funcionó?": lo que la gente nos cuenta desde la app.
 *
 * Guarda la pantalla de origen porque en el piloto lo más valioso no es el
 * texto sino SABER DÓNDE se trabaron.
 *
 * Y la nota del plan, para que el admin apunte por qué le puso ese plan a
 * una empresa ("piloto gratis hasta diciembre", "paga desde enero").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            // De qué pantalla salió el comentario.
            $table->string('screen')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('plan_note')->nullable()->after('plan_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('plan_note');
        });
    }
};
