<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("maintenance_tickets", function (Blueprint $table) {
            $table->timestamp("resolved_at")->nullable()->after("photos");
            $table->decimal("cost", 10, 2)->nullable()->after("resolved_at");
            $table->foreignId("assigned_to")->nullable()->after("property_id")->constrained("users")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table("maintenance_tickets", function (Blueprint $table) {
            $table->dropColumn(["resolved_at", "cost", "assigned_to"]);
        });
    }
};
