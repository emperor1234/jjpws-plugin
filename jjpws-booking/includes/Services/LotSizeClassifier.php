<?php

namespace JJPWS\Services;

class LotSizeClassifier {

    public const TIER_SMALL  = 'small';   // <1.0 acre
    public const TIER_MEDIUM = 'medium';  // 1.0–1.5 acres
    public const TIER_LARGE  = 'large';   // 1.5+ acres → quote only

    public const SQFT_PER_ACRE = 43560;

    private static array $labels = [
        self::TIER_SMALL  => 'Under 1 acre',
        self::TIER_MEDIUM => '1 – 1.5 acres',
        self::TIER_LARGE  => 'Over 1.5 acres (custom quote)',
    ];

    /**
     * Classify a lot by acreage. Returns null only if input invalid.
     */
    public static function classify_by_acres( float $acres ): string {
        if ( $acres < 1.0 ) {
            return self::TIER_SMALL;
        }
        if ( $acres < 1.5 ) {
            return self::TIER_MEDIUM;
        }
        return self::TIER_LARGE;
    }

    public static function classify_by_sqft( int $sqft ): string {
        return self::classify_by_acres( self::sqft_to_acres( $sqft ) );
    }

    public static function sqft_to_acres( int $sqft ): float {
        return round( $sqft / self::SQFT_PER_ACRE, 3 );
    }

    public static function label( string $tier ): string {
        return self::$labels[ $tier ] ?? $tier;
    }

    public static function all_labels(): array {
        return self::$labels;
    }

    /**
     * Returns true if this tier requires a custom quote (no self-checkout).
     */
    public static function requires_quote( string $tier ): bool {
        return $tier === self::TIER_LARGE;
    }
}
