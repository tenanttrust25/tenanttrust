<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("property_units", function (Blueprint $table) {
            $table->id();
            $table->foreignId("property_id")->constrained()->cascadeOnDelete();
            $table->string("unit")->nullable();
            $table->integer("bedrooms")->default(0);
            $table->integer("bathrooms")->default(0);
            $table->integer("square_feet")->nullable();
            $table->decimal("rent_amount", 10, 2)->default(0);
            $table->string("status")->default("vacant");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("property_units");
    }
};
