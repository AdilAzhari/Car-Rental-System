# JPJ Traffic Violation Integration Guide

This document provides a comprehensive guide for integrating with Malaysia's JPJ (Jabatan Pengangkutan Jalan) system to check real traffic violations via SMS.

## 🚗 Overview

The CarRentSystem now supports real-time traffic violation checking through JPJ's SMS service. The system can:

- Send SMS queries to JPJ's short code (32728)
- Parse JPJ responses automatically
- Cache results to avoid repeated queries
- Display violations in a user-friendly format
- Integrate with multiple SMS providers

## 🏗️ Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   CarRentSystem │    │   SMS Provider   │    │   JPJ System    │
│                 │    │   (Maxis/Digi/   │    │   (32728)       │
│  ┌─────────────┐│    │   Third-party)   │    │                 │
│  │ Violation   ││    │                  │    │                 │
│  │ Service     ││───▶│  Send SMS        │───▶│  Process Query  │
│  └─────────────┘│    │  "JPJ SAMAN      │    │                 │
│                 │    │   ABC1234"       │    │                 │
│  ┌─────────────┐│    │                  │    │                 │
│  │ Webhook     ││◀───│  Receive         │◀───│  Send Response  │
│  │ Controller  ││    │  Response        │    │                 │
│  └─────────────┘│    └──────────────────┘    └─────────────────┘
└─────────────────┘
```

## 📋 Prerequisites

1. **SMS Provider Account**: You need an account with an SMS provider that supports Malaysian short codes:
   - **SMS Country** (recommended for Malaysia)
   - **MSG91**
   - **Twilio** (limited - doesn't support short codes directly)
   - **Malaysian Telco APIs** (Maxis, Digi, Celcom)

2. **Webhook Endpoint**: A publicly accessible URL for receiving JPJ responses
3. **SSL Certificate**: HTTPS is required for webhook security

## ⚙️ Configuration

### Environment Variables

Add these to your `.env` file:

```env
# JPJ SMS Integration
JPJ_SMS_API_KEY=your_sms_provider_api_key_here
JPJ_SMS_PROVIDER_URL=https://api.smsprovider.com/send
JPJ_NUMBER=32728
TRAFFIC_CHECK_ENABLED=true
TRAFFIC_CACHE_HOURS=24

# Malaysian Telco API (optional alternative)
MAXIS_API_KEY=your_maxis_api_key_here
MAXIS_API_ENDPOINT=https://api.maxis.com.my

# Twilio (for webhook processing)
TWILIO_ACCOUNT_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_FROM_NUMBER=your_twilio_phone_number
```

### SMS Provider Setup

#### Option 1: SMS Country (Recommended)

1. Sign up at [SMS Country](https://smscountry.com/)
2. Get API credentials
3. Verify Malaysian short code support
4. Set webhook URL: `https://yourdomain.com/api/webhooks/jpj-response`

#### Option 2: Malaysian Telco APIs

Contact Malaysian telecom providers directly:
- **Maxis**: enterprise.maxis.com.my
- **Digi**: enterprise.digi.com.my
- **Celcom**: enterprise.celcom.com.my

#### Option 3: Third-party Aggregators

- **MSG91**: msg91.com
- **TextLocal**: textlocal.my
- **SMSAlert**: smsalert.co.in

## 🔧 Implementation

### 1. SMS Service Integration

The `JpjSmsService` handles all SMS communication:

```php
use App\Services\JpjSmsService;

$jpjService = app(JpjSmsService::class);
$result = $jpjService->checkTrafficViolations('ABC1234');
```

### 2. Webhook Processing

JPJ responses are processed via webhook:

```php
// Webhook endpoint: /api/webhooks/jpj-response
// Handles incoming SMS responses from JPJ
```

### 3. Traffic Violation Service

Main service for checking violations:

```php
use App\Services\TrafficViolationService;

$violationService = app(TrafficViolationService::class);
$vehicle = Vehicle::where('plate_number', 'ABC1234')->first();
$violations = $violationService->checkVehicleViolations($vehicle);
```

## 🧪 Testing

### Command Line Testing

```bash
# Test complete integration
php artisan jpj:test --plate=ABC1234

# Test webhook parsing
php artisan jpj:test --webhook

# Clear cache
php artisan jpj:test --clear-cache
```

### Manual Testing

1. **Test Webhook Endpoint**:
```bash
curl -X POST https://yourdomain.com/api/webhooks/twilio/test \
  -H "Content-Type: application/json" \
  -d '{"message": "JPJ SAMAN ABC1234 TIADA SAMAN"}'
```

2. **Test via Admin Panel**:
   - Go to Vehicle view page
   - Click "Check Violations" button
   - Monitor logs and results

## 📊 JPJ Response Formats

### No Violations
```
JPJ SAMAN ABC1234
TIADA SAMAN TERTUNGGAK
TARIKH: 15/01/2025
```

### With Violations
```
JPJ SAMAN ABC1234
KESALAHAN LAJU KM 234.5 RM 150.00
LAMPU MERAH JALAN AMPANG RM 300.00
TARIKH: 10/01/2025
JUMLAH: RM 450.00
```

## 🔍 Monitoring & Logging

### Log Locations

- **Application Logs**: `storage/logs/laravel.log`
- **JPJ Requests**: Search for "JPJ SMS" in logs
- **Webhook Processing**: Search for "JPJ response" in logs

### Key Metrics to Monitor

1. **SMS Success Rate**: Track failed SMS sends
2. **Response Processing**: Monitor webhook failures
3. **Cache Hit Rate**: Optimize API usage
4. **Parse Errors**: Monitor JPJ response format changes

## 🚨 Error Handling

### Common Issues

1. **SMS Provider Down**: System falls back to test mode
2. **JPJ Format Change**: Parser handles gracefully, logs warnings
3. **Webhook Failures**: Retry mechanism with exponential backoff
4. **Rate Limiting**: Respects SMS provider limits

### Fallback Mechanisms

1. **Test Mode**: Realistic simulation when real service unavailable
2. **Cache**: Serves cached data if fresh check fails
3. **Multiple Providers**: Automatic failover between SMS providers

## 🔐 Security

### Webhook Security

1. **Request Validation**: Validates incoming webhook requests
2. **Rate Limiting**: Prevents abuse
3. **SSL/TLS**: All communication encrypted
4. **Input Sanitization**: All JPJ responses sanitized

### Data Protection

1. **PII Handling**: Plate numbers encrypted in logs
2. **Cache Security**: Violation data securely cached
3. **Audit Trail**: All checks logged for compliance

## 💰 Cost Optimization

### SMS Usage Optimization

1. **Caching**: 24-hour cache reduces duplicate requests
2. **Batch Processing**: Bulk vehicle checks with delays
3. **Smart Refresh**: Only check when necessary
4. **Rate Limiting**: Prevents excessive API usage

### Estimated Costs

- **SMS Country**: ~RM 0.05 per SMS
- **Maxis API**: ~RM 0.03 per SMS
- **Expected Usage**: 1-5 SMS per vehicle per month

## 🚀 Production Deployment

### Pre-deployment Checklist

- [ ] SMS provider account configured
- [ ] Webhook URL publicly accessible
- [ ] SSL certificate installed
- [ ] Environment variables set
- [ ] Test integration working
- [ ] Monitoring configured
- [ ] Backup SMS provider ready

### Deployment Steps

1. **Configure SMS Provider**
2. **Set Webhook URL**
3. **Update Environment Variables**
4. **Test Integration**
5. **Monitor Initial Usage**
6. **Scale as Needed**

## 📞 Support & Troubleshooting

### Debug Commands

```bash
# Check configuration
php artisan config:show services.jpj_sms

# Test SMS service
php artisan jpj:test --plate=TEST1234

# View recent logs
tail -f storage/logs/laravel.log | grep "JPJ"
```

### Common Solutions

1. **"SMS service not configured"**: Check API keys in `.env`
2. **"Webhook not receiving data"**: Verify URL accessibility
3. **"Parse errors"**: Check JPJ response format changes
4. **"Rate limiting"**: Reduce check frequency

### Support Contacts

- **SMS Country**: support@smscountry.com
- **Maxis Enterprise**: enterprise@maxis.com.my
- **JPJ**: 1-800-88-7266

## 📈 Future Enhancements

1. **Real-time Notifications**: Push notifications for new violations
2. **Payment Integration**: Direct payment of fines
3. **Multiple Plate Support**: Bulk checking for fleets
4. **Analytics Dashboard**: Violation trends and statistics
5. **Mobile App**: Native mobile integration

## 📜 Legal Compliance

- Ensure compliance with Malaysian data protection laws
- Only check vehicles owned by your customers
- Respect JPJ's terms of service
- Implement proper data retention policies

---

**Note**: This integration requires careful testing and monitoring. Start with test mode and gradually enable real SMS integration once you're confident the system works correctly.