<?php

namespace JJPWS\Services;

class LotSizeService {

    /**
     * Resolve lot size from address.
     *
     * Returns array: [ sqft, acres, tier, label, source, lat, lng ]
     * or null if even geocoding failed.
     *
     * `source` will be 'manual_required' when geocoding worked but the parcel
     * lookup didn't — the form falls back to a manual selector silently.
     */
    public function resolve_from_address( string $street, string $city, string $state, string $zip ): ?array {
        $coords = $this->geocode( $street, $city, $state, $zip );

        if ( ! $coords ) {
            return null;
        }

        [ 'lat' => $lat, 'lng' => $lng ] = $coords;

        $acres = $this->arcgis_lookup( $lat, $lng );

        if ( $acres !== null && $acres > 0 ) {
            $sqft  = (int) round( $acres * LotSizeClassifier::SQFT_PER_ACRE );
            $tier  = LotSizeClassifier::classify_by_acres( $acres );
            $label = LotSizeClassifier::label( $tier );

            return [
                'sqft'   => $sqft,
                'acres'  => round( $acres, 3 ),
                'tier'   => $tier,
                'label'  => $label,
                'source' => 'arcgis',
                'lat'    => $lat,
                'lng'    => $lng,
            ];
        }

        return [
            'sqft'   => null,
            'acres'  => null,
            'tier'   => null,
            'label'  => null,
            'source' => 'manual_required',
            'lat'    => $lat,
            'lng'    => $lng,
        ];
    }

    public function geocode_public( string $street, string $city, string $state, string $zip ): ?array {
        return $this->geocode( $street, $city, $state, $zip );
    }

    private function geocode( string $street, string $city, string $state, string $zip ): ?array {
        $api_key = $this->get_google_api_key();

        if ( empty( $api_key ) ) {
            return $this->nominatim_geocode( $street, $city, $state, $zip );
        }

        $address  = urlencode( "{$street}, {$city}, {$state} {$zip}" );
        $url      = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$api_key}";
        $response = $this->http_get( $url );

        if ( ! $response ) return null;
        $data = json_decode( $response, true );
        if ( empty( $data['results'][0]['geometry']['location'] ) ) return null;

        $loc = $data['results'][0]['geometry']['location'];
        return [ 'lat' => (float) $loc['lat'], 'lng' => (float) $loc['lng'] ];
    }

    private function nominatim_geocode( string $street, string $city, string $state, string $zip ): ?array {
        $query    = urlencode( "{$street}, {$city}, {$state}, {$zip}, USA" );
        $url      = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=1";
        $response = $this->http_get( $url, [ 'User-Agent' => 'JJPetWasteServices/1.0' ] );

        if ( ! $response ) return null;
        $data = json_decode( $response, true );
        if ( empty( $data[0]['lat'] ) ) return null;

        return [ 'lat' => (float) $data[0]['lat'], 'lng' => (float) $data[0]['lon'] ];
    }

    /**
     * Query an ArcGIS Feature/MapServer endpoint for the parcel containing the
     * given point. Endpoint and acreage field name are admin-configurable.
     *
     * @return float|null acres, or null on miss/failure
     */
    private function arcgis_lookup( float $lat, float $lng ): ?float {
        $result = $this->arcgis_lookup_diagnostic( $lat, $lng );
        return $result['acres'] ?? null;
    }

    /**
     * Same as arcgis_lookup but returns full diagnostic data (URL, HTTP
     * status, raw response, parsed acres, error reason). Used by both the
     * regular lookup and the admin diagnostic tool.
     */
    public function arcgis_lookup_diagnostic( float $lat, float $lng ): array {
        $endpoint = $this->get_arcgis_endpoint();
        $field    = $this->get_arcgis_field();

        $diag = [
            'endpoint'        => $endpoint,
            'configured_field'=> $field,
            'lat'             => $lat,
            'lng'             => $lng,
            'request_url'     => null,
            'http_status'     => null,
            'http_error'      => null,
            'response_excerpt'=> null,
            'attributes'      => null,
            'matched_field'   => null,
            'acres'           => null,
            'error'           => null,
        ];

        if ( empty( $endpoint ) ) {
            $diag['error'] = 'No ArcGIS endpoint configured.';
            return $diag;
        }

        // ArcGIS accepts geometry as a JSON object — most reliable form for
        // point-in-polygon queries because spatialReference travels with the geometry.
        $geometry_json = wp_json_encode( [
            'x' => $lng,
            'y' => $lat,
            'spatialReference' => [ 'wkid' => 4326 ],
        ] );

        $params = [
            'geometry'       => $geometry_json,
            'geometryType'   => 'esriGeometryPoint',
            'inSR'           => '4326',
            'spatialRel'     => 'esriSpatialRelIntersects',
            'outFields'      => '*',          // pull all fields so we can fuzzy-match
            'returnGeometry' => 'false',
            'f'              => 'json',
        ];

        $base = strtok( $endpoint, '?' );
        $url  = $base . '?' . http_build_query( $params );
        $diag['request_url'] = $url;

        $response = wp_remote_get( $url, [
            'timeout'    => 10,
            'user-agent' => 'JJPetWasteServices/1.0',
        ] );

        if ( is_wp_error( $response ) ) {
            $diag['http_error'] = $response->get_error_message();
            $diag['error']      = 'HTTP request failed: ' . $diag['http_error'];
            error_log( 'JJPWS ArcGIS HTTP error: ' . $diag['http_error'] . ' URL: ' . $url );
            return $diag;
        }

        $diag['http_status']      = (int) wp_remote_retrieve_response_code( $response );
        $body                     = (string) wp_remote_retrieve_body( $response );
        $diag['response_excerpt'] = mb_substr( $body, 0, 1500 );

        if ( $diag['http_status'] !== 200 ) {
            $diag['error'] = "Endpoint returned HTTP {$diag['http_status']}.";
            error_log( "JJPWS ArcGIS HTTP {$diag['http_status']} for {$url}" );
            return $diag;
        }

        $data = json_decode( $body, true );

        if ( ! is_array( $data ) ) {
            $diag['error'] = 'Endpoint returned non-JSON response.';
            return $diag;
        }

        if ( ! empty( $data['error'] ) ) {
            $diag['error'] = 'ArcGIS error: ' . ( $data['error']['message'] ?? json_encode( $data['error'] ) );
            return $diag;
        }

        if ( empty( $data['features'][0]['attributes'] ) ) {
            $diag['error'] = 'No parcel found at this location (the address may be outside the configured GIS coverage area).';
            return $diag;
        }

        $attrs = $data['features'][0]['attributes'];
        $diag['attributes'] = $attrs;

        // Find the acreage field — exact, case-insensitive, then any
        // field that looks like acreage.
        $value         = null;
        $matched_field = null;

        if ( isset( $attrs[ $field ] ) ) {
            $value         = $attrs[ $field ];
            $matched_field = $field;
        } else {
            foreach ( $attrs as $k => $v ) {
                if ( strcasecmp( $k, $field ) === 0 ) {
                    $value         = $v;
                    $matched_field = $k;
                    break;
                }
            }
        }

        if ( $value === null || $value === '' ) {
            // Last-resort fuzzy search for a field name containing "acre"
            foreach ( $attrs as $k => $v ) {
                if ( stripos( $k, 'acre' ) !== false && is_numeric( $v ) ) {
                    $value         = $v;
                    $matched_field = $k;
                    break;
                }
            }
        }

        if ( $value === null || $value === '' || ! is_numeric( $value ) ) {
            $available = implode( ', ', array_keys( $attrs ) );
            $diag['error'] = "Could not find a usable acreage field. Available fields: {$available}";
            return $diag;
        }

        $diag['matched_field'] = $matched_field;
        $diag['acres']         = (float) $value;

        return $diag;
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
        if ( is_string( $keys ) ) $keys = maybe_unserialize( $keys );
        return is_array( $keys ) ? ( $keys['google_maps'] ?? '' ) : '';
    }

    private function get_arcgis_endpoint(): string {
        return trim( (string) get_option(
            'jjpws_parcel_endpoint',
            'https://gis.cherokeecountyga.gov/arcgis/rest/services/MainLayers/MapServer/1/query'
        ) );
    }

    private function get_arcgis_field(): string {
        return trim( (string) get_option( 'jjpws_parcel_acreage_field', 'Acreage' ) );
    }
}
