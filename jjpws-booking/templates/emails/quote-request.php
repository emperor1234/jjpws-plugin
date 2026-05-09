<?php if ( ! defined( 'ABSPATH' ) ) exit;
$site_name = get_bloginfo( 'name' );
$reason_labels = [
    'too_many_dogs' => '5 or more dogs',
    'large_lot'     => 'Lot size over 1.5 acres',
    'out_of_range'  => 'Address beyond service radius',
    'other'         => 'Other',
];
$reason_label = $reason_labels[ $jjpws_reason ?? '' ] ?? ( $jjpws_reason ?? '—' );
$address      = trim( "{$jjpws_street_address}, {$jjpws_city}, {$jjpws_state} {$jjpws_zip_code}", ', ' );
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8" /><title>New Quote Request</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#1a4d2e;padding:24px 32px;">
        <h1 style="color:#fff;margin:0;font-size:22px;">New Quote Request</h1>
    </td></tr>
    <tr><td style="padding:32px;">
        <p>A new customer has requested a custom quote.</p>
        <table width="100%" cellpadding="8" cellspacing="0" style="border:1px solid #e5e5e5;border-radius:6px;margin:20px 0;">
            <tr style="background:#f9f9f9;"><td><strong>Reason</strong></td><td><?php echo esc_html( $reason_label ); ?></td></tr>
            <tr><td><strong>Name</strong></td><td><?php echo esc_html( $jjpws_customer_name ?: '—' ); ?></td></tr>
            <tr style="background:#f9f9f9;"><td><strong>Email</strong></td><td><?php echo esc_html( $jjpws_customer_email ); ?></td></tr>
            <tr><td><strong>Phone</strong></td><td><?php echo esc_html( $jjpws_customer_phone ?: '—' ); ?></td></tr>
            <?php if ( $address && $address !== ',' ) : ?>
            <tr style="background:#f9f9f9;"><td><strong>Address</strong></td><td><?php echo esc_html( $address ); ?></td></tr>
            <?php endif; ?>
            <?php if ( ! empty( $jjpws_lot_size_acres ) ) : ?>
            <tr><td><strong>Lot Size</strong></td><td><?php echo esc_html( $jjpws_lot_size_acres ); ?> acres</td></tr>
            <?php endif; ?>
            <?php if ( ! empty( $jjpws_dog_count ) ) : ?>
            <tr style="background:#f9f9f9;"><td><strong>Dogs</strong></td><td><?php echo absint( $jjpws_dog_count ); ?></td></tr>
            <?php endif; ?>
            <?php if ( ! empty( $jjpws_distance_miles ) ) : ?>
            <tr><td><strong>Distance</strong></td><td><?php echo esc_html( $jjpws_distance_miles ); ?> miles</td></tr>
            <?php endif; ?>
        </table>
        <h3>Message</h3>
        <p style="background:#f9f9f9;padding:12px;border-radius:4px;white-space:pre-wrap;"><?php echo esc_html( $jjpws_message ); ?></p>
        <p>Reply directly to this email to contact the customer.</p>
    </td></tr>
</table>
</body>
</html>
