<?php

namespace App\Services;

use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrafficViolationService
{
    protected string $apiUrl;

    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('traffic.api_url', 'https://api.traffic.gov.my/violations');
        $this->apiKey = config('traffic.api_key', env('TRAFFIC_API_KEY'));
    }

    /**
     * Fetch traffic violations for a specific vehicle from Traffic Department API
     */
    public function fetchViolationsForVehicle(Vehicle $vehicle): array
    {
        try {
            if ($this->apiKey === '' || $this->apiKey === '0') {
                Log::warning('Traffic API key not configured');

                return [];
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->apiUrl.'/check', [
                    'plate_number' => $vehicle->plate_number,
                    'vin' => $vehicle->vin,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Update vehicle with latest violation data
                $this->updateVehicleViolations($vehicle, $data);

                return $data['violations'] ?? [];
            }

            Log::error('Traffic API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'vehicle_id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Traffic violations API error', [
                'message' => $e->getMessage(),
                'vehicle_id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
            ]);

            return [];
        }
    }

    /**
     * Update vehicle with traffic violations data
     */
    protected function updateVehicleViolations(Vehicle $vehicle, array $data): void
    {
        $violations = $data['violations'] ?? [];
        $totalFines = 0;
        $hasPending = false;

        foreach ($violations as $violation) {
            if (isset($violation['fine_amount'])) {
                $totalFines += $violation['fine_amount'];
            }

            if (isset($violation['status']) && $violation['status'] === 'pending') {
                $hasPending = true;
            }
        }

        $vehicle->update([
            'traffic_violations' => $violations,
            'violations_last_checked' => now(),
            'total_violations_count' => count($violations),
            'total_fines_amount' => $totalFines,
            'has_pending_violations' => $hasPending,
        ]);

        Log::info('Vehicle violations updated', [
            'vehicle_id' => $vehicle->id,
            'plate_number' => $vehicle->plate_number,
            'violations_count' => count($violations),
            'total_fines' => $totalFines,
        ]);
    }

    /**
     * Batch update violations for multiple vehicles
     */
    public function updateAllVehicleViolations(): int
    {
        $updated = 0;

        Vehicle::whereDoesntHave('violations_last_checked')
            ->orWhere('violations_last_checked', '<', now()->subHours(24))
            ->chunk(10, function ($vehicles) use (&$updated): void {
                foreach ($vehicles as $vehicle) {
                    $this->fetchViolationsForVehicle($vehicle);
                    $updated++;

                    // Rate limiting - wait 1 second between requests
                    sleep(1);
                }
            });

        return $updated;
    }

    /**
     * Check if vehicle has any pending violations
     */
    public function hasPendingViolations(Vehicle $vehicle): bool
    {
        if ($this->shouldRefreshViolations($vehicle)) {
            $this->fetchViolationsForVehicle($vehicle);
        }

        return $vehicle->has_pending_violations;
    }

    /**
     * Get formatted violations for display
     */
    public function getFormattedViolations(Vehicle $vehicle): array
    {
        $violations = $vehicle->traffic_violations ?? [];

        return collect($violations)->map(fn ($violation): array => [
            'violation_type' => $violation['type'] ?? 'Unknown Violation',
            'date' => isset($violation['date']) ? Carbon::parse($violation['date'])->format('Y-m-d') : null,
            'location' => $violation['location'] ?? 'Unknown Location',
            'fine_amount' => $violation['fine_amount'] ?? 0,
            'status' => $violation['status'] ?? 'unknown',
            'reference_number' => $violation['reference'] ?? null,
            'due_date' => isset($violation['due_date']) ? Carbon::parse($violation['due_date'])->format('Y-m-d') : null,
            'description' => $violation['description'] ?? null,
        ])->toArray();
    }

    /**
     * Check if violations data should be refreshed
     */
    protected function shouldRefreshViolations(Vehicle $vehicle): bool
    {
        if (! $vehicle->violations_last_checked) {
            return true;
        }

        return $vehicle->violations_last_checked->lt(now()->subHours(24));
    }

    /**
     * Create sample violation data for testing
     */
    public function createSampleViolations(Vehicle $vehicle): void
    {
        $sampleViolations = [
            [
                'type' => 'Speeding',
                'date' => now()->subDays(15)->toDateString(),
                'location' => 'PLUS Highway KM 15.2',
                'fine_amount' => 150.00,
                'status' => 'pending',
                'reference' => 'TRF-2025-001234',
                'due_date' => now()->addDays(30)->toDateString(),
                'description' => 'Speed limit exceeded by 20km/h',
            ],
            [
                'type' => 'Traffic Light Violation',
                'date' => now()->subDays(8)->toDateString(),
                'location' => 'Jalan Bukit Bintang Intersection',
                'fine_amount' => 300.00,
                'status' => 'pending',
                'reference' => 'TRF-2025-001567',
                'due_date' => now()->addDays(23)->toDateString(),
                'description' => 'Running red light',
            ],
        ];

        $this->updateVehicleViolations($vehicle, ['violations' => $sampleViolations]);
    }
}
