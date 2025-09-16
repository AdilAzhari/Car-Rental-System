<x-filament-widgets::widget>
{{--    <x-filament::section>--}}
{{--        <div class="flex items-center justify-between">--}}
{{--            <h3 class="text-sm font-medium text-gray-900 dark:text-white">--}}
{{--                {{ __('app.language') }}--}}
{{--            </h3>--}}
{{--            --}}
{{--            <div class="flex gap-2">--}}
{{--                @foreach($this->getAvailableLocales() as $localeCode => $locale)--}}
{{--                    <button--}}
{{--                        wire:click="switchLanguage('{{ $localeCode }}')"--}}
{{--                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors--}}
{{--                               {{ $this->getCurrentLocale() === $localeCode --}}
{{--                                  ? 'bg-primary-500 text-white' --}}
{{--                                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"--}}
{{--                    >--}}
{{--                        <span class="text-base">{{ $locale['flag'] }}</span>--}}
{{--                        <span>{{ $locale['native'] }}</span>--}}
{{--                    </button>--}}
{{--                @endforeach--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </x-filament::section>--}}
</x-filament-widgets::widget>
