<?php

return [
    'vehicle_status' => [
        'pending' => 'في انتظار الموافقة',
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
        'draft' => 'مسودة',
        'published' => 'منشور',
        'maintenance' => 'قيد الصيانة',
        'archived' => 'مؤرشف',
    ],

    'payment_status' => [
        'pending' => 'معلق',
        'confirmed' => 'مؤكد',
        'failed' => 'فشل',
        'refunded' => 'مسترد',
        'cancelled' => 'ملغي',
        'processing' => 'قيد المعالجة',
    ],

    'booking_status' => [
        'pending' => 'معلق',
        'confirmed' => 'مؤكد',
        'ongoing' => 'جاري',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
    ],

    'user_role' => [
        'admin' => 'مدير',
        'owner' => 'مالك مركبة',
        'customer' => 'عميل/مستأجر',
        'renter' => 'عميل/مستأجر',
    ],

    'fuel_type' => [
        'petrol' => 'بنزين',
        'diesel' => 'ديزل',
        'hybrid' => 'هجين',
        'electric' => 'كهربائي',
        'lpg' => 'غاز البترول المسال',
    ],

    'transmission' => [
        'manual' => 'يدوي',
        'automatic' => 'أوتوماتيكي',
        'cvt' => 'CVT',
    ],

    'payment_method' => [
        'cash' => 'نقد',
        'credit_card' => 'بطاقة ائتمان',
        'debit_card' => 'بطاقة خصم',
        'bank_transfer' => 'حوالة بنكية',
        'e_wallet' => 'محفظة إلكترونية',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
    ],

    'vehicle_category' => [
        'economy' => 'اقتصادي',
        'compact' => 'صغير الحجم',
        'midsize' => 'متوسط الحجم',
        'fullsize' => 'كبير الحجم',
        'luxury' => 'فاخر',
        'suv' => 'دفع رباعي',
        'minivan' => 'حافلة صغيرة',
        'pickup' => 'شاحنة صغيرة',
        'convertible' => 'قابل للطي',
        'sports' => 'رياضي',
    ],
];
