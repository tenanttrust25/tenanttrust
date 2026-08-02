<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("tenancies", function (Blueprint $table) {
            $table->foreignId("property_unit_id")->nullable()->after("property_id")->constrained("property_units")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table("tenancies", function (Blueprint $table) {
            $table->dropColumn("property_unit_id");
        });
    }
};
