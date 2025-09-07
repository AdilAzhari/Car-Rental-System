# 🚗 Car Rental System

A comprehensive car rental management system built with Laravel 12, Filament v4, and modern web technologies. This system provides a complete solution for managing vehicles, bookings, customers, and payments with an intuitive admin interface.

## ✨ Features

### 🎯 Core Functionality
- **Vehicle Management**: Complete fleet management with detailed vehicle information, images, and status tracking
- **Booking System**: Advanced booking management with status workflows and automatic calculations
- **Customer Management**: User management with role-based access (admin, renter, owner)
- **Payment Processing**: Payment tracking with multiple methods and status management
- **Review System**: Interactive star rating system for customer feedback

### 🎨 Enhanced UI/UX
- **Tabbed Form Interface**: Organized booking forms with 4 intuitive sections
- **Enhanced Info Lists**: Comprehensive view pages with color-coded status badges
- **Interactive Calendar**: Visual booking calendar with color-coded events
- **Advanced Filtering**: Date range filtering for enhanced data discovery
- **Star Rating System**: Interactive 5-star rating components
- **Responsive Design**: Mobile-friendly interface with modern styling

### 📊 Advanced Features
- **Live Calculations**: Automatic rental amount calculations based on dates and rates
- **Status Workflows**: Guided booking status transitions (Pending → Confirmed → Ongoing → Completed)
- **Activity Logging**: Comprehensive audit trail with Spatie Activity Log integration
- **Notifications**: Laravel notification system for user communications
- **Media Management**: Spatie Media Library for vehicle image handling

## 🛠 Tech Stack

### Backend
- **Laravel 12** - PHP framework with latest features
- **PHP 8.4** - Modern PHP with performance improvements
- **MySQL** - Reliable database with comprehensive relationships

### Admin Interface
- **Filament v4** - Modern admin panel with Server-Driven UI
- **Livewire v3** - Dynamic frontend interactions
- **Alpine.js** - Lightweight JavaScript framework
- **Tailwind CSS** - Utility-first CSS framework

### Enhanced Packages
- **malzariey/filament-daterangepicker-filter** - Advanced date range filtering
- **mokhosh/filament-rating** - Interactive star rating components
- **guava/calendar** - Full-featured calendar widget integration
- **spatie/laravel-activitylog** - Comprehensive activity logging
- **spatie/laravel-medialibrary** - Media file management

## 🚀 Installation

### Prerequisites
- PHP 8.4+
- Composer
- Node.js & NPM
- MySQL/MariaDB
- Web server (Apache/Nginx)

### Step 1: Clone Repository
```bash
git clone <repository-url>
cd CarRentSystem
```

### Step 2: Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### Step 3: Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Database Setup
Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=car_rental_system
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run migrations and seed data:
```bash
# Run database migrations
php artisan migrate

# Seed the database with sample data
php artisan db:seed
```

### Step 5: Asset Compilation
```bash
# Build assets for development
npm run dev

# Or build for production
npm run build
```

### Step 6: Start Development Server
```bash
php artisan serve
```

Visit `http://localhost:8000/admin` to access the admin panel.

## 📖 Usage Guide

### Default Login Credentials
```
Email: admin@example.com
Password: password
```

### Managing Bookings
1. **Create Booking**: Navigate to Bookings → Create
2. **Fill Form**: Use the tabbed interface to enter booking details
3. **Status Management**: Use the status workflow buttons in view mode
4. **Calendar View**: Access the calendar page for visual booking management

### Vehicle Management
1. **Add Vehicles**: Create vehicles with detailed specifications
2. **Upload Images**: Use media library for vehicle photos
3. **Status Tracking**: Monitor vehicle availability and maintenance

### Customer Reviews
1. **Review System**: Customers can rate their rental experience
2. **Star Ratings**: 5-star rating system with interactive components
3. **Feedback Management**: Admin can view and moderate reviews

## 🏗 Architecture

### Database Schema
The system uses a well-structured database with the following main entities:

- **Users** - Customer and admin accounts with role-based access
- **Vehicles** - Fleet management with detailed specifications
- **Bookings** - Rental reservations with status workflows
- **Payments** - Financial transactions and payment tracking
- **Reviews** - Customer feedback with star ratings
- **Activity Logs** - Comprehensive audit trails

### Key Relationships
```
Users (1:n) Bookings (n:1) Vehicles
Bookings (1:1) Payments
Bookings (1:n) Reviews
All Models → Activity Logs
```

### Filament Resources
- **BookingResource** - Main booking management interface
- **VehicleResource** - Fleet management
- **UserResource** - Customer and staff management
- **ActivityLogResource** - System activity monitoring

## 🎨 UI Components

### Enhanced Booking Forms
- **Tab 1**: Basic Information (Customer, Vehicle, Dates)
- **Tab 2**: Location & Pricing (Pickup/Dropoff, Costs)
- **Tab 3**: Status & Payment (Booking Status, Payment Details)
- **Tab 4**: Additional Notes (Special Requests)

### Info List Components
- **Booking Overview**: Key information with status badges
- **Quick Actions**: Direct navigation to related resources
- **Customer Details**: Enhanced customer information display
- **Vehicle Information**: Comprehensive vehicle details

### Calendar Integration
- **Color-coded Events**: Visual status representation
- **Interactive Calendar**: Click events for booking details
- **Status Legend**: Clear status color guide
- **Multi-view Options**: Month, week, and day views

## 🔧 Development

### Code Standards
- **Laravel Conventions**: Follow Laravel best practices
- **PSR Standards**: PSR-4 autoloading and PSR-12 coding style
- **Type Declarations**: Full PHP type hints throughout
- **Documentation**: Comprehensive PHPDoc blocks

### Testing
```bash
# Run all tests
php artisan test

# Run specific test files
php artisan test tests/Feature/BookingTest.php
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Static analysis (if configured)
./vendor/bin/phpstan analyse
```

## 🔒 Security

### Authentication & Authorization
- **Laravel Breeze** integration for authentication
- **Role-based Access Control** with proper permissions
- **CSRF Protection** on all forms
- **SQL Injection Prevention** through Eloquent ORM

### Data Protection
- **Input Validation** using Form Request classes
- **XSS Prevention** through proper output escaping
- **Secure File Uploads** with validation and storage controls

## 📝 API Documentation

The system includes RESTful API endpoints for:
- Booking management
- Vehicle information
- Customer data
- Payment processing

API documentation is available at `/api/documentation` (if configured).

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow Laravel and Filament best practices
- Write comprehensive tests for new features
- Update documentation for any changes
- Use conventional commit messages

## 📄 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## 🙏 Acknowledgments

- **Laravel Team** for the excellent framework
- **Filament Team** for the amazing admin panel
- **Package Authors** for the enhanced functionality packages
- **Community Contributors** for inspiration and feedback

## 📞 Support

For support and questions:
- Create an issue in the repository
- Check the documentation
- Review the code comments and PHPDoc blocks

---

**Built with ❤️ using Laravel 12 & Filament v4**