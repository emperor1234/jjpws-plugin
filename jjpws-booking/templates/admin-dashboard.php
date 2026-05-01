<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjpws-admin-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'JJ Pet Waste — Customers', 'jjpws-booking' ); ?></h1>

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

    <form method="get" class="jjpws-filter-form">
        <input type="hidden" name="page" value="jjpws-dashboard" />
        <div class="jjpws-filter-row">
            <select name="status">
                <option value=""><?php esc_html_e( 'All Statuses', 'jjpws-booking' ); ?></option>
                <?php foreach ( [ 'active', 'past_due', 'cancelled', 'completed' ] as $s ) : ?>
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

    <table class="wp-list-table widefat fixed striped jjpws-customer-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Customer', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Email', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Type', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Address', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Acreage', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Dogs', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Frequency', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Total', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Status', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Next Bill', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Since', 'jjpws-booking' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $subscriptions ) ) : ?>
            <tr><td colspan="11"><?php esc_html_e( 'No customers found.', 'jjpws-booking' ); ?></td></tr>
        <?php else :
            $acreage_labels = [
                'small'  => 'Under 0.75 ac',
                'medium' => '0.75–1.5 ac',
                'large'  => 'Over 1.5 ac',
            ];
            $freq_labels = [
                'twice_weekly' => 'Twice/Week',
                'weekly'       => 'Weekly',
                'biweekly'     => 'Bi-Weekly',
            ];
            foreach ( $subscriptions as $sub ) :
                $is_one_time = ( $sub->service_type === 'one_time' );
                $price = '$' . number_format( $sub->total_price_cents / 100, 2 );
                $next  = ( $sub->current_period_end && ! $is_one_time ) ? date( 'M j, Y', strtotime( $sub->current_period_end ) ) : '—';
                $since = date( 'M j, Y', strtotime( $sub->created_at ) );
                $type_label = $is_one_time ? 'One-Time' : ( ! empty( $sub->annual_prepay ) ? 'Annual' : 'Monthly' );
        ?>
            <tr>
                <td><?php echo esc_html( $sub->display_name ?? '—' ); ?></td>
                <td><a href="mailto:<?php echo esc_attr( $sub->user_email ); ?>"><?php echo esc_html( $sub->user_email ?? '—' ); ?></a></td>
                <td><?php echo esc_html( $type_label ); ?></td>
                <td><?php echo esc_html( "{$sub->street_address}, {$sub->city}, {$sub->state} {$sub->zip_code}" ); ?></td>
                <td><?php echo esc_html( $acreage_labels[ $sub->acreage_tier ] ?? $sub->acreage_tier ); ?></td>
                <td><?php echo absint( $sub->dog_count ); ?></td>
                <td><?php echo esc_html( $is_one_time ? '—' : ( $freq_labels[ $sub->frequency ] ?? $sub->frequency ) ); ?></td>
                <td><?php echo esc_html( $price ); ?></td>
                <td><span class="jjpws-status jjpws-status--<?php echo esc_attr( $sub->status ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $sub->status ) ) ); ?></span></td>
                <td><?php echo esc_html( $next ); ?></td>
                <td><?php echo esc_html( $since ); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

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
