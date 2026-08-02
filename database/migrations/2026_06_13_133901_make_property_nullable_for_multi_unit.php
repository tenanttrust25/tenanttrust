<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("properties", function (Blueprint $table) {
            $table->string("property_type")->nullable()->change();
            $table->decimal("price", 10, 2)->nullable()->change();
            $table->string("status")->default("active")->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table("properties", function (Blueprint $table) {
            $table->string("property_type")->nullable(false)->change();
            $table->decimal("price", 10, 2)->nullable(false)->change();
            $table->string("status")->nullable(false)->default("active")->change();
        });
    }
};
