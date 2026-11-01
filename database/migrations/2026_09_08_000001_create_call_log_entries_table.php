<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_log_entries', function (Blueprint $table) {
            $table->id();
            $table->string('caller_name', 255);
            $table->string('address', 255)->nullable();
            $table->string('phone', 32)->nullable();
            $table->dateTime('called_at');
            $table->text('description');
            $table->timestamps();

            $table->index('called_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_log_entries');
    }
};
