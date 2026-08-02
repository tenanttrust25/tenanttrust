<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("tenant_files", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained("users")->cascadeOnDelete();
            $table->foreignId("uploaded_by")->constrained("users")->cascadeOnDelete();
            $table->string("name");
            $table->text("description")->nullable();
            $table->string("file_path");
            $table->string("file_type")->default("other"); // lease, N1, N4, other
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("tenant_files");
    }
};
