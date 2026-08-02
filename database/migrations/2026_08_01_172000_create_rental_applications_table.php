<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_applications', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email');
            $table->string('address_applying_for');
            $table->unsignedBigInteger('property_unit_id')->nullable();
            $table->text('rental_history')->nullable();
            $table->boolean('has_pets')->default(false);
            $table->string('pets_details')->nullable();
            $table->boolean('is_smoker')->default(false);
            $table->boolean('consent_credit_check')->default(false);
            $table->boolean('consent_background_check')->default(false);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('property_unit_id')
                ->references('id')
                ->on('property_units')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_applications');
    }
};
