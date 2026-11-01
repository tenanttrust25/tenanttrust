<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_unit_id')->nullable()->constrained('property_units')->nullOnDelete();
            $table->string('type')->default('other');
            $table->string('title');
            $table->date('due_date');
            $table->date('completed_at')->nullable();
            $table->string('recurrence')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['completed_at', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_items');
    }
};
