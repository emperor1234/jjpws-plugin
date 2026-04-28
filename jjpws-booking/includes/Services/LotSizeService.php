<?php

namespace JJPWS\Services;

class LotSizeService {

    /**
     * Resolve lot size from address components.
     *
     * Returns array: [ sqft, category, label, source, lat, lng ]
     * Returns null if resolution is impossible (caller shows manual fallback).
     */
    public function resolve_from_address( string $street, string $city, string $state, string $zip ): ?array {
        $coords = $this->geocode( $street, $city, $state, $zip );

        if ( ! $coords ) {
            return null;
        }

        [ 'lat' => $lat, 'lng' => $lng ] = $coords;

        $sqft = $this->regrid_lookup( $lat, $lng );
        $source = 'regrid';

        if ( $sqft === null ) {
            $sqft   = null;
            $source = 'manual_required';
        }

        if ( $sqft !== null ) {
            $category = LotSizeClassifier::classify( $sqft );
            $label    = LotSizeClassifier::label( $category );

            return compact( 'sqft', 'category', 'label', 'source', 'lat', 'lng' );
        }

        return [
            'sqft'     => null,
            'category' => null,
            'label'    => null,
            'source'   => 'manual_required',
            'lat'      => $lat,
            'lng'      => $lng,
        ];
    }

    private function geocode( string $street, string $city, string $state, string $zip ): ?array {
        $api_key = $this->get_google_api_key();

        if ( empty( $api_key ) ) {
            return $this->nominatim_geocode( $street, $city, $state, $zip );
        }

        $address  = urlencode( "{$street}, {$city}, {$state} {$zip}" );
        $url      = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$api_key}";
        $response = $this->http_get( $url );

        if ( ! $response ) {
            return null;
        }

        $data = json_decode( $response, true );

        if ( empty( $data['results'][0]['geometry']['location'] ) ) {
            return null;
        }

        $loc = $data['results'][0]['geometry']['location'];

        return [ 'lat' => (float) $loc['lat'], 'lng' => (float) $loc['lng'] ];
    }

    private function nominatim_geocode( string $street, string $city, string $state, string $zip ): ?array {
        $query    = urlencode( "{$street}, {$city}, {$state}, {$zip}, USA" );
        $url      = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=1";
        $response = $this->http_get( $url, [ 'User-Agent' => 'JJPetWasteServices/1.0 (jjpetwasteservices.com)' ] );

        if ( ! $response ) {
            return null;
        }

        $data = json_decode( $response, true );

        if ( empty( $data[0]['lat'] ) ) {
            return null;
        }

        return [ 'lat' => (float) $data[0]['lat'], 'lng' => (float) $data[0]['lon'] ];
    }

    private function regrid_lookup( float $lat, float $lng ): ?int {
        $api_key = $this->get_regrid_api_key();

        if ( empty( $api_key ) ) {
            return null;
        }

        $url      = "https://app.regrid.com/api/v1/parcel.json?lat={$lat}&lon={$lng}&token={$api_key}&fields=ll_gisacre";
        $response = $this->http_get( $url );

        if ( ! $response ) {
            return null;
        }

        $data = json_decode( $response, true );

        if ( empty( $data['results']['parcels']['features'][0]['properties']['ll_gisacre'] ) ) {
            return null;
        }

        $acres = (float) $data['results']['parcels']['features'][0]['properties']['ll_gisacre'];
        $sqft  = (int) round( $acres * 43560 );

        return $sqft > 0 ? $sqft : null;
    }

    private function http_get( string $url, array $extra_headers = [] ): ?string {
        $args = [
            'timeout'    => 8,
            'user-agent' => 'JJPetWasteServices/1.0',
            'headers'    => $extra_headers,
        ];

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'JJPWS LotSizeService HTTP error: ' . $response->get_error_message() );
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code !== 200 ) {
            error_log( "JJPWS LotSizeService HTTP {$code} for {$url}" );
            return null;
        }

        return wp_remote_retrieve_body( $response );
    }

    private function get_google_api_key(): string {
        $keys = get_option( 'jjpws_api_keys', [] );

        if ( is_string( $keys ) ) {
            $keys = maybe_unserialize( $keys );
        }

        return $keys['google_maps'] ?? '';
    }

    private function get_regrid_api_key(): string {
        $keys = get_option( 'jjpws_api_keys', [] );

        if ( is_string( $keys ) ) {
            $keys = maybe_unserialize( $keys );
        }

        return $keys['regrid'] ?? '';
    }
}
