@php
    $currentLocale = app()->getLocale();
    $isRtl = in_array($currentLocale, ['ar', 'he', 'fa', 'ur']);
@endphp

@if($isRtl)
    <script>
        document.documentElement.setAttribute('dir', 'rtl');
        document.body.classList.add('rtl');
    </script>
    <style>
        /* Inline RTL styles for immediate application */
        html[dir="rtl"] {
            direction: rtl;
        }
        
        [dir="rtl"] * {
            font-family: 'Segoe UI', 'Tahoma', 'Arial', 'Noto Sans Arabic', sans-serif !important;
        }
        
        [dir="rtl"] .fi-sidebar-nav-item-label {
            text-align: right;
        }
        
        [dir="rtl"] .fi-input, 
        [dir="rtl"] .fi-select {
            text-align: right;
        }
        
        [dir="rtl"] .fi-table-cell,
        [dir="rtl"] .fi-table-header-cell {
            text-align: right;
        }
        
        [dir="rtl"] .fi-breadcrumb-item::after {
            content: "‹";
        }
        
        /* Fix dropdown positioning for RTL */
        [dir="rtl"] .fi-dropdown-panel {
            left: auto !important;
            right: 0 !important;
        }
    </style>
@else
    <script>
        document.documentElement.setAttribute('dir', 'ltr');
        document.body.classList.remove('rtl');
    </script>
@endif