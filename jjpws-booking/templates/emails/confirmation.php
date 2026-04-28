<?php if ( ! defined( 'ABSPATH' ) ) exit;
$site_name  = get_bloginfo( 'name' );
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
$price_fmt   = '$' . number_format( ( $monthly_price_cents ?? 0 ) / 100, 2 );
$freq_label  = $freq_labels[ $frequency ?? '' ] ?? ( $frequency ?? '' );
$lot_label   = $lot_labels[ $lot_size_category ?? '' ] ?? ( $lot_size_category ?? '' );
$user        = get_userdata( $user_id ?? 0 );
$first_name  = $user ? $user->first_name ?: $user->display_name : 'there';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8" /><title>Booking Confirmed</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#2c7a3d;padding:24px 32px;">
        <h1 style="color:#fff;margin:0;font-size:22px;">Booking Confirmed!</h1>
    </td></tr>
    <tr><td style="padding:32px;">
        <p>Hi <?php echo esc_html( $first_name ); ?>,</p>
        <p>Great news — your pet waste pickup service has been booked. Here's a summary:</p>
        <table width="100%" cellpadding="8" cellspacing="0" style="border:1px solid #e5e5e5;border-radius:6px;margin:20px 0;">
            <tr style="background:#f9f9f9;"><td><strong>Service Address</strong></td>
                <td><?php echo esc_html( "{$street_address}, {$city}, {$state} {$zip_code}" ); ?></td></tr>
            <tr><td><strong>Lot Size</strong></td>
                <td><?php echo esc_html( $lot_label ); ?></td></tr>
            <tr style="background:#f9f9f9;"><td><strong>Number of Dogs</strong></td>
                <td><?php echo absint( $dog_count ?? 0 ); ?></td></tr>
            <tr><td><strong>Service Frequency</strong></td>
                <td><?php echo esc_html( $freq_label ); ?></td></tr>
            <tr style="background:#f9f9f9;"><td><strong>Monthly Total</strong></td>
                <td style="font-size:18px;color:#2c7a3d;"><strong><?php echo esc_html( $price_fmt ); ?></strong></td></tr>
        </table>
        <p>Your first service will be scheduled soon. If you have any questions, just reply to this email.</p>
        <p>Thank you for choosing <?php echo esc_html( $site_name ); ?>!</p>
        <p>— The JJ Pet Waste Team</p>
    </td></tr>
    <tr><td style="background:#f4f4f4;padding:16px 32px;text-align:center;font-size:12px;color:#888;">
        <?php echo esc_html( $site_name ); ?> &bull; <?php echo esc_html( home_url() ); ?>
    </td></tr>
</table>
</body>
</html>
