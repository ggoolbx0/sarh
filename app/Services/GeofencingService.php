<?php

namespace App\Services;

use App\Models\Branch;

class GeofencingService
{
    /**
     * Validate GPS coordinates against a branch's geofence.
     *
     * @return array{distance_meters: float, within_geofence: bool}
     */
    public function validatePosition(Branch $branch, float $lat, float $lng): array
    {
        $distance = $branch->distanceTo($lat, $lng);

        return [
            'distance_meters'  => $distance,
            'within_geofence'  => $distance <= $branch->geofence_radius,
        ];
    }

    /**
     * Static Haversine distance calculation (meters).
     * Standalone version — no Branch model dependency.
     */
    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo   = deg2rad($lat2);
        $lonTo   = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
           + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
