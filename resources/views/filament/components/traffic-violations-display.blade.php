@php
    $violations = $getRecord()?->traffic_violations ?? [];
@endphp

@if(is_array($violations) && count($violations) > 0)
    <div class="space-y-3">
        @foreach($violations as $violation)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-100 dark:bg-red-800 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600 dark:text-red-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-semibold text-red-900 dark:text-red-100">
                                {{ $violation['violation_type'] ?? __('resources.traffic_violation') }}
                            </h4>
                            @if(isset($violation['fine_amount']))
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100 rounded-full">
                                    RM {{ number_format($violation['fine_amount'], 2) }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                            @if(isset($violation['date']))
                                <div class="flex items-center space-x-2">
                                    <span class="text-red-600 dark:text-red-400">📅</span>
                                    <span class="text-red-800 dark:text-red-200">{{ \Carbon\Carbon::parse($violation['date'])->format('d M Y') }}</span>
                                </div>
                            @endif
                            
                            @if(isset($violation['location']))
                                <div class="flex items-center space-x-2">
                                    <span class="text-red-600 dark:text-red-400">📍</span>
                                    <span class="text-red-700 dark:text-red-300">{{ $violation['location'] }}</span>
                                </div>
                            @endif
                            
                            @if(isset($violation['status']))
                                <div class="flex items-center space-x-2">
                                    <span class="text-red-600 dark:text-red-400">🏷️</span>
                                    <span class="text-red-700 dark:text-red-300">{{ ucfirst($violation['status']) }}</span>
                                </div>
                            @endif
                            
                            @if(isset($violation['reference_number']))
                                <div class="flex items-center space-x-2">
                                    <span class="text-red-600 dark:text-red-400">#️⃣</span>
                                    <span class="text-red-700 dark:text-red-300 font-mono text-xs">{{ $violation['reference_number'] }}</span>
                                </div>
                            @endif
                        </div>
                        
                        @if(isset($violation['description']))
                            <p class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $violation['description'] }}</p>
                        @endif
                        
                        @if(isset($violation['due_date']))
                            <div class="mt-3 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded">
                                <p class="text-xs text-yellow-800 dark:text-yellow-200 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('resources.due_date') }}: {{ \Carbon\Carbon::parse($violation['due_date'])->format('d M Y') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
        <svg class="mx-auto h-12 w-12 text-green-400 dark:text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('resources.no_violations') }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('resources.no_violations_description') }}</p>
    </div>
@endif