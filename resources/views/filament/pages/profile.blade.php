@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-filament-panels::page>
    <div class="max-w-7xl mx-auto">
        {{-- Header Section with Cover and Profile --}}
        <div class="relative">
            {{-- Cover Image --}}
            <div class="h-48 bg-gradient-to-r from-primary-600 via-primary-700 to-primary-800 rounded-t-2xl relative overflow-hidden">
                <div class="absolute inset-0 bg-black/20"></div>
                <div class="absolute inset-0">
                    <div class="h-full w-full bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iNCIvPjwvZz48L2c+PC9zdmc+')] opacity-30"></div>
                </div>

                {{-- New User Welcome Banner --}}
                @if(auth()->user()->is_new_user)
                    <div class="absolute top-4 left-4 right-4">
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-white">
                                        {{ __('resources.complete_your_profile') }}
                                    </h3>
                                    <p class="text-xs text-white/80 mt-0.5">
                                        {{ __('resources.new_user_profile_message') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Profile Info Overlay --}}
            <div class="bg-white dark:bg-gray-900 rounded-b-2xl shadow-xl relative -mt-16 pt-16 pb-6 px-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-6">
                    {{-- Profile Avatar --}}
                    <div class="relative group cursor-pointer" onclick="document.getElementById('avatar-upload').click()">
                        <div class="relative">
                            @if(auth()->user()->avatar)
                                <img class="h-32 w-32 rounded-2xl object-cover ring-4 ring-white dark:ring-gray-800 shadow-lg transition-transform group-hover:scale-105"
                                     src="{{ Storage::url(auth()->user()->avatar) }}"
                                     alt="{{ auth()->user()->name }}">
                            @else
                                <div class="h-32 w-32 rounded-2xl bg-gradient-to-br from-primary-500 via-primary-600 to-primary-700 flex items-center justify-center ring-4 ring-white dark:ring-gray-800 shadow-lg transition-transform group-hover:scale-105">
                                    <span class="text-4xl font-bold text-white">
                                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                            {{-- Camera Overlay --}}
                            <div class="absolute inset-0 rounded-2xl bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <div class="text-center">
                                    <x-heroicon-m-camera class="w-8 h-8 text-white mx-auto mb-1" />
                                    <p class="text-xs text-white font-medium">{{ __('resources.change_photo') }}</p>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="avatar-upload" class="hidden" accept="image/*">
                    </div>

                    {{-- User Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white truncate">
                                {{ auth()->user()->name ?? __('resources.unnamed_user') }}
                            </h1>
                            @if(auth()->user()->is_verified)
                                <div class="flex-shrink-0">
                                    <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                        <x-heroicon-m-check-badge class="w-4 h-4 mr-1" />
                                        Verified
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center space-x-2">
                                <x-heroicon-m-envelope class="w-4 h-4" />
                                <span>{{ auth()->user()->email }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <x-heroicon-m-calendar-days class="w-4 h-4" />
                                <span>{{ __('resources.member_since') }} {{ auth()->user()->created_at->format('F Y') }}</span>
                            </div>
                            @if(auth()->user()->phone)
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-m-phone class="w-4 h-4" />
                                    <span>{{ auth()->user()->phone }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Quick Stats --}}
                        <div class="mt-4 flex space-x-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ auth()->user()->vehicles()->count() }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('resources.vehicles') }}
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ auth()->user()->bookings()->count() }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('resources.bookings') }}
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ auth()->user()->reviews()->count() }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('resources.reviews') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Profile Form --}}
            <div class="lg:col-span-2">
                <form wire:submit="updateProfile" class="space-y-6">
                    {{ $this->form }}

                    {{-- Form Actions --}}
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-m-clock class="w-4 h-4" />
                                    <span>{{ __('resources.profile_last_updated') }}: {{ auth()->user()->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="flex space-x-3">
                                @foreach($this->getFormActions() as $action)
                                    {{ $action }}
                                @endforeach
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Sidebar with Activity & Info --}}
            <div class="space-y-6">
                {{-- Account Status Card --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <x-heroicon-m-shield-check class="w-5 h-5 mr-2 text-primary-600" />
                        Account Status
                    </h3>

                    <div class="space-y-4">
                        {{-- Account Type --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Account Type</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400">
                                {{ ucfirst(auth()->user()->role->value ?? 'renter') }}
                            </span>
                        </div>

                        {{-- Status --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Status</span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                {{ ucfirst(auth()->user()->status->value ?? 'active') }}
                            </span>
                        </div>

                        {{-- Last Login --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Last Login</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : __('resources.never') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions Card --}}
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <x-heroicon-m-bolt class="w-5 h-5 mr-2 text-primary-600" />
                        Quick Actions
                    </h3>

                    <div class="space-y-3">
                        <a href="{{ route('filament.admin.resources.vehicles.index') }}" class="block w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <div class="flex items-center space-x-3">
                                <x-heroicon-m-truck class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Manage Vehicles</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">View and edit your vehicles</div>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('filament.admin.resources.bookings.index') }}" class="block w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <div class="flex items-center space-x-3">
                                <x-heroicon-m-calendar class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">View Bookings</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Check your rental history</div>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('filament.admin.resources.users.index') }}" class="block w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <div class="flex items-center space-x-3">
                                <x-heroicon-m-cog-6-tooth class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Account Settings</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Advanced preferences</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>