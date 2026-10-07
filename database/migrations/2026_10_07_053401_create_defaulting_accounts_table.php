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
        Schema::create('defaulting_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_id', 64)->unique();
            $table->string('old_account_id', 64)->nullable();
            $table->string('name');
            $table->text('address')->nullable();
            $table->decimal('closing_balance', 14, 2);
            $table->string('category', 10)->nullable();
            $table->text('progress')->nullable();
            $table->decimal('paid_amount', 14, 2)->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defaulting_accounts');
    }
};
