<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_company_id')->constrained('companies');
            $table->foreignId('to_company_id')->constrained('companies');
            $table->unsignedTinyInteger('score');
            $table->boolean('on_time')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['deal_id', 'from_company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
