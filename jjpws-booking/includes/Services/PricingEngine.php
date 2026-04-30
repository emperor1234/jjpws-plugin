<?php

namespace JJPWS\Services;

class PricingEngine {

    public const DOG_TIER_1   = '1';
    public const DOG_TIER_2_3 = '2-3';
    public const DOG_TIER_4   = '4';

    public const FREQ_TWICE_WEEKLY = 'twice_weekly';
    public const FREQ_WEEKLY       = 'weekly';
    public const FREQ_BIWEEKLY     = 'biweekly';

    public const SERVICE_RECURRING = 'recurring';
    public const SERVICE_ONE_TIME  = 'one_time';

    public const VISITS_PER_MONTH = [
        self::FREQ_TWICE_WEEKLY => 8,
        self::FREQ_WEEKLY       => 4,
        self::FREQ_BIWEEKLY     => 2,
    ];

    public const FREQ_LABELS = [
        self::FREQ_TWICE_WEEKLY => 'Twice a Week',
        self::FREQ_WEEKLY       => 'Weekly',
        self::FREQ_BIWEEKLY     => 'Bi-Weekly',
    ];

    public const DOG_TIER_LABELS = [
        self::DOG_TIER_1   => '1 dog',
        self::DOG_TIER_2_3 => '2–3 dogs',
        self::DOG_TIER_4   => '4 dogs',
    ];

    public const TIME_SINCE_OPTIONS = [
        'recent'  => [ 'label' => 'Less than 4 weeks ago', 'surcharge_key' => 'recent' ],
        'mid'     => [ 'label' => '4–7 weeks ago',         'surcharge_key' => 'mid' ],
        'long'    => [ 'label' => '8+ weeks ago / new yard', 'surcharge_key' => 'long' ],
    ];

    /**
     * Map raw dog count to billing tier. Returns null for 5+ (quote only).
     */
    public static function dog_tier_for( int $count ): ?string {
        if ( $count === 1 )                    return self::DOG_TIER_1;
        if ( $count >= 2 && $count <= 3 )      return self::DOG_TIER_2_3;
        if ( $count === 4 )                    return self::DOG_TIER_4;
        return null;
    }

    public static function requires_quote_for_dogs( int $count ): bool {
        return $count >= 5;
    }

    /**
     * Calculate full price breakdown.
     *
     * Inputs (associative array):
     *   service_type:        'recurring' | 'one_time'
     *   acreage_tier:        'small' | 'medium'
     *   dog_count:           int 1-4
     *   frequency:           'twice_weekly' | 'weekly' | 'biweekly' (recurring only)
     *   time_since_cleaned:  'recent' | 'mid' | 'long'
     *   distance_miles:      float (0 if not yet known)
     *   annual_prepay:       bool (recurring only)
     *
     * Returns full breakdown (all amounts in cents).
     */
    public static function calculate( array $params ): array {
        $service_type = $params['service_type'] ?? self::SERVICE_RECURRING;
        $acreage_tier = $params['acreage_tier'] ?? LotSizeClassifier::TIER_SMALL;
        $dog_count    = (int) ( $params['dog_count'] ?? 1 );
        $frequency    = $params['frequency'] ?? self::FREQ_WEEKLY;
        $time_since   = $params['time_since_cleaned'] ?? 'recent';
        $miles        = (float) ( $params['distance_miles'] ?? 0 );
        $annual       = ! empty( $params['annual_prepay'] );

        $matrix       = self::get_matrix();
        $surcharges   = self::get_surcharges();
        $distance_cfg = self::get_distance_config();
        $one_time     = self::get_one_time_price_cents();

        $premium_pct  = (float) ( $matrix['acreage_premium_pct'] ?? 5 );
        $premium_mult = $acreage_tier === LotSizeClassifier::TIER_MEDIUM
            ? 1 + ( $premium_pct / 100 )
            : 1.0;

        // Distance fee per visit (one-time uses single visit)
        $extra_miles      = max( 0, $miles - (float) $distance_cfg['free_miles'] );
        $distance_per_visit_cents = (int) round( $extra_miles * (int) $distance_cfg['per_mile_cents'] );

        $neglect_cents = (int) ( $surcharges['time_since_cleaned'][ $time_since ] ?? 0 );

        if ( $service_type === self::SERVICE_ONE_TIME ) {
            return self::calculate_one_time(
                $one_time,
                $acreage_tier,
                $premium_mult,
                $distance_per_visit_cents,
                $neglect_cents
            );
        }

        return self::calculate_recurring(
            $matrix,
            $acreage_tier,
            $premium_mult,
            $dog_count,
            $frequency,
            $distance_per_visit_cents,
            $neglect_cents,
            $annual
        );
    }

    private static function calculate_one_time(
        int $one_time_cents,
        string $acreage_tier,
        float $premium_mult,
        int $distance_per_visit_cents,
        int $neglect_cents
    ): array {
        $base_cents       = (int) round( $one_time_cents * $premium_mult );
        $premium_cents    = $base_cents - $one_time_cents;
        $total_cents      = $base_cents + $distance_per_visit_cents + $neglect_cents;

        return [
            'service_type'             => self::SERVICE_ONE_TIME,
            'acreage_tier'             => $acreage_tier,
            'one_time_base_cents'      => $one_time_cents,
            'acreage_premium_cents'    => $premium_cents,
            'distance_fee_cents'       => $distance_per_visit_cents,
            'neglect_surcharge_cents'  => $neglect_cents,
            'total_cents'              => $total_cents,
            'total_formatted'          => self::format_cents( $total_cents ),
            'first_payment_cents'      => $total_cents,  // single charge
            'recurring_monthly_cents'  => 0,
        ];
    }

    private static function calculate_recurring(
        array $matrix,
        string $acreage_tier,
        float $premium_mult,
        int $dog_count,
        string $frequency,
        int $distance_per_visit_cents,
        int $neglect_cents,
        bool $annual_prepay
    ): array {
        $tier = self::dog_tier_for( $dog_count );

        if ( $tier === null ) {
            throw new \InvalidArgumentException( 'Dog count requires custom quote (5+).' );
        }

        $base_per_visit          = (int) ( $matrix['base'][ $tier ][ $frequency ] ?? 0 );
        $per_visit_with_premium  = (int) round( $base_per_visit * $premium_mult );
        $premium_per_visit_cents = $per_visit_with_premium - $base_per_visit;
        $per_visit_with_distance = $per_visit_with_premium + $distance_per_visit_cents;

        $visits_per_month  = self::VISITS_PER_MONTH[ $frequency ] ?? 4;
        $monthly_cents     = $per_visit_with_distance * $visits_per_month;

        $first_payment     = $monthly_cents + $neglect_cents;

        $annual_cents       = 0;
        $annual_savings     = 0;

        if ( $annual_prepay ) {
            $year_total      = $monthly_cents * 12;
            $annual_cents    = (int) round( $year_total * 0.90 );  // 10% off
            $annual_savings  = $year_total - $annual_cents;
            $first_payment   = $annual_cents + $neglect_cents;
        }

        return [
            'service_type'             => self::SERVICE_RECURRING,
            'acreage_tier'             => $acreage_tier,
            'dog_tier'                 => $tier,
            'frequency'                => $frequency,
            'visits_per_month'         => $visits_per_month,
            'base_per_visit_cents'     => $base_per_visit,
            'acreage_premium_cents'    => $premium_per_visit_cents,
            'distance_fee_per_visit'   => $distance_per_visit_cents,
            'distance_fee_monthly'     => $distance_per_visit_cents * $visits_per_month,
            'per_visit_total_cents'    => $per_visit_with_distance,
            'monthly_cents'            => $monthly_cents,
            'recurring_monthly_cents'  => $monthly_cents,
            'neglect_surcharge_cents'  => $neglect_cents,
            'annual_prepay'            => $annual_prepay,
            'annual_total_cents'       => $annual_cents,
            'annual_savings_cents'     => $annual_savings,
            'first_payment_cents'      => $first_payment,
            'total_cents'              => $first_payment,
            'total_formatted'          => self::format_cents( $first_payment ),
        ];
    }

    public static function format_cents( int $cents ): string {
        return '$' . number_format( $cents / 100, 2 );
    }

    // ── Settings storage ─────────────────────────────────────────────────────

    public static function get_matrix(): array {
        $stored = get_option( 'jjpws_pricing_matrix' );

        if ( $stored ) {
            $decoded = json_decode( $stored, true );
            if ( is_array( $decoded ) && isset( $decoded['base'] ) ) {
                return $decoded;
            }
        }

        return self::default_matrix();
    }

    public static function default_matrix(): array {
        return [
            'base' => [
                self::DOG_TIER_1 => [
                    self::FREQ_TWICE_WEEKLY => 1800,
                    self::FREQ_WEEKLY       => 1700,
                    self::FREQ_BIWEEKLY     => 1900,
                ],
                self::DOG_TIER_2_3 => [
                    self::FREQ_TWICE_WEEKLY => 2200,
                    self::FREQ_WEEKLY       => 2100,
                    self::FREQ_BIWEEKLY     => 2300,
                ],
                self::DOG_TIER_4 => [
                    self::FREQ_TWICE_WEEKLY => 2700,
                    self::FREQ_WEEKLY       => 2600,
                    self::FREQ_BIWEEKLY     => 2800,
                ],
            ],
            'acreage_premium_pct' => 5,
        ];
    }

    public static function get_surcharges(): array {
        $stored = get_option( 'jjpws_surcharges' );

        if ( $stored ) {
            $decoded = json_decode( $stored, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return self::default_surcharges();
    }

    public static function default_surcharges(): array {
        return [
            'time_since_cleaned' => [
                'recent' => 0,
                'mid'    => 2500,
                'long'   => 5000,
            ],
        ];
    }

    public static function get_distance_config(): array {
        $stored = get_option( 'jjpws_distance_config' );

        if ( $stored ) {
            $decoded = json_decode( $stored, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return self::default_distance_config();
    }

    public static function default_distance_config(): array {
        return [
            'free_miles'     => 5,
            'per_mile_cents' => 250,
            'max_miles'      => 15,
        ];
    }

    public static function get_one_time_price_cents(): int {
        return (int) get_option( 'jjpws_one_time_price_cents', 7000 );
    }
}
