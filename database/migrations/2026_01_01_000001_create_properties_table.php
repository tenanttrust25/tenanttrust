<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('address');
            $table->string('city');
            $table->string('province');
            $table->string('postal_code');
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->integer('square_feet')->nullable();
            $table->string('property_type'); // for-rent, for-sale
            $table->string('status')->default('active'); // active, rented, sold
            $table->boolean('featured')->default(false);
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
