<?php

use App\Enums\ListingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('kind', 20);
            $table->date('needed_by')->nullable();
            $table->string('destination', 20)->nullable();
            $table->string('cut_point', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default(ListingStatus::Open->value);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['status', 'kind', 'expires_at']);
        });

        Schema::create('listing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained();
            $table->json('lengths');
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_items');
        Schema::dropIfExists('listings');
    }
};
