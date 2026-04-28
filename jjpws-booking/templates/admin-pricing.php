<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjpws-admin-wrap">
    <h1><?php esc_html_e( 'JJ Pet Waste — Pricing Settings', 'jjpws-booking' ); ?></h1>

    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Pricing saved successfully.', 'jjpws-booking' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'jjpws_save_pricing' ); ?>
        <input type="hidden" name="action" value="jjpws_save_pricing" />

        <!-- Base Price Table -->
        <h2><?php esc_html_e( 'Base Monthly Price (1 Dog)', 'jjpws-booking' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Enter prices in dollars (e.g. 60 for $60.00)', 'jjpws-booking' ); ?></p>

        <table class="wp-list-table widefat fixed striped jjpws-pricing-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Lot Size', 'jjpws-booking' ); ?></th>
                    <?php foreach ( $freq_labels as $key => $label ) : ?>
                        <th><?php echo esc_html( $label ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $labels as $cat => $lot_label ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $lot_label ); ?></strong></td>
                    <?php foreach ( $freq_labels as $freq => $flabel ) :
                        $val = number_format( ( $matrix['base'][ $cat ][ $freq ] ?? 0 ) / 100, 2 );
                    ?>
                        <td>
                            <label for="base_<?php echo esc_attr( "{$cat}_{$freq}" ); ?>" class="screen-reader-text">
                                <?php echo esc_html( "{$lot_label} — {$flabel}" ); ?>
                            </label>
                            <div class="jjpws-price-input">
                                <span>$</span>
                                <input type="number" step="0.01" min="0"
                                       id="base_<?php echo esc_attr( "{$cat}_{$freq}" ); ?>"
                                       name="base_<?php echo esc_attr( "{$cat}_{$freq}" ); ?>"
                                       value="<?php echo esc_attr( $val ); ?>" />
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Dog Adder -->
        <h2 style="margin-top:2em;"><?php esc_html_e( 'Per Additional Dog Adder (Monthly)', 'jjpws-booking' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Amount added per each dog above the first.', 'jjpws-booking' ); ?></p>

        <table class="wp-list-table widefat fixed striped jjpws-pricing-table jjpws-adder-table">
            <thead>
                <tr>
                    <?php foreach ( $freq_labels as $key => $label ) : ?>
                        <th><?php echo esc_html( $label ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach ( $freq_labels as $freq => $flabel ) :
                        $val = number_format( ( $matrix['dog_adder'][ $freq ] ?? 0 ) / 100, 2 );
                    ?>
                        <td>
                            <div class="jjpws-price-input">
                                <span>$</span>
                                <input type="number" step="0.01" min="0"
                                       name="adder_<?php echo esc_attr( $freq ); ?>"
                                       value="<?php echo esc_attr( $val ); ?>" />
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>

        <!-- Lot Size Thresholds -->
        <h2 style="margin-top:2em;"><?php esc_html_e( 'Lot Size Thresholds (sq ft)', 'jjpws-booking' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Define the square footage boundaries for each lot size category.', 'jjpws-booking' ); ?></p>

        <?php
        $thresholds_raw = get_option( 'jjpws_lot_size_thresholds' );
        $thresholds = [];
        if ( $thresholds_raw ) {
            $thresholds = json_decode( $thresholds_raw, true ) ?: [];
        }
        $default_thresholds = [
            'xs' => [ 'min' => 0,     'max' => 2999  ],
            'sm' => [ 'min' => 3000,  'max' => 5999  ],
            'md' => [ 'min' => 6000,  'max' => 9999  ],
            'lg' => [ 'min' => 10000, 'max' => 17999 ],
            'xl' => [ 'min' => 18000, 'max' => PHP_INT_MAX ],
        ];
        ?>

        <table class="wp-list-table widefat fixed striped jjpws-pricing-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Category', 'jjpws-booking' ); ?></th>
                    <th><?php esc_html_e( 'Min (sq ft)', 'jjpws-booking' ); ?></th>
                    <th><?php esc_html_e( 'Max (sq ft)', 'jjpws-booking' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $labels as $cat => $lot_label ) :
                $t   = $thresholds[ $cat ] ?? $default_thresholds[ $cat ];
                $min = $t['min'] ?? 0;
                $max = $cat === 'xl' ? '' : ( $t['max'] ?? 0 );
            ?>
                <tr>
                    <td><strong><?php echo esc_html( $lot_label ); ?></strong></td>
                    <td>
                        <input type="number" min="0" name="threshold_min_<?php echo esc_attr( $cat ); ?>"
                               value="<?php echo absint( $min ); ?>" />
                    </td>
                    <td>
                        <?php if ( $cat === 'xl' ) : ?>
                            <input type="text" value="Unlimited" readonly style="background:#f0f0f0;color:#888;" />
                            <input type="hidden" name="threshold_max_<?php echo esc_attr( $cat ); ?>" value="999999999" />
                        <?php else : ?>
                            <input type="number" min="0" name="threshold_max_<?php echo esc_attr( $cat ); ?>"
                                   value="<?php echo absint( $max ); ?>" />
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php submit_button( __( 'Save Pricing', 'jjpws-booking' ) ); ?>
    </form>
</div>
