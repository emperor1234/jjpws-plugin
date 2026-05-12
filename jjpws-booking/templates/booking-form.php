<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="jjpws-booking-wrap" class="jjpws-wrap">

    <?php if ( isset( $_GET['booking'] ) && $_GET['booking'] === 'success' ) : ?>
        <div class="jjpws-success-banner">
            <h2><?php esc_html_e( 'Booking Confirmed!', 'jjpws-booking' ); ?></h2>
            <p><?php esc_html_e( 'Thank you for booking with JJ Pet Waste Services. You\'ll receive a confirmation email shortly.', 'jjpws-booking' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( isset( $_GET['booking'] ) && $_GET['booking'] === 'cancelled' ) : ?>
        <div class="jjpws-notice-banner">
            <p><?php esc_html_e( 'Your booking was not completed. You can try again below.', 'jjpws-booking' ); ?></p>
        </div>
    <?php endif; ?>

    <div class="jjpws-steps-indicator" role="progressbar" aria-label="Booking steps">
        <div class="jjpws-step jjpws-step--active" data-step="1">
            <span class="jjpws-step__number">1</span>
            <span class="jjpws-step__label"><?php esc_html_e( 'Address', 'jjpws-booking' ); ?></span>
        </div>
        <div class="jjpws-step" data-step="2">
            <span class="jjpws-step__number">2</span>
            <span class="jjpws-step__label"><?php esc_html_e( 'Service', 'jjpws-booking' ); ?></span>
        </div>
        <div class="jjpws-step" data-step="3">
            <span class="jjpws-step__number">3</span>
            <span class="jjpws-step__label"><?php esc_html_e( 'Review', 'jjpws-booking' ); ?></span>
        </div>
    </div>

    <form id="jjpws-booking-form" novalidate>

        <!-- ── Step 1: Address ─────────────────────────────────────────── -->
        <div class="jjpws-form-step jjpws-form-step--active" data-step="1">
            <h3><?php esc_html_e( 'Where do you need service?', 'jjpws-booking' ); ?></h3>

            <div class="jjpws-field">
                <label for="jjpws-street"><?php esc_html_e( 'Street Address', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                <input type="text" id="jjpws-street" name="street" autocomplete="address-line1" placeholder="123 Main St" required minlength="5" />
                <span class="jjpws-field-error" id="err-street"></span>
            </div>

            <div class="jjpws-field-row">
                <div class="jjpws-field">
                    <label for="jjpws-city"><?php esc_html_e( 'City', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                    <input type="text" id="jjpws-city" name="city" autocomplete="address-level2" required />
                    <span class="jjpws-field-error" id="err-city"></span>
                </div>
                <div class="jjpws-field jjpws-field--sm">
                    <label for="jjpws-state"><?php esc_html_e( 'State', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                    <input type="text" id="jjpws-state" name="state" autocomplete="address-level1" maxlength="2" required />
                </div>
                <div class="jjpws-field jjpws-field--sm">
                    <label for="jjpws-zip"><?php esc_html_e( 'ZIP', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                    <input type="text" id="jjpws-zip" name="zip" autocomplete="postal-code" pattern="\d{5}" maxlength="5" required />
                </div>
            </div>

            <input type="hidden" id="jjpws-lat" name="lat" />
            <input type="hidden" id="jjpws-lng" name="lng" />
            <input type="hidden" id="jjpws-lot-sqft" name="lot_size_sqft" />
            <input type="hidden" id="jjpws-lot-acres" name="lot_size_acres" />
            <input type="hidden" id="jjpws-lot-category" name="acreage_tier" />
            <input type="hidden" id="jjpws-distance-miles" name="distance_miles" />

            <div id="jjpws-lot-loading" class="jjpws-lot-loading" style="display:none;">
                <span class="jjpws-spinner"></span>
                <?php esc_html_e( 'Looking up your address…', 'jjpws-booking' ); ?>
            </div>

            <!-- Auto-resolved lot info -->
            <div id="jjpws-lot-resolved" class="jjpws-lot-resolved" style="display:none;">
                <p>
                    <strong><?php esc_html_e( 'Lot Size:', 'jjpws-booking' ); ?></strong>
                    <span id="jjpws-lot-acres-text"></span>
                </p>
                <p id="jjpws-distance-text" style="display:none;">
                    <strong><?php esc_html_e( 'Distance:', 'jjpws-booking' ); ?></strong>
                    <span id="jjpws-distance-value"></span>
                    <span id="jjpws-distance-fee-note" style="display:none; color:#666; font-size:13px;"></span>
                </p>
            </div>

            <!-- Manual lot size fallback -->
            <div id="jjpws-lot-manual" class="jjpws-field jjpws-lot-manual" style="display:none;">
                <label for="jjpws-lot-manual-select">
                    <?php esc_html_e( 'Select your approximate lot size', 'jjpws-booking' ); ?>
                    <span class="jjpws-req">*</span>
                </label>
                <select id="jjpws-lot-manual-select">
                    <option value=""><?php esc_html_e( '— Select lot size —', 'jjpws-booking' ); ?></option>
                    <option value="small"><?php esc_html_e( 'Under 0.75 acre', 'jjpws-booking' ); ?></option>
                    <option value="medium"><?php esc_html_e( '0.75 – 1.5 acres', 'jjpws-booking' ); ?></option>
                    <option value="large"><?php esc_html_e( 'Over 1.5 acres', 'jjpws-booking' ); ?></option>
                </select>
                <span class="jjpws-field-error" id="err-lot-manual"></span>
            </div>

            <!-- Out of range / quote needed -->
            <div id="jjpws-quote-prompt" class="jjpws-quote-prompt" style="display:none;">
                <h4 id="jjpws-quote-prompt-title"><?php esc_html_e( 'This service requires a custom quote', 'jjpws-booking' ); ?></h4>
                <p id="jjpws-quote-prompt-msg"></p>
                <button type="button" class="jjpws-btn jjpws-btn--primary" id="jjpws-show-quote-form">
                    <?php esc_html_e( 'Request a Quote', 'jjpws-booking' ); ?>
                </button>
            </div>

            <div class="jjpws-step-nav">
                <button type="button" class="jjpws-btn jjpws-btn--primary" id="jjpws-step1-next">
                    <?php esc_html_e( 'Continue', 'jjpws-booking' ); ?>
                </button>
            </div>
        </div>

        <!-- ── Step 2: Service Details ─────────────────────────────────── -->
        <div class="jjpws-form-step" data-step="2">
            <h3><?php esc_html_e( 'Service Details', 'jjpws-booking' ); ?></h3>

            <div class="jjpws-field">
                <label><?php esc_html_e( 'Service Type', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                <div class="jjpws-radio-group" role="radiogroup">
                    <label class="jjpws-radio-card">
                        <input type="radio" name="service_type" value="recurring" checked />
                        <span class="jjpws-radio-card__label">
                            <strong><?php esc_html_e( 'Recurring Service', 'jjpws-booking' ); ?></strong>
                            <small><?php esc_html_e( 'Regular ongoing visits — billed monthly', 'jjpws-booking' ); ?></small>
                        </span>
                    </label>
                    <label class="jjpws-radio-card">
                        <input type="radio" name="service_type" value="one_time" />
                        <span class="jjpws-radio-card__label">
                            <strong><?php esc_html_e( 'One-Time Cleanup', 'jjpws-booking' ); ?></strong>
                            <small><?php esc_html_e( 'Single visit — no recurring charges', 'jjpws-booking' ); ?></small>
                        </span>
                    </label>
                </div>
            </div>

            <div id="jjpws-recurring-fields">
                <div class="jjpws-field">
                    <label for="jjpws-dog-count"><?php esc_html_e( 'Number of Dogs', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                    <div class="jjpws-stepper">
                        <button type="button" class="jjpws-stepper__btn jjpws-stepper__btn--minus" aria-label="Decrease">−</button>
                        <input type="number" id="jjpws-dog-count" name="dog_count" value="1" min="1" max="10" readonly />
                        <button type="button" class="jjpws-stepper__btn jjpws-stepper__btn--plus" aria-label="Increase">+</button>
                    </div>
                    <span class="jjpws-field-error" id="err-dogs"></span>
                </div>

                <div class="jjpws-field">
                    <label><?php esc_html_e( 'How often?', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                    <div class="jjpws-radio-group" role="radiogroup">
                        <label class="jjpws-radio-card">
                            <input type="radio" name="frequency" value="twice_weekly" />
                            <span class="jjpws-radio-card__label">
                                <strong><?php esc_html_e( 'Twice a Week', 'jjpws-booking' ); ?></strong>
                                <small><?php esc_html_e( '8 visits per month', 'jjpws-booking' ); ?></small>
                            </span>
                        </label>
                        <label class="jjpws-radio-card jjpws-radio-card--popular">
                            <span class="jjpws-badge"><?php esc_html_e( 'Most Popular', 'jjpws-booking' ); ?></span>
                            <input type="radio" name="frequency" value="weekly" checked />
                            <span class="jjpws-radio-card__label">
                                <strong><?php esc_html_e( 'Weekly', 'jjpws-booking' ); ?></strong>
                                <small><?php esc_html_e( '4 visits per month', 'jjpws-booking' ); ?></small>
                            </span>
                        </label>
                        <label class="jjpws-radio-card">
                            <input type="radio" name="frequency" value="biweekly" />
                            <span class="jjpws-radio-card__label">
                                <strong><?php esc_html_e( 'Bi-Weekly', 'jjpws-booking' ); ?></strong>
                                <small><?php esc_html_e( '2 visits per month', 'jjpws-booking' ); ?></small>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="jjpws-field">
                <label for="jjpws-time-since"><?php esc_html_e( 'How long since the yard was last cleaned?', 'jjpws-booking' ); ?></label>
                <select id="jjpws-time-since" name="time_since_cleaned">
                    <option value="recent"><?php esc_html_e( 'Less than 4 weeks ago', 'jjpws-booking' ); ?></option>
                    <option value="mid"><?php esc_html_e( '4–7 weeks ago', 'jjpws-booking' ); ?></option>
                    <option value="long"><?php esc_html_e( '8+ weeks ago / new yard', 'jjpws-booking' ); ?></option>
                </select>
                <small style="color:#666;display:block;margin-top:4px;">
                    <?php esc_html_e( 'A surcharge may apply for yards needing extra catch-up cleaning.', 'jjpws-booking' ); ?>
                </small>
            </div>

            <div class="jjpws-field" id="jjpws-annual-prepay-row">
                <label class="jjpws-checkbox-row">
                    <input type="checkbox" id="jjpws-annual-prepay" name="annual_prepay" value="1" />
                    <span><strong><?php esc_html_e( 'Save 10% — Pay annually', 'jjpws-booking' ); ?></strong>
                          <small style="display:block;color:#666;">
                              <?php esc_html_e( 'Pay for a full year upfront and save.', 'jjpws-booking' ); ?>
                          </small></span>
                </label>
            </div>

            <input type="hidden" id="jjpws-total-cents" name="total_price_cents" />

            <div id="jjpws-price-loading" class="jjpws-lot-loading" style="display:none;">
                <span class="jjpws-spinner"></span>
                <?php esc_html_e( 'Calculating price…', 'jjpws-booking' ); ?>
            </div>

            <div id="jjpws-price-preview" class="jjpws-price-preview" style="display:none;">
                <p class="jjpws-price-preview__label" id="jjpws-price-label"><?php esc_html_e( 'Estimated Monthly Cost:', 'jjpws-booking' ); ?></p>
                <p class="jjpws-price-preview__amount" id="jjpws-price-amount">—</p>
                <div class="jjpws-price-breakdown" id="jjpws-price-breakdown"></div>
            </div>

            <!-- 5+ dogs / out-of-cap inline quote prompt -->
            <div id="jjpws-step2-quote-prompt" class="jjpws-quote-prompt" style="display:none;">
                <h4><?php esc_html_e( 'Custom quote needed', 'jjpws-booking' ); ?></h4>
                <p id="jjpws-step2-quote-msg"></p>
                <button type="button" class="jjpws-btn jjpws-btn--primary" id="jjpws-show-quote-form-2">
                    <?php esc_html_e( 'Request a Quote', 'jjpws-booking' ); ?>
                </button>
            </div>

            <div class="jjpws-step-nav">
                <button type="button" class="jjpws-btn jjpws-btn--secondary" id="jjpws-step2-back">← <?php esc_html_e( 'Back', 'jjpws-booking' ); ?></button>
                <button type="button" class="jjpws-btn jjpws-btn--primary" id="jjpws-step2-next">
                    <?php esc_html_e( 'Continue to Review', 'jjpws-booking' ); ?>
                </button>
            </div>
        </div>

        <!-- ── Step 3: Review ─────────────────────────────────────────── -->
        <div class="jjpws-form-step" data-step="3">
            <h3><?php esc_html_e( 'Review Your Booking', 'jjpws-booking' ); ?></h3>

            <div class="jjpws-review-card">
                <div class="jjpws-review-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Service Type', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-type">—</span>
                </div>
                <div class="jjpws-review-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Address', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-address">—</span>
                </div>
                <div class="jjpws-review-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Lot Size', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-lot">—</span>
                </div>
                <div class="jjpws-review-row" id="review-dogs-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Dogs', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-dogs">—</span>
                </div>
                <div class="jjpws-review-row" id="review-frequency-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Frequency', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-frequency">—</span>
                </div>
                <div class="jjpws-review-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Time Since Last Cleaned', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-time-since">—</span>
                </div>
                <div class="jjpws-review-row jjpws-review-row--total">
                    <span class="jjpws-review-row__label" id="review-total-label"><?php esc_html_e( 'Due Today', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-price">—</span>
                </div>
                <div class="jjpws-review-row" id="review-recurring-row" style="display:none;">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Then per month', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-recurring">—</span>
                </div>
            </div>

            <p class="jjpws-review-note">
                <?php esc_html_e( 'You\'ll be redirected to our secure Stripe checkout. No charges happen until you confirm there.', 'jjpws-booking' ); ?>
            </p>

            <div id="jjpws-checkout-error" class="jjpws-error-msg" style="display:none;"></div>

            <?php
            $jjpws_resume_url     = add_query_arg( 'jjpws_resume', '1', get_permalink() );
            $jjpws_login_url      = wp_login_url( $jjpws_resume_url );
            $jjpws_user_logged_in = is_user_logged_in();

            if ( function_exists( 'wc_get_page_permalink' ) ) {
                $wc_account_url     = wc_get_page_permalink( 'myaccount' );
                $jjpws_register_url = $wc_account_url
                    ? add_query_arg( 'jjpws_resume', '1', $wc_account_url )
                    : $jjpws_login_url;
            } else {
                // Native WP registration page — jjpws_resume flag carried as GET param so
                // Plugin::add_resume_to_register_form() can inject it as a hidden POST field,
                // allowing Plugin::auto_login_after_jjpws_registration() to redirect back here.
                $jjpws_register_url = add_query_arg( 'jjpws_resume', '1', wp_registration_url() );
            }
            ?>

            <!-- Auth gate: shown to visitors who are NOT yet logged in ──── -->
            <div id="jjpws-auth-gate" class="jjpws-step3-auth-gate"
                 style="<?php echo $jjpws_user_logged_in ? 'display:none;' : ''; ?>">
                <h4><?php esc_html_e( 'Create an Account to Complete Your Booking', 'jjpws-booking' ); ?></h4>
                <p><?php esc_html_e( 'An account lets you manage your subscription, view billing history, and cancel anytime.', 'jjpws-booking' ); ?></p>
                <div class="jjpws-step3-auth-gate-actions">
                    <a href="<?php echo esc_url( $jjpws_register_url ); ?>"
                       id="jjpws-register-link"
                       class="jjpws-btn jjpws-btn--primary">
                        <?php esc_html_e( 'Create a Free Account', 'jjpws-booking' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $jjpws_login_url ); ?>"
                       id="jjpws-login-link"
                       class="jjpws-btn jjpws-btn--secondary">
                        <?php esc_html_e( 'Already have an account? Log In', 'jjpws-booking' ); ?>
                    </a>
                </div>
                <p class="jjpws-step3-auth-note">
                    <?php esc_html_e( 'Your booking details are saved — you won\'t need to start over after signing in.', 'jjpws-booking' ); ?>
                </p>
            </div>

            <!-- Checkout nav: shown only to logged-in users ─────────────── -->
            <div class="jjpws-step-nav" id="jjpws-checkout-actions"
                 style="<?php echo $jjpws_user_logged_in ? '' : 'display:none;'; ?>">
                <button type="button" class="jjpws-btn jjpws-btn--secondary" id="jjpws-step3-back">← <?php esc_html_e( 'Back', 'jjpws-booking' ); ?></button>
                <button type="button" class="jjpws-btn jjpws-btn--primary jjpws-btn--cta" id="jjpws-complete-booking">
                    <?php esc_html_e( 'Complete Booking', 'jjpws-booking' ); ?>
                    <span class="jjpws-btn__spinner" style="display:none;"></span>
                </button>
            </div>
        </div>
    </form>

    <?php
    $jjpws_attribution = trim( (string) get_option(
        'jjpws_parcel_attribution',
        'Parcel data © Georgia GIS / Georgia Open Data. Boundaries and acreage are for reference only and do not constitute a legal survey.'
    ) );
    if ( $jjpws_attribution ) :
    ?>
    <p class="jjpws-attribution"><?php echo esc_html( $jjpws_attribution ); ?></p>
    <?php endif; ?>

    <!-- Quote Request Modal/Section -->
    <div id="jjpws-quote-form" class="jjpws-quote-form" style="display:none;">
        <h3><?php esc_html_e( 'Request a Custom Quote', 'jjpws-booking' ); ?></h3>
        <p><?php esc_html_e( 'Drop your details and a short message — we\'ll get back to you within one business day.', 'jjpws-booking' ); ?></p>

        <input type="hidden" id="jjpws-quote-reason" name="reason" />

        <div class="jjpws-field">
            <label for="jjpws-quote-name"><?php esc_html_e( 'Your Name', 'jjpws-booking' ); ?></label>
            <input type="text" id="jjpws-quote-name" name="name" />
        </div>
        <div class="jjpws-field">
            <label for="jjpws-quote-email"><?php esc_html_e( 'Email', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
            <input type="email" id="jjpws-quote-email" name="email" required />
            <span class="jjpws-field-error" id="err-quote-email"></span>
        </div>
        <div class="jjpws-field">
            <label for="jjpws-quote-phone"><?php esc_html_e( 'Phone', 'jjpws-booking' ); ?></label>
            <input type="tel" id="jjpws-quote-phone" name="phone" />
        </div>
        <div class="jjpws-field">
            <label for="jjpws-quote-message"><?php esc_html_e( 'Message', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
            <textarea id="jjpws-quote-message" name="message" rows="4" required></textarea>
            <span class="jjpws-field-error" id="err-quote-message"></span>
        </div>

        <div id="jjpws-quote-error" class="jjpws-error-msg" style="display:none;"></div>
        <div id="jjpws-quote-success" class="jjpws-success-banner" style="display:none;">
            <h2><?php esc_html_e( 'Thanks!', 'jjpws-booking' ); ?></h2>
            <p><?php esc_html_e( 'We received your request and will follow up within one business day.', 'jjpws-booking' ); ?></p>
        </div>

        <div class="jjpws-step-nav" id="jjpws-quote-actions">
            <button type="button" class="jjpws-btn jjpws-btn--secondary" id="jjpws-quote-cancel">← <?php esc_html_e( 'Back', 'jjpws-booking' ); ?></button>
            <button type="button" class="jjpws-btn jjpws-btn--primary" id="jjpws-quote-submit">
                <?php esc_html_e( 'Send Request', 'jjpws-booking' ); ?>
                <span class="jjpws-btn__spinner" style="display:none;"></span>
            </button>
        </div>
    </div>
</div>
