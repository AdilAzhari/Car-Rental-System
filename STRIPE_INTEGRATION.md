# Stripe Payment Integration

This document describes the Stripe payment integration for the Car Rental System using Laravel Cashier.

## Overview

The system uses Stripe for secure payment processing with support for:
- **Credit/Debit Cards** via Stripe Payment Intents
- **Alternative Payment Methods** (Touch 'n Go, Cash on Pickup, Bank Transfer)
- **Webhook Integration** for real-time payment status updates

## Architecture

### Backend Components

#### 1. StripeController (`app/Http/Controllers/Api/StripeController.php`)

Main controller handling all Stripe-related operations:

**Methods:**
- `createPaymentIntent()` - Creates a Stripe Payment Intent for a booking
- `confirmPayment()` - Confirms payment success and updates booking status
- `processPayment()` - Handles alternative payment methods (TNG, Cash, Bank Transfer)
- `paymentReturn()` - Processes payment callbacks/returns
- `getPublishableKey()` - Returns Stripe publishable key for frontend
- `webhook()` - Handles Stripe webhook events

**Features:**
- Automatic payment status tracking
- Duplicate payment prevention
- Authorization checks (user must own the booking)
- Transaction logging
- Database transaction safety

#### 2. API Routes (`routes/api.php`)

**Public Routes:**
```php
GET  /api/stripe/publishable-key          // Get Stripe public key
POST /api/webhooks/stripe                 // Stripe webhook endpoint
```

**Protected Routes (requires auth):**
```php
POST /api/stripe/payment-intent           // Create payment intent
POST /api/stripe/confirm-payment          // Confirm payment
POST /api/stripe/process-payment          // Process alternative payments
GET  /api/stripe/payment-return/{booking} // Handle payment returns
```

### Frontend Components

#### PaymentCheckout.vue

**Key Features:**
- Automatic Stripe.js loading
- Secure payment form with Stripe Elements
- Real-time card validation
- Error handling and display
- Support for multiple payment methods

**Payment Flow:**
1. User selects payment method
2. Frontend creates payment intent via API
3. Stripe.js securely collects card details
4. Payment is confirmed with Stripe
5. Backend confirms payment and updates booking
6. User is redirected to booking confirmation

## Configuration

### Environment Variables

```env
# Stripe Keys
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx

# Currency
APP_CURRENCY=MYR  # or USD, EUR, etc.
```

### Cashier Configuration

Published to `config/cashier.php`. Key settings:
- API keys from environment
- Webhook endpoint configuration
- Currency settings

## Payment Methods

### 1. Stripe Card Payments

**Process:**
1. Customer enters card details in secure Stripe Element
2. PaymentIntent created on backend
3. Card charged via Stripe
4. Payment confirmed
5. Booking status updated to "confirmed"

**Status Flow:**
- `pending` → `processing` → `completed`

### 2. Touch 'n Go (TNG)

**Process:**
1. Customer selects TNG
2. Backend creates payment record
3. Redirect to TNG payment page
4. Callback updates payment status

### 3. Cash on Pickup

**Process:**
1. Customer selects cash
2. Payment record created with `pending` status
3. Admin confirms payment on vehicle pickup
4. Status updated to `completed`

### 4. Bank Transfer

**Process:**
1. Customer selects bank transfer
2. Payment record created with `processing` status
3. Instructions sent to customer
4. Admin confirms payment manually

## Webhook Setup

### Stripe Dashboard Configuration

1. Go to Stripe Dashboard → Developers → Webhooks
2. Add endpoint: `https://yourdomain.com/api/webhooks/stripe`
3. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
4. Copy webhook secret to `.env`

### Handled Events

**payment_intent.succeeded:**
- Updates payment status to `completed`
- Updates booking status to `confirmed`
- Sends confirmation email (if configured)

**payment_intent.payment_failed:**
- Updates payment status to `failed`
- Logs failure reason
- Customer can retry payment

## Security Features

1. **Authorization**: Users can only pay for their own bookings
2. **Duplicate Prevention**: Checks for existing payments
3. **CSRF Protection**: All POST requests require CSRF token
4. **Rate Limiting**: Strict limits on payment endpoints (10/minute)
5. **Webhook Verification**: Signature verification for webhooks
6. **Database Transactions**: Ensures data consistency

## Testing

### Test Cards (Stripe Test Mode)

**Successful Payment:**
```
Card: 4242 4242 4242 4242
Expiry: Any future date
CVC: Any 3 digits
```

**Payment Declined:**
```
Card: 4000 0000 0000 0002
Expiry: Any future date
CVC: Any 3 digits
```

**Requires Authentication:**
```
Card: 4000 0025 0000 3155
Expiry: Any future date
CVC: Any 3 digits
```

### Testing Webhooks Locally

Use Stripe CLI:
```bash
stripe listen --forward-to localhost:8000/api/webhooks/stripe
stripe trigger payment_intent.succeeded
```

## Database Schema

### Payments Table

```sql
- booking_id: Foreign key to bookings
- amount: Payment amount
- payment_method: stripe, tng, cash, bank_transfer
- payment_status: pending, processing, completed, failed
- transaction_id: Stripe PaymentIntent ID or custom ID
- payment_date: Timestamp
- payment_details: JSON with additional data
```

## Error Handling

All endpoints return consistent JSON responses:

**Success:**
```json
{
  "success": true,
  "message": "Payment confirmed successfully",
  "payment": {...},
  "booking": {...}
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error description"
}
```

## Production Checklist

- [ ] Update Stripe keys to live keys
- [ ] Configure webhook URL in Stripe Dashboard
- [ ] Test all payment methods in production
- [ ] Enable SSL/HTTPS
- [ ] Configure email notifications
- [ ] Set up monitoring and alerts
- [ ] Review rate limiting settings
- [ ] Test webhook delivery
- [ ] Configure proper error logging
- [ ] Set up payment reconciliation process

## Support & Troubleshooting

### Common Issues

**1. "Failed to create payment intent"**
- Check Stripe API keys are correct
- Verify booking exists and user has permission
- Check Laravel logs for detailed error

**2. "Payment succeeded but booking not confirmed"**
- Check webhook is properly configured
- Verify webhook secret is correct
- Check webhook delivery in Stripe Dashboard

**3. "Card declined"**
- Verify sufficient funds (or use test cards in test mode)
- Check card details are entered correctly
- Review Stripe Dashboard for decline reason

### Logs

Payment errors are logged to:
- Laravel logs: `storage/logs/laravel.log`
- Stripe Dashboard: Dashboard → Developers → Logs

## API Documentation

For detailed API documentation, see the inline PHPDoc comments in `StripeController.php`.

## Support

For issues or questions:
- Check Laravel logs
- Review Stripe Dashboard
- Contact development team
