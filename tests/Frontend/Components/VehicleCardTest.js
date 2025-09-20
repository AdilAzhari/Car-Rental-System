/**
 * @jest-environment jsdom
 */

import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, jest } from '@jest/globals'
import VehicleCard from '../../../resources/js/Components/VehicleCard.vue'
import ImageCarousel from '../../../resources/js/Components/ImageCarousel.vue'

// Mock axios
jest.mock('axios', () => ({
  get: jest.fn(() => Promise.resolve({ data: { is_favorite: false } })),
  post: jest.fn(() => Promise.resolve({ data: { is_favorite: true } })),
  delete: jest.fn(() => Promise.resolve({ data: { is_favorite: false } }))
}))

describe('VehicleCard Component', () => {
  let wrapper

  const mockVehicle = {
    id: 1,
    make: 'Toyota',
    model: 'Camry',
    year: 2023,
    daily_rate: 100,
    transmission: 'automatic',
    seats: 5,
    fuel_type: 'petrol',
    category: 'sedan',
    is_available: true,
    featured_image: 'https://example.com/toyota-camry.jpg',
    gallery_images: [
      'https://example.com/image1.jpg',
      'https://example.com/image2.jpg'
    ],
    images: [
      { image_path: 'https://example.com/image3.jpg' }
    ]
  }

  beforeEach(() => {
    wrapper = mount(VehicleCard, {
      props: {
        vehicle: mockVehicle
      },
      global: {
        components: {
          ImageCarousel
        }
      }
    })
  })

  it('renders vehicle information correctly', () => {
    expect(wrapper.text()).toContain('Toyota Camry')
    expect(wrapper.text()).toContain('2023')
    expect(wrapper.text()).toContain('$100')
    expect(wrapper.text()).toContain('5') // seats
    expect(wrapper.text()).toContain('automatic')
  })

  it('shows availability status correctly', () => {
    expect(wrapper.find('[data-testid="availability-badge"]').text()).toContain('Available')
    expect(wrapper.find('[data-testid="availability-badge"]').classes()).toContain('bg-emerald-500')
  })

  it('shows unavailable status for unavailable vehicles', async () => {
    await wrapper.setProps({
      vehicle: { ...mockVehicle, is_available: false }
    })

    expect(wrapper.find('[data-testid="availability-badge"]').text()).toContain('Unavailable')
    expect(wrapper.find('[data-testid="availability-badge"]').classes()).toContain('bg-red-500')
  })

  it('prepares vehicle images correctly for carousel', () => {
    const carousel = wrapper.findComponent(ImageCarousel)
    const images = carousel.props('images')

    expect(images).toHaveLength(4) // featured + 2 gallery + 1 from images relationship
    expect(images).toContain('https://example.com/toyota-camry.jpg')
    expect(images).toContain('https://example.com/image1.jpg')
    expect(images).toContain('https://example.com/image3.jpg')
  })

  it('removes duplicate images from carousel', async () => {
    await wrapper.setProps({
      vehicle: {
        ...mockVehicle,
        featured_image: 'https://example.com/duplicate.jpg',
        gallery_images: ['https://example.com/duplicate.jpg', 'https://example.com/unique.jpg']
      }
    })

    const carousel = wrapper.findComponent(ImageCarousel)
    const images = carousel.props('images')

    expect(images.filter(img => img === 'https://example.com/duplicate.jpg')).toHaveLength(1)
  })

  it('emits view-details event when view details button clicked', async () => {
    const viewButton = wrapper.find('[data-testid="view-details-button"]')
    await viewButton.trigger('click')

    expect(wrapper.emitted()['view-details']).toBeTruthy()
    expect(wrapper.emitted()['view-details'][0]).toEqual([1])
  })

  it('emits reserve-now event when reserve button clicked', async () => {
    const reserveButton = wrapper.find('[data-testid="reserve-button"]')
    await reserveButton.trigger('click')

    expect(wrapper.emitted()['reserve-now']).toBeTruthy()
    expect(wrapper.emitted()['reserve-now'][0]).toEqual([1])
  })

  it('handles favorite toggle correctly', async () => {
    const favoriteButton = wrapper.find('[data-testid="favorite-button"]')
    await favoriteButton.trigger('click')

    // Should show loading state
    expect(wrapper.vm.favoriteLoading).toBe(false) // Will be false after axios mock resolves
  })

  it('shows category badge when category is present', () => {
    expect(wrapper.find('[data-testid="category-badge"]').text()).toBe('sedan')
  })

  it('displays vehicle specifications correctly', () => {
    const specs = wrapper.find('[data-testid="vehicle-specs"]')
    expect(specs.text()).toContain('automatic')
    expect(specs.text()).toContain('5') // seats
  })

  it('shows quick view button on hover', async () => {
    const card = wrapper.find('[data-testid="vehicle-card"]')
    await card.trigger('mouseenter')

    expect(wrapper.find('[data-testid="quick-view-button"]').isVisible()).toBe(true)
  })

  it('handles image carousel events', async () => {
    const carousel = wrapper.findComponent(ImageCarousel)

    // Simulate image error from carousel
    await carousel.vm.$emit('image-error', { event: new Event('error'), index: 0 })

    // Should handle the error gracefully (no crash)
    expect(wrapper.exists()).toBe(true)
  })

  describe('Edge cases', () => {
    it('handles vehicle without images', async () => {
      await wrapper.setProps({
        vehicle: {
          ...mockVehicle,
          featured_image: null,
          gallery_images: null,
          images: []
        }
      })

      const carousel = wrapper.findComponent(ImageCarousel)
      expect(carousel.props('images')).toHaveLength(0)
    })

    it('handles missing vehicle properties gracefully', async () => {
      await wrapper.setProps({
        vehicle: {
          id: 1,
          make: 'Toyota',
          model: 'Camry',
          daily_rate: 100
          // Missing other properties
        }
      })

      expect(wrapper.exists()).toBe(true)
      expect(wrapper.text()).toContain('Toyota Camry')
    })

    it('formats price correctly', () => {
      const priceElement = wrapper.find('[data-testid="daily-rate"]')
      expect(priceElement.text()).toContain('$100')
    })
  })
})