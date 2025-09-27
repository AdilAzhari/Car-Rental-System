<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add all enum values including temporary 'paid' value
        $allValues = array_merge(PaymentStatus::values(), ['paid']);
        DB::statement("ALTER TABLE car_rental_bookings MODIFY COLUMN payment_status ENUM('".implode("','", $allValues)."') DEFAULT 'unpaid'");

        // Then map old 'paid' values to new 'confirmed' values
        DB::table('car_rental_bookings')->where('payment_status', 'paid')->update(['payment_status' => 'confirmed']);

        // Finally, remove the temporary 'paid' value from enum
        DB::statement("ALTER TABLE car_rental_bookings MODIFY COLUMN payment_status ENUM('".implode("','", PaymentStatus::values())."') DEFAULT 'unpaid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert confirmed back to paid
        DB::table('car_rental_bookings')->where('payment_status', 'confirmed')->update(['payment_status' => 'paid']);

        // Revert to old enum values
        DB::statement("ALTER TABLE car_rental_bookings MODIFY COLUMN payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid'");
    }
};
