<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_items', function (Blueprint $table) {
            // true  = letter leaves the top address zone blank -> PostGrid prints
            //         the addresses there (1 sheet).
            // false = addresses print on their own page (2 sheets), safe for any PDF.
            $table->boolean('single_sheet')->default(false)->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('mail_items', function (Blueprint $table) {
            $table->dropColumn('single_sheet');
        });
    }
};
