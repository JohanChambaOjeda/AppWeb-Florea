<?php

use App\Enums\OfferStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->decimal('price_per_stem', 6, 2)->nullable();
            $table->string('delivery', 20);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default(OfferStatus::Sent->value)->index();
            $table->timestamps();
        });

        Schema::create('offer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 8, 2);
            $table->string('unit', 20);
            $table->unsignedSmallInteger('length')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_items');
        Schema::dropIfExists('offers');
    }
};
