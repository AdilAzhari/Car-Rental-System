# Stripe Payment Integration Setup

## Development Setup

### 1. Get Stripe Test Keys
1. Create a Stripe account at https://stripe.com
2. Go to Developers > API Keys
3. Get your test keys (starts with `pk_test_` and `sk_test_`)

### 2. Environment Configuration
Add these to your `.env` file:

```bash
# Stripe Payment Gateway (Development)
STRIPE_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET=sk_test_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

### 3. Install Dependencies
Make sure Stripe PHP SDK is installed:
```bash
composer require stripe/stripe-php
```

### 4. Test Payment Flow

#### 4.1 Create a Test Booking
1. Go to `/cars` and select a vehicle
2. Make a reservation for future dates
3. This will create a booking in 'pending_payment' status

#### 4.2 Access Payment Checkout
1. Go to `/my-bookings`
2. Click on your pending booking
3. Click "Pay Now" button
4. You'll be redirected to `/booking/{id}/payment`

#### 4.3 Test Stripe Payments
Use these test card numbers:

**Successful Payment:**
- Card: 4242 4242 4242 4242
- Any future expiry date
- Any 3-digit CVC

**Payment Requires Authentication:**
- Card: 4000 0025 0000 3155

**Declined Payment:**
- Card: 4000 0000 0000 9995

### 5. Payment Flow Summary

1. **Booking Creation** → Status: `pending_payment`
2. **Click "Pay Now"** → Redirect to checkout page
3. **Enter Card Details** → Creates Stripe Payment Intent
4. **Submit Payment** → Processes with Stripe
5. **Success** → Updates booking to `confirmed`, payment to `paid`
6. **Redirect** → Back to booking details with success message

### 6. API Endpoints

- `POST /api/payments/intent` - Create payment intent
- `POST /api/payments/process` - Process payment (non-Stripe)
- `POST /api/webhooks/stripe` - Handle Stripe webhooks
- `GET /booking/{id}/payment` - Payment checkout page
- `GET /booking/payment/return/{id}` - Payment return page

### 7. Testing Webhooks (Optional)

For local webhook testing, use Stripe CLI:

```bash
# Install Stripe CLI
# Windows: scoop install stripe
# Mac: brew install stripe/stripe-cli/stripe

# Login to Stripe
stripe login

# Forward webhooks to local server
stripe listen --forward-to localhost:8000/api/webhooks/stripe

# This will give you a webhook secret starting with whsec_
# Add it to your .env as STRIPE_WEBHOOK_SECRET
```

### 8. Verify Installation

Run the payment tests:
```bash
php artisan test tests/Feature/PaymentManagementTest.php
```

All tests should pass if Stripe is configured correctly.

## Production Setup

1. Replace test keys with live keys from Stripe Dashboard
2. Set up webhook endpoint in Stripe Dashboard pointing to your domain
3. Add webhook secret to production environment
4. Test with real payment methods

## Troubleshooting

1. **"Stripe key not provided"** - Check STRIPE_KEY in .env
2. **"Invalid API key"** - Verify secret key starts with sk_test_ or sk_live_
3. **Payment fails** - Check browser console for errors
4. **Webhook signature verification fails** - Verify STRIPE_WEBHOOK_SECRET

## Security Notes

- Never commit real Stripe keys to version control
- Use environment variables for all sensitive data
- Test webhooks signature verification
- Always validate payment amounts server-side