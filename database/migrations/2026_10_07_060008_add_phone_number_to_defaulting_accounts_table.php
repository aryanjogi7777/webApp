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
            $table->string('phone_number', 32)->nullable()->after('address')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defaulting_accounts', function (Blueprint $table) {
            $table->dropIndex(['phone_number']);
            $table->dropColumn('phone_number');
        });
    }
};
