<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Template vars injected by AccountPage::render_shortcode():
 * @var WP_User  $user
 * @var array    $subscriptions
 * @var string   $nonce
 * @var string   $active_tab  'profile' | 'subscriptions'
 */
$page_url = get_permalink();
?>

<div class="jjpws-account-wrap">

    <!-- Tab nav -->
    <nav class="jjpws-account-tabs" role="tablist">
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'profile', $page_url ) ); ?>"
           class="jjpws-tab <?php echo $active_tab === 'profile' ? 'jjpws-tab--active' : ''; ?>"
           role="tab" aria-selected="<?php echo $active_tab === 'profile' ? 'true' : 'false'; ?>">
            <?php esc_html_e( 'My Profile', 'jjpws-booking' ); ?>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'tab', 'subscriptions', $page_url ) ); ?>"
           class="jjpws-tab <?php echo $active_tab === 'subscriptions' ? 'jjpws-tab--active' : ''; ?>"
           role="tab" aria-selected="<?php echo $active_tab === 'subscriptions' ? 'true' : 'false'; ?>">
            <?php esc_html_e( 'My Subscriptions', 'jjpws-booking' ); ?>
            <?php
            $active_count = array_reduce( $subscriptions, fn( $c, $s ) => $c + ( $s->status === 'active' ? 1 : 0 ), 0 );
            if ( $active_count ) : ?>
                <span class="jjpws-badge jjpws-badge--count"><?php echo absint( $active_count ); ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <!-- ── Profile tab ──────────────────────────────────────────────────── -->
    <?php if ( $active_tab === 'profile' ) : ?>
    <div class="jjpws-account-panel" id="jjpws-profile-panel">

        <h2><?php esc_html_e( 'My Profile', 'jjpws-booking' ); ?></h2>

        <div id="jjpws-profile-success" class="jjpws-success-inline" style="display:none;"></div>
        <div id="jjpws-profile-error"   class="jjpws-error-msg"    style="display:none;"></div>

        <form id="jjpws-profile-form" novalidate>

            <div class="jjpws-field-row">
                <div class="jjpws-field">
                    <label for="jjpws-first-name"><?php esc_html_e( 'First Name', 'jjpws-booking' ); ?></label>
                    <input type="text" id="jjpws-first-name" name="first_name"
                           value="<?php echo esc_attr( $user->first_name ); ?>" />
                </div>
                <div class="jjpws-field">
                    <label for="jjpws-last-name"><?php esc_html_e( 'Last Name', 'jjpws-booking' ); ?></label>
                    <input type="text" id="jjpws-last-name" name="last_name"
                           value="<?php echo esc_attr( $user->last_name ); ?>" />
                </div>
            </div>

            <div class="jjpws-field">
                <label for="jjpws-display-name"><?php esc_html_e( 'Display Name', 'jjpws-booking' ); ?></label>
                <input type="text" id="jjpws-display-name" name="display_name"
                       value="<?php echo esc_attr( $user->display_name ); ?>" />
            </div>

            <div class="jjpws-field">
                <label><?php esc_html_e( 'Email Address', 'jjpws-booking' ); ?></label>
                <input type="email" value="<?php echo esc_attr( $user->user_email ); ?>" disabled />
                <small class="jjpws-field-hint">
                    <?php esc_html_e( 'To change your email please contact us.', 'jjpws-booking' ); ?>
                </small>
            </div>

            <div class="jjpws-field">
                <label for="jjpws-phone"><?php esc_html_e( 'Phone Number', 'jjpws-booking' ); ?></label>
                <input type="tel" id="jjpws-phone" name="billing_phone"
                       value="<?php echo esc_attr( get_user_meta( $user->ID, 'billing_phone', true ) ); ?>"
                       placeholder="(xxx) xxx-xxxx" />
            </div>

            <hr class="jjpws-divider" />
            <h3><?php esc_html_e( 'Change Password', 'jjpws-booking' ); ?></h3>
            <p class="jjpws-field-hint"><?php esc_html_e( 'Leave blank to keep your current password.', 'jjpws-booking' ); ?></p>

            <div class="jjpws-field">
                <label for="jjpws-password"><?php esc_html_e( 'New Password', 'jjpws-booking' ); ?></label>
                <input type="password" id="jjpws-password" name="password" autocomplete="new-password" />
                <span class="jjpws-field-error" id="err-password"></span>
            </div>

            <div class="jjpws-field">
                <label for="jjpws-password-confirm"><?php esc_html_e( 'Confirm New Password', 'jjpws-booking' ); ?></label>
                <input type="password" id="jjpws-password-confirm" name="password_confirm" autocomplete="new-password" />
                <span class="jjpws-field-error" id="err-password-confirm"></span>
            </div>

            <div class="jjpws-form-actions">
                <button type="submit" class="jjpws-btn jjpws-btn--primary" id="jjpws-profile-save">
                    <?php esc_html_e( 'Save Changes', 'jjpws-booking' ); ?>
                    <span class="jjpws-btn__spinner" style="display:none;"></span>
                </button>
            </div>

        </form>
    </div>

    <script>
    (function () {
        const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
        const nonce   = <?php echo wp_json_encode( $nonce ); ?>;
        const form    = document.getElementById('jjpws-profile-form');
        const successBox = document.getElementById('jjpws-profile-success');
        const errorBox   = document.getElementById('jjpws-profile-error');

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            successBox.style.display = 'none';
            errorBox.style.display = 'none';

            const errPass    = document.getElementById('err-password');
            const errConfirm = document.getElementById('err-password-confirm');
            errPass.textContent = '';
            errConfirm.textContent = '';

            const pass    = form.querySelector('[name="password"]').value;
            const confirm = form.querySelector('[name="password_confirm"]').value;

            if (pass && pass.length < 8) {
                errPass.textContent = 'Password must be at least 8 characters.';
                return;
            }
            if (pass && pass !== confirm) {
                errConfirm.textContent = 'Passwords do not match.';
                return;
            }

            const btn     = document.getElementById('jjpws-profile-save');
            const spinner = btn.querySelector('.jjpws-btn__spinner');
            btn.disabled = true;
            spinner.style.display = 'inline-block';

            const fd = new FormData();
            fd.append('action', 'jjpws_update_profile');
            fd.append('nonce', nonce);
            fd.append('first_name',    form.querySelector('[name="first_name"]').value.trim());
            fd.append('last_name',     form.querySelector('[name="last_name"]').value.trim());
            fd.append('display_name',  form.querySelector('[name="display_name"]').value.trim());
            fd.append('billing_phone', form.querySelector('[name="billing_phone"]').value.trim());
            if (pass) fd.append('password', pass);

            try {
                const res  = await fetch(ajaxUrl, { method: 'POST', body: fd });
                const json = await res.json();
                if (json.success) {
                    successBox.textContent = json.data?.message || 'Profile updated.';
                    successBox.style.display = 'block';
                    form.querySelector('[name="password"]').value = '';
                    form.querySelector('[name="password_confirm"]').value = '';
                } else {
                    errorBox.textContent = json.data?.message || 'Could not save changes.';
                    errorBox.style.display = 'block';
                }
            } catch (_) {
                errorBox.textContent = 'Network error. Please try again.';
                errorBox.style.display = 'block';
            } finally {
                btn.disabled = false;
                spinner.style.display = 'none';
            }
        });
    })();
    </script>

    <?php endif; ?>

    <!-- ── Subscriptions tab ──────────────────────────────────────────────── -->
    <?php if ( $active_tab === 'subscriptions' ) : ?>
    <div class="jjpws-account-panel" id="jjpws-subscriptions-panel">

        <h2><?php esc_html_e( 'My Subscriptions', 'jjpws-booking' ); ?></h2>

        <?php if ( empty( $subscriptions ) ) : ?>
            <p class="jjpws-empty-state">
                <?php esc_html_e( 'You have no subscriptions yet.', 'jjpws-booking' ); ?>
                <a href="<?php echo esc_url( home_url( '/book' ) ); ?>"><?php esc_html_e( 'Book a service →', 'jjpws-booking' ); ?></a>
            </p>
        <?php else : ?>
            <?php foreach ( $subscriptions as $sub ) :
                $freq_labels = [
                    'twice_weekly' => 'Twice a Week',
                    'weekly'       => 'Weekly',
                    'biweekly'     => 'Bi-Weekly',
                ];
                $acreage_labels = [
                    'small'  => 'Under 0.75 acre',
                    'medium' => '0.75 – 1.5 acres',
                ];
                $freq_label    = $freq_labels[ $sub->frequency ] ?? ucfirst( str_replace( '_', ' ', $sub->frequency ) );
                $acreage_label = $acreage_labels[ $sub->acreage_tier ] ?? $sub->acreage_tier;
                $price_fmt     = '$' . number_format( $sub->total_price_cents / 100, 2 );
                $period_end    = $sub->current_period_end
                    ? date( 'F j, Y', strtotime( $sub->current_period_end ) )
                    : '—';
                $is_active = $sub->status === 'active';
                $is_recurring = $sub->service_type !== 'one_time';
            ?>
            <div class="jjpws-sub-card jjpws-sub-card--<?php echo esc_attr( $sub->status ); ?>">

                <div class="jjpws-sub-card__header">
                    <span class="jjpws-status jjpws-status--<?php echo esc_attr( $sub->status ); ?>">
                        <?php echo esc_html( ucfirst( str_replace( '_', ' ', $sub->status ) ) ); ?>
                    </span>
                    <span class="jjpws-sub-card__meta">
                        <?php echo esc_html( $sub->service_type === 'one_time' ? 'One-Time Cleanup' : 'Recurring Service' ); ?>
                        &bull;
                        <?php esc_html_e( 'Started', 'jjpws-booking' ); ?>
                        <?php echo esc_html( date( 'M j, Y', strtotime( $sub->created_at ) ) ); ?>
                    </span>
                </div>

                <div class="jjpws-sub-card__body">

                    <div class="jjpws-sub-detail-row">
                        <span><?php esc_html_e( 'Service Address', 'jjpws-booking' ); ?></span>
                        <strong><?php echo esc_html( "{$sub->street_address}, {$sub->city}, {$sub->state} {$sub->zip_code}" ); ?></strong>
                    </div>

                    <?php if ( $sub->lot_size_acres ) : ?>
                    <div class="jjpws-sub-detail-row">
                        <span><?php esc_html_e( 'Lot Size', 'jjpws-booking' ); ?></span>
                        <strong>
                            <?php echo esc_html( $acreage_label ); ?>
                            <?php if ( $sub->lot_size_acres ) : ?>
                            (<?php echo esc_html( number_format( (float) $sub->lot_size_acres, 2 ) ); ?> acres)
                            <?php endif; ?>
                        </strong>
                    </div>
                    <?php endif; ?>

                    <?php if ( $is_recurring ) : ?>
                    <div class="jjpws-sub-detail-row">
                        <span><?php esc_html_e( 'Dogs', 'jjpws-booking' ); ?></span>
                        <strong><?php echo absint( $sub->dog_count ); ?></strong>
                    </div>
                    <div class="jjpws-sub-detail-row">
                        <span><?php esc_html_e( 'Visit Frequency', 'jjpws-booking' ); ?></span>
                        <strong><?php echo esc_html( $freq_label ); ?></strong>
                    </div>
                    <?php if ( $sub->annual_prepay ) : ?>
                    <div class="jjpws-sub-detail-row">
                        <span><?php esc_html_e( 'Billing', 'jjpws-booking' ); ?></span>
                        <strong><?php esc_html_e( 'Annual Prepay (10% discount)', 'jjpws-booking' ); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <div class="jjpws-sub-detail-row jjpws-sub-detail-row--price">
                        <span><?php echo esc_html( $sub->annual_prepay ? __( 'Annual Total', 'jjpws-booking' ) : ( $is_recurring ? __( 'Monthly Cost', 'jjpws-booking' ) : __( 'Total Paid', 'jjpws-booking' ) ) ); ?></span>
                        <strong class="jjpws-price-highlight"><?php echo esc_html( $price_fmt ); ?></strong>
                    </div>

                    <?php if ( $is_active && $is_recurring && $sub->current_period_end ) : ?>
                    <div class="jjpws-sub-detail-row">
                        <span><?php esc_html_e( 'Next Billing Date', 'jjpws-booking' ); ?></span>
                        <strong><?php echo esc_html( $period_end ); ?></strong>
                    </div>
                    <?php endif; ?>

                </div>

                <?php if ( $is_active && $is_recurring ) : ?>
                <div class="jjpws-sub-card__footer">
                    <button type="button"
                            class="jjpws-btn jjpws-btn--danger jjpws-cancel-sub-btn"
                            data-sub-id="<?php echo absint( $sub->id ); ?>"
                            data-period-end="<?php echo esc_attr( $period_end ); ?>"
                            data-nonce="<?php echo esc_attr( $nonce ); ?>">
                        <?php esc_html_e( 'Cancel Subscription', 'jjpws-booking' ); ?>
                    </button>
                </div>
                <?php elseif ( $sub->status === 'cancelled' && $sub->cancelled_at ) : ?>
                <p class="jjpws-sub-card__cancelled-note">
                    <?php printf(
                        esc_html__( 'Cancelled on %s.', 'jjpws-booking' ),
                        esc_html( date( 'F j, Y', strtotime( $sub->cancelled_at ) ) )
                    ); ?>
                </p>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>

            <p style="text-align:center; margin-top:24px;">
                <a href="<?php echo esc_url( home_url( '/book' ) ); ?>" class="jjpws-btn jjpws-btn--secondary">
                    <?php esc_html_e( '+ Book Another Service', 'jjpws-booking' ); ?>
                </a>
            </p>
        <?php endif; ?>

    </div>

    <!-- Cancellation Confirmation Modal -->
    <div id="jjpws-cancel-modal" class="jjpws-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="jjpws-cancel-modal-title">
        <div class="jjpws-modal__overlay"></div>
        <div class="jjpws-modal__content">
            <h3 id="jjpws-cancel-modal-title"><?php esc_html_e( 'Cancel Subscription?', 'jjpws-booking' ); ?></h3>
            <p id="jjpws-cancel-modal-body">
                <?php esc_html_e( 'Are you sure? Your service will continue until the end of your billing period.', 'jjpws-booking' ); ?>
            </p>
            <div id="jjpws-cancel-modal-error" class="jjpws-error-msg" style="display:none;"></div>
            <div class="jjpws-modal__actions">
                <button type="button" id="jjpws-cancel-modal-close" class="jjpws-btn jjpws-btn--secondary">
                    <?php esc_html_e( 'Keep Subscription', 'jjpws-booking' ); ?>
                </button>
                <button type="button" id="jjpws-cancel-modal-confirm" class="jjpws-btn jjpws-btn--danger">
                    <?php esc_html_e( 'Yes, Cancel', 'jjpws-booking' ); ?>
                    <span class="jjpws-btn__spinner" style="display:none;"></span>
                </button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
        let pendingSubId = null;
        let pendingNonce = null;

        document.querySelectorAll('.jjpws-cancel-sub-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                pendingSubId = btn.dataset.subId;
                pendingNonce = btn.dataset.nonce;
                const modal = document.getElementById('jjpws-cancel-modal');
                const body  = document.getElementById('jjpws-cancel-modal-body');
                body.textContent = btn.dataset.periodEnd !== '—'
                    ? `Are you sure? Your service continues until ${btn.dataset.periodEnd}.`
                    : 'Are you sure you want to cancel your subscription?';
                modal.style.display = 'flex';
            });
        });

        document.getElementById('jjpws-cancel-modal-close')?.addEventListener('click', () => {
            document.getElementById('jjpws-cancel-modal').style.display = 'none';
        });

        document.getElementById('jjpws-cancel-modal-confirm')?.addEventListener('click', async function () {
            const btn     = this;
            const spinner = btn.querySelector('.jjpws-btn__spinner');
            const errDiv  = document.getElementById('jjpws-cancel-modal-error');

            btn.disabled = true;
            spinner.style.display = 'inline-block';
            errDiv.style.display  = 'none';

            try {
                const fd = new FormData();
                fd.append('action', 'jjpws_cancel_subscription');
                fd.append('nonce', pendingNonce);
                fd.append('subscription_id', pendingSubId);

                const res  = await fetch(ajaxUrl, { method: 'POST', body: fd });
                const json = await res.json();

                if (json.success) {
                    location.reload();
                } else {
                    errDiv.textContent = json.data?.message || 'Something went wrong.';
                    errDiv.style.display = 'block';
                    btn.disabled = false;
                    spinner.style.display = 'none';
                }
            } catch (_) {
                errDiv.textContent = 'Network error. Please try again.';
                errDiv.style.display = 'block';
                btn.disabled = false;
                spinner.style.display = 'none';
            }
        });
    })();
    </script>

    <?php endif; ?>

</div>
