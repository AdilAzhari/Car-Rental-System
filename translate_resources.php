<?php

/**
 * Translation Replacement Script
 * This script helps replace hard-coded English strings with translation keys
 * Run with: php translate_resources.php
 */

// Define the replacements mapping
$replacements = [
    // Common labels
    "'Availability'" => "__('resources.availability')",
    "'Book Now'" => "__('resources.book_now')",
    "'Customer'" => "__('resources.customer')",
    "'Duration'" => "__('resources.duration')",
    "'Booked On'" => "__('resources.booked_on')",
    "'Renter'" => "__('resources.renter')",
    "'Vehicle'" => "__('resources.vehicle')",
    "'Days'" => "__('resources.days')",
    "'Created'" => "__('resources.created')",
    "'Payment Notes'" => "__('resources.payment_notes')",
    "'Update Status'" => "__('resources.update_status')",
    "'View Payments'" => "__('resources.view_payments')",
    "'Confirm Payment'" => "__('resources.confirm_payment')",
    "'Log ID'" => "__('resources.log_id')",
    "'Log Name'" => "__('resources.log_name')",
    "'Event Type'" => "__('resources.event_type')",
    "'Subject Type'" => "__('resources.subject_type')",
    "'Subject ID'" => "__('resources.subject_id')",
    "'User ID'" => "__('resources.user_id')",
    "'Timestamp'" => "__('resources.timestamp')",
    "'Properties'" => "__('resources.properties')",
    "'Featured Image'" => "__('resources.featured_image')",
    "'Gallery'" => "__('resources.gallery')",
    "'Make'" => "__('resources.make')",
    "'Model'" => "__('resources.model')",
    "'Year'" => "__('resources.year')",
    "'License Plate'" => "__('resources.license_plate')",
    "'VIN Number'" => "__('resources.vin_number')",
    "'Category'" => "__('resources.category')",
    "'Transmission'" => "__('resources.transmission')",
    "'Fuel Type'" => "__('resources.fuel_type')",
    "'Seats'" => "__('resources.seats')",
    "'Doors'" => "__('resources.doors')",
    "'Daily Rate'" => "__('resources.daily_rate')",
    "'Status'" => "__('resources.status')",
    "'Available for Rent'" => "__('resources.available_for_rent')",
    "'Insurance Included'" => "__('resources.insurance_included')",
    "'Current Location'" => "__('resources.current_location')",
    "'Pickup Location'" => "__('resources.pickup_location')",
    "'Vehicle Features'" => "__('resources.vehicle_features')",
    "'Description'" => "__('resources.description')",
    "'Add Vehicle'" => "__('resources.add_vehicle')",
    "'Booking ID'" => "__('resources.booking_id')",
    "'Payment Status'" => "__('resources.payment_status')",
    "'Total Amount'" => "__('resources.total_amount')",
    "'Renter Email'" => "__('resources.renter_email')",
    "'Booking Created'" => "__('resources.booking_created')",
    "'Last Updated'" => "__('resources.last_updated')",
    "'Deposit'" => "__('resources.deposit')",
    "'Commission'" => "__('resources.commission')",
    "'Payment Method'" => "__('resources.payment_method')",
    "'Drop off Location'" => "__('resources.drop_off_location')",
    "'Special Requests'" => "__('resources.special_requests')",
    "'Edit Booking'" => "__('resources.edit_booking')",
    "'View Payment'" => "__('resources.view_payment')",
    "'Publish'" => "__('resources.publish')",
    "'Mark for Maintenance'" => "__('resources.mark_for_maintenance')",
    "'Archive'" => "__('resources.archive')",
    "'Start Date'" => "__('resources.start_date')",
    "'End Date'" => "__('resources.end_date')",
    "'Number of Days'" => "__('resources.number_of_days')",
    "'Subtotal'" => "__('resources.subtotal')",
    "'Insurance Fee'" => "__('resources.insurance_fee')",
    "'Tax Amount'" => "__('resources.tax_amount')",
    "'Booking Status'" => "__('resources.booking_status')",
    "'Rating'" => "__('resources.rating')",
    "'Review'" => "__('resources.review')",
    "'Amount'" => "__('resources.amount')",
    "'Transaction ID'" => "__('resources.transaction_id')",
    "'Processed At'" => "__('resources.processed_at')",
    "'Refund Amount'" => "__('resources.refund_amount')",
    "'Refunded At'" => "__('resources.refunded_at')",
    "'Gateway Response'" => "__('resources.gateway_response')",
    "'Image'" => "__('resources.image')",
    "'Bookings'" => "__('resources.bookings')",
    "'Added'" => "__('resources.added')",
    "'Review ID'" => "__('resources.review_id')",
    "'Overall Rating'" => "__('resources.overall_rating')",
    "'Recommends'" => "__('resources.recommends')",
    "'Customer Name'" => "__('resources.customer_name')",
    "'Customer Email'" => "__('resources.customer_email')",
    "'Submitted On'" => "__('resources.submitted_on')",
    "'Review Text'" => "__('resources.review_text')",
    "'Visibility'" => "__('resources.visibility')",
    "'Vehicle Make Model'" => "__('resources.vehicle_make_model')",
    "'Show Review'" => "__('resources.show_review')",
    "'Payment ID'" => "__('resources.payment_id')",

    // Dropdown options
    "'Economy'" => "__('resources.economy')",
    "'Compact'" => "__('resources.compact')",
    "'Midsize'" => "__('resources.midsize')",
    "'Luxury'" => "__('resources.luxury')",
    "'SUV'" => "__('resources.suv')",
    "'Automatic'" => "__('resources.automatic')",
    "'Manual'" => "__('resources.manual')",
    "'CVT'" => "__('resources.cvt')",
    "'Available'" => "__('resources.available')",
    "'Not Available'" => "__('resources.not_available')",
    "'Under Maintenance'" => "__('resources.under_maintenance')",
    "'Published'" => "__('resources.published')",
    "'Draft'" => "__('resources.draft')",
    "'Archived'" => "__('resources.archived')",
];

// Files to process
$filesToProcess = [
    'app/Filament/Resources/VehicleResource/Pages/ViewVehicle.php',
    'app/Filament/Resources/VehicleResource/Pages/ListVehicles.php',
    'app/Filament/Resources/VehicleResource/Pages/EditVehicle.php',
    'app/Filament/Resources/VehicleResource/RelationManagers/BookingsRelationManager.php',
    'app/Filament/Resources/Bookings/Tables/BookingsTable.php',
    'app/Filament/Resources/Bookings/Schemas/BookingForm.php',
    'app/Filament/Resources/Bookings/Schemas/BookingInfolist.php',
    'app/Filament/Resources/Bookings/RelationManagers/ReviewRelationManager.php',
    'app/Filament/Resources/Bookings/RelationManagers/PaymentRelationManager.php',
    'app/Filament/Resources/UserResource/RelationManagers/VehiclesRelationManager.php',
    'app/Filament/Resources/UserResource/RelationManagers/BookingsRelationManager.php',
    'app/Filament/Resources/ReviewResource/Pages/ViewReview.php',
    'app/Filament/Resources/ActivityLogResource/Pages/ViewActivityLog.php',
    'app/Filament/Resources/Payments/Tables/PaymentsTable.php',
];

function replaceInFile($filePath, $replacements)
{
    if (! file_exists($filePath)) {
        echo "File not found: $filePath\n";

        return false;
    }

    $content = file_get_contents($filePath);
    $originalContent = $content;

    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Updated: $filePath\n";

        return true;
    }

    return false;
}

echo "Starting translation replacement...\n\n";

$updatedFiles = 0;
foreach ($filesToProcess as $file) {
    if (replaceInFile($file, $replacements)) {
        $updatedFiles++;
    }
}

echo "\nCompleted! Updated $updatedFiles files.\n";
echo "Don't forget to clear your application cache: php artisan cache:clear\n";
echo "Test the translations by switching to Arabic in your admin panel.\n";
