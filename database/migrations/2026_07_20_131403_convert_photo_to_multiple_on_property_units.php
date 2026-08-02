<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, change the column type to longText to hold JSON arrays
        Schema::table('property_units', function (Blueprint $table) {
            $table->longText('photo')->nullable()->change();
        });

        // Convert existing single photo paths to JSON arrays
        DB::statement("UPDATE property_units SET photo = CONCAT('[\"', photo, '\"]') WHERE photo IS NOT NULL AND photo NOT LIKE '[%'");
    }

    public function down(): void
    {
        // Convert JSON arrays back to single strings (take first photo)
        DB::statement("UPDATE property_units SET photo = JSON_UNQUOTE(JSON_EXTRACT(photo, '$[0]')) WHERE photo IS NOT NULL AND photo LIKE '[%'");

        Schema::table('property_units', function (Blueprint $table) {
            $table->string('photo')->nullable()->change();
        });
    }
};
