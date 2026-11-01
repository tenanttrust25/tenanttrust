<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('n9_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->string('landlord_name')->nullable();
            $table->string('tenant_names')->nullable();
            $table->string('unit_address')->nullable();
            $table->date('termination_date');
            $table->string('phone')->nullable();
            $table->string('signature')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('n9_submissions');
    }
};
