<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("payments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("invoice_id")->constrained("invoices")->cascadeOnDelete();
            $table->foreignId("tenant_id")->constrained("users")->cascadeOnDelete();
            $table->decimal("amount", 10, 2);
            $table->string("payment_method"); // interac, credit
            $table->string("transaction_id")->nullable();
            $table->string("status")->default("pending"); // pending, completed, failed
            $table->timestamp("paid_at")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("payments");
    }
};
