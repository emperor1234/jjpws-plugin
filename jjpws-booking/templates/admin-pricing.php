<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjpws-admin-wrap">
    <h1><?php esc_html_e( 'JJ Pet Waste — Pricing', 'jjpws-booking' ); ?></h1>

    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Pricing saved.', 'jjpws-booking' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'jjpws_save_pricing' ); ?>
        <input type="hidden" name="action" value="jjpws_save_pricing" />

        <h2><?php esc_html_e( 'Per-Service Base Rates (under 1 acre)', 'jjpws-booking' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Per-visit prices in dollars. Yards 1–1.5 acres receive the premium below.', 'jjpws-booking' ); ?>
        </p>

        <table class="wp-list-table widefat fixed striped jjpws-pricing-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Dog Count', 'jjpws-booking' ); ?></th>
                    <?php foreach ( $freq_labels as $key => $label ) : ?>
                        <th><?php echo esc_html( $label ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $dog_labels as $dt => $dt_label ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $dt_label ); ?></strong></td>
                    <?php foreach ( $freq_labels as $f => $f_label ) :
                        $val = number_format( ( $matrix['base'][ $dt ][ $f ] ?? 0 ) / 100, 2 );
                    ?>
                        <td>
                            <div class="jjpws-price-input">
                                <span>$</span>
                                <input type="number" step="0.01" min="0"
                                       name="base_<?php echo esc_attr( "{$dt}_{$f}" ); ?>"
                                       value="<?php echo esc_attr( $val ); ?>" />
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2 style="margin-top:2em;"><?php esc_html_e( '1–1.5 Acre Yard Premium', 'jjpws-booking' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Percentage added to base rates for yards in the 1–1.5 acre tier.', 'jjpws-booking' ); ?>
        </p>
        <p>
            <input type="number" step="0.1" min="0" max="100"
                   name="acreage_premium_pct"
                   value="<?php echo esc_attr( $matrix['acreage_premium_pct'] ?? 5 ); ?>"
                   style="width:80px;" /> %
        </p>

        <h2 style="margin-top:2em;"><?php esc_html_e( 'One-Time Cleanup Price', 'jjpws-booking' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Flat price for a single cleanup of yards under 1.5 acres. The 1–1.5 acre premium and any neglect surcharge still apply.', 'jjpws-booking' ); ?>
        </p>
        <p>
            <span>$</span>
            <input type="number" step="0.01" min="0"
                   name="one_time_price"
                   value="<?php echo esc_attr( number_format( $one_time / 100, 2 ) ); ?>"
                   style="width:120px;" />
        </p>

        <h2 style="margin-top:2em;"><?php esc_html_e( 'Neglect Surcharge — Time Since Last Cleaned', 'jjpws-booking' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'One-time surcharge applied to the first payment based on how long since the yard was last serviced.', 'jjpws-booking' ); ?>
        </p>

        <table class="wp-list-table widefat fixed striped jjpws-pricing-table" style="max-width:600px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Time Since Last Cleaned', 'jjpws-booking' ); ?></th>
                    <th><?php esc_html_e( 'Surcharge ($)', 'jjpws-booking' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $time_opts as $key => $opt ) :
                $val = number_format( ( $surcharges['time_since_cleaned'][ $key ] ?? 0 ) / 100, 2 );
            ?>
                <tr>
                    <td><strong><?php echo esc_html( $opt['label'] ); ?></strong></td>
                    <td>
                        <div class="jjpws-price-input">
                            <span>$</span>
                            <input type="number" step="0.01" min="0"
                                   name="neglect_<?php echo esc_attr( $key ); ?>"
                                   value="<?php echo esc_attr( $val ); ?>" />
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php submit_button( __( 'Save Pricing', 'jjpws-booking' ) ); ?>
    </form>
</div>
