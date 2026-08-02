<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("tenancies", function (Blueprint $table) {
            $table->id();
            $table->foreignId("property_id")->constrained()->cascadeOnDelete();
            $table->foreignId("tenant_id")->constrained("users")->cascadeOnDelete();
            $table->date("lease_start");
            $table->date("lease_end")->nullable();
            $table->decimal("rent_amount", 10, 2);
            $table->decimal("security_deposit", 10, 2)->default(0);
            $table->string("status")->default("active");
            $table->date("notice_given_at")->nullable();
            $table->date("move_out_date")->nullable();
            $table->text("notes")->nullable();
            $table->timestamps();

            $table->unique(["property_id", "tenant_id", "lease_start"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("tenancies");
    }
};
