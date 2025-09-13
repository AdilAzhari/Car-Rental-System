<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gradient">RentVehicle Pro</h1>
                            <p class="text-xs text-gray-500">Premium Vehicle Rentals</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button @click="goToDashboard" class="text-gray-600 hover:text-blue-600 font-medium transition-colors flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Dashboard</span>
                        </button>
                        <button class="btn-primary text-sm px-4 py-2">Get Started</button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative bg-gradient-to-br from-blue-900 via-blue-800 to-purple-900 overflow-hidden">
            <div class="absolute inset-0 bg-black opacity-20"></div>
            <div class="absolute inset-0">
                <div class="absolute top-10 left-10 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
                <div class="absolute top-40 right-10 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
                <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center animate-fade-in">
                    <h2 class="text-5xl md:text-6xl font-extrabold text-white mb-6">
                        Find Your Perfect
                        <span class="block text-yellow-400">Rental Vehicle</span>
                    </h2>
                    <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                        Discover premium vehicles from trusted providers. Book instantly with our seamless rental experience.
                    </p>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-3xl mx-auto mb-12">
                        <div class="text-center animate-slide-up">
                            <div class="text-3xl font-bold text-yellow-400">1000+</div>
                            <div class="text-blue-100">Premium Vehicles</div>
                        </div>
                        <div class="text-center animate-slide-up" style="animation-delay: 0.1s">
                            <div class="text-3xl font-bold text-yellow-400">50+</div>
                            <div class="text-blue-100">Locations</div>
                        </div>
                        <div class="text-center animate-slide-up" style="animation-delay: 0.2s">
                            <div class="text-3xl font-bold text-yellow-400">24/7</div>
                            <div class="text-blue-100">Support</div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button @click="scrollToFilters" class="btn-primary text-lg px-8 py-4">
                            Explore Vehicles
                        </button>
                        <button class="btn-secondary text-lg px-8 py-4 bg-white bg-opacity-20 border-white text-white hover:bg-white hover:text-blue-900">
                            Learn More
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <!-- Filters Section -->
            <div id="filters" class="bg-white rounded-2xl shadow-xl p-8 mb-12 border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Find Your Perfect Vehicle</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <!-- Location Filter -->
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Location
                        </label>
                        <input
                            v-model="filters.location"
                            type="text"
                            placeholder="Enter city or area"
                            class="search-input"
                            @input="debouncedSearchVehicles"
                        >
                    </div>

                    <!-- Start Date -->
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Pick-up Date
                        </label>
                        <input
                            v-model="filters.start_date"
                            type="date"
                            :min="tomorrow"
                            class="search-input"
                            @change="handleDateChangeAndSearch"
                        >
                    </div>

                    <!-- End Date -->
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Return Date
                        </label>
                        <input
                            v-model="filters.end_date"
                            type="date"
                            :min="filters.start_date || tomorrow"
                            class="search-input"
                            @change="searchVehicles"
                        >
                    </div>

                    <!-- Price Range -->
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            Max Daily Rate
                        </label>
                        <select v-model="filters.price_max" class="filter-select" @change="searchVehicles">
                            <option value="">Any Budget</option>
                            <option value="50">Under $50/day</option>
                            <option value="100">Under $100/day</option>
                            <option value="200">Under $200/day</option>
                            <option value="500">Under $500/day</option>
                        </select>
                    </div>

                    <!-- Transmission -->
                    <div class="space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Transmission
                        </label>
                        <select v-model="filters.transmission" class="filter-select" @change="searchVehicles">
                            <option value="">Any Type</option>
                            <option value="automatic">Automatic</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                </div>

                <!-- Search Button -->
                <div class="mt-8 flex flex-col sm:flex-row gap-4">
                    <button
                        @click="searchVehicles"
                        :disabled="loading"
                        class="btn-primary flex-1 sm:flex-none inline-flex items-center justify-center"
                    >
                        <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span v-if="loading">Searching Vehicles...</span>
                        <span v-else>Search Available Vehicles</span>
                    </button>
                    <button @click="clearFilters" class="btn-secondary flex-1 sm:flex-none">
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Results Header -->
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-8 p-6 bg-white rounded-xl border border-gray-100">
                <div class="mb-4 lg:mb-0">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Available Vehicles</h3>
                    <p class="text-gray-600">
                        <span v-if="vehicles.length" class="font-semibold text-blue-600">{{ totalVehicles }}</span>
                        <span v-if="vehicles.length"> premium vehicles available</span>
                        <span v-else-if="!loading" class="text-amber-600">No vehicles match your criteria</span>
                    </p>
                </div>
                
                <!-- Sort Options -->
                <div class="flex items-center space-x-3">
                    <label class="text-sm font-medium text-gray-700">Sort by:</label>
                    <select
                        v-model="sortBy"
                        @change="sortVehicles"
                        class="filter-select text-sm"
                    >
                        <option value="price_low">Price: Low to High</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="rating">Best Rated</option>
                        <option value="newest">Newest First</option>
                    </select>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="n in 8" :key="n" class="bg-white rounded-lg shadow-md animate-pulse">
                    <div class="h-48 bg-gray-200 rounded-t-lg"></div>
                    <div class="p-4">
                        <div class="h-4 bg-gray-200 rounded mb-2"></div>
                        <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                        <div class="h-8 bg-gray-200 rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Cars Grid -->
            <div v-else-if="vehicles.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <VehicleCard
                    v-for="vehicle in vehicles"
                    :key="vehicle.id"
                    :vehicle="vehicle"
                    @view-details="goToVehicleDetails"
                />
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No vehicles found</h3>
                <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
            </div>

            <!-- Pagination -->
            <div v-if="pagination && pagination.last_page > 1" class="mt-8 flex justify-center">
                <nav class="flex items-center space-x-2">
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page <= 1"
                        class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400"
                    >
                        Previous
                    </button>
                    
                    <template v-for="page in visiblePages" :key="page">
                        <button
                            v-if="page !== '...'"
                            @click="changePage(page)"
                            :class="page === pagination.current_page 
                                ? 'bg-blue-600 text-white' 
                                : 'bg-white text-gray-700 hover:bg-gray-50'"
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium"
                        >
                            {{ page }}
                        </button>
                        <span v-else class="px-2 text-gray-500">...</span>
                    </template>
                    
                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page >= pagination.last_page"
                        class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:bg-gray-100 disabled:text-gray-400"
                    >
                        Next
                    </button>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import VehicleCard from '../Components/VehicleCard.vue'
import axios from 'axios'

// Reactive data
const vehicles = ref([])
const loading = ref(false)
const totalVehicles = ref(0)
const pagination = ref(null)
const sortBy = ref('price_low')

const filters = ref({
    location: '',
    start_date: '',
    end_date: '',
    price_min: '',
    price_max: '',
    transmission: ''
})

// Computed
const tomorrow = computed(() => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    return tomorrow.toISOString().split('T')[0]
})

const visiblePages = computed(() => {
    if (!pagination.value) return []
    
    const current = pagination.value.current_page
    const total = pagination.value.last_page
    const pages = []
    
    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            pages.push(i)
        }
    } else {
        pages.push(1)
        
        if (current > 4) {
            pages.push('...')
        }
        
        const start = Math.max(2, current - 2)
        const end = Math.min(total - 1, current + 2)
        
        for (let i = start; i <= end; i++) {
            pages.push(i)
        }
        
        if (current < total - 3) {
            pages.push('...')
        }
        
        if (total > 1) {
            pages.push(total)
        }
    }
    
    return pages
})

// Methods
let searchTimeout = null
const debouncedSearchVehicles = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        searchVehicles()
    }, 500)
}

const searchVehicles = async (page = 1) => {
    loading.value = true

    try {
        const params = new URLSearchParams()

        Object.entries(filters.value).forEach(([key, value]) => {
            if (value) params.append(key, value)
        })

        if (page > 1) params.append('page', page)

        const response = await axios.get(`/api/cars?${params}`)

        vehicles.value = response.data.data
        pagination.value = response.data.meta
        totalVehicles.value = response.data.meta?.total || vehicles.value.length

    } catch (error) {
        console.error('Error fetching vehicles:', error)
        vehicles.value = []
        totalVehicles.value = 0
    } finally {
        loading.value = false
    }
}

const handleDateChangeAndSearch = () => {
    if (filters.value.start_date && filters.value.end_date) {
        if (new Date(filters.value.end_date) <= new Date(filters.value.start_date)) {
            const startDate = new Date(filters.value.start_date)
            startDate.setDate(startDate.getDate() + 1)
            filters.value.end_date = startDate.toISOString().split('T')[0]
        }
    }
    searchVehicles()
}

const sortVehicles = () => {
    const sortedVehicles = [...vehicles.value]

    switch (sortBy.value) {
        case 'price_low':
            sortedVehicles.sort((a, b) => parseFloat(a.daily_rate) - parseFloat(b.daily_rate))
            break
        case 'price_high':
            sortedVehicles.sort((a, b) => parseFloat(b.daily_rate) - parseFloat(a.daily_rate))
            break
        case 'rating':
            sortedVehicles.sort((a, b) => (b.average_rating || 0) - (a.average_rating || 0))
            break
        case 'newest':
            sortedVehicles.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
            break
    }

    vehicles.value = sortedVehicles
}

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        searchVehicles(page)
    }
}

const goToVehicleDetails = (vehicle) => {
    router.visit(`/cars/${vehicle.id}`)
}

const goToDashboard = () => {
    router.visit('/dashboard')
}

const scrollToFilters = () => {
    document.getElementById('filters').scrollIntoView({ 
        behavior: 'smooth',
        block: 'start'
    })
}

const clearFilters = () => {
    filters.value = {
        location: '',
        start_date: '',
        end_date: '',
        price_min: '',
        price_max: '',
        transmission: ''
    }
    searchVehicles()
}

// Lifecycle
onMounted(() => {
    searchVehicles()
})
</script>