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
     * Default endpoint: Cherokee County GA GIS — public open data.
     *
     * @return float|null acres, or null on miss/failure
     */
    private function arcgis_lookup( float $lat, float $lng ): ?float {
        $endpoint = $this->get_arcgis_endpoint();
        $field    = $this->get_arcgis_field();

        if ( empty( $endpoint ) || empty( $field ) ) {
            return null;
        }

        $params = [
            'geometry'       => "{$lng},{$lat}",
            'geometryType'   => 'esriGeometryPoint',
            'inSR'           => '4326',
            'spatialRel'     => 'esriSpatialRelIntersects',
            'outFields'      => $field,
            'outSR'          => '4326',
            'returnGeometry' => 'false',
            'f'              => 'json',
        ];

        // Strip any pre-existing query string the admin may have pasted in
        $base = strtok( $endpoint, '?' );
        $url  = $base . '?' . http_build_query( $params );

        $response = $this->http_get( $url );
        if ( ! $response ) return null;

        $data = json_decode( $response, true );
        if ( empty( $data['features'][0]['attributes'] ) ) return null;

        $attrs = $data['features'][0]['attributes'];

        // Try exact field, then a few common case variants
        $value = $attrs[ $field ] ?? null;
        if ( $value === null ) {
            foreach ( $attrs as $k => $v ) {
                if ( strcasecmp( $k, $field ) === 0 ) {
                    $value = $v;
                    break;
                }
            }
        }

        if ( $value === null || $value === '' ) return null;

        return (float) $value;
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
