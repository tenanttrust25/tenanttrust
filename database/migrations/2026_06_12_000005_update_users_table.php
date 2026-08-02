<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->string("phone")->nullable()->after("role");
            $table->string("address")->nullable()->after("phone");
            $table->text("mailing_address")->nullable()->after("address");
        });
    }

    public function down(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->dropColumn(["phone", "address", "mailing_address"]);
        });
    }
};
