<?php

namespace App\Actions\Vehicle;

use App\Http\Resources\CarResource;
use App\Repositories\VehicleRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchVehiclesAction
{
    public function __construct(
        private readonly VehicleRepository $vehicleRepository
    ) {}

    public function execute(Request $request): AnonymousResourceCollection
    {
        $searchDTO = \App\DTOs\VehicleSearchDTO::fromRequest($request);
        $vehicles = $this->vehicleRepository->searchWithFilters($request);

        return CarResource::collection($vehicles);
    }

    public function executeWithDTO(\App\DTOs\VehicleSearchDTO $searchDTO): AnonymousResourceCollection
    {
        // Convert DTO back to request-like structure for repository compatibility
        $mockRequest = new Request($searchDTO->toArray());
        $vehicles = $this->vehicleRepository->searchWithFilters($mockRequest);

        return CarResource::collection($vehicles);
    }
}
