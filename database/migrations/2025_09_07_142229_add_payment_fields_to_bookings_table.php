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
        Schema::table('car_rental_bookings', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid');
            $table->enum('payment_method', ['stripe', 'visa', 'credit', 'credit_card', 'tng', 'touch_n_go', 'cash', 'bank_transfer'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_rental_bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_method']);
        });
    }
};
