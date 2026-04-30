<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjpws-admin-wrap">
    <h1><?php esc_html_e( 'JJ Pet Waste — Business & API Settings', 'jjpws-booking' ); ?></h1>

    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'jjpws-booking' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'jjpws_save_settings' ); ?>
        <input type="hidden" name="action" value="jjpws_save_settings" />

        <h2><?php esc_html_e( 'Business Information', 'jjpws-booking' ); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="business_address"><?php esc_html_e( 'Business Address', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="text" id="business_address" name="business_address"
                               value="<?php echo esc_attr( $business_address ); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e( 'Used as the origin for all distance calculations. Customers further than your max-mile cap are blocked from booking.', 'jjpws-booking' ); ?>
                        </p>
                        <?php if ( is_array( $origin_coords ) && ! empty( $origin_coords['lat'] ) ) : ?>
                            <p class="description" style="color:green;">
                                ✓ Resolved to: <?php echo esc_html( $origin_coords['lat'] . ', ' . $origin_coords['lng'] ); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="business_email"><?php esc_html_e( 'Business Email', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="email" id="business_email" name="business_email"
                               value="<?php echo esc_attr( $business_email ); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e( 'Receives booking notifications and quote requests. Defaults to the WP admin email if blank.', 'jjpws-booking' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="business_phone"><?php esc_html_e( 'Business Phone', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="text" id="business_phone" name="business_phone"
                               value="<?php echo esc_attr( $business_phone ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Optional, shown on quote-request screens.', 'jjpws-booking' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top:2em;"><?php esc_html_e( 'Distance / Travel Fee', 'jjpws-booking' ); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label><?php esc_html_e( 'Free Miles', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="number" min="0" step="0.1" name="distance_free_miles"
                               value="<?php echo esc_attr( $distance['free_miles'] ); ?>" style="width:100px;" />
                        <p class="description"><?php esc_html_e( 'No travel fee within this radius from your business.', 'jjpws-booking' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Fee Per Mile ($)', 'jjpws-booking' ); ?></label></th>
                    <td>
                        $ <input type="number" min="0" step="0.01" name="distance_per_mile"
                               value="<?php echo esc_attr( number_format( $distance['per_mile_cents'] / 100, 2 ) ); ?>"
                               style="width:100px;" />
                        <p class="description"><?php esc_html_e( 'Charged per mile beyond the free radius, per visit.', 'jjpws-booking' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Maximum Service Radius (miles)', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="number" min="0" step="0.1" name="distance_max_miles"
                               value="<?php echo esc_attr( $distance['max_miles'] ); ?>" style="width:100px;" />
                        <p class="description"><?php esc_html_e( 'Customers beyond this radius cannot self-book.', 'jjpws-booking' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top:2em;"><?php esc_html_e( 'Brand Color', 'jjpws-booking' ); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="primary_color"><?php esc_html_e( 'Primary Color', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="color" id="primary_color" name="primary_color"
                               value="<?php echo esc_attr( $primary_color ); ?>" />
                        <p class="description"><?php esc_html_e( 'Used for the booking form buttons, highlights, and price display.', 'jjpws-booking' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top:2em;"><?php esc_html_e( 'Stripe', 'jjpws-booking' ); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="stripe_mode"><?php esc_html_e( 'Mode', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <select name="stripe_mode" id="stripe_mode">
                            <option value="test" <?php selected( $mode, 'test' ); ?>>Test</option>
                            <option value="live" <?php selected( $mode, 'live' ); ?>>Live</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Test Secret Key', 'jjpws-booking' ); ?></th>
                    <td>
                        <input type="password" name="stripe_test_secret" placeholder="sk_test_..." class="regular-text" autocomplete="off" />
                        <?php if ( ! empty( $keys['stripe_test_secret'] ) ) : ?>
                            <p class="description" style="color:green;">✓ Saved. Leave blank to keep.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Live Secret Key', 'jjpws-booking' ); ?></th>
                    <td>
                        <input type="password" name="stripe_live_secret" placeholder="sk_live_..." class="regular-text" autocomplete="off" />
                        <?php if ( ! empty( $keys['stripe_live_secret'] ) ) : ?>
                            <p class="description" style="color:green;">✓ Saved. Leave blank to keep.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Webhook Secret', 'jjpws-booking' ); ?></th>
                    <td>
                        <input type="password" name="stripe_webhook_secret_direct" placeholder="whsec_..." class="regular-text" autocomplete="off" />
                        <?php if ( $webhook_secret ) : ?>
                            <p class="description" style="color:green;">✓ Saved. Leave blank to keep.</p>
                        <?php endif; ?>
                        <p class="description">
                            <?php esc_html_e( 'Webhook URL:', 'jjpws-booking' ); ?>
                            <code><?php echo esc_html( $webhook_url ); ?></code>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top:2em;"><?php esc_html_e( 'External APIs', 'jjpws-booking' ); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'Google Maps API Key', 'jjpws-booking' ); ?></th>
                    <td>
                        <input type="password" name="google_maps" class="regular-text" autocomplete="off" />
                        <?php if ( ! empty( $keys['google_maps'] ) ) : ?>
                            <p class="description" style="color:green;">✓ Saved. Leave blank to keep.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Regrid API Token', 'jjpws-booking' ); ?></th>
                    <td>
                        <input type="password" name="regrid" class="regular-text" autocomplete="off" />
                        <?php if ( ! empty( $keys['regrid'] ) ) : ?>
                            <p class="description" style="color:green;">✓ Saved. Leave blank to keep.</p>
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'Required for automatic lot size detection. Without it, customers manually pick the acreage tier.', 'jjpws-booking' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button( __( 'Save Settings', 'jjpws-booking' ) ); ?>
    </form>
</div>
