<?php

use App\Enums\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('name');
            $table->string('tax_id', 13)->index();
            $table->foreignId('zone_id')->constrained();
            $table->string('address_reference')->nullable();
            $table->string('id_document_path')->nullable();
            $table->timestamp('verified_at')->nullable()->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plan', 20)->default(Plan::Pilot->value);
            $table->date('plan_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
