<?php

namespace App\Exceptions;

use RuntimeException;

class OutOfGeofenceException extends RuntimeException
{
    public float $distance;
    public float $allowedRadius;

    public function __construct(float $distance, float $allowedRadius)
    {
        $this->distance = round($distance, 2);
        $this->allowedRadius = $allowedRadius;

        parent::__construct(
            __('attendance.outside_geofence', [
                'distance' => $this->distance,
                'radius'   => $this->allowedRadius,
            ])
        );
    }
}
