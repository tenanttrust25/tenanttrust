<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('call_sid', 64)->unique();
            $table->string('from_number', 32)->nullable();
            $table->string('to_number', 32)->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('state', 32)->default('greet');
            $table->string('intent', 32)->nullable();
            $table->string('tenant_name', 255)->nullable();
            $table->string('tenant_address', 255)->nullable();
            $table->string('reference', 255)->nullable();
            $table->text('issue')->nullable();
            $table->json('transcript')->nullable();
            $table->json('data')->nullable();
            $table->string('status', 32)->default('in_progress');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
