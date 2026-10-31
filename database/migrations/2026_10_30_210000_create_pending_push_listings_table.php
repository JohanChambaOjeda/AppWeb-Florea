<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Para no llenar de avisos a los productores (regla anti-spam).
 *
 * El primer "Busco" que coincide sale al instante. Si llegan más durante los
 * siguientes minutos, se guardan aquí y el comando push:digest los junta en
 * un solo aviso: "Hay 4 pedidos nuevos con tus variedades".
 *
 * El índice único es lo que garantiza "un push por publicación": aunque el
 * job corra dos veces, la segunda no inserta nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_push_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at');

            $table->unique(['user_id', 'listing_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Hasta cuándo se agrupan los avisos de este productor. Null =
            // no hay ventana abierta, así que el próximo sale al instante.
            $table->timestamp('push_window_until')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_push_listings');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('push_window_until');
        });
    }
};
