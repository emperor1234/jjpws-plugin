<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjpws-admin-wrap jjpws-guide">
    <h1><?php esc_html_e( 'JJ Pet Waste — Setup Guide', 'jjpws-booking' ); ?></h1>

    <p class="jjpws-guide__intro">
        <?php esc_html_e( 'This is a one-stop reference for everything in the plugin: setup, settings, pricing, and how each feature works. Follow the checklist top-to-bottom the first time you set up.', 'jjpws-booking' ); ?>
    </p>

    <!-- ── Quick Setup Checklist ─────────────────────────────────────── -->
    <div class="jjpws-guide__card">
        <h2>✅ Quick Setup Checklist</h2>
        <p class="description">Items below get a green check once they're done. Click any item to jump to its setting.</p>

        <ul class="jjpws-checklist">
            <li class="<?php echo $checks['business_address'] ? 'done' : 'pending'; ?>">
                <span class="jjpws-checklist__icon"><?php echo $checks['business_address'] ? '✓' : '○'; ?></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">
                    <?php esc_html_e( 'Business address entered', 'jjpws-booking' ); ?>
                </a>
                <small><?php esc_html_e( '— used for distance calculations', 'jjpws-booking' ); ?></small>
            </li>
            <li class="<?php echo $checks['business_email'] ? 'done' : 'pending'; ?>">
                <span class="jjpws-checklist__icon"><?php echo $checks['business_email'] ? '✓' : '○'; ?></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">
                    <?php esc_html_e( 'Business email entered', 'jjpws-booking' ); ?>
                </a>
                <small><?php esc_html_e( '— receives bookings & quotes', 'jjpws-booking' ); ?></small>
            </li>
            <li class="<?php echo $checks['google_maps'] ? 'done' : 'pending'; ?>">
                <span class="jjpws-checklist__icon"><?php echo $checks['google_maps'] ? '✓' : '○'; ?></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">
                    <?php esc_html_e( 'Google Maps API key', 'jjpws-booking' ); ?>
                </a>
                <small><?php esc_html_e( '— enables address autocomplete', 'jjpws-booking' ); ?></small>
            </li>
            <li class="<?php echo $checks['regrid'] ? 'done' : 'pending'; ?>">
                <span class="jjpws-checklist__icon"><?php echo $checks['regrid'] ? '✓' : '○'; ?></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">
                    <?php esc_html_e( 'Regrid API token', 'jjpws-booking' ); ?>
                </a>
                <small><?php esc_html_e( '— enables auto lot size detection', 'jjpws-booking' ); ?></small>
            </li>
            <li class="<?php echo ( $checks['stripe_test'] || $checks['stripe_live'] ) ? 'done' : 'pending'; ?>">
                <span class="jjpws-checklist__icon"><?php echo ( $checks['stripe_test'] || $checks['stripe_live'] ) ? '✓' : '○'; ?></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">
                    <?php esc_html_e( 'Stripe API keys', 'jjpws-booking' ); ?>
                </a>
                <small><?php esc_html_e( '— required for payments', 'jjpws-booking' ); ?></small>
            </li>
            <li class="<?php echo $checks['webhook_secret'] ? 'done' : 'pending'; ?>">
                <span class="jjpws-checklist__icon"><?php echo $checks['webhook_secret'] ? '✓' : '○'; ?></span>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">
                    <?php esc_html_e( 'Stripe webhook secret', 'jjpws-booking' ); ?>
                </a>
                <small><?php esc_html_e( '— required to record bookings', 'jjpws-booking' ); ?></small>
            </li>
            <li class="<?php echo $checks['allow_register'] ? 'done' : 'pending'; ?>">
                <span class="jjpws-checklist__icon"><?php echo $checks['allow_register'] ? '✓' : '○'; ?></span>
                <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">
                    <?php esc_html_e( 'WordPress: "Anyone can register" enabled', 'jjpws-booking' ); ?>
                </a>
                <small><?php esc_html_e( '— so customers can create accounts', 'jjpws-booking' ); ?></small>
            </li>
        </ul>
    </div>

    <!-- ── How the Plugin Works ──────────────────────────────────────── -->
    <div class="jjpws-guide__card">
        <h2>🔍 How It All Works</h2>

        <p>
            <?php esc_html_e( 'The plugin runs the entire booking flow on your /book page through a shortcode. Here\'s the path a customer takes:', 'jjpws-booking' ); ?>
        </p>

        <ol class="jjpws-guide__steps">
            <li><strong>Enters their address</strong> — the plugin auto-detects lot size (acres) and calculates distance from your business address.</li>
            <li><strong>Picks service type</strong> — Recurring (monthly) or One-time cleanup.</li>
            <li><strong>Enters dog count + frequency</strong> — only for recurring service. Price updates live.</li>
            <li><strong>Picks "time since last cleaned"</strong> — adds a one-time surcharge if the yard has been neglected.</li>
            <li><strong>(Optional) Toggles Annual Prepay</strong> — pays for a full year and saves 10%.</li>
            <li><strong>Reviews and clicks Complete Booking</strong> — gets redirected to your Stripe checkout page.</li>
            <li><strong>Pays on Stripe</strong> — Stripe processes the payment, tells your site, and the booking is recorded.</li>
        </ol>

        <p><strong>If anything triggers a custom quote</strong> (5+ dogs, lot over 1.5 acres, address beyond your service radius), they get a quote-request form instead of self-checkout. That request lands in your inbox AND in <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-quotes' ) ); ?>">Quote Requests</a>.</p>
    </div>

    <!-- ── Adding the Form to a Page ─────────────────────────────────── -->
    <div class="jjpws-guide__card">
        <h2>📝 How to Add the Booking Form to Your Site</h2>

        <p><?php esc_html_e( 'Place the shortcode below on whichever page you want the form to appear on (typically your /book page).', 'jjpws-booking' ); ?></p>

        <div class="jjpws-shortcode">
            <code>[jjpws_booking_form]</code>
            <button type="button" class="button" onclick="navigator.clipboard.writeText('[jjpws_booking_form]'); this.textContent='Copied!';">Copy</button>
        </div>

        <h4><?php esc_html_e( 'In Elementor:', 'jjpws-booking' ); ?></h4>
        <ol>
            <li>Edit the page with Elementor</li>
            <li>Drag a <strong>Shortcode</strong> widget where you want the form</li>
            <li>Paste <code>[jjpws_booking_form]</code></li>
            <li>Update</li>
        </ol>

        <h4><?php esc_html_e( 'In the Block Editor (Gutenberg):', 'jjpws-booking' ); ?></h4>
        <ol>
            <li>Edit the page</li>
            <li>Click + → search "Shortcode"</li>
            <li>Paste <code>[jjpws_booking_form]</code> and Update</li>
        </ol>

        <p class="description"><strong>Tip:</strong> The form auto-styles to match your site's font and inherits the brand color you pick in Settings.</p>
    </div>

    <!-- ── Settings Reference ────────────────────────────────────────── -->
    <div class="jjpws-guide__card">
        <h2>⚙️ Settings Reference</h2>
        <p>Each setting page is documented below. Click the section heading to jump there.</p>

        <h3><a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">JJ Pet Waste → Settings</a></h3>

        <h4>Business Information</h4>
        <ul>
            <li><strong>Business Address</strong> — full street address. Used as the origin for every distance calculation. Customers further than your max-mile cap get the quote-request form instead of self-checkout. <em>The plugin auto-geocodes this address; you'll see the resolved lat/lng below the field once saved.</em></li>
            <li><strong>Business Email</strong> — every new booking and quote request emails this address. Replies to quote emails go directly to the customer (Reply-To header is set automatically).</li>
            <li><strong>Business Phone</strong> — optional, shown on the quote-confirmation email so customers can call you directly.</li>
        </ul>

        <h4>Distance / Travel Fee</h4>
        <ul>
            <li><strong>Free Miles</strong> — radius around your business with no travel fee (default: 5 mi).</li>
            <li><strong>Fee Per Mile</strong> — charged per mile beyond the free radius, per visit (default: $2.50).</li>
            <li><strong>Maximum Service Radius</strong> — anything beyond this is shown the quote form (default: 15 mi).</li>
        </ul>

        <h4>Brand Color</h4>
        <ul>
            <li>Pick any color — it's applied to the booking form's buttons, highlights, and price displays.</li>
        </ul>

        <h4>Stripe</h4>
        <ul>
            <li><strong>Mode</strong> — "Test" while you're verifying things, "Live" once you're ready to take real payments.</li>
            <li><strong>Test/Live Secret Key</strong> — copy from Stripe Dashboard → Developers → API Keys.</li>
            <li><strong>Webhook Secret</strong> — copy from Stripe Dashboard → Developers → Webhooks → click your endpoint → "Reveal signing secret".</li>
            <li><strong>Webhook URL</strong> (shown on the Settings page) — paste this into Stripe Dashboard → Developers → Webhooks → "Add endpoint" and subscribe to these events:
                <ul>
                    <li><code>checkout.session.completed</code></li>
                    <li><code>invoice.payment_succeeded</code></li>
                    <li><code>invoice.payment_failed</code></li>
                    <li><code>customer.subscription.deleted</code></li>
                    <li><code>customer.subscription.updated</code></li>
                </ul>
            </li>
        </ul>

        <h4>External APIs</h4>
        <ul>
            <li><strong>Google Maps API Key</strong> — needed for address autocomplete and geocoding. Get one at <a href="https://console.cloud.google.com/google/maps-apis/start" target="_blank" rel="noopener">Google Cloud Console</a>. Enable: <em>Maps JavaScript API</em>, <em>Places API</em>, <em>Geocoding API</em>.</li>
            <li><strong>Regrid API Token</strong> — needed for automatic lot size detection. Sign up at <a href="https://regrid.com" target="_blank" rel="noopener">regrid.com</a>. Free tier covers 1,000 lookups/month. <em>Without this, the form falls back to a manual "select your lot size" dropdown.</em></li>
        </ul>

        <hr>

        <h3><a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-pricing' ) ); ?>">JJ Pet Waste → Pricing</a></h3>
        <ul>
            <li><strong>Per-Service Base Rates</strong> — 9 cells = 3 dog tiers (1, 2–3, 4) × 3 frequencies (Twice/Wk, Weekly, Bi-Weekly). These are <strong>per-visit</strong> prices for yards under 0.75 acres.</li>
            <li><strong>0.75–1.5 Acre Yard Premium</strong> — percent added to base rates for the larger acreage tier (default 5%).</li>
            <li><strong>One-Time Cleanup Price</strong> — flat price for a single service of yards under 1.5 acres.</li>
            <li><strong>Neglect Surcharge</strong> — three editable rows for "less than 4 weeks ago" (default $0), "4–7 weeks ago" ($25), "8+ weeks ago/new yard" ($50). Applied as a one-time line item on the first payment.</li>
        </ul>

        <hr>

        <h3><a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-dashboard' ) ); ?>">JJ Pet Waste → Customers</a></h3>
        <ul>
            <li>Lists every successful booking — recurring subscriptions and one-time cleanups.</li>
            <li>Filter by status (Active / Past Due / Cancelled / Completed) or search by address/city.</li>
            <li>Click an email to compose a message directly.</li>
        </ul>

        <hr>

        <h3><a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-quotes' ) ); ?>">JJ Pet Waste → Quote Requests</a></h3>
        <ul>
            <li>Every quote-request submission lands here AND in your business email.</li>
            <li>Reasons can be: 5+ dogs, lot over 1.5 acres, address out of service range, or "other".</li>
            <li>Reply directly from your email — the Reply-To is set to the customer.</li>
        </ul>
    </div>

    <!-- ── Pricing math explained ───────────────────────────────────── -->
    <div class="jjpws-guide__card">
        <h2>💰 How Pricing Is Calculated</h2>

        <p><strong>For recurring service:</strong></p>
        <pre class="jjpws-formula">
per_visit  = base[dog_tier][frequency] × (1 + acreage_premium% if 0.75–1.5 ac else 0)
per_visit += distance_fee_per_visit       (= max(0, miles − free_miles) × per_mile_rate)
monthly    = per_visit × visits_per_month  (weekly=4, bi=2, twice=8)
first_pay  = monthly + neglect_surcharge

If annual prepay:
  annual   = monthly × 12 × 0.90           (10% off)
  first_pay = annual + neglect_surcharge</pre>

        <p><strong>For one-time cleanup:</strong></p>
        <pre class="jjpws-formula">
total = one_time_price × (1 + acreage_premium% if 0.75–1.5 ac else 0)
      + distance_fee
      + neglect_surcharge</pre>

        <p class="description">All prices are stored in <em>cents</em> internally to avoid floating-point errors. The price the customer sees on the form is recalculated on the server before checkout — they can't tamper with it.</p>
    </div>

    <!-- ── Auto-update ─────────────────────────────────────────────── -->
    <div class="jjpws-guide__card">
        <h2>🔄 Plugin Updates</h2>
        <p>
            This plugin auto-updates from GitHub. When a new version ships, you'll see a normal
            <strong>"Update Available"</strong> notice on
            <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">Plugins page</a>.
            Click <strong>Update Now</strong> — done.
        </p>
        <p>
            <strong>Current version:</strong> <code><?php echo esc_html( JJPWS_VERSION ); ?></code><br>
            <strong>Source:</strong> <a href="https://github.com/emperor1234/jjpws-plugin/releases" target="_blank" rel="noopener">GitHub Releases</a>
        </p>
    </div>

    <!-- ── Troubleshooting ─────────────────────────────────────────── -->
    <div class="jjpws-guide__card">
        <h2>🛠 Troubleshooting</h2>

        <details>
            <summary><strong>The form always asks customers to manually pick lot size</strong></summary>
            <p>You haven't entered a Regrid API token in <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">Settings</a>. Without it the plugin can't look up parcel sizes. Sign up at <a href="https://regrid.com" target="_blank" rel="noopener">regrid.com</a> (free tier).</p>
        </details>

        <details>
            <summary><strong>Customers aren't being asked to register before booking</strong></summary>
            <p>Go to <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">WP Admin → Settings → General</a> and tick <strong>"Anyone can register"</strong> under Membership. WordPress hides the register link entirely without that.</p>
        </details>

        <details>
            <summary><strong>Bookings complete on Stripe but never appear in Customers</strong></summary>
            <p>The webhook isn't connected. Go to <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">Settings</a>, copy the Webhook URL shown there, and paste it into Stripe Dashboard → Developers → Webhooks. Subscribe to the events listed in the Stripe section above. Then copy the signing secret back into the Webhook Secret field.</p>
        </details>

        <details>
            <summary><strong>Distance calculation isn't working</strong></summary>
            <p>Make sure you've entered your Business Address in <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-settings' ) ); ?>">Settings</a>. After saving, the plugin auto-geocodes it — you should see "✓ Resolved to: lat, lng" below the address field.</p>
        </details>

        <details>
            <summary><strong>Distance shows but seems wrong</strong></summary>
            <p>The plugin uses the Haversine formula on the lat/lng coordinates — it's straight-line ("as the crow flies") distance, not driving miles. If you need driving distance, let your developer know and we can switch to the Google Distance Matrix API (uses a small bit of your Google Maps quota per lookup).</p>
        </details>

        <details>
            <summary><strong>I want to change colors / fonts beyond the brand color picker</strong></summary>
            <p>The brand color picker controls the primary color used throughout the form. If you need broader styling changes (fonts, layout, etc.), they belong in your child theme's CSS or your developer can adjust <code>jjpws-booking/assets/css/booking-form.css</code>.</p>
        </details>

        <details>
            <summary><strong>I switched to Live mode but payments aren't working</strong></summary>
            <p>Make sure you've added the <strong>Live</strong> Stripe secret key (separate from your test key) AND created a <em>new</em> webhook endpoint in Live mode in Stripe Dashboard. Test and Live are completely separate environments in Stripe.</p>
        </details>
    </div>

    <!-- ── Status panel ──────────────────────────────────────────── -->
    <div class="jjpws-guide__card jjpws-guide__status">
        <h2>📊 Current Status</h2>
        <table class="widefat striped">
            <tr>
                <th>Stripe Mode</th>
                <td>
                    <strong style="color:<?php echo $stripe_mode === 'live' ? '#2c7a3d' : '#d97706'; ?>;">
                        <?php echo esc_html( strtoupper( $stripe_mode ) ); ?>
                    </strong>
                </td>
            </tr>
            <tr>
                <th>Plugin Version</th>
                <td><?php echo esc_html( JJPWS_VERSION ); ?></td>
            </tr>
            <tr>
                <th>Webhook URL</th>
                <td><code><?php echo esc_html( $webhook_url ); ?></code></td>
            </tr>
            <tr>
                <th>WP Allow Registration</th>
                <td><?php echo $checks['allow_register'] ? '✓ Yes' : '✗ Disabled'; ?></td>
            </tr>
        </table>
    </div>
</div>
