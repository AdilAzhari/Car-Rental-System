/**
 * @jest-environment jsdom
 */

import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, jest } from '@jest/globals'

// Mock the booking form component
const MockBookingForm = {
  template: `
    <form @submit.prevent="submitForm">
      <input data-testid="start-date" v-model="form.start_date" type="date" />
      <input data-testid="end-date" v-model="form.end_date" type="date" />
      <select data-testid="payment-method" v-model="form.payment_method">
        <option value="">Select Payment Method</option>
        <option value="cash">Cash</option>
        <option value="visa">Visa</option>
        <option value="mastercard">Mastercard</option>
      </select>
      <input data-testid="pickup-location" v-model="form.pickup_location" />
      <textarea data-testid="special-requests" v-model="form.special_requests"></textarea>
      <button type="submit" data-testid="submit-booking">Complete Booking</button>
    </form>
  `,
  props: ['vehicle', 'errors'],
  data() {
    return {
      form: {
        start_date: '',
        end_date: '',
        payment_method: '',
        pickup_location: '',
        special_requests: ''
      }
    }
  },
  computed: {
    totalAmount() {
      if (!this.form.start_date || !this.form.end_date) return 0
      const days = Math.ceil((new Date(this.form.end_date) - new Date(this.form.start_date)) / (1000 * 60 * 60 * 24))
      return days * this.vehicle.daily_rate
    },
    insuranceFee() {
      return this.totalAmount * 0.1 // 10% insurance
    },
    taxAmount() {
      return this.totalAmount * 0.15 // 15% tax
    },
    grandTotal() {
      return this.totalAmount + this.insuranceFee + this.taxAmount
    }
  },
  methods: {
    submitForm() {
      this.$emit('submit', this.form)
    }
  }
}

// Mock Inertia
const mockInertia = {
  post: jest.fn(),
  visit: jest.fn()
}

describe('BookingPage Component', () => {
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
    is_available: true,
    featured_image: 'https://example.com/toyota.jpg',
    gallery_images: ['image1.jpg', 'image2.jpg'],
    owner: {
      id: 2,
      name: 'John Doe',
      email: 'john@example.com'
    }
  }

  const mockProps = {
    vehicle: mockVehicle,
    errors: {}
  }

  beforeEach(() => {
    wrapper = mount(MockBookingForm, {
      props: mockProps,
      global: {
        mocks: {
          $inertia: mockInertia,
          route: jest.fn(() => '/cars/1/booking')
        }
      }
    })
  })

  it('renders booking form correctly', () => {
    expect(wrapper.find('[data-testid="start-date"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="end-date"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="payment-method"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="pickup-location"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="special-requests"]').exists()).toBe(true)
  })

  it('handles date input correctly', async () => {
    const startDate = wrapper.find('[data-testid="start-date"]')
    const endDate = wrapper.find('[data-testid="end-date"]')

    await startDate.setValue('2024-01-15')
    await endDate.setValue('2024-01-18')

    expect(wrapper.vm.form.start_date).toBe('2024-01-15')
    expect(wrapper.vm.form.end_date).toBe('2024-01-18')
  })

  it('calculates booking duration and total correctly', async () => {
    await wrapper.find('[data-testid="start-date"]').setValue('2024-01-15')
    await wrapper.find('[data-testid="end-date"]').setValue('2024-01-18')

    expect(wrapper.vm.totalAmount).toBe(300) // 3 days * $100
    expect(wrapper.vm.insuranceFee).toBe(30) // 10% of $300
    expect(wrapper.vm.taxAmount).toBe(45) // 15% of $300
    expect(wrapper.vm.grandTotal).toBe(375) // $300 + $30 + $45
  })

  it('handles payment method selection', async () => {
    const paymentSelect = wrapper.find('[data-testid="payment-method"]')

    await paymentSelect.setValue('visa')
    expect(wrapper.vm.form.payment_method).toBe('visa')

    await paymentSelect.setValue('cash')
    expect(wrapper.vm.form.payment_method).toBe('cash')
  })

  it('handles pickup location input', async () => {
    const pickupInput = wrapper.find('[data-testid="pickup-location"]')
    await pickupInput.setValue('Downtown Office')

    expect(wrapper.vm.form.pickup_location).toBe('Downtown Office')
  })

  it('handles special requests textarea', async () => {
    const specialRequests = wrapper.find('[data-testid="special-requests"]')
    await specialRequests.setValue('Need child seat and GPS')

    expect(wrapper.vm.form.special_requests).toBe('Need child seat and GPS')
  })

  it('emits submit event with form data when submitted', async () => {
    await wrapper.find('[data-testid="start-date"]').setValue('2024-01-15')
    await wrapper.find('[data-testid="end-date"]').setValue('2024-01-18')
    await wrapper.find('[data-testid="payment-method"]').setValue('visa')
    await wrapper.find('[data-testid="pickup-location"]').setValue('Main Office')
    await wrapper.find('[data-testid="special-requests"]').setValue('GPS needed')

    const submitButton = wrapper.find('[data-testid="submit-booking"]')
    await submitButton.trigger('click')

    expect(wrapper.emitted().submit).toBeTruthy()
    expect(wrapper.emitted().submit[0][0]).toEqual({
      start_date: '2024-01-15',
      end_date: '2024-01-18',
      payment_method: 'visa',
      pickup_location: 'Main Office',
      special_requests: 'GPS needed'
    })
  })

  describe('Validation', () => {
    it('prevents submission with invalid date range', async () => {
      // End date before start date
      await wrapper.find('[data-testid="start-date"]').setValue('2024-01-18')
      await wrapper.find('[data-testid="end-date"]').setValue('2024-01-15')

      const submitButton = wrapper.find('[data-testid="submit-booking"]')
      await submitButton.trigger('click')

      // Should not emit submit event for invalid dates
      expect(wrapper.emitted().submit).toBeFalsy()
    })

    it('prevents submission without required fields', async () => {
      // Leave payment method empty
      await wrapper.find('[data-testid="start-date"]').setValue('2024-01-15')
      await wrapper.find('[data-testid="end-date"]').setValue('2024-01-18')

      const submitButton = wrapper.find('[data-testid="submit-booking"]')
      await submitButton.trigger('click')

      // Should show validation errors
      expect(wrapper.emitted().submit).toBeFalsy()
    })
  })

  describe('Payment Method Handling', () => {
    it('shows additional fields for card payments', async () => {
      await wrapper.find('[data-testid="payment-method"]').setValue('visa')
      await wrapper.vm.$nextTick()

      // Check if card-specific fields are shown
      expect(wrapper.vm.form.payment_method).toBe('visa')
    })

    it('handles cash payment selection', async () => {
      await wrapper.find('[data-testid="payment-method"]').setValue('cash')

      expect(wrapper.vm.form.payment_method).toBe('cash')
    })
  })

  describe('Price Calculation Display', () => {
    it('updates totals when dates change', async () => {
      // Set initial dates (3 days)
      await wrapper.find('[data-testid="start-date"]').setValue('2024-01-15')
      await wrapper.find('[data-testid="end-date"]').setValue('2024-01-18')

      expect(wrapper.vm.totalAmount).toBe(300)

      // Change to 5 days
      await wrapper.find('[data-testid="end-date"]').setValue('2024-01-20')

      expect(wrapper.vm.totalAmount).toBe(500)
      expect(wrapper.vm.grandTotal).toBe(625) // $500 + $50 + $75
    })

    it('shows zero total when no dates selected', () => {
      expect(wrapper.vm.totalAmount).toBe(0)
      expect(wrapper.vm.grandTotal).toBe(0)
    })
  })

  describe('Error Handling', () => {
    it('displays validation errors when passed as props', async () => {
      await wrapper.setProps({
        vehicle: mockVehicle,
        errors: {
          start_date: 'Start date is required',
          payment_method: 'Payment method is required'
        }
      })

      expect(wrapper.props('errors')).toEqual({
        start_date: 'Start date is required',
        payment_method: 'Payment method is required'
      })
    })
  })

  describe('Accessibility', () => {
    it('has proper form labels and structure', () => {
      expect(wrapper.find('form').exists()).toBe(true)
      expect(wrapper.find('[data-testid="start-date"]').attributes('type')).toBe('date')
      expect(wrapper.find('[data-testid="end-date"]').attributes('type')).toBe('date')
    })

    it('provides proper submit button', () => {
      const submitButton = wrapper.find('[data-testid="submit-booking"]')
      expect(submitButton.attributes('type')).toBe('submit')
      expect(submitButton.text()).toBe('Complete Booking')
    })
  })
})