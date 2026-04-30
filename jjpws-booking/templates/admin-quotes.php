<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap jjpws-admin-wrap">
    <h1><?php esc_html_e( 'JJ Pet Waste — Quote Requests', 'jjpws-booking' ); ?></h1>

    <p><?php printf( esc_html__( '%d total quote requests', 'jjpws-booking' ), $total ); ?></p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Date', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Name', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Email', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Phone', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Address', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Reason', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Lot (ac)', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Dogs', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Miles', 'jjpws-booking' ); ?></th>
                <th><?php esc_html_e( 'Message', 'jjpws-booking' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ( empty( $quotes ) ) : ?>
            <tr><td colspan="10"><?php esc_html_e( 'No quote requests yet.', 'jjpws-booking' ); ?></td></tr>
        <?php else : foreach ( $quotes as $q ) : ?>
            <tr>
                <td><?php echo esc_html( date( 'M j, Y g:ia', strtotime( $q->created_at ) ) ); ?></td>
                <td><?php echo esc_html( $q->customer_name ?: '—' ); ?></td>
                <td><a href="mailto:<?php echo esc_attr( $q->customer_email ); ?>"><?php echo esc_html( $q->customer_email ); ?></a></td>
                <td><?php echo esc_html( $q->customer_phone ?: '—' ); ?></td>
                <td><?php echo esc_html( trim( "{$q->street_address}, {$q->city}, {$q->state} {$q->zip_code}", ', ' ) ); ?></td>
                <td><?php echo esc_html( $q->reason ?: '—' ); ?></td>
                <td><?php echo esc_html( $q->lot_size_acres ?: '—' ); ?></td>
                <td><?php echo esc_html( $q->dog_count ?: '—' ); ?></td>
                <td><?php echo esc_html( $q->distance_miles ?: '—' ); ?></td>
                <td style="max-width:300px;"><?php echo esc_html( $q->message ); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
