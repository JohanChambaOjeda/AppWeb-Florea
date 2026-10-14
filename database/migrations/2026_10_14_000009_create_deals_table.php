<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->unique()->constrained();
            $table->foreignId('buyer_company_id')->constrained('companies');
            $table->foreignId('seller_company_id')->constrained('companies');
            $table->timestamp('closed_at');
            $table->string('delivered', 20)->nullable();
            $table->decimal('rejected_pct', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['buyer_company_id', 'seller_company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
