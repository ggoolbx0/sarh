<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Services\GeofencingService;
use Tests\TestCase;

class GeofencingServiceTest extends TestCase
{
    private GeofencingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeofencingService();
    }

    /**
     * TC-GFS-001: validatePosition returns correct structure.
     */
    public function test_validate_position_returns_correct_structure(): void
    {
        $branch = new Branch([
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
            'geofence_radius' => 17,
        ]);

        $result = $this->service->validatePosition($branch, 24.71365, 46.67535);

        $this->assertArrayHasKey('distance_meters', $result);
        $this->assertArrayHasKey('within_geofence', $result);
        $this->assertIsFloat($result['distance_meters']);
        $this->assertIsBool($result['within_geofence']);
    }

    /**
     * TC-GFS-002: Static haversineDistance matches Branch::distanceTo.
     */
    public function test_static_haversine_matches_branch_model(): void
    {
        $branch = new Branch([
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ]);

        $targetLat = 24.71380;
        $targetLng = 46.67550;

        $branchDistance  = $branch->distanceTo($targetLat, $targetLng);
        $serviceDistance = GeofencingService::haversineDistance(
            24.7136, 46.6753,
            $targetLat, $targetLng
        );

        $this->assertEquals($branchDistance, $serviceDistance);
    }

    /**
     * TC-GFS-003: Zero distance at exact branch center.
     */
    public function test_zero_distance_at_branch_center(): void
    {
        $branch = new Branch([
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
            'geofence_radius' => 17,
        ]);

        $result = $this->service->validatePosition($branch, 24.7136, 46.6753);

        $this->assertEquals(0.0, $result['distance_meters']);
        $this->assertTrue($result['within_geofence']);
    }

    /**
     * TC-GFS-004: Outside geofence detection.
     */
    public function test_outside_geofence_detection(): void
    {
        $branch = new Branch([
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
            'geofence_radius' => 17,
        ]);

        // 50m+ away
        $result = $this->service->validatePosition($branch, 24.7140, 46.6757);

        $this->assertGreaterThan(17, $result['distance_meters']);
        $this->assertFalse($result['within_geofence']);
    }

    /**
     * TC-GFS-005: Custom geofence radius is respected.
     */
    public function test_custom_geofence_radius_is_respected(): void
    {
        $branch = new Branch([
            'latitude'        => 24.7136,
            'longitude'       => 46.6753,
            'geofence_radius' => 100, // 100 meters
        ]);

        // ~50m away — should be WITHIN 100m geofence
        $result = $this->service->validatePosition($branch, 24.7140, 46.6757);

        $this->assertTrue($result['within_geofence']);
    }
}
