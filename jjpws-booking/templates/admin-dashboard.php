<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjpws-admin-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'JJ Pet Waste — Customers', 'jjpws-booking' ); ?></h1>

    <!-- Stat Cards -->
    <div class="jjpws-stats">
        <div class="jjpws-stat-card">
            <span class="jjpws-stat-card__num"><?php echo absint( $counts['all'] ); ?></span>
            <span class="jjpws-stat-card__label"><?php esc_html_e( 'Total', 'jjpws-booking' ); ?></span>
        </div>
        <div class="jjpws-stat-card jjpws-stat-card--active">
            <span class="jjpws-stat-card__num"><?php echo absint( $counts['active'] ); ?></span>
            <span class="jjpws-stat-card__label"><?php esc_html_e( 'Active', 'jjpws-booking' ); ?></span>
        </div>
        <div class="jjpws-stat-card jjpws-stat-card--pastdue">
            <span class="jjpws-stat-card__num"><?php echo absint( $counts['past_due'] ); ?></span>
            <span class="jjpws-stat-card__label"><?php esc_html_e( 'Past Due', 'jjpws-booking' ); ?></span>
        </div>
        <div class="jjpws-stat-card jjpws-stat-card--cancelled">
            <span class="jjpws-stat-card__num"><?php echo absint( $counts['cancelled'] ); ?></span>
            <span class="jjpws-stat-card__label"><?php esc_html_e( 'Cancelled', 'jjpws-booking' ); ?></span>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" class="jjpws-filter-form">
        <input type="hidden" name="page" value="jjpws-dashboard" />

        <div class="jjpws-filter-row">
            <select name="status">
                <option value=""><?php esc_html_e( 'All Statuses', 'jjpws-booking' ); ?></option>
                <?php foreach ( [ 'active', 'past_due', 'cancelled' ] as $s ) : ?>
                    <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>>
                        <?php echo esc_html( ucfirst( str_replace( '_', ' ', $s ) ) ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="search" value="<?php echo esc_attr( $search ); ?>"
                   placeholder="<?php esc_attr_e( 'Search address or city…', 'jjpws-booking' ); ?>" />

            <button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'jjpws-booking' ); ?></button>
            <?php if ( $status || $search ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-dashboard' ) ); ?>" class="button">
                    <?php esc_html_e( 'Clear', 'jjpws-booking' ); ?>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table -->
    <table class="wp-list-table widefat fixed striped jjpws-customer-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Customer', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Email', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Address', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Lot Size', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Dogs', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Frequency', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Monthly', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Status', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Next Billing', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Since', 'jjpws-booking' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $subscriptions ) ) : ?>
            <tr><td colspan="10"><?php esc_html_e( 'No customers found.', 'jjpws-booking' ); ?></td></tr>
        <?php else :
            $lot_labels = [
                'xs' => 'Under 3,000 sq ft',
                'sm' => '3,000 – 6,000 sq ft',
                'md' => '6,000 – 10,000 sq ft',
                'lg' => '10,000 – 18,000 sq ft',
                'xl' => '18,000+ sq ft',
            ];
            $freq_labels = [
                'twice_weekly' => 'Twice/Week',
                'weekly'       => 'Weekly',
                'biweekly'     => 'Bi-Weekly',
            ];
            foreach ( $subscriptions as $sub ) :
                $price = '$' . number_format( $sub->monthly_price_cents / 100, 2 );
                $next  = $sub->current_period_end ? date( 'M j, Y', strtotime( $sub->current_period_end ) ) : '—';
                $since = date( 'M j, Y', strtotime( $sub->created_at ) );
        ?>
            <tr>
                <td><?php echo esc_html( $sub->display_name ?? '—' ); ?></td>
                <td><a href="mailto:<?php echo esc_attr( $sub->user_email ); ?>"><?php echo esc_html( $sub->user_email ?? '—' ); ?></a></td>
                <td><?php echo esc_html( "{$sub->street_address}, {$sub->city}, {$sub->state} {$sub->zip_code}" ); ?></td>
                <td><?php echo esc_html( $lot_labels[ $sub->lot_size_category ] ?? $sub->lot_size_category ); ?></td>
                <td><?php echo absint( $sub->dog_count ); ?></td>
                <td><?php echo esc_html( $freq_labels[ $sub->frequency ] ?? $sub->frequency ); ?></td>
                <td><?php echo esc_html( $price ); ?></td>
                <td><span class="jjpws-status jjpws-status--<?php echo esc_attr( $sub->status ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $sub->status ) ) ); ?></span></td>
                <td><?php echo esc_html( $next ); ?></td>
                <td><?php echo esc_html( $since ); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ( $total_pages > 1 ) : ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf( esc_html__( '%d items', 'jjpws-booking' ), $total ); ?>
            </span>
            <span class="pagination-links">
                <?php for ( $p = 1; $p <= $total_pages; $p++ ) :
                    $url = add_query_arg( array_merge( $_GET, [ 'page' => 'jjpws-dashboard', 'paged' => $p ] ), admin_url( 'admin.php' ) );
                ?>
                    <a class="button<?php echo $p === $paged ? ' button-primary' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
                        <?php echo absint( $p ); ?>
                    </a>
                <?php endfor; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>
