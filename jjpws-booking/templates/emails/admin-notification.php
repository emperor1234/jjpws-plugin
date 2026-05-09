<?php if ( ! defined( 'ABSPATH' ) ) exit;
$site_name  = get_bloginfo( 'name' );
$freq_labels = [
    'twice_weekly' => 'Twice a Week',
    'weekly'       => 'Weekly',
    'biweekly'     => 'Bi-Weekly',
];
$lot_labels = [
    'small'  => 'Under 0.75 acre',
    'medium' => '0.75 – 1.5 acres',
    'large'  => 'Over 1.5 acres',
];
$price_fmt  = '$' . number_format( ( $jjpws_total_price_cents ?? 0 ) / 100, 2 );
$freq_label = $freq_labels[ $jjpws_frequency ?? '' ] ?? ( $jjpws_frequency ?? '' );
$lot_label  = $lot_labels[ $jjpws_acreage_tier ?? '' ] ?? ( $jjpws_acreage_tier ?? '' );
$cust       = $jjpws_customer ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8" /><title>New Booking</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#1a4d2e;padding:24px 32px;">
        <h1 style="color:#fff;margin:0;font-size:22px;">New Booking Received</h1>
    </td></tr>
    <tr><td style="padding:32px;">
        <p>A new customer has booked a service.</p>
        <table width="100%" cellpadding="8" cellspacing="0" style="border:1px solid #e5e5e5;border-radius:6px;margin:20px 0;">
            <tr style="background:#f9f9f9;"><td><strong>Customer</strong></td>
                <td><?php echo esc_html( $cust ? $cust->display_name : '—' ); ?></td></tr>
            <tr><td><strong>Email</strong></td>
                <td><?php echo esc_html( $cust ? $cust->user_email : '—' ); ?></td></tr>
            <tr style="background:#f9f9f9;"><td><strong>Service Address</strong></td>
                <td><?php echo esc_html( "{$jjpws_street_address}, {$jjpws_city}, {$jjpws_state} {$jjpws_zip_code}" ); ?></td></tr>
            <tr><td><strong>Lot Size</strong></td>
                <td><?php echo esc_html( $lot_label ); ?></td></tr>
            <tr style="background:#f9f9f9;"><td><strong>Dogs</strong></td>
                <td><?php echo absint( $jjpws_dog_count ?? 0 ); ?></td></tr>
            <tr><td><strong>Frequency</strong></td>
                <td><?php echo esc_html( $freq_label ); ?></td></tr>
            <tr style="background:#f9f9f9;"><td><strong>Monthly Revenue</strong></td>
                <td style="font-size:18px;color:#1a4d2e;"><strong><?php echo esc_html( $price_fmt ); ?></strong></td></tr>
        </table>
        <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=jjpws-dashboard' ) ); ?>">View all customers →</a></p>
    </td></tr>
</table>
</body>
</html>
