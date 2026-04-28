<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="jjpws-account-subs">
    <h2><?php esc_html_e( 'My Subscriptions', 'jjpws-booking' ); ?></h2>

    <?php if ( empty( $subscriptions ) ) : ?>
        <p>
            <?php esc_html_e( 'You have no active subscriptions.', 'jjpws-booking' ); ?>
            <a href="<?php echo esc_url( home_url( '/book' ) ); ?>"><?php esc_html_e( 'Book a service', 'jjpws-booking' ); ?></a>
        </p>
    <?php else : ?>

        <?php foreach ( $subscriptions as $sub ) :
            $freq_labels = [
                'twice_weekly' => 'Twice a Week',
                'weekly'       => 'Weekly',
                'biweekly'     => 'Bi-Weekly',
            ];
            $lot_labels = [
                'xs' => 'Under 3,000 sq ft',
                'sm' => '3,000 – 6,000 sq ft',
                'md' => '6,000 – 10,000 sq ft',
                'lg' => '10,000 – 18,000 sq ft',
                'xl' => '18,000+ sq ft',
            ];
            $freq_label = $freq_labels[ $sub->frequency ] ?? $sub->frequency;
            $lot_label  = $lot_labels[ $sub->lot_size_category ] ?? $sub->lot_size_category;
            $price_fmt  = '$' . number_format( $sub->monthly_price_cents / 100, 2 );
            $period_end = $sub->current_period_end ? date( 'F j, Y', strtotime( $sub->current_period_end ) ) : '—';
            $is_active  = $sub->status === 'active';
        ?>
        <div class="jjpws-sub-card jjpws-sub-card--<?php echo esc_attr( $sub->status ); ?>">
            <div class="jjpws-sub-card__header">
                <span class="jjpws-sub-card__status jjpws-status jjpws-status--<?php echo esc_attr( $sub->status ); ?>">
                    <?php echo esc_html( ucfirst( str_replace( '_', ' ', $sub->status ) ) ); ?>
                </span>
                <span class="jjpws-sub-card__date">
                    <?php esc_html_e( 'Started', 'jjpws-booking' ); ?>
                    <?php echo esc_html( date( 'F j, Y', strtotime( $sub->created_at ) ) ); ?>
                </span>
            </div>

            <div class="jjpws-sub-card__body">
                <div class="jjpws-sub-detail-row">
                    <span><?php esc_html_e( 'Address', 'jjpws-booking' ); ?></span>
                    <strong><?php echo esc_html( "{$sub->street_address}, {$sub->city}, {$sub->state} {$sub->zip_code}" ); ?></strong>
                </div>
                <div class="jjpws-sub-detail-row">
                    <span><?php esc_html_e( 'Lot Size', 'jjpws-booking' ); ?></span>
                    <strong><?php echo esc_html( $lot_label ); ?></strong>
                </div>
                <div class="jjpws-sub-detail-row">
                    <span><?php esc_html_e( 'Dogs', 'jjpws-booking' ); ?></span>
                    <strong><?php echo esc_html( $sub->dog_count ); ?></strong>
                </div>
                <div class="jjpws-sub-detail-row">
                    <span><?php esc_html_e( 'Frequency', 'jjpws-booking' ); ?></span>
                    <strong><?php echo esc_html( $freq_label ); ?></strong>
                </div>
                <div class="jjpws-sub-detail-row">
                    <span><?php esc_html_e( 'Monthly Cost', 'jjpws-booking' ); ?></span>
                    <strong><?php echo esc_html( $price_fmt ); ?></strong>
                </div>
                <?php if ( $is_active && $sub->current_period_end ) : ?>
                <div class="jjpws-sub-detail-row">
                    <span><?php esc_html_e( 'Next Billing Date', 'jjpws-booking' ); ?></span>
                    <strong><?php echo esc_html( $period_end ); ?></strong>
                </div>
                <?php endif; ?>
            </div>

            <?php if ( $is_active ) : ?>
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
            const periodEnd = btn.dataset.periodEnd;
            const modal = document.getElementById('jjpws-cancel-modal');
            const body  = document.getElementById('jjpws-cancel-modal-body');
            body.textContent = `Are you sure? Your service ends on ${periodEnd}.`;
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
        errDiv.style.display = 'none';

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
        } catch (e) {
            errDiv.textContent = 'Network error. Please try again.';
            errDiv.style.display = 'block';
            btn.disabled = false;
            spinner.style.display = 'none';
        }
    });
})();
</script>
