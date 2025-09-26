# Twilio SMS Integration

This Laravel application now includes Twilio SMS functionality for sending notifications and messages.

## Setup

### 1. Twilio Account Setup

1. Create a Twilio account at https://www.twilio.com/
2. Get your Account SID and Auth Token from the Twilio Console
3. Purchase a Twilio phone number or use the trial number

### 2. Environment Configuration

Add these variables to your `.env` file:

```env
SMS_ENABLED=true

TWILIO_ACCOUNT_SID=your_twilio_account_sid_here
TWILIO_AUTH_TOKEN=your_twilio_auth_token_here
TWILIO_FROM_NUMBER=+1234567890
```

## Usage

### Sending SMS via Notifications

```php
use App\Notifications\BookingConfirmedSms;
use App\Notifications\SmsNotification;

// Send booking confirmation
$user->notify(new BookingConfirmedSms($booking));

// Send custom SMS
$user->notify(new SmsNotification('Your custom message'));
```

### Direct SMS Service Usage

```php
use App\Services\SmsService;

$smsService = app(SmsService::class);

// Send SMS
$result = $smsService->sendSms('+1234567890', 'Hello from CarRent!');

// Validate phone number
$validation = $smsService->validatePhoneNumber('+1234567890');

// Check message status
$status = $smsService->getMessageStatus('message_sid');
```

### Available Commands

```bash
# Test SMS functionality
php artisan twilio:test-sms +1234567890 --message="Test message"

# Send booking reminders
php artisan bookings:send-reminders --dry-run
php artisan bookings:send-reminders
```

## Available Notification Classes

- `SmsNotification` - Generic SMS notification
- `BookingConfirmedSms` - Booking confirmation messages
- `BookingReminderSms` - Booking reminder messages

## Service Methods

### SmsService

- `sendSms(string $toNumber, string $message): array` - Send SMS
- `validatePhoneNumber(string $phoneNumber): array` - Validate phone number
- `getMessageStatus(string $messageSid): array` - Check message status
- `sendBookingNotification(...)` - Send booking confirmation
- `sendBookingReminder(...)` - Send booking reminder
- `sendBookingCancellation(...)` - Send cancellation notice
- `sendTrafficCheck(...)` - Send traffic violation check

## Phone Number Format

Use E.164 format for phone numbers:
- US: +1234567890
- UK: +441234567890
- Malaysia: +60123456789

## Testing

1. Configure your Twilio credentials in `.env`
2. Run the test command: `php artisan twilio:test-sms +1234567890`
3. Check the logs for detailed information

## Logging

All SMS activities are logged to Laravel's log system with the following information:
- Success/failure status
- Message SIDs
- Error codes and messages
- Phone numbers (to/from)

Check `storage/logs/laravel.log` for SMS-related logs.

## Error Handling

The service includes comprehensive error handling for:
- Invalid Twilio credentials
- Invalid phone numbers
- Network connectivity issues
- Twilio API errors
- Rate limiting

## Cost Considerations

- Each SMS sent through Twilio incurs a cost
- Use the `--dry-run` flag for testing commands
- Monitor usage through the Twilio Console
- Consider implementing rate limiting for production use