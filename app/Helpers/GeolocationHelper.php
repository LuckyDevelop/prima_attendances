<?php

declare(strict_types=1);

namespace App\Helpers;

class GeolocationHelper
{
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Calculate the distance between two coordinates using the Haversine formula.
     */
    public static function distanceInMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
    ): float {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Determine whether the given user coordinates are within the allowed office radius.
     */
    public static function isWithinRadius(
        float $userLat,
        float $userLng,
        float $officeLat,
        float $officeLng,
        int $radiusMeters,
    ): bool {
        $distance = self::distanceInMeters($userLat, $userLng, $officeLat, $officeLng);

        return $distance <= $radiusMeters;
    }
}
