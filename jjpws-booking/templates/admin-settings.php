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

        <h2 style="margin-top:2em;"><?php esc_html_e( 'Parcel Data (ArcGIS / Open Data)', 'jjpws-booking' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Lot size lookup uses a public ArcGIS REST endpoint — typically your county GIS open data feed. Default is Cherokee County, GA. Override below if you serve a different county.', 'jjpws-booking' ); ?>
        </p>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="parcel_endpoint"><?php esc_html_e( 'ArcGIS Query Endpoint', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="url" id="parcel_endpoint" name="parcel_endpoint"
                               value="<?php echo esc_attr( $parcel_endpoint ); ?>" class="large-text" />
                        <p class="description">
                            <?php esc_html_e( 'Full URL to the FeatureServer/MapServer layer\'s /query endpoint. Example:', 'jjpws-booking' ); ?>
                            <br>
                            <code>https://gis.cherokeecountyga.gov/arcgis/rest/services/MainLayers/MapServer/1/query</code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="parcel_acreage_field"><?php esc_html_e( 'Acreage Field Name', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="text" id="parcel_acreage_field" name="parcel_acreage_field"
                               value="<?php echo esc_attr( $parcel_field ); ?>" class="regular-text" />
                        <p class="description">
                            <?php esc_html_e( 'The attribute field on each parcel that holds acreage. Common names: "Acreage", "ACRES", "GIS_ACRES".', 'jjpws-booking' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="parcel_attribution"><?php esc_html_e( 'Attribution Text', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <textarea id="parcel_attribution" name="parcel_attribution" rows="2" class="large-text"><?php echo esc_textarea( $parcel_attribution ); ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Shown below the booking form to credit the data source and disclaim that boundaries are reference-only.', 'jjpws-booking' ); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h3 style="margin-top:1.5em;"><?php esc_html_e( 'Test Parcel Lookup', 'jjpws-booking' ); ?></h3>
        <p class="description">
            <?php esc_html_e( 'Run a live lookup against the configured ArcGIS endpoint. Use a real address inside your service area. The result shows exactly what the GIS server returned, so you can spot config issues at a glance.', 'jjpws-booking' ); ?>
        </p>
        <table class="form-table" id="jjpws-diag-form">
            <tbody>
                <tr>
                    <th><label><?php esc_html_e( 'Test Address', 'jjpws-booking' ); ?></label></th>
                    <td>
                        <input type="text" id="jjpws-diag-street" placeholder="Street" class="regular-text" />
                        <input type="text" id="jjpws-diag-city"   placeholder="City"   />
                        <input type="text" id="jjpws-diag-state"  placeholder="State"  size="3" />
                        <input type="text" id="jjpws-diag-zip"    placeholder="ZIP"    size="6" />
                        <br>
                        <button type="button" class="button button-secondary" id="jjpws-diag-run" style="margin-top:8px;">
                            <?php esc_html_e( 'Run Diagnostic', 'jjpws-booking' ); ?>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <div id="jjpws-diag-result" style="display:none; margin:1em 0; padding:1em; background:#f6f7f7; border-left:4px solid #2c7a3d; border-radius:4px;"></div>

        <script>
        (function () {
            const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
            const nonce   = <?php echo wp_json_encode( wp_create_nonce( 'jjpws_diagnose' ) ); ?>;
            const btn     = document.getElementById('jjpws-diag-run');
            const out     = document.getElementById('jjpws-diag-result');

            btn?.addEventListener('click', async () => {
                btn.disabled = true;
                btn.textContent = 'Running…';
                out.style.display = 'block';
                out.innerHTML = '<em>Looking up…</em>';

                const fd = new FormData();
                fd.append('action', 'jjpws_diagnose_parcel');
                fd.append('nonce', nonce);
                fd.append('street', document.getElementById('jjpws-diag-street').value.trim());
                fd.append('city',   document.getElementById('jjpws-diag-city').value.trim());
                fd.append('state',  document.getElementById('jjpws-diag-state').value.trim());
                fd.append('zip',    document.getElementById('jjpws-diag-zip').value.trim());

                try {
                    const res  = await fetch(ajaxUrl, { method: 'POST', body: fd });
                    const json = await res.json();
                    out.innerHTML = renderDiag(json);
                } catch (e) {
                    out.innerHTML = '<strong style="color:red;">Network error:</strong> ' + e.message;
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Run Diagnostic';
                }
            });

            function renderDiag(json) {
                if (!json.success) {
                    let html = `<h4 style="color:#c0392b; margin-top:0;">✗ Failed at ${json.data?.stage || 'unknown'} stage</h4>`;
                    html += `<p>${escapeHtml(json.data?.message || 'Unknown error')}</p>`;
                    if (json.data?.geocode) {
                        const g = json.data.geocode;
                        html += `<details style="margin-top:.8em;"><summary><strong>Geocoding details</strong></summary>`;
                        html += `<p>Provider: <code>${g.provider}</code></p>`;
                        if (g.api_status) html += `<p>API status: <code>${g.api_status}</code></p>`;
                        if (g.http_status) html += `<p>HTTP status: <code>${g.http_status}</code></p>`;
                        html += `<p>Request URL:<br><code style="font-size:11px;word-break:break-all;">${escapeHtml(g.request_url || '')}</code></p>`;
                        html += `</details>`;
                    }
                    return html;
                }
                const g  = json.data.geocoded;
                const gd = json.data.geocode_detail || {};
                const p  = json.data.parcel;
                let html = `<h4 style="margin-top:0; color:#2c7a3d;">✓ Geocoding</h4>`;
                html += `<p>Lat/Lng: <code>${g.lat}, ${g.lng}</code> (provider: <code>${gd.provider || '?'}</code>)</p>`;
                html += `<h4>Parcel Lookup</h4>`;
                if (p.acres !== null) {
                    html += `<p style="color:#2c7a3d;"><strong>✓ Found parcel:</strong> ${p.acres} acres (matched field: <code>${p.matched_field}</code>)</p>`;
                } else {
                    html += `<p style="color:#c0392b;"><strong>✗ ${escapeHtml(p.error || 'No data returned')}</strong></p>`;
                }
                html += `<details style="margin-top:1em;"><summary><strong>Request URL</strong></summary><textarea readonly rows="3" style="width:100%;font-family:monospace;font-size:11px;">${escapeHtml(p.request_url || '')}</textarea></details>`;
                if (p.attributes) {
                    html += `<details style="margin-top:.5em;"><summary><strong>Returned attributes</strong></summary><pre style="font-size:11px;overflow:auto;max-height:200px;">${escapeHtml(JSON.stringify(p.attributes, null, 2))}</pre></details>`;
                }
                if (p.response_excerpt && !p.attributes) {
                    html += `<details style="margin-top:.5em;"><summary><strong>Raw response (first 1500 chars)</strong></summary><pre style="font-size:11px;overflow:auto;max-height:200px;">${escapeHtml(p.response_excerpt)}</pre></details>`;
                }
                return html;
            }

            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            }
        })();
        </script>

        <h2 style="margin-top:2em;"><?php esc_html_e( 'Google Maps API', 'jjpws-booking' ); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'Google Maps API Key', 'jjpws-booking' ); ?></th>
                    <td>
                        <input type="password" name="google_maps" class="regular-text" autocomplete="off" />
                        <?php if ( ! empty( $keys['google_maps'] ) ) : ?>
                            <p class="description" style="color:green;">✓ Saved. Leave blank to keep.</p>
                        <?php endif; ?>
                        <p class="description">
                            <?php esc_html_e( 'Used for address autocomplete (Places) and geocoding. Requires: Geocoding API, Maps JavaScript API, Places API. Without it, ArcGIS or Nominatim/OpenStreetMap is used as a fallback.', 'jjpws-booking' ); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top:2em;"><?php esc_html_e( 'ArcGIS Developer API', 'jjpws-booking' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Your ArcGIS Developer API key from developers.arcgis.com. Create a free account, open the Dashboard, click "Create an API Key", and enable the Geocoding and Routing privileges. This key powers address geocoding (finding lat/lng from a street address) and is used as a fallback when Google Maps geocoding is unavailable.', 'jjpws-booking' ); ?>
        </p>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'ArcGIS Developer API Key', 'jjpws-booking' ); ?></th>
                    <td>
                        <input type="password" name="arcgis_developer_key" class="regular-text" autocomplete="off" placeholder="AAPKxxxxxxxxxxxxxxxxxxxxxxxx" />
                        <?php if ( ! empty( $keys['arcgis_developer_key'] ) ) : ?>
                            <p class="description" style="color:green;">✓ Saved. Leave blank to keep.</p>
                        <?php endif; ?>
                        <p class="description">
                            <?php esc_html_e( 'Geocoding priority: Google Maps → ArcGIS World Geocoder → Nominatim (OpenStreetMap). Enter your ArcGIS key here to use the ArcGIS World Geocoder when Google Maps is unavailable or not configured.', 'jjpws-booking' ); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button( __( 'Save Settings', 'jjpws-booking' ) ); ?>
    </form>
</div>
