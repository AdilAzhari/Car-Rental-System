/**
 * @jest-environment jsdom
 */

import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, jest } from '@jest/globals'
import ImageCarousel from '../../../resources/js/Components/ImageCarousel.vue'

describe('ImageCarousel Component', () => {
  let wrapper

  const mockImages = [
    'https://example.com/image1.jpg',
    'https://example.com/image2.jpg',
    'https://example.com/image3.jpg'
  ]

  beforeEach(() => {
    wrapper = mount(ImageCarousel, {
      props: {
        images: mockImages,
        autoplay: false,
        showDots: true,
        showCounter: true,
        allowFullscreen: true
      }
    })
  })

  it('renders correctly with images', () => {
    expect(wrapper.exists()).toBe(true)
    expect(wrapper.find('img').exists()).toBe(true)
  })

  it('displays correct image counter', () => {
    const counter = wrapper.find('[data-testid="image-counter"]')
    expect(counter.text()).toContain('1 / 3')
  })

  it('shows dots indicator when enabled', () => {
    const dots = wrapper.findAll('[data-testid="dot-indicator"]')
    expect(dots).toHaveLength(3)
  })

  it('navigates to next image when next button clicked', async () => {
    const nextButton = wrapper.find('[data-testid="nav-next"]')
    await nextButton.trigger('click')

    const counter = wrapper.find('[data-testid="image-counter"]')
    expect(counter.text()).toContain('2 / 3')
  })

  it('navigates to previous image when prev button clicked', async () => {
    // First go to second image
    await wrapper.find('[data-testid="nav-next"]').trigger('click')

    // Then go back to first
    const prevButton = wrapper.find('[data-testid="nav-prev"]')
    await prevButton.trigger('click')

    const counter = wrapper.find('[data-testid="image-counter"]')
    expect(counter.text()).toContain('1 / 3')
  })

  it('navigates to specific image when dot clicked', async () => {
    const thirdDot = wrapper.findAll('[data-testid="dot-indicator"]')[2]
    await thirdDot.trigger('click')

    const counter = wrapper.find('[data-testid="image-counter"]')
    expect(counter.text()).toContain('3 / 3')
  })

  it('opens fullscreen mode when fullscreen button clicked', async () => {
    const fullscreenButton = wrapper.find('[data-testid="fullscreen-button"]')
    await fullscreenButton.trigger('click')

    expect(wrapper.vm.isFullscreen).toBe(true)
  })

  it('handles infinite scroll correctly', async () => {
    await wrapper.setProps({ infinite: true })

    // Go to last image
    await wrapper.find('[data-testid="dot-indicator"]:last-child').trigger('click')

    // Click next should go to first image
    await wrapper.find('[data-testid="nav-next"]').trigger('click')

    const counter = wrapper.find('[data-testid="image-counter"]')
    expect(counter.text()).toContain('1 / 3')
  })

  it('emits imageChange event when image changes', async () => {
    await wrapper.find('[data-testid="nav-next"]').trigger('click')

    expect(wrapper.emitted().imageChange).toBeTruthy()
    expect(wrapper.emitted().imageChange[0]).toEqual([1])
  })

  it('handles keyboard navigation', async () => {
    await wrapper.trigger('keydown', { key: 'ArrowRight' })

    const counter = wrapper.find('[data-testid="image-counter"]')
    expect(counter.text()).toContain('2 / 3')
  })

  it('handles empty images array gracefully', async () => {
    await wrapper.setProps({ images: [] })

    expect(wrapper.find('[data-testid="no-images"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('No images available')
  })

  it('shows loading state correctly', async () => {
    await wrapper.setProps({ loading: true })

    expect(wrapper.find('[data-testid="loading-state"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Loading...')
  })

  it('handles image error correctly', async () => {
    const img = wrapper.find('img')
    await img.trigger('error')

    expect(wrapper.emitted().imageError).toBeTruthy()
  })

  describe('Autoplay functionality', () => {
    beforeEach(() => {
      jest.useFakeTimers()
    })

    afterEach(() => {
      jest.useRealTimers()
    })

    it('starts autoplay when enabled', async () => {
      await wrapper.setProps({ autoplay: true, autoplayDelay: 1000 })

      jest.advanceTimersByTime(1000)
      await wrapper.vm.$nextTick()

      const counter = wrapper.find('[data-testid="image-counter"]')
      expect(counter.text()).toContain('2 / 3')
    })

    it('stops autoplay when disabled', async () => {
      await wrapper.setProps({ autoplay: true, autoplayDelay: 1000 })

      // Start autoplay
      jest.advanceTimersByTime(500)

      // Disable autoplay
      await wrapper.setProps({ autoplay: false })

      // Advance past original delay
      jest.advanceTimersByTime(1000)
      await wrapper.vm.$nextTick()

      const counter = wrapper.find('[data-testid="image-counter"]')
      expect(counter.text()).toContain('1 / 3') // Should still be on first image
    })
  })
})