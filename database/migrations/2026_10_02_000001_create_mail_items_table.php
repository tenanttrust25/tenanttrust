<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_items', function (Blueprint $table) {
            $table->id();
            $table->string('source_type')->nullable();   // form_submission | n9_submission | letterhead | manual
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('property_unit_id')->nullable();
            $table->string('form_label')->nullable();    // N1, N4, N5, N9, N11, Letter ...
            $table->string('recipient_name');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country', 2)->default('CA');
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('draft');  // draft | sent | failed | cancelled
            $table->string('postgrid_id')->nullable();
            $table->string('postgrid_status')->nullable();
            $table->text('postgrid_url')->nullable();
            $table->string('mode')->nullable();          // test | live
            $table->decimal('cost', 8, 2)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_items');
    }
};
