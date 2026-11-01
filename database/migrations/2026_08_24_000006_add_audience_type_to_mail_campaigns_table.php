<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_campaigns', function (Blueprint $table) {
            $table->string('audience_type')->default('tenants')->after('audience_filter');
        });
    }

    public function down(): void
    {
        Schema::table('mail_campaigns', function (Blueprint $table) {
            $table->dropColumn('audience_type');
        });
    }
};
