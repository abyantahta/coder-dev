<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;

class LocationController extends Controller
{
    public function index()
    {
        $query = Location::query();
        $sortField = request("sort_field", "location_name");
        $sortDirection = request("sort_direction", "asc");

        if (request("location_name")) {
            $query->where("location_name", "like", "%" . request("location_name") . "%");
        }

        $locations = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();

        return inertia("Locations/Index", [
            "locations" => LocationResource::collection($locations),
            "queryParams" => request()->query() ?: null,
            "success" => session('success'),
            "error" => session('error'),
        ]);
    }

    public function store(StoreLocationRequest $request)
    {
        Location::create($request->validated());

        return to_route('locations.index')->with('success', 'Location was created');
    }

    public function update(UpdateLocationRequest $request, Location $location)
    {
        $location->update($request->validated());

        return to_route('locations.index')->with('success', 'Location was updated');
    }

    public function destroy(Location $location)
    {
        if ($location->transactions()->exists()) {
            return to_route('locations.index')->with('error', 'This location is still used by existing transactions and cannot be deleted');
        }

        $location->delete();

        return to_route('locations.index')->with('success', 'Location was deleted');
    }
}
