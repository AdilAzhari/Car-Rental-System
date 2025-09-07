# 📡 Car Rental System - API Documentation

This document outlines the RESTful API endpoints available in the Car Rental System.

## 🔐 Authentication

### Overview
The API uses Laravel Sanctum for authentication. Users must obtain an API token to access protected endpoints.

### Authentication Endpoints

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "role": "renter"
        },
        "token": "1|abc123...token"
    }
}
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

#### Get User Profile
```http
GET /api/auth/user
Authorization: Bearer {token}
```

## 🚗 Vehicles API

### List Vehicles
```http
GET /api/vehicles
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- per_page: int (default: 15, max: 100)
- status: string (available, rented, maintenance)
- make: string
- model: string
- year: int
- transmission: string (automatic, manual)
- fuel_type: string (gasoline, diesel, electric, hybrid)
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "make": "Toyota",
            "model": "Camry",
            "year": 2023,
            "plate_number": "ABC123",
            "color": "White",
            "daily_rate": 45.00,
            "status": "available",
            "transmission": "automatic",
            "fuel_type": "gasoline",
            "features": ["GPS", "Bluetooth", "AC"],
            "images": [
                {
                    "id": 1,
                    "url": "/storage/vehicles/toyota-camry-1.jpg",
                    "alt": "Toyota Camry Front View"
                }
            ]
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 50,
        "last_page": 4
    }
}
```

### Get Vehicle Details
```http
GET /api/vehicles/{id}
Authorization: Bearer {token}
```

### Create Vehicle (Admin Only)
```http
POST /api/vehicles
Authorization: Bearer {token}
Content-Type: application/json

{
    "make": "Toyota",
    "model": "Camry",
    "year": 2023,
    "plate_number": "ABC123",
    "color": "White",
    "daily_rate": 45.00,
    "transmission": "automatic",
    "fuel_type": "gasoline",
    "mileage": 15000,
    "engine_size": "2.5L",
    "doors": 4,
    "seats": 5,
    "features": ["GPS", "Bluetooth", "AC"],
    "status": "available"
}
```

### Update Vehicle (Admin Only)
```http
PUT /api/vehicles/{id}
Authorization: Bearer {token}
Content-Type: application/json
```

### Delete Vehicle (Admin Only)
```http
DELETE /api/vehicles/{id}
Authorization: Bearer {token}
```

## 📅 Bookings API

### List User Bookings
```http
GET /api/bookings
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- per_page: int (default: 15)
- status: string (pending, confirmed, ongoing, completed, cancelled)
- start_date: date (YYYY-MM-DD)
- end_date: date (YYYY-MM-DD)
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "vehicle_id": 5,
            "start_date": "2024-01-15",
            "end_date": "2024-01-18",
            "total_amount": 135.00,
            "status": "confirmed",
            "pickup_location": "Airport Terminal 1",
            "dropoff_location": "Downtown Hotel",
            "payment_status": "paid",
            "payment_method": "credit_card",
            "special_requests": "Child seat required",
            "created_at": "2024-01-10T10:30:00Z",
            "vehicle": {
                "id": 5,
                "make": "Honda",
                "model": "Civic",
                "year": 2023,
                "plate_number": "XYZ789",
                "daily_rate": 45.00
            }
        }
    ]
}
```

### Get Booking Details
```http
GET /api/bookings/{id}
Authorization: Bearer {token}
```

### Create Booking
```http
POST /api/bookings
Authorization: Bearer {token}
Content-Type: application/json

{
    "vehicle_id": 5,
    "start_date": "2024-01-15",
    "end_date": "2024-01-18",
    "pickup_location": "Airport Terminal 1",
    "dropoff_location": "Downtown Hotel",
    "special_requests": "Child seat required"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Booking created successfully",
    "data": {
        "id": 15,
        "vehicle_id": 5,
        "start_date": "2024-01-15",
        "end_date": "2024-01-18",
        "total_amount": 135.00,
        "status": "pending",
        "pickup_location": "Airport Terminal 1",
        "dropoff_location": "Downtown Hotel",
        "payment_status": "unpaid",
        "special_requests": "Child seat required"
    }
}
```

### Update Booking
```http
PUT /api/bookings/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "pickup_location": "Updated Location",
    "dropoff_location": "Updated Destination",
    "special_requests": "Updated requirements"
}
```

### Cancel Booking
```http
DELETE /api/bookings/{id}
Authorization: Bearer {token}
```

## 💳 Payments API

### Get Booking Payment
```http
GET /api/bookings/{booking_id}/payment
Authorization: Bearer {token}
```

### Process Payment
```http
POST /api/payments
Authorization: Bearer {token}
Content-Type: application/json

{
    "booking_id": 15,
    "payment_method": "credit_card",
    "amount": 135.00,
    "transaction_id": "txn_abc123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Payment processed successfully",
    "data": {
        "id": 25,
        "booking_id": 15,
        "amount": 135.00,
        "payment_method": "credit_card",
        "payment_status": "confirmed",
        "transaction_id": "txn_abc123",
        "processed_at": "2024-01-10T14:30:00Z"
    }
}
```

## ⭐ Reviews API

### List Vehicle Reviews
```http
GET /api/vehicles/{vehicle_id}/reviews
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- per_page: int (default: 10)
- rating: int (1-5)
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 8,
            "booking_id": 12,
            "rating": 5,
            "review": "Excellent vehicle, very clean and reliable!",
            "created_at": "2024-01-05T16:45:00Z",
            "reviewer": {
                "name": "Sarah Johnson",
                "initial": "SJ"
            }
        }
    ],
    "meta": {
        "average_rating": 4.6,
        "total_reviews": 23
    }
}
```

### Create Review
```http
POST /api/reviews
Authorization: Bearer {token}
Content-Type: application/json

{
    "booking_id": 12,
    "rating": 5,
    "review": "Excellent service and clean vehicle!"
}
```

### Update Review
```http
PUT /api/reviews/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "rating": 4,
    "review": "Updated review content"
}
```

### Delete Review
```http
DELETE /api/reviews/{id}
Authorization: Bearer {token}
```

## 📊 Statistics API (Admin Only)

### Dashboard Statistics
```http
GET /api/admin/statistics
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_bookings": 156,
        "active_bookings": 23,
        "total_vehicles": 45,
        "available_vehicles": 32,
        "total_revenue": 15420.50,
        "monthly_revenue": 3240.75,
        "average_rating": 4.6,
        "recent_bookings": [...],
        "revenue_chart": {
            "labels": ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
            "data": [1200, 1800, 2100, 2400, 2800, 3240]
        }
    }
}
```

### Booking Analytics
```http
GET /api/admin/analytics/bookings
Authorization: Bearer {token}

Query Parameters:
- start_date: date (YYYY-MM-DD)
- end_date: date (YYYY-MM-DD)
- group_by: string (day, week, month, year)
```

### Vehicle Analytics
```http
GET /api/admin/analytics/vehicles
Authorization: Bearer {token}
```

## 👥 Users API (Admin Only)

### List Users
```http
GET /api/admin/users
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- per_page: int (default: 20)
- role: string (admin, renter, owner)
- status: string (active, inactive)
- search: string
```

### Get User Details
```http
GET /api/admin/users/{id}
Authorization: Bearer {token}
```

### Create User
```http
POST /api/admin/users
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "role": "renter",
    "phone": "+1234567890",
    "address": "123 Main St, City, Country"
}
```

### Update User
```http
PUT /api/admin/users/{id}
Authorization: Bearer {token}
```

### Delete User
```http
DELETE /api/admin/users/{id}
Authorization: Bearer {token}
```

## 📱 Mobile App Endpoints

### Vehicle Availability Check
```http
GET /api/mobile/availability
Authorization: Bearer {token}

Query Parameters:
- start_date: date (YYYY-MM-DD) - required
- end_date: date (YYYY-MM-DD) - required
- location: string
```

### Nearby Vehicles
```http
GET /api/mobile/nearby
Authorization: Bearer {token}

Query Parameters:
- latitude: float - required
- longitude: float - required
- radius: int (default: 10km)
```

### Quick Booking
```http
POST /api/mobile/quick-booking
Authorization: Bearer {token}
Content-Type: application/json

{
    "vehicle_id": 5,
    "start_date": "2024-01-15",
    "end_date": "2024-01-18",
    "pickup_coordinates": {
        "latitude": 40.7128,
        "longitude": -74.0060
    }
}
```

## 🚫 Error Responses

### Standard Error Format
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Validation error message"]
    },
    "code": "ERROR_CODE"
}
```

### Common HTTP Status Codes
- **200 OK** - Successful request
- **201 Created** - Resource created successfully
- **400 Bad Request** - Invalid request data
- **401 Unauthorized** - Authentication required
- **403 Forbidden** - Access denied
- **404 Not Found** - Resource not found
- **422 Unprocessable Entity** - Validation errors
- **500 Internal Server Error** - Server error

### Error Codes
- `VALIDATION_FAILED` - Request validation failed
- `UNAUTHORIZED` - Invalid or missing authentication
- `FORBIDDEN` - Access denied for this resource
- `RESOURCE_NOT_FOUND` - Requested resource not found
- `BOOKING_CONFLICT` - Vehicle not available for selected dates
- `PAYMENT_FAILED` - Payment processing failed
- `RATE_LIMIT_EXCEEDED` - Too many requests

## 🔄 Rate Limiting

### Default Limits
- **Authenticated requests**: 60 requests per minute
- **Guest requests**: 20 requests per minute
- **Payment endpoints**: 10 requests per minute

### Rate Limit Headers
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1642681200
```

## 📚 Postman Collection

A complete Postman collection with all API endpoints is available at:
`/docs/postman/Car-Rental-System-API.postman_collection.json`

## 🧪 Testing

### API Testing
```bash
# Run API tests
php artisan test --filter=Api

# Test specific endpoint
php artisan test tests/Feature/Api/BookingApiTest.php
```

### Example Test Request
```bash
# Using curl
curl -X GET \
  http://localhost:8000/api/vehicles \
  -H 'Authorization: Bearer your-token-here' \
  -H 'Accept: application/json'
```

For more detailed API documentation with interactive testing, visit the Swagger documentation at `/api/documentation` when the application is running.