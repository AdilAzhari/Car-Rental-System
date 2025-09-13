<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ArabicTranslationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_bookings_page_displays_arabic_translations(): void
    {
        // Create a user with admin role
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/admin')
                ->pause(1000)

                    // First switch to Arabic
                ->clickAtXPath("//button[contains(@class, 'language-switcher')]")
                ->pause(500)

                    // Visit bookings page
                ->visit('/admin/bookings')
                ->pause(2000)

                    // Check table column headers are in Arabic
                ->assertSee('المعرف') // ID
                ->assertSee('العميل') // Customer
                ->assertSee('المركبة') // Vehicle
                ->assertSee('فترة الإيجار') // Rental Period
                ->assertSee('المبلغ') // Amount
                ->assertSee('الحالة') // Status
                ->assertSee('الدفع') // Payment

                    // Check filter labels are in Arabic
                ->assertSee('حالة الحجز') // Booking Status
                ->assertSee('حالة الدفع') // Payment Status
                ->assertSee('طريقة الدفع') // Payment Method

                    // Check action buttons are in Arabic
                ->assertSee('عرض') // View
                ->assertSee('تحرير') // Edit

                    // Take screenshot for verification
                ->screenshot('bookings-arabic');
        });
    }

    public function test_vehicles_page_displays_arabic_translations(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/admin')
                ->pause(1000)

                    // Switch to Arabic
                ->clickAtXPath("//button[contains(@class, 'language-switcher')]")
                ->pause(500)

                    // Visit vehicles page
                ->visit('/admin/vehicles')
                ->pause(2000)

                    // Check table column headers are in Arabic
                ->assertSee('الصورة') // Image
                ->assertSee('الماركة') // Make
                ->assertSee('الطراز') // Model
                ->assertSee('السنة') // Year
                ->assertSee('الرخصة') // License
                ->assertSee('الفئة') // Category
                ->assertSee('الحالة') // Status
                ->assertSee('متاح') // Available
                ->assertSee('السعر اليومي') // Daily Rate

                    // Check if create button is in Arabic
                ->assertSee('إنشاء') // Create

                    // Take screenshot for verification
                ->screenshot('vehicles-arabic');
        });
    }

    public function test_vehicle_form_displays_arabic_translations(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/admin')
                ->pause(1000)

                    // Switch to Arabic
                ->clickAtXPath("//button[contains(@class, 'language-switcher')]")
                ->pause(500)

                    // Visit vehicle creation form
                ->visit('/admin/vehicles/create')
                ->pause(3000)

                    // Check form section titles are in Arabic
                ->assertSee('معلومات المركبة') // Vehicle Information
                ->assertSee('فئات المركبات') // Vehicle Categories
                ->assertSee('الملكية والتسعير') // Ownership & Pricing
                ->assertSee('حالة المركبة والموقع') // Vehicle Status & Location

                    // Check form field labels are in Arabic
                ->assertSee('الماركة') // Make
                ->assertSee('الطراز') // Model
                ->assertSee('السنة') // Year
                ->assertSee('لوحة الترخيص') // License Plate
                ->assertSee('الفئة') // Category
                ->assertSee('ناقل الحركة') // Transmission
                ->assertSee('نوع الوقود') // Fuel Type

                    // Check form actions are in Arabic
                ->assertSee('حفظ') // Save
                ->assertSee('إلغاء') // Cancel

                    // Take screenshot for verification
                ->screenshot('vehicle-form-arabic');
        });
    }

    public function test_dashboard_widgets_display_arabic_translations(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/admin')
                ->pause(1000)

                    // Switch to Arabic
                ->clickAtXPath("//button[contains(@class, 'language-switcher')]")
                ->pause(500)
                ->refresh()
                ->pause(2000)

                    // Check dashboard widgets display Arabic text
                ->assertSee('إجمالي المستخدمين') // Total Users
                ->assertSee('إجمالي المركبات') // Total Vehicles
                ->assertSee('إجمالي الحجوزات') // Total Bookings
                ->assertSee('الإيرادات الشهرية') // Monthly Revenue

                    // Check if navigation is in Arabic
                ->assertSee('المستخدمون') // Users
                ->assertSee('المركبات') // Vehicles
                ->assertSee('الحجوزات') // Bookings

                    // Take screenshot for verification
                ->screenshot('dashboard-arabic');
        });
    }

    public function test_language_switch_between_english_and_arabic(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/admin/vehicles')
                ->pause(2000)

                    // Initially should be in English
                ->assertSee('Make')
                ->assertSee('Model')
                ->assertSee('Vehicle')
                ->assertSee('Status')

                    // Switch to Arabic
                ->clickAtXPath("//button[contains(@class, 'language-switcher')]")
                ->pause(1000)
                ->refresh()
                ->pause(2000)

                    // Should now be in Arabic
                ->assertSee('الماركة') // Make
                ->assertSee('الطراز') // Model
                ->assertSee('المركبة') // Vehicle
                ->assertSee('الحالة') // Status

                    // Switch back to English
                ->clickAtXPath("//button[contains(@class, 'language-switcher')]")
                ->pause(1000)
                ->refresh()
                ->pause(2000)

                    // Should be back to English
                ->assertSee('Make')
                ->assertSee('Model')
                ->assertSee('Vehicle')
                ->assertSee('Status')

                    // Take screenshot for verification
                ->screenshot('language-switch-test');
        });
    }

    public function test_enum_translations_work_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/admin')
                ->pause(1000)

                    // Switch to Arabic
                ->clickAtXPath("//button[contains(@class, 'language-switcher')]")
                ->pause(500)

                    // Visit vehicle creation form to check enum options
                ->visit('/admin/vehicles/create')
                ->pause(3000)

                    // Check fuel type options are in Arabic
                ->click('[data-field="fuel_type"]')
                ->pause(500)
                ->assertSee('بنزين') // Petrol
                ->assertSee('ديزل') // Diesel
                ->assertSee('هجين') // Hybrid
                ->assertSee('كهربائي') // Electric

                    // Check transmission options are in Arabic
                ->click('[data-field="transmission"]')
                ->pause(500)
                ->assertSee('يدوي') // Manual
                ->assertSee('أوتوماتيكي') // Automatic

                    // Check vehicle category options are in Arabic
                ->click('[data-field="category"]')
                ->pause(500)
                ->assertSee('اقتصادي') // Economy
                ->assertSee('صغير الحجم') // Compact
                ->assertSee('فاخر') // Luxury
                ->assertSee('دفع رباعي') // SUV

                    // Take screenshot for verification
                ->screenshot('enum-translations-arabic');
        });
    }
}
