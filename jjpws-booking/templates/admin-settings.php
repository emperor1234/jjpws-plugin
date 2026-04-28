<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjpws-admin-wrap">
    <h1><?php esc_html_e( 'JJ Pet Waste — API & Stripe Settings', 'jjpws-booking' ); ?></h1>

    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'jjpws-booking' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'jjpws_save_settings' ); ?>
        <input type="hidden" name="action" value="jjpws_save_settings" />

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="stripe_mode"><?php esc_html_e( 'Stripe Mode', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <select name="stripe_mode" id="stripe_mode">
                            <option value="test" <?php selected( $mode, 'test' ); ?>><?php esc_html_e( 'Test Mode', 'jjpws-booking' ); ?></option>
                            <option value="live" <?php selected( $mode, 'live' ); ?>><?php esc_html_e( 'Live Mode', 'jjpws-booking' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Use Test Mode during development. Switch to Live when ready to accept real payments.', 'jjpws-booking' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="stripe_test_secret"><?php esc_html_e( 'Stripe Test Secret Key', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="password" id="stripe_test_secret" name="stripe_test_secret" class="regular-text"
                               placeholder="sk_test_..." autocomplete="off" />
                        <?php if ( ! empty( $keys['stripe_test_secret'] ) ) : ?>
                            <p class="description" style="color:green;">✓ <?php esc_html_e( 'Key saved. Leave blank to keep existing.', 'jjpws-booking' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="stripe_live_secret"><?php esc_html_e( 'Stripe Live Secret Key', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="password" id="stripe_live_secret" name="stripe_live_secret" class="regular-text"
                               placeholder="sk_live_..." autocomplete="off" />
                        <?php if ( ! empty( $keys['stripe_live_secret'] ) ) : ?>
                            <p class="description" style="color:green;">✓ <?php esc_html_e( 'Key saved. Leave blank to keep existing.', 'jjpws-booking' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="stripe_webhook_secret_direct"><?php esc_html_e( 'Stripe Webhook Signing Secret', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="password" id="stripe_webhook_secret_direct" name="stripe_webhook_secret_direct" class="regular-text"
                               placeholder="whsec_..." autocomplete="off" />
                        <?php if ( $webhook_secret ) : ?>
                            <p class="description" style="color:green;">✓ <?php esc_html_e( 'Secret saved. Leave blank to keep existing.', 'jjpws-booking' ); ?></p>
                        <?php endif; ?>
                        <p class="description">
                            <?php esc_html_e( 'Webhook URL to register in your Stripe Dashboard:', 'jjpws-booking' ); ?>
                            <code><?php echo esc_html( $webhook_url ); ?></code>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="google_maps"><?php esc_html_e( 'Google Maps API Key', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="password" id="google_maps" name="google_maps" class="regular-text" autocomplete="off" />
                        <?php if ( ! empty( $keys['google_maps'] ) ) : ?>
                            <p class="description" style="color:green;">✓ <?php esc_html_e( 'Key saved. Leave blank to keep existing.', 'jjpws-booking' ); ?></p>
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'Required for address autocomplete. If omitted, autocomplete is disabled and Nominatim is used for geocoding (slower).', 'jjpws-booking' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="regrid"><?php esc_html_e( 'Regrid API Key', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="password" id="regrid" name="regrid" class="regular-text" autocomplete="off" />
                        <?php if ( ! empty( $keys['regrid'] ) ) : ?>
                            <p class="description" style="color:green;">✓ <?php esc_html_e( 'Key saved. Leave blank to keep existing.', 'jjpws-booking' ); ?></p>
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'Used for parcel lot size lookup. Free tier: 1,000 requests/month. If omitted, manual lot size selection is always shown.', 'jjpws-booking' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button( __( 'Save Settings', 'jjpws-booking' ) ); ?>
    </form>
</div>
