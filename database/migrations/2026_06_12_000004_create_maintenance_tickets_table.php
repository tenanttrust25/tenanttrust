<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("maintenance_tickets", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained("users")->cascadeOnDelete();
            $table->foreignId("property_id")->constrained("properties")->cascadeOnDelete();
            $table->string("ticket_number")->unique();
            $table->string("title");
            $table->text("description");
            $table->string("priority")->default("medium"); // low, medium, high, emergency
            $table->string("status")->default("open"); // open, in_progress, resolved, closed
            $table->json("photos")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("maintenance_tickets");
    }
};
