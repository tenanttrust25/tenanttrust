<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_log_entries', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('caller_name')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('call_log_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
