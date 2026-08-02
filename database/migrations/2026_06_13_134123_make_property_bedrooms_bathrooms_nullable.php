<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("properties", function (Blueprint $table) {
            $table->integer("bedrooms")->nullable()->default(0)->change();
            $table->integer("bathrooms")->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table("properties", function (Blueprint $table) {
            $table->integer("bedrooms")->nullable(false)->change();
            $table->integer("bathrooms")->nullable(false)->change();
        });
    }
};
