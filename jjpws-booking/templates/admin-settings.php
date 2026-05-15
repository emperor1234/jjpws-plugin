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

        <div id="jjpws-diag-result" style="display:none; margin:1.2em 0;"></div>

        <style>
        .jjpws-diag-steps { display:flex; flex-direction:column; gap:12px; font-size:13px; }
        .jjpws-diag-card  { background:#fff; border:1px solid #ddd; border-radius:6px; overflow:hidden; }
        .jjpws-diag-card-head { display:flex; align-items:center; gap:10px; padding:10px 14px; font-weight:600; font-size:13px; }
        .jjpws-diag-card-head.pass  { background:#f0fdf4; border-bottom:1px solid #bbf7d0; color:#166534; }
        .jjpws-diag-card-head.fail  { background:#fef2f2; border-bottom:1px solid #fecaca; color:#991b1b; }
        .jjpws-diag-card-head.warn  { background:#fffbeb; border-bottom:1px solid #fde68a; color:#92400e; }
        .jjpws-diag-card-head.skip  { background:#f9fafb; border-bottom:1px solid #e5e7eb; color:#6b7280; }
        .jjpws-diag-card-body { padding:10px 14px; }
        .jjpws-diag-card-body p  { margin:.35em 0; }
        .jjpws-diag-fix { margin-top:8px; padding:7px 10px; background:#fff8e1; border-left:3px solid #f59e0b; border-radius:3px; font-size:12px; }
        .jjpws-diag-fix strong { color:#92400e; }
        .jjpws-diag-provider-row { display:flex; align-items:baseline; gap:6px; padding:3px 0; border-bottom:1px solid #f3f4f6; }
        .jjpws-diag-provider-row:last-child { border-bottom:none; }
        .jjpws-diag-badge { display:inline-block; padding:1px 7px; border-radius:9px; font-size:11px; font-weight:600; white-space:nowrap; }
        .jjpws-diag-badge.ok   { background:#dcfce7; color:#166534; }
        .jjpws-diag-badge.err  { background:#fee2e2; color:#991b1b; }
        .jjpws-diag-badge.skip { background:#f3f4f6; color:#6b7280; }
        </style>

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
                out.innerHTML = '<p style="color:#555;padding:8px 0;"><em>Testing all providers — this can take up to 15 seconds…</em></p>';

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
                    out.innerHTML = json.success ? renderDiag(json.data) : renderError(json.data);
                } catch (e) {
                    out.innerHTML = card('fail', '⚠ Network Error', `<p>Could not reach the server. Check your internet connection and try again.</p><p style="font-size:11px;color:#999;">${esc(e.message)}</p>`);
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Run Diagnostic';
                }
            });

            /* ── helpers ─────────────────────────────────────────── */

            function esc(s) {
                return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            }

            function card(status, title, body) {
                return `<div class="jjpws-diag-card">
                    <div class="jjpws-diag-card-head ${esc(status)}">${esc(title)}</div>
                    <div class="jjpws-diag-card-body">${body}</div>
                </div>`;
            }

            function badge(ok, skipLabel) {
                if (skipLabel) return `<span class="jjpws-diag-badge skip">${esc(skipLabel)}</span>`;
                return ok
                    ? `<span class="jjpws-diag-badge ok">✓ Success</span>`
                    : `<span class="jjpws-diag-badge err">✗ Failed</span>`;
            }

            function fix(msg) {
                return `<div class="jjpws-diag-fix"><strong>How to fix:</strong> ${msg}</div>`;
            }

            function details(summary, content) {
                return `<details style="margin-top:6px;"><summary style="cursor:pointer;font-size:12px;color:#555;">${esc(summary)}</summary><div style="margin-top:6px;">${content}</div></details>`;
            }

            /* ── main renderer ───────────────────────────────────── */

            function renderError(data) {
                return card('fail', '✗ Error', `<p>${esc(data?.message || 'Unknown error.')}</p>`);
            }

            function renderDiag(d) {
                return `<div class="jjpws-diag-steps">
                    ${renderGeocoding(d.geocoding)}
                    ${d.geocoding?.lat != null ? renderCountyGIS(d.county_gis) : ''}
                    ${d.geocoding?.lat != null ? renderLivingAtlas(d.living_atlas) : ''}
                    ${renderOverall(d)}
                </div>`;
            }

            /* ── Step 1: Geocoding ───────────────────────────────── */

            function renderGeocoding(g) {
                if (!g) return card('fail', 'Step 1 — Address Lookup (Geocoding)', '<p>No geocoding data returned.</p>');

                const winner = g.winner;
                let body = '';

                // Provider rows
                const providers = [
                    { key: 'google',    label: 'Google Maps' },
                    { key: 'arcgis',    label: 'ArcGIS World Geocoder' },
                    { key: 'nominatim', label: 'Nominatim (OpenStreetMap)' },
                ];

                body += '<div style="margin-bottom:8px;">';
                for (const p of providers) {
                    const info = g[p.key];
                    if (!info) continue;
                    let badgeHtml, note = '';
                    if (!info.configured && p.key !== 'nominatim') {
                        badgeHtml = badge(false, 'Not configured');
                        note = ' — no API key saved';
                    } else if (!info.tried) {
                        badgeHtml = badge(false, 'Skipped');
                        note = ' — skipped (earlier provider succeeded)';
                    } else if (info.success) {
                        badgeHtml = badge(true);
                        note = ` — found <code>${info.lat}, ${info.lng}</code>`;
                    } else {
                        badgeHtml = badge(false);
                        note = info.error ? ` — ${esc(info.error)}` : '';
                    }
                    body += `<div class="jjpws-diag-provider-row">${badgeHtml} <strong>${esc(p.label)}</strong>${note}</div>`;

                    // Extra Google hint if failed
                    if (p.key === 'google' && info.tried && !info.success && info.detail) {
                        const detail = info.detail;
                        if (detail.api_status && detail.api_status !== 'OK') {
                            let hint = '';
                            if (detail.api_status === 'REQUEST_DENIED') hint = 'Your Google API key is invalid, the Geocoding API isn\'t enabled on the Cloud project, or the key has HTTP-referrer restrictions (remove them — server requests have no referrer).';
                            else if (detail.api_status === 'OVER_QUERY_LIMIT') hint = 'You\'ve hit Google\'s rate limit or daily quota. Wait and try again, or enable billing on your Google Cloud project.';
                            else if (detail.api_status === 'ZERO_RESULTS') hint = 'Google couldn\'t find this address. Check the spelling, or test with a different address.';
                            if (hint) body += fix(hint);
                        }
                    }
                    // ArcGIS not configured hint
                    if (p.key === 'arcgis' && !info.configured) {
                        body += `<div style="font-size:12px;color:#555;padding:2px 0 4px 24px;">Add your ArcGIS Developer API key in the <strong>ArcGIS Developer API</strong> section below to enable this as a geocoding fallback.</div>`;
                    }
                }
                body += '</div>';

                if (winner) {
                    body += `<p style="margin:.5em 0 0;"><strong>Used:</strong> ${esc(winner.charAt(0).toUpperCase() + winner.slice(1))} &mdash; coordinates: <code>${g.lat}, ${g.lng}</code></p>`;
                    return card('pass', '✓ Step 1 — Address Found', body);
                } else {
                    body += fix('None of the geocoding providers could find this address. Check that the address is spelled correctly and is a real US address. If Google failed, check the API key and enabled APIs. If only Nominatim was tried, add a Google Maps or ArcGIS key for better accuracy.');
                    return card('fail', '✗ Step 1 — Address Not Found', body);
                }
            }

            /* ── Step 2: County GIS ──────────────────────────────── */

            function renderCountyGIS(p) {
                if (!p) return card('skip', 'Step 2 — County GIS (skipped — geocoding failed)', '');

                let body = `<p><strong>Endpoint:</strong> <code style="font-size:11px;word-break:break-all;">${esc(p.endpoint || '(none)')}</code></p>`;
                body += `<p><strong>Acreage field setting:</strong> <code>${esc(p.configured_field || 'Acreage')}</code></p>`;

                if (p.acres !== null) {
                    body += `<p style="color:#166534;"><strong>✓ Parcel found:</strong> ${p.acres} acres (matched field: <code>${esc(p.matched_field)}</code>)</p>`;
                    return card('pass', '✓ Step 2 — County GIS Found Parcel', body);
                }

                // Failed — give targeted guidance
                body += `<p style="color:#991b1b;"><strong>✗ ${esc(p.error || 'No parcel data returned')}</strong></p>`;

                if (!p.endpoint) {
                    body += fix('No ArcGIS endpoint is configured. Enter your county\'s GIS query URL in the <strong>ArcGIS Query Endpoint</strong> field above and save.');
                } else if (p.http_error) {
                    body += fix(`The server couldn\'t reach the GIS endpoint: "${esc(p.http_error)}". Check that the URL is correct and the GIS server is online.`);
                } else if (p.http_status && p.http_status !== 200) {
                    body += fix(`The GIS server returned HTTP ${p.http_status}. This usually means the endpoint URL is wrong or the service is temporarily unavailable.`);
                } else if (p.error && p.error.includes('acreage field')) {
                    body += fix(`The parcel was found but none of its fields contained acreage. Look at the "Returned attributes" below to find the correct field name, then update the <strong>Acreage Field Name</strong> setting.`);
                } else if (p.error && p.error.includes('outside the configured GIS')) {
                    body += fix('This address is outside your county GIS coverage. That\'s normal if you serve multiple counties — the Living Atlas step below acts as a national fallback.');
                } else if (!p.endpoint || p.endpoint.includes('cherokeecountyga.gov')) {
                    body += fix('You\'re using the default Cherokee County, GA endpoint. If your customers are in a different county, update the endpoint to your county\'s ArcGIS REST URL.');
                }

                if (p.attributes) {
                    body += details('View returned parcel attributes', `<pre style="font-size:11px;overflow:auto;max-height:200px;background:#f9fafb;padding:8px;border-radius:4px;">${esc(JSON.stringify(p.attributes, null, 2))}</pre>`);
                } else if (p.response_excerpt) {
                    body += details('View raw response (first 1500 chars)', `<pre style="font-size:11px;overflow:auto;max-height:200px;background:#f9fafb;padding:8px;border-radius:4px;">${esc(p.response_excerpt)}</pre>`);
                }
                if (p.request_url) {
                    body += details('View request URL', `<code style="font-size:11px;word-break:break-all;">${esc(p.request_url)}</code>`);
                }

                return card('fail', '✗ Step 2 — County GIS: No Parcel Found', body);
            }

            /* ── Step 3: Living Atlas ────────────────────────────── */

            function renderLivingAtlas(la) {
                if (!la) return card('skip', 'Step 3 — ArcGIS Living Atlas (skipped — geocoding failed)', '');

                let body = '';

                if (!la.key_configured) {
                    body += `<p>The ArcGIS Developer API key is <strong>not configured</strong>.</p>`;
                    body += fix('Go to the <strong>ArcGIS Developer API</strong> section below, paste your developer key and save. This enables a national parcel fallback that works for any US address.');
                    return card('warn', '⚠ Step 3 — ArcGIS Living Atlas: Key Not Set', body);
                }

                if (la.success) {
                    body += `<p style="color:#166534;"><strong>✓ Parcel found:</strong> ${la.acres} acres from the national Living Atlas dataset.</p>`;
                    return card('pass', '✓ Step 3 — ArcGIS Living Atlas Found Parcel', body);
                }

                body += `<p style="color:#991b1b;"><strong>✗ ${esc(la.error || 'No parcel data returned')}</strong></p>`;
                body += fix('The Living Atlas national dataset has partial coverage. If neither the county GIS nor Living Atlas found a parcel, the customer will see a manual lot-size selector — that\'s the intended fallback and the booking can still proceed.');
                return card('warn', '⚠ Step 3 — ArcGIS Living Atlas: No Data', body);
            }

            /* ── Overall result ──────────────────────────────────── */

            function renderOverall(d) {
                const geo = d.geocoding;
                const gis = d.county_gis;
                const la  = d.living_atlas;

                if (!geo || geo.lat == null) {
                    return card('fail', '✗ Overall Result — Address Lookup Failed',
                        '<p>The system could not determine the customer\'s location. Lot size cannot be auto-detected. Fix the geocoding errors above.</p>');
                }

                const acresFound = (gis && gis.acres != null) || (la && la.success);

                if (acresFound) {
                    const source = (gis && gis.acres != null) ? 'County GIS' : 'ArcGIS Living Atlas';
                    const acres  = (gis && gis.acres != null) ? gis.acres : la.acres;
                    return card('pass', '✓ Overall Result — Lot Size Auto-Detected',
                        `<p>The booking form will automatically fill in the lot size (<strong>${acres} acres</strong>, source: ${esc(source)}). Customers at this address will not need to enter their lot size manually.</p>`);
                }

                return card('warn', '⚠ Overall Result — Manual Selection Required',
                    `<p>Geocoding worked (address found at <code>${geo.lat}, ${geo.lng}</code>) but neither parcel source returned a lot size for this address.</p><p>Customers at this address will see a manual lot-size selector in the booking form. This is the intended fallback — the booking can still complete.</p><p>To improve coverage, fix any issues flagged in Steps 2 or 3 above.</p>`);
            }

            function escapeHtml(s) {
                return esc(s);
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
