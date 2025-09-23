@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-filament-panels::page>
    @if(auth()->user()->is_new_user)
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ __('resources.complete_your_profile') }}
                    </h3>
                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                        {{ __('resources.new_user_profile_message') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Profile Avatar Section -->
    <div class="mb-6 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center space-x-6">
            <div class="flex-shrink-0">
                @if(auth()->user()->avatar)
                    <img class="h-20 w-20 rounded-full object-cover ring-4 ring-primary-500"
                         src="{{ Storage::url(auth()->user()->avatar) }}"
                         alt="{{ auth()->user()->name }}">
                @else
                    <div class="h-20 w-20 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center ring-4 ring-primary-500">
                        <span class="text-2xl font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                    {{ auth()->user()->name ?? __('resources.unnamed_user') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ auth()->user()->email }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('resources.member_since') }} {{ auth()->user()->created_at->format('F Y') }}
                </p>
            </div>
            <div class="flex-shrink-0">
                <button type="button"
                        class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                        onclick="document.getElementById('avatar-upload').click()">
                    <x-heroicon-m-camera class="w-4 h-4 mr-2 inline" />
                    {{ __('resources.change_photo') }}
                </button>
                <input type="file" id="avatar-upload" class="hidden" accept="image/*">
            </div>
        </div>
    </div>

    <form wire:submit="updateProfile">
        {{ $this->form }}

        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('resources.profile_last_updated') }}:
                    <span class="font-medium">{{ auth()->user()->updated_at->diffForHumans() }}</span>
                </div>
                <div class="flex space-x-3">
                    @foreach($this->getFormActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            </div>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
