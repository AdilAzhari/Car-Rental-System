<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <button @click="goBack" class="flex items-center text-gray-600 hover:text-gray-900">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </button>
                    <h1 class="text-2xl font-bold text-gray-900">Complete Your Reservation</h1>
                    <div class="flex items-center space-x-4">
                        <button class="text-gray-600 hover:text-gray-900">Sign In</button>
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Sign Up</button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Loading State -->
        <div v-if="loading" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="animate-pulse space-y-6">
                <div class="h-64 bg-gray-200 rounded-lg"></div>
                <div class="h-48 bg-gray-200 rounded-lg"></div>
                <div class="h-32 bg-gray-200 rounded-lg"></div>
            </div>
        </div>

        <!-- Reservation Form -->
        <div v-else-if="car" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Car Summary -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Car Details</h2>
                        <div class="flex space-x-4">
                            <div class="w-24 h-16 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                                <img 
                                    v-if="car.featured_image"
                                    :src="car.featured_image" 
                                    :alt="`${car.make} ${car.model}`"
                                    class="w-full h-full object-cover"
                                >
                            </div>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900">{{ car.make }} {{ car.model }}</h3>
                                <p class="text-gray-600 text-sm">{{ car.year }} • {{ car.transmission }} • {{ car.seats }} seats</p>
                                <p class="text-gray-600 text-sm flex items-center mt-1">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ car.pickup_location || car.location }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details Form -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Booking Details</h2>
                        
                        <form @submit.prevent="submitReservation" class="space-y-4">
                            <!-- Dates -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        :min="tomorrow"
                                        required
                                        :class="{'border-red-300': errors.start_date}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        @change="calculateTotal"
                                    >
                                    <p v-if="errors.start_date" class="mt-1 text-sm text-red-600">{{ errors.start_date }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                    <input
                                        v-model="form.end_date"
                                        type="date"
                                        :min="form.start_date || tomorrow"
                                        required
                                        :class="{'border-red-300': errors.end_date}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        @change="calculateTotal"
                                    >
                                    <p v-if="errors.end_date" class="mt-1 text-sm text-red-600">{{ errors.end_date }}</p>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Payment Method</label>
                                <div class="space-y-3">
                                    <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50" :class="{'bg-blue-50 border-blue-500': form.payment_method === 'visa'}">
                                        <input
                                            v-model="form.payment_method"
                                            type="radio"
                                            value="visa"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                        >
                                        <div class="ml-3 flex items-center justify-between w-full">
                                            <div class="flex items-center">
                                                <svg class="w-8 h-6 mr-3" viewBox="0 0 48 32">
                                                    <rect width="48" height="32" rx="4" fill="#1A1F71"/>
                                                    <path d="M18.5 11h-3.2l-2 10h3.2l2-10zm7.4 6.5c0-1.5-1.9-2.3-3-2.8-.7-.3-1.1-.5-1.1-.8 0-.4.4-.8 1.3-.8.7 0 1.2.2 1.6.3l.3-1.8c-.4-.1-1-.3-1.8-.3-1.9 0-3.3 1-3.3 2.4 0 1 .9 1.6 1.6 2 .7.4 1 .6 1 1s-.4.7-1.1.7c-.9 0-1.4-.3-1.8-.4l-.3 1.8c.4.2 1.2.3 2 .3 2.1-.1 3.6-1 3.6-2.5zm5.9-6.4c-.4 0-.8.2-1 .6l-3.6 8.8h2l.4-1.1h2.4l.2 1.1h1.8l-1.6-10h-1.6zm.3 7.1l1-2.7.6 2.7h-1.6zm-10.6-7.1l-1.9 6.8-.2-1c-.3-1-.6-2.1-1.9-2.6-.6-.2-1.4-.4-2.2-.4h-.1l.1.2c0 0 .8.2 1.6.6 1.2.7 1.4 1.6 1.8 3.1l1.2 4.3h2l3.1-10h-1.5z" fill="white"/>
                                                </svg>
                                                <div>
                                                    <div class="font-medium text-gray-900">Visa</div>
                                                    <div class="text-sm text-gray-600">Instant confirmation</div>
                                                </div>
                                            </div>
                                            <div class="text-sm text-green-600 font-medium">Recommended</div>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50" :class="{'bg-blue-50 border-blue-500': form.payment_method === 'credit'}">
                                        <input
                                            v-model="form.payment_method"
                                            type="radio"
                                            value="credit"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                        >
                                        <div class="ml-3 flex items-center justify-between w-full">
                                            <div class="flex items-center">
                                                <svg class="w-8 h-6 mr-3" viewBox="0 0 48 32">
                                                    <rect width="48" height="32" rx="4" fill="#EB001B"/>
                                                    <rect x="16" y="0" width="16" height="32" fill="#FF5F00"/>
                                                    <circle cx="24" cy="16" r="12" fill="#FF5F00"/>
                                                    <circle cx="16" cy="16" r="12" fill="#EB001B"/>
                                                    <circle cx="32" cy="16" r="12" fill="#F79E1B"/>
                                                </svg>
                                                <div>
                                                    <div class="font-medium text-gray-900">Credit Card</div>
                                                    <div class="text-sm text-gray-600">Instant confirmation</div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50" :class="{'bg-blue-50 border-blue-500': form.payment_method === 'cash'}">
                                        <input
                                            v-model="form.payment_method"
                                            type="radio"
                                            value="cash"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                        >
                                        <div class="ml-3 flex items-center justify-between w-full">
                                            <div class="flex items-center">
                                                <svg class="w-8 h-6 mr-3 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                </svg>
                                                <div>
                                                    <div class="font-medium text-gray-900">Cash Payment</div>
                                                    <div class="text-sm text-gray-600">Pay at pickup - Pending confirmation</div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="errors.payment_method" class="mt-1 text-sm text-red-600">{{ errors.payment_method }}</p>
                            </div>

                            <!-- Special Requests -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Special Requests (Optional)</label>
                                <textarea
                                    v-model="form.special_requests"
                                    rows="3"
                                    placeholder="Any special requests or notes for the car owner..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                ></textarea>
                            </div>

                            <!-- Terms -->
                            <div class="flex items-start">
                                <input
                                    v-model="form.agree_terms"
                                    type="checkbox"
                                    required
                                    class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                                <label class="ml-3 text-sm text-gray-700">
                                    I agree to the <a href="#" class="text-blue-600 hover:text-blue-500">Terms of Service</a> and <a href="#" class="text-blue-600 hover:text-blue-500">Privacy Policy</a>, and confirm that all information provided is accurate.
                                </label>
                            </div>
                            <p v-if="errors.agree_terms" class="text-sm text-red-600">{{ errors.agree_terms }}</p>
                        </form>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Booking Summary</h2>
                        
                        <!-- Price Breakdown -->
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Daily Rate</span>
                                <span class="font-medium">${{ car.daily_rate }}</span>
                            </div>
                            
                            <div v-if="bookingDays > 0" class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ bookingDays }} days</span>
                                <span class="font-medium">${{ (car.daily_rate * bookingDays).toFixed(2) }}</span>
                            </div>

                            <!-- Show payment method status -->
                            <div v-if="form.payment_method === 'cash'" class="bg-yellow-50 p-3 rounded-md">
                                <div class="flex">
                                    <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="text-sm">
                                        <p class="text-yellow-800 font-medium">Cash Payment</p>
                                        <p class="text-yellow-700">Your booking will be pending until the owner confirms.</p>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="form.payment_method === 'visa' || form.payment_method === 'credit'" class="bg-green-50 p-3 rounded-md">
                                <div class="flex">
                                    <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="text-sm">
                                        <p class="text-green-800 font-medium">Instant Confirmation</p>
                                        <p class="text-green-700">Your booking will be confirmed immediately.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="border-t border-gray-200 pt-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-gray-900">
                                    ${{ totalPrice.toFixed(2) }}
                                </span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button
                            @click="submitReservation"
                            :disabled="submitting || !canSubmit"
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white py-3 px-4 rounded-lg font-medium transition-colors duration-200"
                        >
                            <span v-if="submitting">Processing...</span>
                            <span v-else-if="form.payment_method === 'cash'">Request Booking</span>
                            <span v-else>Confirm & Book</span>
                        </button>

                        <p class="text-xs text-gray-500 mt-2 text-center">
                            <span v-if="form.payment_method === 'cash'">No payment required now</span>
                            <span v-else>You'll be charged immediately</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div v-else class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Unable to load car details</h3>
            <p class="mt-1 text-sm text-gray-500">Please try again or contact support if the problem persists.</p>
        </div>

        <!-- Success Modal -->
        <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg p-6 max-w-md mx-4">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Booking {{ bookingResult?.status === 'confirmed' ? 'Confirmed' : 'Requested' }}!</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ bookingResult?.status === 'confirmed' 
                            ? 'Your car reservation has been confirmed. You\'ll receive a confirmation email shortly.' 
                            : 'Your booking request has been sent to the car owner. You\'ll be notified once it\'s confirmed.' }}
                    </p>
                    <div class="bg-gray-50 rounded-lg p-3 mb-4 text-left">
                        <p class="text-sm"><strong>Booking ID:</strong> #{{ bookingResult?.id }}</p>
                        <p class="text-sm"><strong>Car:</strong> {{ car?.make }} {{ car?.model }}</p>
                        <p class="text-sm"><strong>Dates:</strong> {{ form.start_date }} to {{ form.end_date }}</p>
                        <p class="text-sm"><strong>Total:</strong> ${{ totalPrice.toFixed(2) }}</p>
                    </div>
                    <button 
                        @click="goToBookings"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium"
                    >
                        View My Bookings
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

// Reactive data
const car = ref(null)
const loading = ref(true)
const submitting = ref(false)
const showSuccessModal = ref(false)
const bookingResult = ref(null)
const errors = ref({})

const form = ref({
    car_id: null,
    start_date: '',
    end_date: '',
    payment_method: 'visa',
    special_requests: '',
    agree_terms: false
})

// Computed
const tomorrow = computed(() => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    return tomorrow.toISOString().split('T')[0]
})

const bookingDays = computed(() => {
    if (!form.value.start_date || !form.value.end_date) return 0
    const start = new Date(form.value.start_date)
    const end = new Date(form.value.end_date)
    return Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1
})

const totalPrice = computed(() => {
    if (!car.value || bookingDays.value === 0) return 0
    return car.value.daily_rate * bookingDays.value
})

const canSubmit = computed(() => {
    return form.value.car_id &&
           form.value.start_date &&
           form.value.end_date &&
           form.value.payment_method &&
           form.value.agree_terms &&
           bookingDays.value > 0
})

// Methods
const fetchCar = async (carId) => {
    loading.value = true
    
    try {
        const response = await axios.get(`/api/cars/${carId}`)
        car.value = response.data.data
        form.value.car_id = car.value.id
    } catch (error) {
        console.error('Error fetching car:', error)
    } finally {
        loading.value = false
    }
}

const calculateTotal = () => {
    if (form.value.start_date && form.value.end_date) {
        if (new Date(form.value.end_date) <= new Date(form.value.start_date)) {
            const startDate = new Date(form.value.start_date)
            startDate.setDate(startDate.getDate() + 1)
            form.value.end_date = startDate.toISOString().split('T')[0]
        }
    }
}

const submitReservation = async () => {
    if (!canSubmit.value || submitting.value) return
    
    submitting.value = true
    errors.value = {}
    
    try {
        const response = await axios.post('/api/bookings', {
            car_id: form.value.car_id,
            start_date: form.value.start_date,
            end_date: form.value.end_date,
            payment_method: form.value.payment_method,
            special_requests: form.value.special_requests
        })
        
        bookingResult.value = response.data.data
        showSuccessModal.value = true
        
    } catch (error) {
        console.error('Booking error:', error)
        
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else if (error.response?.data?.message) {
            errors.value = { general: error.response.data.message }
        } else {
            errors.value = { general: 'An error occurred while processing your booking. Please try again.' }
        }
    } finally {
        submitting.value = false
    }
}

const goBack = () => {
    if (car.value) {
        router.visit(`/cars/${car.value.id}`)
    } else {
        router.visit('/cars')
    }
}

const goToBookings = () => {
    router.visit('/my-bookings')
}

// Lifecycle
onMounted(() => {
    // Get car ID from URL parameters
    const urlParams = new URLSearchParams(window.location.search)
    const carId = urlParams.get('car_id')
    const startDate = urlParams.get('start_date')
    const endDate = urlParams.get('end_date')
    
    if (!carId) {
        router.visit('/cars')
        return
    }
    
    // Pre-fill form with URL parameters
    if (startDate) form.value.start_date = startDate
    if (endDate) form.value.end_date = endDate
    
    fetchCar(carId)
})
</script>