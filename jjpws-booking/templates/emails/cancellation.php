<?php if ( ! defined( 'ABSPATH' ) ) exit;
$site_name  = get_bloginfo( 'name' );
$user       = get_userdata( $user_id ?? 0 );
$first_name = $user ? $user->first_name ?: $user->display_name : 'there';
$period_end = isset( $current_period_end ) && $current_period_end
    ? date( 'F j, Y', strtotime( $current_period_end ) )
    : 'the end of your current billing period';
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8" /><title>Subscription Cancelled</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#666;padding:24px 32px;">
        <h1 style="color:#fff;margin:0;font-size:22px;">Subscription Cancellation Confirmed</h1>
    </td></tr>
    <tr><td style="padding:32px;">
        <p>Hi <?php echo esc_html( $first_name ); ?>,</p>
        <p>We've received your cancellation request. Your pet waste pickup subscription has been cancelled.</p>
        <p>Your service will continue through <strong><?php echo esc_html( $period_end ); ?></strong> — no further charges will be made after that date.</p>
        <p>We're sorry to see you go! If you'd like to restart service in the future, you can always book again at <a href="<?php echo esc_url( home_url( '/book' ) ); ?>"><?php echo esc_html( home_url( '/book' ) ); ?></a>.</p>
        <p>Thank you for being a customer of <?php echo esc_html( $site_name ); ?>.</p>
        <p>— The JJ Pet Waste Team</p>
    </td></tr>
    <tr><td style="background:#f4f4f4;padding:16px 32px;text-align:center;font-size:12px;color:#888;">
        <?php echo esc_html( $site_name ); ?> &bull; <?php echo esc_html( home_url() ); ?>
    </td></tr>
</table>
</body>
</html>
