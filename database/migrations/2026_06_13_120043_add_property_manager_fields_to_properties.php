<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("properties", function (Blueprint $table) {
            $table->foreignId("owner_id")->nullable()->after("id")->constrained("users")->nullOnDelete();
            $table->integer("unit_count")->default(1)->after("square_feet");
            $table->json("tags")->nullable()->after("unit_count");
            $table->string("property_subtype")->nullable()->after("property_type");
            $table->year("year_built")->nullable()->after("square_feet");
            $table->decimal("latitude", 10, 7)->nullable()->after("photo");
            $table->decimal("longitude", 10, 7)->nullable()->after("latitude");
            $table->decimal("market_value", 12, 2)->nullable()->after("price");
        });
    }

    public function down(): void
    {
        Schema::table("properties", function (Blueprint $table) {
            $table->dropColumn(["owner_id", "unit_count", "tags", "property_subtype", "year_built", "latitude", "longitude", "market_value"]);
        });
    }
};
