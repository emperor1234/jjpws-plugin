<?php

namespace JJPWS\Services;

class LotSizeClassifier {

    private static array $labels = [
        'xs' => 'Under 3,000 sq ft',
        'sm' => '3,000 – 6,000 sq ft',
        'md' => '6,000 – 10,000 sq ft',
        'lg' => '10,000 – 18,000 sq ft',
        'xl' => '18,000+ sq ft',
    ];

    public static function classify( int $sqft ): string {
        $thresholds = self::get_thresholds();

        foreach ( $thresholds as $category => $range ) {
            if ( $sqft >= $range['min'] && $sqft <= $range['max'] ) {
                return $category;
            }
        }

        return 'xl';
    }

    public static function label( string $category ): string {
        return self::$labels[ $category ] ?? $category;
    }

    public static function all_categories(): array {
        return array_keys( self::$labels );
    }

    public static function all_labels(): array {
        return self::$labels;
    }

    private static function get_thresholds(): array {
        $stored = get_option( 'jjpws_lot_size_thresholds' );

        if ( $stored ) {
            $decoded = json_decode( $stored, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return [
            'xs' => [ 'min' => 0,     'max' => 2999  ],
            'sm' => [ 'min' => 3000,  'max' => 5999  ],
            'md' => [ 'min' => 6000,  'max' => 9999  ],
            'lg' => [ 'min' => 10000, 'max' => 17999 ],
            'xl' => [ 'min' => 18000, 'max' => PHP_INT_MAX ],
        ];
    }
}
