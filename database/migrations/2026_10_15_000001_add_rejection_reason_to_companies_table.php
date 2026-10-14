<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rechazar una empresa = soft delete + motivo opcional.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('rejection_reason', 500)->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
