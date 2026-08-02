<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->after('password')
                ->comment('Stripe Connect account ID for receiving payouts');
            $table->boolean('stripe_onboarding_complete')->default(false)->after('stripe_account_id');
            $table->timestamp('stripe_account_created_at')->nullable()->after('stripe_onboarding_complete');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['stripe_account_id', 'stripe_onboarding_complete', 'stripe_account_created_at']);
        });
    }
};
