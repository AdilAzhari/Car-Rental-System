<template>
  <div class="min-h-screen bg-background">
    <!-- Navigation -->
    <nav class="bg-background/95 backdrop-blur-sm border-b border-border sticky top-0 z-40">
      <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <Link href="/" class="flex items-center space-x-2 group">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center transform group-hover:scale-105 transition-all duration-200">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
              </svg>
            </div>
            <span class="text-xl font-bold text-primary">
              RentLux
            </span>
          </Link>

          <!-- Desktop Navigation -->
          <div class="hidden md:flex items-center space-x-8">
            <Link
              href="/"
              class="text-foreground hover:text-primary transition-fast font-medium"
              :class="{ 'text-primary': $page.url === '/' }"
            >
              Home
            </Link>
            <Link
              href="/cars"
              class="text-foreground hover:text-primary transition-fast font-medium"
              :class="{ 'text-primary': $page.url.startsWith('/cars') }"
            >
              Vehicles
            </Link>
            <template v-if="$page.props.auth?.user">
              <Link
                href="/my-bookings"
                class="text-foreground hover:text-primary transition-fast font-medium"
                :class="{ 'text-primary': $page.url.startsWith('/my-bookings') }"
              >
                My Bookings
              </Link>
              <Link
                href="/dashboard"
                class="text-foreground hover:text-primary transition-fast font-medium"
                :class="{ 'text-primary': $page.url.startsWith('/dashboard') }"
              >
                Dashboard
              </Link>
            </template>
            <a href="#contact" class="text-foreground hover:text-primary transition-fast font-medium">
              Contact
            </a>
          </div>

          <!-- User Menu -->
          <div class="flex items-center space-x-3">
            <template v-if="$page.props.auth?.user">
              <!-- Phone Number -->
              <div class="hidden md:flex items-center gap-2 text-sm text-muted-foreground">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <span>(555) 123-4567</span>
              </div>

              <!-- User Dropdown -->
              <div class="relative" ref="userDropdown">
                <button
                  @click="showUserMenu = !showUserMenu"
                  class="flex items-center space-x-3 text-foreground hover:text-primary transition-colors duration-200"
                >
                  <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-white">
                      {{ $page.props.auth?.user?.name?.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <span class="hidden sm:block font-medium">{{ $page.props.auth?.user?.name }}</span>
                  <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showUserMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </button>

                <!-- Dropdown Menu -->
                <transition name="dropdown">
                  <div
                    v-if="showUserMenu"
                    class="absolute right-0 mt-2 w-48 bg-card rounded-lg border border-border shadow-premium py-2 z-50"
                  >
                    <Link
                      href="/dashboard"
                      class="block px-4 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground transition-colors duration-200"
                    >
                      Dashboard
                    </Link>
                    <Link
                      href="/my-bookings"
                      class="block px-4 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground transition-colors duration-200"
                    >
                      My Bookings
                    </Link>
                    <hr class="my-2 border-border">
                    <Link
                      href="/logout"
                      method="post"
                      class="block px-4 py-2 text-sm text-destructive hover:bg-destructive/10 transition-colors duration-200"
                    >
                      Sign Out
                    </Link>
                  </div>
                </transition>
              </div>
            </template>
            <template v-else>
              <Link
                href="/login"
                class="text-foreground hover:text-primary font-medium transition-colors duration-200"
              >
                Sign In
              </Link>
              <Link
                href="/register"
                class="hero-button px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105"
              >
                Get Started
              </Link>
            </template>

            <!-- Mobile Menu Button -->
            <button
              @click="showMobileMenu = !showMobileMenu"
              class="md:hidden p-2 text-foreground hover:text-primary transition-colors duration-200"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="!showMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Menu -->
      <transition name="mobile-menu">
        <div v-if="showMobileMenu" class="md:hidden bg-background border-t border-border">
          <div class="px-4 py-4 space-y-3">
            <Link
              href="/"
              class="block text-foreground hover:text-primary transition-fast font-medium py-2"
              @click="showMobileMenu = false"
            >
              Home
            </Link>
            <Link
              href="/cars"
              class="block text-foreground hover:text-primary transition-fast font-medium py-2"
              @click="showMobileMenu = false"
            >
              Vehicles
            </Link>
            <template v-if="$page.props.auth?.user">
              <Link
                href="/my-bookings"
                class="block text-foreground hover:text-primary transition-fast font-medium py-2"
                @click="showMobileMenu = false"
              >
                My Bookings
              </Link>
              <Link
                href="/dashboard"
                class="block text-foreground hover:text-primary transition-fast font-medium py-2"
                @click="showMobileMenu = false"
              >
                Dashboard
              </Link>
              <a
                href="#contact"
                class="block text-foreground hover:text-primary transition-fast font-medium py-2"
                @click="showMobileMenu = false"
              >
                Contact
              </a>

              <div class="pt-3 border-t border-border space-y-3">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                  <span>(555) 123-4567</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                  <span>info@rentlux.com</span>
                </div>
                <Link
                  href="/logout"
                  method="post"
                  class="block w-full text-center py-2 text-destructive hover:bg-destructive/10 rounded-lg transition-colors duration-200"
                  @click="showMobileMenu = false"
                >
                  Sign Out
                </Link>
              </div>
            </template>
            <template v-else>
              <a
                href="#contact"
                class="block text-foreground hover:text-primary transition-fast font-medium py-2"
                @click="showMobileMenu = false"
              >
                Contact
              </a>

              <div class="pt-3 border-t border-border space-y-3">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                  <span>(555) 123-4567</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                  <span>info@rentlux.com</span>
                </div>
                <Link
                  href="/login"
                  class="block text-foreground hover:text-primary transition-fast font-medium py-2"
                  @click="showMobileMenu = false"
                >
                  Sign In
                </Link>
                <Link
                  href="/register"
                  class="block w-full text-center hero-button py-2 rounded-lg transition-colors duration-200"
                  @click="showMobileMenu = false"
                >
                  Get Started
                </Link>
              </div>
            </template>
          </div>
        </div>
      </transition>
    </nav>

    <!-- Main Content -->
    <main class="pt-16">
      <slot />
    </main>

    <!-- Footer -->
    <footer id="contact" class="bg-accent text-accent-foreground mt-20">
      <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Brand -->
          <div class="col-span-1 md:col-span-2">
            <div class="flex items-center space-x-2 mb-4">
              <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                </svg>
              </div>
              <span class="text-xl font-bold">RentLux</span>
            </div>
            <p class="text-muted-foreground max-w-md">
              Premium car rental service with the best vehicles and unmatched customer experience. Your journey begins here.
            </p>
          </div>

          <!-- Quick Links -->
          <div>
            <h4 class="font-semibold mb-4">Quick Links</h4>
            <ul class="space-y-2">
              <li><Link href="/" class="text-muted-foreground hover:text-accent-foreground transition-colors duration-200">Home</Link></li>
              <li><Link href="/cars" class="text-muted-foreground hover:text-accent-foreground transition-colors duration-200">Vehicles</Link></li>
              <li><a href="#locations" class="text-muted-foreground hover:text-accent-foreground transition-colors duration-200">Locations</a></li>
              <li><a href="#contact" class="text-muted-foreground hover:text-accent-foreground transition-colors duration-200">Contact</a></li>
            </ul>
          </div>

          <!-- Contact -->
          <div>
            <h4 class="font-semibold mb-4">Contact</h4>
            <ul class="space-y-2 text-muted-foreground">
              <li class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                (555) 123-4567
              </li>
              <li class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                info@rentlux.com
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>123 Rental Street<br>City, State 12345</span>
              </li>
            </ul>
          </div>
        </div>

        <div class="border-t border-border mt-8 pt-8 text-center text-muted-foreground">
          <p>&copy; 2025 RentLux. All rights reserved.</p>
        </div>
      </div>
    </footer>

    <!-- Global Loading Overlay -->
    <div v-if="$page.props.loading" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center">
      <div class="bg-white rounded-xl p-6 shadow-large">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
          <span class="text-secondary-700">Loading...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'

const showUserMenu = ref(false)
const showMobileMenu = ref(false)
const userDropdown = ref(null)

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (userDropdown.value && !userDropdown.value.contains(event.target)) {
    showUserMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease-out;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.95);
}

.mobile-menu-enter-active,
.mobile-menu-leave-active {
  transition: all 0.3s ease-out;
}

.mobile-menu-enter-from,
.mobile-menu-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>