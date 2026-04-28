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
            <span class="jjpws-step__label"><?php esc_html_e( 'Your Address', 'jjpws-booking' ); ?></span>
        </div>
        <div class="jjpws-step" data-step="2">
            <span class="jjpws-step__number">2</span>
            <span class="jjpws-step__label"><?php esc_html_e( 'Service Details', 'jjpws-booking' ); ?></span>
        </div>
        <div class="jjpws-step" data-step="3">
            <span class="jjpws-step__number">3</span>
            <span class="jjpws-step__label"><?php esc_html_e( 'Review & Book', 'jjpws-booking' ); ?></span>
        </div>
    </div>

    <form id="jjpws-booking-form" novalidate>

        <!-- ── Step 1: Address ─────────────────────────────────────────── -->
        <div class="jjpws-form-step jjpws-form-step--active" data-step="1">
            <h3><?php esc_html_e( 'Where do you need service?', 'jjpws-booking' ); ?></h3>

            <div class="jjpws-field">
                <label for="jjpws-street"><?php esc_html_e( 'Street Address', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                <input type="text" id="jjpws-street" name="street" autocomplete="address-line1"
                       placeholder="123 Main St" required minlength="5" />
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
                    <span class="jjpws-field-error" id="err-state"></span>
                </div>
                <div class="jjpws-field jjpws-field--sm">
                    <label for="jjpws-zip"><?php esc_html_e( 'ZIP Code', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                    <input type="text" id="jjpws-zip" name="zip" autocomplete="postal-code" pattern="\d{5}" maxlength="5" required />
                    <span class="jjpws-field-error" id="err-zip"></span>
                </div>
            </div>

            <!-- Hidden geocode fields -->
            <input type="hidden" id="jjpws-lat" name="lat" />
            <input type="hidden" id="jjpws-lng" name="lng" />
            <input type="hidden" id="jjpws-lot-sqft" name="lot_size_sqft" />
            <input type="hidden" id="jjpws-lot-category" name="lot_size_category" />
            <input type="hidden" id="jjpws-lot-label" name="lot_size_label" />

            <!-- Lot size resolved display -->
            <div id="jjpws-lot-resolved" class="jjpws-lot-resolved" style="display:none;">
                <p><?php esc_html_e( 'Detected lot size:', 'jjpws-booking' ); ?> <strong id="jjpws-lot-label-text"></strong></p>
            </div>

            <!-- Manual lot size fallback (shown only when API fails) -->
            <div id="jjpws-lot-manual" class="jjpws-lot-manual" style="display:none;">
                <label for="jjpws-lot-manual-select">
                    <?php esc_html_e( 'Select your approximate lot size', 'jjpws-booking' ); ?>
                    <span class="jjpws-req">*</span>
                    <span class="jjpws-tooltip" title="<?php esc_attr_e( 'Most residential lots are Small or Medium. When in doubt, choose the size closest to your property.', 'jjpws-booking' ); ?>">?</span>
                </label>
                <select id="jjpws-lot-manual-select">
                    <option value=""><?php esc_html_e( '— Select lot size —', 'jjpws-booking' ); ?></option>
                    <option value="xs"><?php esc_html_e( 'Under 3,000 sq ft', 'jjpws-booking' ); ?></option>
                    <option value="sm"><?php esc_html_e( '3,000 – 6,000 sq ft', 'jjpws-booking' ); ?></option>
                    <option value="md"><?php esc_html_e( '6,000 – 10,000 sq ft', 'jjpws-booking' ); ?></option>
                    <option value="lg"><?php esc_html_e( '10,000 – 18,000 sq ft', 'jjpws-booking' ); ?></option>
                    <option value="xl"><?php esc_html_e( '18,000+ sq ft', 'jjpws-booking' ); ?></option>
                </select>
                <span class="jjpws-field-error" id="err-lot-manual"></span>
            </div>

            <!-- Lookup status -->
            <div id="jjpws-lot-loading" class="jjpws-lot-loading" style="display:none;">
                <span class="jjpws-spinner"></span>
                <?php esc_html_e( 'Looking up your lot size…', 'jjpws-booking' ); ?>
            </div>

            <div class="jjpws-step-nav">
                <button type="button" class="jjpws-btn jjpws-btn--primary" id="jjpws-step1-next">
                    <?php esc_html_e( 'Continue', 'jjpws-booking' ); ?>
                </button>
            </div>
        </div>

        <!-- ── Step 2: Service Details ─────────────────────────────────── -->
        <div class="jjpws-form-step" data-step="2">
            <h3><?php esc_html_e( 'Tell us about your dogs', 'jjpws-booking' ); ?></h3>

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
                <label><?php esc_html_e( 'Service Frequency', 'jjpws-booking' ); ?> <span class="jjpws-req">*</span></label>
                <div class="jjpws-radio-group" role="radiogroup">
                    <label class="jjpws-radio-card">
                        <input type="radio" name="frequency" value="twice_weekly" />
                        <span class="jjpws-radio-card__label">
                            <strong><?php esc_html_e( 'Twice a Week', 'jjpws-booking' ); ?></strong>
                            <small><?php esc_html_e( 'Cleanest yard, most visits', 'jjpws-booking' ); ?></small>
                        </span>
                    </label>
                    <label class="jjpws-radio-card jjpws-radio-card--popular">
                        <span class="jjpws-badge"><?php esc_html_e( 'Most Popular', 'jjpws-booking' ); ?></span>
                        <input type="radio" name="frequency" value="weekly" checked />
                        <span class="jjpws-radio-card__label">
                            <strong><?php esc_html_e( 'Weekly', 'jjpws-booking' ); ?></strong>
                            <small><?php esc_html_e( 'Perfect balance of clean and value', 'jjpws-booking' ); ?></small>
                        </span>
                    </label>
                    <label class="jjpws-radio-card">
                        <input type="radio" name="frequency" value="biweekly" />
                        <span class="jjpws-radio-card__label">
                            <strong><?php esc_html_e( 'Bi-Weekly', 'jjpws-booking' ); ?></strong>
                            <small><?php esc_html_e( 'Every two weeks, budget-friendly', 'jjpws-booking' ); ?></small>
                        </span>
                    </label>
                </div>
                <span class="jjpws-field-error" id="err-frequency"></span>
            </div>

            <div id="jjpws-price-preview" class="jjpws-price-preview" style="display:none;">
                <p class="jjpws-price-preview__label"><?php esc_html_e( 'Estimated Monthly Cost:', 'jjpws-booking' ); ?></p>
                <p class="jjpws-price-preview__amount" id="jjpws-price-amount">—</p>
                <input type="hidden" id="jjpws-price-cents" name="monthly_price_cents" />
            </div>

            <div id="jjpws-price-loading" class="jjpws-lot-loading" style="display:none;">
                <span class="jjpws-spinner"></span>
                <?php esc_html_e( 'Calculating price…', 'jjpws-booking' ); ?>
            </div>

            <div class="jjpws-step-nav">
                <button type="button" class="jjpws-btn jjpws-btn--secondary" id="jjpws-step2-back">
                    ← <?php esc_html_e( 'Back', 'jjpws-booking' ); ?>
                </button>
                <button type="button" class="jjpws-btn jjpws-btn--primary" id="jjpws-step2-next">
                    <?php esc_html_e( 'Continue to Review', 'jjpws-booking' ); ?>
                </button>
            </div>
        </div>

        <!-- ── Step 3: Review & Confirm ───────────────────────────────── -->
        <div class="jjpws-form-step" data-step="3">
            <h3><?php esc_html_e( 'Review Your Booking', 'jjpws-booking' ); ?></h3>

            <div class="jjpws-review-card">
                <div class="jjpws-review-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Service Address', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-address">—</span>
                </div>
                <div class="jjpws-review-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Lot Size', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-lot">—</span>
                </div>
                <div class="jjpws-review-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Number of Dogs', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-dogs">—</span>
                </div>
                <div class="jjpws-review-row">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Service Frequency', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-frequency">—</span>
                </div>
                <div class="jjpws-review-row jjpws-review-row--total">
                    <span class="jjpws-review-row__label"><?php esc_html_e( 'Monthly Total', 'jjpws-booking' ); ?></span>
                    <span class="jjpws-review-row__value" id="review-price">—</span>
                </div>
            </div>

            <p class="jjpws-review-note">
                <?php esc_html_e( 'You will be redirected to our secure checkout to enter your payment details. No charges are made until you confirm.', 'jjpws-booking' ); ?>
            </p>

            <div id="jjpws-checkout-error" class="jjpws-error-msg" style="display:none;"></div>

            <div class="jjpws-step-nav">
                <button type="button" class="jjpws-btn jjpws-btn--secondary" id="jjpws-step3-back">
                    ← <?php esc_html_e( 'Back', 'jjpws-booking' ); ?>
                </button>
                <button type="button" class="jjpws-btn jjpws-btn--primary jjpws-btn--cta" id="jjpws-complete-booking">
                    <?php esc_html_e( 'Complete Booking', 'jjpws-booking' ); ?>
                    <span class="jjpws-btn__spinner" style="display:none;"></span>
                </button>
            </div>
        </div>

    </form>
</div>
