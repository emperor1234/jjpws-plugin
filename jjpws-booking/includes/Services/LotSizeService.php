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
            error_log( 'JJPWS LotSizeService: all geocoding providers failed — manual fallback.' );
            return null;
        }

        [ 'lat' => $lat, 'lng' => $lng ] = $coords;

        // 1. Admin-configured county GIS endpoint
        $acres = $this->arcgis_lookup( $lat, $lng );

        // 2. ArcGIS Living Atlas USA_Parcels — national dataset, uses developer key.
        //    Covers parcels across the US regardless of county GIS configuration.
        if ( ( $acres === null || $acres <= 0 ) ) {
            $arcgis_key = $this->get_arcgis_developer_key();
            if ( ! empty( $arcgis_key ) ) {
                $acres = $this->living_atlas_lookup( $lat, $lng, $arcgis_key );
                if ( $acres !== null && $acres > 0 ) {
                    error_log( "JJPWS LotSizeService: Living Atlas returned {$acres} acres." );
                }
            }
        }

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

        error_log( "JJPWS LotSizeService: parcel lookup returned no acreage at {$lat},{$lng} — manual fallback." );

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

    /**
     * Like geocode() but returns full diagnostic info including the
     * underlying Google API status / error_message.
     */
    public function geocode_diagnostic( string $street, string $city, string $state, string $zip ): array {
        $api_key = $this->get_google_api_key();
        $diag    = [
            'provider'      => $api_key ? 'google' : 'nominatim',
            'request_url'   => null,
            'http_status'   => null,
            'api_status'    => null,
            'error_message' => null,
            'lat'           => null,
            'lng'           => null,
        ];

        $address = urlencode( "{$street}, {$city}, {$state} {$zip}" );

        if ( empty( $api_key ) ) {
            $url = "https://nominatim.openstreetmap.org/search?q={$address},USA&format=json&limit=1";
            $diag['request_url'] = $url;
            $response = wp_remote_get( $url, [ 'timeout' => 8, 'user-agent' => 'JJPetWasteServices/1.0' ] );
            if ( is_wp_error( $response ) ) {
                $diag['error_message'] = $response->get_error_message();
                return $diag;
            }
            $diag['http_status'] = (int) wp_remote_retrieve_response_code( $response );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $data[0]['lat'] ) ) {
                $diag['lat'] = (float) $data[0]['lat'];
                $diag['lng'] = (float) $data[0]['lon'];
            } else {
                $diag['error_message'] = 'Nominatim returned no results for this address.';
            }
            return $diag;
        }

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$api_key}";
        $diag['request_url'] = preg_replace( '/key=[^&]+/', 'key=***REDACTED***', $url );

        $response = wp_remote_get( $url, [ 'timeout' => 8, 'user-agent' => 'JJPetWasteServices/1.0' ] );
        if ( is_wp_error( $response ) ) {
            $diag['error_message'] = 'HTTP error: ' . $response->get_error_message();
            return $diag;
        }

        $diag['http_status'] = (int) wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        $diag['api_status'] = $data['status'] ?? 'UNKNOWN';

        if ( ( $data['status'] ?? '' ) !== 'OK' ) {
            $hint = $this->google_status_hint( $data['status'] ?? '' );
            $diag['error_message'] = ( $data['error_message'] ?? "Google API status: {$diag['api_status']}" )
                . ( $hint ? "  Hint: {$hint}" : '' );
            error_log( 'JJPWS Google geocoding failed: ' . $diag['error_message'] );
            return $diag;
        }

        if ( empty( $data['results'][0]['geometry']['location'] ) ) {
            $diag['error_message'] = 'Google returned OK but no geometry — address may be ambiguous.';
            return $diag;
        }

        $loc = $data['results'][0]['geometry']['location'];
        $diag['lat'] = (float) $loc['lat'];
        $diag['lng'] = (float) $loc['lng'];

        return $diag;
    }

    private function google_status_hint( string $status ): string {
        return match ( $status ) {
            'REQUEST_DENIED'   => 'Usually means the API key is invalid, the Geocoding API isn\'t enabled, or HTTP-referrer restrictions are blocking server-side calls. Server requests have NO referrer — use IP restrictions (or none) for this key, NOT HTTP-referrer restrictions.',
            'OVER_QUERY_LIMIT' => 'You\'ve exceeded your daily quota or rate limit.',
            'OVER_DAILY_LIMIT' => 'Billing isn\'t enabled on the Google Cloud project, or your daily quota is exhausted.',
            'INVALID_REQUEST'  => 'The address or parameters are malformed.',
            'ZERO_RESULTS'     => 'Google couldn\'t find this address.',
            default            => '',
        };
    }

    private function geocode( string $street, string $city, string $state, string $zip ): ?array {
        $google_key = $this->get_google_api_key();
        $arcgis_key = $this->get_arcgis_developer_key();

        // 1. Google Maps (geocode_diagnostic handles the full Google call + captures
        //    detailed status for the admin diagnostic tool).
        if ( ! empty( $google_key ) ) {
            $diag = $this->geocode_diagnostic( $street, $city, $state, $zip );
            if ( $diag['lat'] !== null ) {
                return [ 'lat' => $diag['lat'], 'lng' => $diag['lng'] ];
            }
            error_log( 'JJPWS geocode: Google failed (' . ( $diag['error_message'] ?? 'unknown' ) . '), trying next provider.' );
        }

        // 2. ArcGIS World Geocoder (if developer key configured).
        if ( ! empty( $arcgis_key ) ) {
            $result = $this->arcgis_geocode( $street, $city, $state, $zip, $arcgis_key );
            if ( $result ) {
                return $result;
            }
            error_log( 'JJPWS geocode: ArcGIS World Geocoder failed, falling back to Nominatim.' );
        }

        // 3. Nominatim (OpenStreetMap) — always last resort.
        return $this->nominatim_geocode( $street, $city, $state, $zip );
    }

    /**
     * Geocode via ArcGIS World Geocoding Service using a Developer API key.
     * Returns ['lat' => float, 'lng' => float] or null on failure.
     */
    private function arcgis_geocode( string $street, string $city, string $state, string $zip, string $api_key ): ?array {
        $single_line = urlencode( "{$street}, {$city}, {$state} {$zip}" );
        $url         = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates'
                     . '?SingleLine=' . $single_line
                     . '&f=json&maxLocations=1&outFields=Match_addr'
                     . '&token=' . urlencode( $api_key );

        $response = wp_remote_get( $url, [
            'timeout'    => 10,
            'user-agent' => 'JJPetWasteServices/1.0',
        ] );

        if ( is_wp_error( $response ) ) {
            error_log( 'JJPWS ArcGIS geocode HTTP error: ' . $response->get_error_message() );
            return null;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            error_log( "JJPWS ArcGIS geocode HTTP {$code}" );
            return null;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['candidates'][0]['location'] ) ) {
            error_log( 'JJPWS ArcGIS geocode: no candidates returned.' );
            return null;
        }

        // ArcGIS returns x = longitude, y = latitude
        $loc = $data['candidates'][0]['location'];
        return [ 'lat' => (float) $loc['y'], 'lng' => (float) $loc['x'] ];
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

    /**
     * Query the ArcGIS Living Atlas USA_Parcels national dataset.
     * Works anywhere in the US using the developer API key as the token.
     * Acts as a universal fallback when the county GIS endpoint misses.
     */
    private function living_atlas_lookup( float $lat, float $lng, string $api_key ): ?float {
        $url = 'https://services.arcgis.com/P3ePLMYs2RVChkJx/arcgis/rest/services/USA_Parcels/FeatureServer/0/query'
             . '?' . http_build_query( [
                 'geometry'       => wp_json_encode( [
                     'x'                => $lng,
                     'y'                => $lat,
                     'spatialReference' => [ 'wkid' => 4326 ],
                 ] ),
                 'geometryType'   => 'esriGeometryPoint',
                 'inSR'           => '4326',
                 'spatialRel'     => 'esriSpatialRelIntersects',
                 'outFields'      => 'LOT_SIZE_AC,CALC_ACREAGE,GIS_ACRES,ACRES,AREACALC',
                 'returnGeometry' => 'false',
                 'f'              => 'json',
                 'token'          => $api_key,
             ] );

        $response = wp_remote_get( $url, [
            'timeout'    => 10,
            'user-agent' => 'JJPetWasteServices/1.0',
        ] );

        if ( is_wp_error( $response ) ) {
            error_log( 'JJPWS Living Atlas HTTP error: ' . $response->get_error_message() );
            return null;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            error_log( "JJPWS Living Atlas HTTP {$code}" );
            return null;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $data['error'] ) ) {
            error_log( 'JJPWS Living Atlas API error: ' . ( $data['error']['message'] ?? json_encode( $data['error'] ) ) );
            return null;
        }

        if ( empty( $data['features'][0]['attributes'] ) ) {
            return null;
        }

        $attrs = $data['features'][0]['attributes'];

        foreach ( [ 'LOT_SIZE_AC', 'CALC_ACREAGE', 'GIS_ACRES', 'ACRES', 'AREACALC' ] as $field ) {
            if ( isset( $attrs[ $field ] ) && is_numeric( $attrs[ $field ] ) && (float) $attrs[ $field ] > 0 ) {
                return (float) $attrs[ $field ];
            }
        }

        // Fuzzy: any field with "acre" in the name
        foreach ( $attrs as $k => $v ) {
            if ( stripos( $k, 'acre' ) !== false && is_numeric( $v ) && (float) $v > 0 ) {
                return (float) $v;
            }
        }

        return null;
    }

    private function get_google_api_key(): string {
        $keys = get_option( 'jjpws_api_keys', [] );
        if ( is_string( $keys ) ) $keys = maybe_unserialize( $keys );
        return is_array( $keys ) ? ( $keys['google_maps'] ?? '' ) : '';
    }

    private function get_arcgis_developer_key(): string {
        $keys = get_option( 'jjpws_api_keys', [] );
        if ( is_string( $keys ) ) $keys = maybe_unserialize( $keys );
        return is_array( $keys ) ? ( $keys['arcgis_developer_key'] ?? '' ) : '';
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
