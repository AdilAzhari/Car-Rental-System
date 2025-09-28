<template>
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Complete Payment</h1>
                <p class="text-gray-600 mt-2">Booking #{{ booking.id }}</p>
            </div>

            <!-- Booking Summary -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-2">Booking Summary</h3>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span>Vehicle:</span>
                        <span>{{ booking.vehicle?.make }} {{ booking.vehicle?.model }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Duration:</span>
                        <span>{{ booking.days }} days</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Dates:</span>
                        <span>{{ formatDate(booking.start_date) }} - {{ formatDate(booking.end_date) }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="flex justify-between font-semibold text-lg">
                        <span>Total:</span>
                        <span>{{ currencySymbol }}{{ booking.total_amount }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="space-y-4">
                <!-- Stripe Card Payment -->
                <div v-if="showStripeForm">
                    <h3 class="font-semibold mb-4">Pay with Card</h3>
                    <div id="card-element" class="p-3 border border-gray-300 rounded-lg bg-white">
                        <!-- Stripe Elements will be mounted here -->
                    </div>
                    <div id="card-errors" role="alert" class="text-red-600 text-sm mt-2"></div>

                    <button
                        @click="submitStripePayment"
                        :disabled="processing || !cardComplete"
                        class="w-full mt-4 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold py-3 px-4 rounded-lg transition-colors"
                    >
                        <span v-if="processing">Processing...</span>
                        <span v-else>Pay {{ currencySymbol }}{{ booking.total_amount }}</span>
                    </button>
                </div>

                <!-- Other Payment Methods -->
                <div class="space-y-2">
                    <button
                        @click="processPayment('tng')"
                        :disabled="processing"
                        class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold py-3 px-4 rounded-lg transition-colors"
                    >
                        Pay with Touch 'n Go
                    </button>

                    <button
                        @click="processPayment('cash')"
                        :disabled="processing"
                        class="w-full bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white font-semibold py-3 px-4 rounded-lg transition-colors"
                    >
                        Pay Cash on Pickup
                    </button>
                </div>
            </div>

            <!-- Back Button -->
            <div class="mt-6 text-center">
                <a :href="`/booking/${booking.id}`" class="text-blue-600 hover:text-blue-800">
                    ← Back to Booking Details
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    booking: Object,
    stripe_key: String
})

// Reactive state
const processing = ref(false)
const showStripeForm = ref(true)
const cardComplete = ref(false)
const stripe = ref(null)
const elements = ref(null)
const cardElement = ref(null)

// Computed
const currencySymbol = computed(() => {
    // You can customize this based on your app's currency config
    return 'RM '
})

// Methods
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString()
}

const initializeStripe = async () => {
    try {
        // Load Stripe.js
        if (!window.Stripe) {
            const script = document.createElement('script')
            script.src = 'https://js.stripe.com/v3/'
            script.onload = () => {
                setupStripeElements()
            }
            document.head.appendChild(script)
        } else {
            setupStripeElements()
        }
    } catch (error) {
        console.error('Failed to load Stripe:', error)
        showStripeForm.value = false
    }
}

const setupStripeElements = () => {
    if (!props.stripe_key) {
        console.error('Stripe key not provided')
        showStripeForm.value = false
        return
    }

    stripe.value = window.Stripe(props.stripe_key)
    elements.value = stripe.value.elements()

    // Create card element
    cardElement.value = elements.value.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#424770',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
        },
    })

    cardElement.value.mount('#card-element')

    // Listen for changes
    cardElement.value.on('change', (event) => {
        const displayError = document.getElementById('card-errors')
        if (event.error) {
            displayError.textContent = event.error.message
            cardComplete.value = false
        } else {
            displayError.textContent = ''
            cardComplete.value = event.complete
        }
    })
}

const submitStripePayment = async () => {
    if (!stripe.value || !cardElement.value || processing.value) return

    processing.value = true

    try {
        // Create payment intent
        const intentResponse = await fetch('/api/payments/intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`
            },
            body: JSON.stringify({
                booking_id: props.booking.id
            })
        })

        const intentData = await intentResponse.json()

        if (!intentData.success) {
            throw new Error(intentData.message || 'Failed to create payment intent')
        }

        // Confirm payment with Stripe
        const { error, paymentIntent } = await stripe.value.confirmCardPayment(
            intentData.client_secret,
            {
                payment_method: {
                    card: cardElement.value,
                }
            }
        )

        if (error) {
            throw new Error(error.message)
        }

        // Payment succeeded
        if (paymentIntent.status === 'succeeded') {
            // Redirect to success page
            router.visit(`/booking/payment/return/${props.booking.id}?payment_intent=${paymentIntent.id}&status=succeeded`)
        }

    } catch (error) {
        console.error('Payment failed:', error)
        const errorElement = document.getElementById('card-errors')
        if (errorElement) {
            errorElement.textContent = error.message
        }
    } finally {
        processing.value = false
    }
}

const processPayment = async (method) => {
    processing.value = true

    try {
        const response = await fetch('/api/payments/process', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Authorization': `Bearer ${localStorage.getItem('auth_token') || ''}`
            },
            body: JSON.stringify({
                booking_id: props.booking.id,
                payment_method: method
            })
        })

        const data = await response.json()

        if (data.success) {
            // Handle different payment methods
            if (method === 'tng' && data.payment_url) {
                // Redirect to Touch 'n Go payment page
                window.location.href = data.payment_url
            } else if (method === 'cash') {
                // Show success message for cash payment
                router.visit(`/booking/payment/return/${props.booking.id}?method=cash&status=pending`)
            }
        } else {
            throw new Error(data.message || 'Payment processing failed')
        }

    } catch (error) {
        console.error('Payment failed:', error)
        alert('Payment failed: ' + error.message)
    } finally {
        processing.value = false
    }
}

// Initialize Stripe when component mounts
onMounted(() => {
    initializeStripe()
})
</script>

<style scoped>
#card-element {
    min-height: 40px;
}
</style>