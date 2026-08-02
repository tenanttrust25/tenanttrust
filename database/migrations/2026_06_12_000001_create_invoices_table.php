<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("invoices", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained("users")->cascadeOnDelete();
            $table->foreignId("property_id")->nullable()->constrained("properties")->nullOnDelete();
            $table->string("invoice_number")->unique();
            $table->decimal("amount", 10, 2);
            $table->text("description")->nullable();
            $table->date("due_date");
            $table->string("status")->default("pending"); // pending, paid, overdue, cancelled
            $table->string("file_path")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("invoices");
    }
};
