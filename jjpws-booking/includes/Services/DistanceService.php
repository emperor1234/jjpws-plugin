<?php

namespace JJPWS\Services;

class DistanceService {

    /**
     * Calculate distance in miles from the configured business origin to the
     * customer's coordinates. Returns null if business address isn't configured
     * or origin lat/lng can't be resolved.
     */
    public function miles_to_customer( float $customer_lat, float $customer_lng ): ?float {
        $origin = $this->get_origin_coords();

        if ( ! $origin ) {
            return null;
        }

        return self::haversine( $origin['lat'], $origin['lng'], $customer_lat, $customer_lng );
    }

    /**
     * Haversine formula — returns distance in miles between two coordinates.
     */
    public static function haversine( float $lat1, float $lng1, float $lat2, float $lng2 ): float {
        $earth_miles = 3958.8;

        $rlat1 = deg2rad( $lat1 );
        $rlat2 = deg2rad( $lat2 );
        $dlat  = deg2rad( $lat2 - $lat1 );
        $dlng  = deg2rad( $lng2 - $lng1 );

        $a = sin( $dlat / 2 ) ** 2 + cos( $rlat1 ) * cos( $rlat2 ) * sin( $dlng / 2 ) ** 2;
        $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

        return round( $earth_miles * $c, 2 );
    }

    /**
     * Resolve and cache the business origin lat/lng from the configured address.
     */
    public function get_origin_coords(): ?array {
        $cached = get_option( 'jjpws_business_origin_coords' );
        $address = trim( (string) get_option( 'jjpws_business_address', '' ) );

        if ( empty( $address ) ) {
            return null;
        }

        // Use cache if it's for the same address
        if ( is_array( $cached ) && ! empty( $cached['address'] ) && $cached['address'] === $address ) {
            return [ 'lat' => (float) $cached['lat'], 'lng' => (float) $cached['lng'] ];
        }

        $coords = $this->geocode( $address );

        if ( ! $coords ) {
            return null;
        }

        update_option( 'jjpws_business_origin_coords', [
            'address' => $address,
            'lat'     => $coords['lat'],
            'lng'     => $coords['lng'],
        ] );

        return $coords;
    }

    private function geocode( string $address ): ?array {
        $api_key = $this->get_google_api_key();
        $encoded = urlencode( $address );

        if ( ! empty( $api_key ) ) {
            $url      = "https://maps.googleapis.com/maps/api/geocode/json?address={$encoded}&key={$api_key}";
            $response = wp_remote_get( $url, [ 'timeout' => 8 ] );

            if ( ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200 ) {
                $data = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( ! empty( $data['results'][0]['geometry']['location'] ) ) {
                    $loc = $data['results'][0]['geometry']['location'];
                    return [ 'lat' => (float) $loc['lat'], 'lng' => (float) $loc['lng'] ];
                }
            }
        }

        // Nominatim fallback
        $url = "https://nominatim.openstreetmap.org/search?q={$encoded}&format=json&limit=1";
        $response = wp_remote_get( $url, [
            'timeout'    => 8,
            'user-agent' => 'JJPetWasteServices/1.0',
        ] );

        if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return null;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data[0]['lat'] ) ) {
            return null;
        }

        return [ 'lat' => (float) $data[0]['lat'], 'lng' => (float) $data[0]['lon'] ];
    }

    private function get_google_api_key(): string {
        $keys = get_option( 'jjpws_api_keys', [] );

        if ( is_string( $keys ) ) {
            $keys = maybe_unserialize( $keys );
        }

        return is_array( $keys ) ? ( $keys['google_maps'] ?? '' ) : '';
    }
}
