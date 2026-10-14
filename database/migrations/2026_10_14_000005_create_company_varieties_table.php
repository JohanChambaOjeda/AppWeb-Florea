<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_varieties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained();
            $table->json('lengths');
            $table->timestamps();

            $table->unique(['company_id', 'variety_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_varieties');
    }
};
