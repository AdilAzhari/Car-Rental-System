<?php

return [
    // Application
    'app_name' => 'نظام تأجير السيارات',
    'welcome' => 'مرحباً',
    'dashboard' => 'لوحة التحكم',
    'language' => 'اللغة',
    
    // Navigation
    'navigation' => [
        'dashboard' => 'لوحة التحكم',
        'user_management' => 'إدارة المستخدمين',
        'vehicle_management' => 'إدارة المركبات',
        'booking_management' => 'إدارة الحجوزات',
        'customer_feedback' => 'تقييمات العملاء',
        'system_management' => 'إدارة النظام',
        'users' => 'المستخدمون',
        'vehicles' => 'المركبات',
        'bookings' => 'الحجوزات',
        'reviews' => 'التقييمات',
        'activity_logs' => 'سجل الأنشطة',
        'settings' => 'الإعدادات',
    ],
    
    // User Roles
    'roles' => [
        'admin' => 'مدير',
        'owner' => 'مالك مركبة',
        'renter' => 'مستأجر',
    ],
    
    // Status
    'status' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'pending' => 'في الانتظار',
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
        'published' => 'منشور',
        'completed' => 'مكتمل',
        'ongoing' => 'جاري',
        'cancelled' => 'ملغي',
    ],
    
    // Common Actions
    'actions' => [
        'create' => 'إنشاء',
        'edit' => 'تعديل',
        'view' => 'عرض',
        'delete' => 'حذف',
        'save' => 'حفظ',
        'cancel' => 'إلغاء',
        'submit' => 'إرسال',
        'search' => 'بحث',
        'filter' => 'تصفية',
        'reset' => 'إعادة تعيين',
        'export' => 'تصدير',
        'import' => 'استيراد',
    ],
    
    // Forms
    'form' => [
        'required_field' => 'هذا الحقل مطلوب',
        'invalid_email' => 'البريد الإلكتروني غير صحيح',
        'password_min_length' => 'كلمة المرور يجب أن تكون على الأقل :min أحرف',
        'password_confirmation' => 'تأكيد كلمة المرور لا يتطابق',
    ],
    
    // Messages
    'messages' => [
        'success_create' => 'تم الإنشاء بنجاح',
        'success_update' => 'تم التحديث بنجاح',
        'success_delete' => 'تم الحذف بنجاح',
        'error_create' => 'خطأ في الإنشاء',
        'error_update' => 'خطأ في التحديث',
        'error_delete' => 'خطأ في الحذف',
        'no_records_found' => 'لم يتم العثور على سجلات',
    ],
];