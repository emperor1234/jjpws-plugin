<?php

namespace JJPWS\Services;

class PricingEngine {

    private static array $valid_categories  = [ 'xs', 'sm', 'md', 'lg', 'xl' ];
    private static array $valid_frequencies = [ 'twice_weekly', 'weekly', 'biweekly' ];

    /**
     * Calculate monthly price in cents.
     *
     * Formula: base_price[lot_size][frequency] + dog_adder[frequency] * (dog_count - 1)
     */
    public static function calculate( string $lot_size_category, int $dog_count, string $frequency ): int {
        if ( ! in_array( $lot_size_category, self::$valid_categories, true ) ) {
            throw new \InvalidArgumentException( 'Invalid lot size category: ' . $lot_size_category );
        }

        if ( ! in_array( $frequency, self::$valid_frequencies, true ) ) {
            throw new \InvalidArgumentException( 'Invalid frequency: ' . $frequency );
        }

        if ( $dog_count < 1 || $dog_count > 10 ) {
            throw new \InvalidArgumentException( 'Dog count must be between 1 and 10.' );
        }

        $matrix    = self::get_matrix();
        $base      = $matrix['base'][ $lot_size_category ][ $frequency ];
        $adder     = $matrix['dog_adder'][ $frequency ];
        $extra     = max( 0, $dog_count - 1 );

        return (int) ( $base + ( $adder * $extra ) );
    }

    public static function get_matrix(): array {
        $stored = get_option( 'jjpws_pricing_matrix' );

        if ( $stored ) {
            $decoded = json_decode( $stored, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return self::default_matrix();
    }

    public static function default_matrix(): array {
        return [
            'base' => [
                'xs' => [ 'twice_weekly' => 6000,  'weekly' => 4000,  'biweekly' => 2500 ],
                'sm' => [ 'twice_weekly' => 7500,  'weekly' => 5000,  'biweekly' => 3200 ],
                'md' => [ 'twice_weekly' => 9000,  'weekly' => 6000,  'biweekly' => 3800 ],
                'lg' => [ 'twice_weekly' => 11000, 'weekly' => 7500,  'biweekly' => 4800 ],
                'xl' => [ 'twice_weekly' => 13000, 'weekly' => 9000,  'biweekly' => 5800 ],
            ],
            'dog_adder' => [
                'twice_weekly' => 1500,
                'weekly'       => 1000,
                'biweekly'     => 700,
            ],
        ];
    }

    public static function format_cents( int $cents ): string {
        return '$' . number_format( $cents / 100, 2 );
    }
}
