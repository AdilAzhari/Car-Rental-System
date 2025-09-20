/**
 * @jest-environment jsdom
 */

import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, jest } from '@jest/globals'
import CarListingPage from '../../../resources/js/Pages/Cars/Index.vue'
import VehicleCard from '../../../resources/js/Components/VehicleCard.vue'

// Mock router
const mockRouter = {
  get: jest.fn(),
  post: jest.fn(),
  visit: jest.fn()
}

// Mock axios
jest.mock('axios', () => ({
  get: jest.fn(() => Promise.resolve({
    data: {
      data: [],
      links: {},
      meta: { total: 0 }
    }
  }))
}))

// Mock Inertia
const mockInertia = {
  get: jest.fn(),
  visit: jest.fn(),
  reload: jest.fn()
}

describe('CarListingPage Component', () => {
  let wrapper

  const mockProps = {
    cars: {
      data: [
        {
          id: 1,
          make: 'Toyota',
          model: 'Camry',
          year: 2023,
          daily_rate: 100,
          transmission: 'automatic',
          seats: 5,
          fuel_type: 'petrol',
          is_available: true,
          featured_image: 'https://example.com/toyota.jpg',
          gallery_images: ['image1.jpg', 'image2.jpg'],
          images: []
        },
        {
          id: 2,
          make: 'Honda',
          model: 'Civic',
          year: 2022,
          daily_rate: 80,
          transmission: 'manual',
          seats: 5,
          fuel_type: 'hybrid',
          is_available: true,
          featured_image: 'https://example.com/honda.jpg',
          gallery_images: [],
          images: [{ image_path: 'honda-detail.jpg' }]
        }
      ],
      links: {
        first: '/cars?page=1',
        last: '/cars?page=1',
        prev: null,
        next: null
      },
      meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        per_page: 12,
        to: 2,
        total: 2
      }
    },
    filters: {
      search: '',
      transmission: '',
      fuel_type: '',
      seats: '',
      price_min: '',
      price_max: '',
      sort_by: 'created_at',
      sort_direction: 'desc'
    }
  }

  beforeEach(() => {
    wrapper = mount(CarListingPage, {
      props: mockProps,
      global: {
        mocks: {
          $inertia: mockInertia,
          route: jest.fn(() => '/cars')
        },
        components: {
          VehicleCard
        },
        stubs: {
          Head: true,
          Link: {
            template: '<a><slot /></a>',
            props: ['href']
          }
        }
      }
    })
  })

  it('renders car listing page correctly', () => {
    expect(wrapper.find('h1').text()).toContain('Available Cars')
    expect(wrapper.findAllComponents(VehicleCard)).toHaveLength(2)
  })

  it('displays search and filter section', () => {
    expect(wrapper.find('[data-testid="search-input"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="transmission-filter"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="fuel-type-filter"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="seats-filter"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="price-range-filter"]').exists()).toBe(true)
  })

  it('handles search input correctly', async () => {
    const searchInput = wrapper.find('[data-testid="search-input"]')
    await searchInput.setValue('Toyota')

    expect(wrapper.vm.form.search).toBe('Toyota')
  })

  it('handles transmission filter change', async () => {
    const transmissionSelect = wrapper.find('[data-testid="transmission-filter"]')
    await transmissionSelect.setValue('automatic')

    expect(wrapper.vm.form.transmission).toBe('automatic')
  })

  it('handles price range filters', async () => {
    const priceMinInput = wrapper.find('[data-testid="price-min-input"]')
    const priceMaxInput = wrapper.find('[data-testid="price-max-input"]')

    await priceMinInput.setValue('50')
    await priceMaxInput.setValue('150')

    expect(wrapper.vm.form.price_min).toBe('50')
    expect(wrapper.vm.form.price_max).toBe('150')
  })

  it('applies filters when filter button is clicked', async () => {
    const filterButton = wrapper.find('[data-testid="apply-filters-button"]')

    // Set some filter values
    await wrapper.find('[data-testid="search-input"]').setValue('Toyota')
    await wrapper.find('[data-testid="transmission-filter"]').setValue('automatic')

    await filterButton.trigger('click')

    expect(mockInertia.get).toHaveBeenCalledWith('/cars', expect.objectContaining({
      search: 'Toyota',
      transmission: 'automatic'
    }))
  })

  it('clears filters when clear button is clicked', async () => {
    // Set some filter values first
    wrapper.vm.form.search = 'Toyota'
    wrapper.vm.form.transmission = 'automatic'
    wrapper.vm.form.price_min = '50'

    const clearButton = wrapper.find('[data-testid="clear-filters-button"]')
    await clearButton.trigger('click')

    expect(wrapper.vm.form.search).toBe('')
    expect(wrapper.vm.form.transmission).toBe('')
    expect(wrapper.vm.form.price_min).toBe('')
  })

  it('handles sorting correctly', async () => {
    const sortSelect = wrapper.find('[data-testid="sort-select"]')
    await sortSelect.setValue('price_asc')

    expect(wrapper.vm.form.sort_by).toBe('daily_rate')
    expect(wrapper.vm.form.sort_direction).toBe('asc')
  })

  it('displays correct number of results', () => {
    const resultsText = wrapper.find('[data-testid="results-count"]')
    expect(resultsText.text()).toContain('2 cars found')
  })

  it('shows no results message when no cars available', async () => {
    await wrapper.setProps({
      cars: {
        data: [],
        links: {},
        meta: { total: 0 }
      }
    })

    expect(wrapper.find('[data-testid="no-results"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('No cars found')
  })

  it('handles pagination correctly', () => {
    const paginationLinks = wrapper.find('[data-testid="pagination"]')
    expect(paginationLinks.exists()).toBe(true)

    // Should show current page info
    expect(wrapper.text()).toContain('1 to 2 of 2 results')
  })

  it('handles vehicle card events', async () => {
    const vehicleCard = wrapper.findComponent(VehicleCard)

    // Test view details event
    await vehicleCard.vm.$emit('view-details', 1)
    expect(mockInertia.visit).toHaveBeenCalledWith('/cars/1')

    // Test reserve now event
    await vehicleCard.vm.$emit('reserve-now', 1)
    expect(mockInertia.visit).toHaveBeenCalledWith('/cars/1/reserve')
  })

  it('displays filter badges for active filters', async () => {
    await wrapper.setProps({
      filters: {
        ...mockProps.filters,
        search: 'Toyota',
        transmission: 'automatic',
        price_min: '50'
      }
    })

    const filterBadges = wrapper.findAll('[data-testid="filter-badge"]')
    expect(filterBadges.length).toBeGreaterThan(0)
    expect(wrapper.text()).toContain('Toyota')
    expect(wrapper.text()).toContain('automatic')
  })

  it('removes individual filter badges', async () => {
    await wrapper.setProps({
      filters: {
        ...mockProps.filters,
        search: 'Toyota'
      }
    })

    const removeBadgeButton = wrapper.find('[data-testid="remove-filter-badge"]')
    await removeBadgeButton.trigger('click')

    expect(mockInertia.get).toHaveBeenCalledWith('/cars', expect.objectContaining({
      search: ''
    }))
  })

  describe('Responsive behavior', () => {
    it('shows mobile filter toggle on small screens', async () => {
      // Simulate mobile viewport
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: 375
      })

      const mobileFilterToggle = wrapper.find('[data-testid="mobile-filter-toggle"]')
      expect(mobileFilterToggle.exists()).toBe(true)
    })

    it('adjusts grid layout for different screen sizes', () => {
      const carGrid = wrapper.find('[data-testid="car-grid"]')
      expect(carGrid.classes()).toContain('grid')
      expect(carGrid.classes()).toContain('md:grid-cols-2')
      expect(carGrid.classes()).toContain('lg:grid-cols-3')
    })
  })

  describe('Performance optimizations', () => {
    it('implements debounced search', async () => {
      jest.useFakeTimers()

      const searchInput = wrapper.find('[data-testid="search-input"]')
      await searchInput.setValue('T')
      await searchInput.setValue('To')
      await searchInput.setValue('Toy')
      await searchInput.setValue('Toyota')

      // Should not trigger search immediately
      expect(mockInertia.get).not.toHaveBeenCalled()

      // Fast forward time
      jest.advanceTimersByTime(500)

      // Should trigger search after debounce delay
      expect(mockInertia.get).toHaveBeenCalledWith('/cars', expect.objectContaining({
        search: 'Toyota'
      }))

      jest.useRealTimers()
    })

    it('handles loading states correctly', async () => {
      wrapper.vm.loading = true
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="apply-filters-button"]').attributes('disabled')).toBeDefined()
    })
  })
})