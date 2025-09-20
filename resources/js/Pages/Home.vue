<template>
  <AppLayout>
    <!-- Enhanced Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden">
      <!-- Background Carousel -->
      <div class="absolute inset-0 z-0">
        <ImageCarousel
          :images="heroImages"
          :autoplay="true"
          :autoplay-delay="6000"
          :infinite="true"
          :show-dots="false"
          :show-counter="false"
          :allow-fullscreen="false"
          class="w-full h-full"
        />
        <div class="absolute inset-0 bg-gradient-to-br from-black/50 via-black/30 to-black/60"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
      </div>

      <!-- Content -->
      <div class="relative z-10 container mx-auto px-4 text-center text-white">
        <div class="animate-fade-in-up">
          <h1 class="text-6xl md:text-8xl font-black mb-8 leading-tight">
            <span class="bg-gradient-to-r from-white via-blue-100 to-white bg-clip-text text-transparent">
              Premium Car Rental
            </span>
            <span class="block text-4xl md:text-6xl bg-gradient-to-r from-blue-400 via-cyan-400 to-blue-500 bg-clip-text text-transparent font-bold mt-4">
              Redefined
            </span>
          </h1>
          <p class="text-xl md:text-2xl mb-12 max-w-3xl mx-auto text-white/90 leading-relaxed">
            Experience luxury, comfort, and reliability with our premium fleet. Your perfect journey starts here.
          </p>
        </div>

        <!-- Search Card -->
        <div class="max-w-6xl mx-auto p-6 bg-white/95 backdrop-blur-sm shadow-premium rounded-lg mb-8">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Pickup Location
              </label>
              <select v-model="searchForm.location" class="premium-input w-full">
                <option value="">Select location</option>
                <option value="downtown">Downtown</option>
                <option value="airport">Airport</option>
                <option value="suburb">Suburb</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Pickup Date
              </label>
              <input
                v-model="searchForm.startDate"
                type="date"
                class="premium-input w-full"
                :min="today"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Return Date
              </label>
              <input
                v-model="searchForm.endDate"
                type="date"
                class="premium-input w-full"
                :min="searchForm.startDate || today"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                </svg>
                Vehicle Type
              </label>
              <select v-model="searchForm.vehicleType" class="premium-input w-full">
                <option value="">Any type</option>
                <option value="economy">Economy</option>
                <option value="standard">Standard</option>
                <option value="luxury">Luxury</option>
                <option value="suv">SUV</option>
              </select>
            </div>
          </div>

          <button
            @click="searchCars"
            class="hero-button w-full md:w-auto px-12 text-lg h-12 rounded-lg flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            Search Vehicles
          </button>
        </div>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            href="/cars"
            class="hero-button px-8 py-4 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105"
          >
            Browse All Vehicles
          </Link>
          <button
            @click="scrollToFeatures"
            class="bg-white/10 backdrop-blur-sm text-white border border-white/20 px-8 py-4 rounded-lg font-semibold hover:bg-white/20 transition-all duration-200"
          >
            Learn More
          </button>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section ref="featuresSection" class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-display font-bold text-secondary-900 mb-4">
            Why Choose CarRent?
          </h2>
          <p class="text-xl text-secondary-600 max-w-3xl mx-auto">
            We provide exceptional service and premium vehicles to make your journey unforgettable.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="(feature, index) in features"
            :key="index"
            class="text-center p-8 rounded-2xl bg-gradient-to-br from-white to-secondary-50 shadow-soft hover:shadow-medium transition-all duration-300 transform hover:-translate-y-2 animate-slide-up"
            :style="{ 'animation-delay': `${index * 0.1}s` }"
          >
            <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-6 transform rotate-3 hover:rotate-0 transition-transform duration-300">
              <component :is="feature.icon" class="w-8 h-8 text-white"/>
            </div>
            <h3 class="text-xl font-semibold text-secondary-900 mb-4">{{ feature.title }}</h3>
            <p class="text-secondary-600 leading-relaxed">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Popular Cars Section -->
    <section id="vehicles" class="py-20 bg-gray-50">
      <div class="container mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Popular Vehicles
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Discover our most loved vehicles, perfect for any occasion.
          </p>
        </div>

        <div v-if="popularCars.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <VehicleCard
            v-for="car in popularCars"
            :key="car.id"
            :vehicle="car"
            @view-details="(id) => $inertia.visit(`/cars/${id}`)"
            @reserve-now="(id) => $inertia.visit(`/cars/${id}`)"
            class="transform hover:scale-105 transition-all duration-300"
          />
        </div>

        <div v-else class="text-center py-12">
          <div class="animate-pulse">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              <div v-for="i in 6" :key="i" class="bg-white rounded-2xl shadow-soft p-6">
                <div class="h-48 bg-secondary-200 rounded-xl mb-4"></div>
                <div class="h-4 bg-secondary-200 rounded mb-2"></div>
                <div class="h-4 bg-secondary-200 rounded w-3/4 mb-4"></div>
                <div class="h-10 bg-secondary-200 rounded"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-12">
          <Link
            href="/cars"
            class="inline-flex items-center bg-gradient-to-r from-primary-500 to-primary-600 text-white px-8 py-4 rounded-xl font-semibold hover:from-primary-600 hover:to-primary-700 transition-all duration-200 transform hover:scale-105 shadow-colored"
          >
            View All Cars
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
          </Link>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 bg-secondary-900 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div
            v-for="(stat, index) in stats"
            :key="index"
            class="animate-slide-up"
            :style="{ 'animation-delay': `${index * 0.1}s` }"
          >
            <div class="text-4xl md:text-5xl font-bold text-accent-400 mb-2">{{ stat.value }}</div>
            <div class="text-primary-200 font-medium">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-primary-600 to-primary-700 text-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl md:text-5xl font-display font-bold mb-6">
          Ready to Hit the Road?
        </h2>
        <p class="text-xl text-primary-100 mb-8 max-w-2xl mx-auto">
          Join thousands of satisfied customers who trust CarRent for their transportation needs. Book your perfect car today!
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            href="/cars"
            class="bg-white text-primary-600 px-8 py-4 rounded-xl font-semibold hover:bg-primary-50 transition-all duration-200 transform hover:scale-105 shadow-soft"
          >
            Browse Cars Now
          </Link>
          <Link
            href="/register"
            class="bg-accent-500 text-white px-8 py-4 rounded-xl font-semibold hover:bg-accent-600 transition-all duration-200 transform hover:scale-105"
          >
            Create Account
          </Link>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ImageCarousel from '@/Components/ImageCarousel.vue'
import VehicleCard from '@/Components/VehicleCard.vue'

// Props
const props = defineProps({
  cars: {
    type: Array,
    default: () => []
  }
})

// Reactive data
const searchForm = ref({
  location: '',
  startDate: '',
  endDate: '',
  vehicleType: ''
})

const featuresSection = ref(null)

// Computed
const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const popularCars = computed(() => {
  return props.cars.slice(0, 6) || []
})

// Hero images for background carousel
const heroImages = [
  'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
  'https://images.unsplash.com/photo-1503376780353-7e6692767b70?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
  'https://images.unsplash.com/photo-1555215695-3004980ad54e?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
  'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
  'https://images.unsplash.com/photo-1583121274602-3e2820c69888?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80'
]

// Data
const features = [
  {
    title: 'Premium Fleet',
    description: 'Choose from our collection of luxury vehicles, all maintained to the highest standards for your comfort and safety.',
    icon: 'CarIcon'
  },
  {
    title: '24/7 Support',
    description: 'Our dedicated support team is available around the clock to assist you with any questions or concerns.',
    icon: 'SupportIcon'
  },
  {
    title: 'Best Prices',
    description: 'Competitive rates with transparent pricing. No hidden fees, just honest pricing for exceptional service.',
    icon: 'PriceIcon'
  }
]

const stats = [
  { value: '10K+', label: 'Happy Customers' },
  { value: '500+', label: 'Premium Cars' },
  { value: '50+', label: 'Locations' },
  { value: '24/7', label: 'Support' }
]

// Icon components
const CarIcon = {
  template: `
    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h3a1 1 0 011 1v2a1 1 0 01-1 1h-1v10a1 1 0 01-1 1H6a1 1 0 01-1-1V8H4a1 1 0 01-1-1V5a1 1 0 011-1h3z"/>
    </svg>
  `
}

const SupportIcon = {
  template: `
    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25A9.75 9.75 0 0121.75 12A9.75 9.75 0 0112 21.75 9.75 9.75 0 012.25 12A9.75 9.75 0 0112 2.25zm0 0V12m0 0h9.75"/>
    </svg>
  `
}

const PriceIcon = {
  template: `
    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
  `
}

// Methods
const searchCars = () => {
  const params = new URLSearchParams()
  if (searchForm.value.location) params.append('location', searchForm.value.location)
  if (searchForm.value.startDate) params.append('start_date', searchForm.value.startDate)
  if (searchForm.value.endDate) params.append('end_date', searchForm.value.endDate)
  if (searchForm.value.vehicleType) params.append('vehicle_type', searchForm.value.vehicleType)

  // Scroll to vehicles section
  const vehiclesSection = document.getElementById('vehicles')
  if (vehiclesSection) {
    vehiclesSection.scrollIntoView({ behavior: 'smooth' })
  }

  window.location.href = '/cars?' + params.toString()
}

const scrollToFeatures = () => {
  featuresSection.value?.scrollIntoView({ behavior: 'smooth' })
}

// Set default dates
onMounted(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  const dayAfter = new Date()
  dayAfter.setDate(dayAfter.getDate() + 2)

  searchForm.value.startDate = tomorrow.toISOString().split('T')[0]
  searchForm.value.endDate = dayAfter.toISOString().split('T')[0]
})
</script>

<style scoped>
@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fade-in-up 1s ease-out;
}

/* Enhanced card styles */
.vehicle-card {
  @apply bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2;
}

.card-hover:hover {
  @apply shadow-2xl;
}

/* Premium input styles */
.premium-input {
  @apply px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white;
}

/* Hero button styles */
.hero-button {
  @apply bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold transition-all duration-300 hover:shadow-lg hover:scale-105;
}

/* Shadow styles */
.shadow-premium {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.shadow-colored {
  box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.3);
}
</style>