<?php

namespace Tests\Unit;

use App\Models\Branch;
use Tests\TestCase;

class BranchGeofencingTest extends TestCase
{
    /**
     * TC-GEO-001: Distance — Same Point = 0
     */
    public function test_distance_same_point_is_zero(): void
    {
        $branch = new Branch([
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
            'geofence_radius' => 17,
        ]);

        $this->assertEquals(0.0, $branch->distanceTo(24.7136, 46.6753));
    }

    /**
     * TC-GEO-002: Within 17m geofence
     */
    public function test_within_geofence(): void
    {
        $branch = new Branch([
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
            'geofence_radius' => 17,
        ]);

        // ~5m offset
        $distance = $branch->distanceTo(24.71363, 46.67533);
        $this->assertLessThan(17, $distance);
        $this->assertTrue($branch->isWithinGeofence(24.71363, 46.67533));
    }

    /**
     * TC-GEO-003: Outside 17m geofence
     */
    public function test_outside_geofence(): void
    {
        $branch = new Branch([
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
            'geofence_radius' => 17,
        ]);

        // ~30m offset
        $distance = $branch->distanceTo(24.7138, 46.6755);
        $this->assertGreaterThan(17, $distance);
        $this->assertFalse($branch->isWithinGeofence(24.7138, 46.6755));
    }

    /**
     * TC-GEO-004: Custom Geofence Radius (50m)
     */
    public function test_custom_geofence_radius(): void
    {
        $branch = new Branch([
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
            'geofence_radius' => 50,
        ]);

        // ~30m offset — within 50m radius
        $this->assertTrue($branch->isWithinGeofence(24.7138, 46.6755));
    }

    /**
     * TC-GEO-005: Haversine returns positive distance for different points
     */
    public function test_haversine_positive_distance(): void
    {
        $branch = new Branch([
            'latitude'  => -33.8688,
            'longitude' => 151.2093,
            'geofence_radius' => 17,
        ]);

        $distance = $branch->distanceTo(-33.8689, 151.2094);
        $this->assertGreaterThan(0, $distance);
        $this->assertIsFloat($distance);
    }
}
