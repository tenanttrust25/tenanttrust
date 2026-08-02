<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('rent_amount', 10, 2)->comment('Total rent collected');
            $table->decimal('management_fee', 10, 2)->comment('Fee deducted');
            $table->decimal('net_amount', 10, 2)->comment('Amount due to owner (rent - fee)');
            $table->string('management_fee_type')->comment('percentage or flat');
            $table->decimal('management_fee_rate', 10, 2)->comment('The rate that was applied');
            $table->string('status')->default('pending')
                ->comment('pending, paid, failed, cancelled');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
