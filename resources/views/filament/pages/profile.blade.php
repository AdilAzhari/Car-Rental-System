@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-filament-panels::page>
    <div class="max-w-6xl mx-auto space-y-6">
        {{-- Profile Header Card --}}
{{--        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">--}}
{{--            <div class="p-6">--}}
{{--                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">--}}
{{--                    <div class="flex items-center space-x-4">--}}
{{--                        --}}{{-- Profile Avatar --}}
{{--                        <div class="relative group cursor-pointer" onclick="document.getElementById('avatar-upload').click()">--}}
{{--                            <div class="relative">--}}
{{--                                @if(auth()->user()->avatar)--}}
{{--                                    <img class="h-20 w-20 rounded-xl object-cover ring-2 ring-primary-500/20 shadow-md transition-all group-hover:ring-primary-500/40"--}}
{{--                                         src="{{ Storage::url(auth()->user()->avatar) }}"--}}
{{--                                         alt="{{ auth()->user()->name }}">--}}
{{--                                @else--}}
{{--                                    <div class="h-20 w-20 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center ring-2 ring-primary-500/20 shadow-md transition-all group-hover:ring-primary-500/40">--}}
{{--                                        <span class="text-2xl font-bold text-white">--}}
{{--                                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}--}}
{{--                                        </span>--}}
{{--                                    </div>--}}
{{--                                @endif--}}
{{--                                --}}{{-- Camera Overlay --}}
{{--                                <div class="absolute inset-0 rounded-xl bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">--}}
{{--                                    <x-heroicon-m-camera class="w-5 h-5 text-white" />--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <input type="file" id="avatar-upload" class="hidden" accept="image/*">--}}
{{--                        </div>--}}

{{--                        --}}{{-- User Info --}}
{{--                        <div>--}}
{{--                            <div class="flex items-center space-x-2 mb-1">--}}
{{--                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">--}}
{{--                                    {{ auth()->user()->name ?? __('resources.unnamed_user') }}--}}
{{--                                </h1>--}}
{{--                                @if(auth()->user()->is_verified)--}}
{{--                                    <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400">--}}
{{--                                        <x-heroicon-m-check-badge class="w-3 h-3 mr-1" />--}}
{{--                                        Verified--}}
{{--                                    </div>--}}
{{--                                @endif--}}
{{--                            </div>--}}
{{--                            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">--}}
{{--                                <div class="flex items-center space-x-1">--}}
{{--                                    <x-heroicon-m-envelope class="w-4 h-4" />--}}
{{--                                    <span>{{ auth()->user()->email }}</span>--}}
{{--                                </div>--}}
{{--                                <div class="flex items-center space-x-1">--}}
{{--                                    <x-heroicon-m-calendar-days class="w-4 h-4" />--}}
{{--                                    <span>{{ __('resources.member_since') }} {{ auth()->user()->created_at->format('M Y') }}</span>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    --}}{{-- Quick Stats --}}
{{--                    <div class="flex space-x-6">--}}
{{--                        <div class="text-center">--}}
{{--                            <div class="text-xl font-bold text-gray-900 dark:text-white">--}}
{{--                                {{ auth()->user()->vehicles()->count() }}--}}
{{--                            </div>--}}
{{--                            <div class="text-xs text-gray-500 dark:text-gray-400">--}}
{{--                                {{ __('resources.vehicles') }}--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="text-center">--}}
{{--                            <div class="text-xl font-bold text-gray-900 dark:text-white">--}}
{{--                                {{ auth()->user()->bookings()->count() }}--}}
{{--                            </div>--}}
{{--                            <div class="text-xs text-gray-500 dark:text-gray-400">--}}
{{--                                {{ __('resources.bookings') }}--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="text-center">--}}
{{--                            <div class="text-xl font-bold text-gray-900 dark:text-white">--}}
{{--                                {{ auth()->user()->reviews()->count() }}--}}
{{--                            </div>--}}
{{--                            <div class="text-xs text-gray-500 dark:text-gray-400">--}}
{{--                                {{ __('resources.reviews') }}--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {{-- Profile Form --}}
            <div class="lg:col-span-3">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <div class="mb-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Settings</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Update your account information and preferences</p>
                        </div>

                        <form wire:submit="updateProfile" class="space-y-6">
                            {{ $this->form }}

                            {{-- Form Actions --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center space-x-2">
                                            <x-heroicon-m-clock class="w-4 h-4" />
                                            <span>{{ __('resources.profile_last_updated') }}: {{ auth()->user()->updated_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    <div class="flex space-x-3">
{{--                                        @foreach($this->getFormActions() as $action)--}}
{{--                                            {{ $action }}--}}
{{--                                        @endforeach--}}
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
{{--            <div class="space-y-6">--}}
{{--                --}}{{-- Account Status Card --}}
{{--                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">--}}
{{--                    <div class="p-6">--}}
{{--                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">--}}
{{--                            <x-heroicon-m-shield-check class="w-4 h-4 mr-2 text-primary-600" />--}}
{{--                            Account Status--}}
{{--                        </h3>--}}

{{--                        <div class="space-y-3">--}}
{{--                            <div class="flex items-center justify-between py-2">--}}
{{--                                <span class="text-xs text-gray-600 dark:text-gray-400">Account Type</span>--}}
{{--                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">--}}
{{--                                    {{ ucfirst(auth()->user()->role->value ?? 'renter') }}--}}
{{--                                </span>--}}
{{--                            </div>--}}

{{--                            <div class="flex items-center justify-between py-2">--}}
{{--                                <span class="text-xs text-gray-600 dark:text-gray-400">Status</span>--}}
{{--                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">--}}
{{--                                    {{ ucfirst(auth()->user()->status->value ?? 'active') }}--}}
{{--                                </span>--}}
{{--                            </div>--}}

{{--                            <div class="flex items-center justify-between py-2">--}}
{{--                                <span class="text-xs text-gray-600 dark:text-gray-400">Last Login</span>--}}
{{--                                <span class="text-xs font-medium text-gray-900 dark:text-white">--}}
{{--                                    {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->format('M j, g:i A') : __('resources.never') }}--}}
{{--                                </span>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                --}}{{-- Quick Actions Card --}}
{{--                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">--}}
{{--                    <div class="p-6">--}}
{{--                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">--}}
{{--                            <x-heroicon-m-bolt class="w-4 h-4 mr-2 text-primary-600" />--}}
{{--                            Quick Actions--}}
{{--                        </h3>--}}

{{--                        <div class="space-y-2">--}}
{{--                            <a href="{{ route('filament.admin.resources.vehicles.index') }}" class="flex items-center space-x-3 w-full p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors group">--}}
{{--                                <x-heroicon-m-truck class="w-4 h-4 text-gray-500 group-hover:text-primary-600" />--}}
{{--                                <div>--}}
{{--                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Vehicles</div>--}}
{{--                                    <div class="text-xs text-gray-500 dark:text-gray-400">Manage fleet</div>--}}
{{--                                </div>--}}
{{--                            </a>--}}

{{--                            <a href="{{ route('filament.admin.resources.bookings.index') }}" class="flex items-center space-x-3 w-full p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors group">--}}
{{--                                <x-heroicon-m-calendar class="w-4 h-4 text-gray-500 group-hover:text-primary-600" />--}}
{{--                                <div>--}}
{{--                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Bookings</div>--}}
{{--                                    <div class="text-xs text-gray-500 dark:text-gray-400">View history</div>--}}
{{--                                </div>--}}
{{--                            </a>--}}

{{--                            <a href="{{ route('filament.admin.resources.users.index') }}" class="flex items-center space-x-3 w-full p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg transition-colors group">--}}
{{--                                <x-heroicon-m-cog-6-tooth class="w-4 h-4 text-gray-500 group-hover:text-primary-600" />--}}
{{--                                <div>--}}
{{--                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Settings</div>--}}
{{--                                    <div class="text-xs text-gray-500 dark:text-gray-400">Preferences</div>--}}
{{--                                </div>--}}
{{--                            </a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                --}}{{-- Recent Activity Card --}}
{{--                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">--}}
{{--                    <div class="p-6">--}}
{{--                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">--}}
{{--                            <x-heroicon-m-clock class="w-4 h-4 mr-2 text-primary-600" />--}}
{{--                            Recent Activity--}}
{{--                        </h3>--}}

{{--                        <div class="space-y-3">--}}
{{--                            <div class="flex items-start space-x-3">--}}
{{--                                <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>--}}
{{--                                <div>--}}
{{--                                    <div class="text-sm text-gray-900 dark:text-white">Profile updated</div>--}}
{{--                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->updated_at->diffForHumans() }}</div>--}}
{{--                                </div>--}}
{{--                            </div>--}}

{{--                            @if(auth()->user()->last_login_at)--}}
{{--                            <div class="flex items-start space-x-3">--}}
{{--                                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>--}}
{{--                                <div>--}}
{{--                                    <div class="text-sm text-gray-900 dark:text-white">Last login</div>--}}
{{--                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->last_login_at->diffForHumans() }}</div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            @endif--}}

{{--                            <div class="flex items-start space-x-3">--}}
{{--                                <div class="w-2 h-2 bg-gray-400 rounded-full mt-2"></div>--}}
{{--                                <div>--}}
{{--                                    <div class="text-sm text-gray-900 dark:text-white">Account created</div>--}}
{{--                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->created_at->diffForHumans() }}</div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
        </div>
    </div>

{{--    <x-filament-actions::modals />--}}
</x-filament-panels::page>
