<?php if ( ! defined( 'ABSPATH' ) ) exit;
$site_name     = get_bloginfo( 'name' );
$business_phone = get_option( 'jjpws_business_phone', '' );
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8" /><title>Quote Request Received</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#2c7a3d;padding:24px 32px;">
        <h1 style="color:#fff;margin:0;font-size:22px;">We Got Your Request!</h1>
    </td></tr>
    <tr><td style="padding:32px;">
        <p>Hi <?php echo esc_html( $jjpws_customer_name ?: 'there' ); ?>,</p>
        <p>Thanks for reaching out to <?php echo esc_html( $site_name ); ?>. We received your request and will follow up within one business day with a custom quote.</p>
        <?php if ( $business_phone ) : ?>
        <p>If you'd like to chat sooner, give us a call at <strong><?php echo esc_html( $business_phone ); ?></strong>.</p>
        <?php endif; ?>
        <p>— The JJ Pet Waste Team</p>
    </td></tr>
</table>
</body>
</html>
