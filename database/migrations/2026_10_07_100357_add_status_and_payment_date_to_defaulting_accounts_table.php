<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('defaulting_accounts', function (Blueprint $table) {
            Schema::table('defaulting_accounts', function (Blueprint $table) {
                $table->string('status', 24)->default('pending')->index();
                $table->date('payment_date')->nullable()->index();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defaulting_accounts', function (Blueprint $table) {
            Schema::table('defaulting_accounts', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropIndex(['payment_date']);
                $table->dropColumn(['status', 'payment_date']);
            });
        });
    }
};
