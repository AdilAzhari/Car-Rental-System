@php
    $currentLocale = app()->getLocale();
    $availableLocales = [
        'en' => [
            'name' => 'English',
            'flag' => '🇺🇸',
            'native' => 'English'
        ],
        'ar' => [
            'name' => 'Arabic', 
            'flag' => '🇸🇦',
            'native' => 'العربية'
        ]
    ];
@endphp

<div class="flex items-center gap-2 px-4">
    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1 language-switcher">
        @foreach($availableLocales as $localeCode => $locale)
            <a
                href="{{ url()->current() }}?locale={{ $localeCode }}"
                class="flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200 hover:scale-105
                       {{ $currentLocale === $localeCode 
                          ? 'bg-primary-500 text-white shadow-sm' 
                          : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                title="{{ __('app.switch_to') }} {{ $locale['name'] }}"
            >
                <span class="text-lg">{{ $locale['flag'] }}</span>
                <span class="hidden sm:inline">{{ $locale['native'] }}</span>
            </a>
        @endforeach
    </div>
</div>