<?php
if (!defined('ABSPATH')) exit;

function tfp_dashboard_render_payment_details_content()
{
    $user_id  = get_current_user_id();
    $name     = tfp_dashboard_user_name();
    $has_paid = tfp_billing_user_has_paid($user_id);

    $card = function_exists('tfp_billing_get_default_card_summary') ? tfp_billing_get_default_card_summary($user_id) : null;
    $stripe_ready = function_exists('tfp_stripe_is_configured') && tfp_stripe_is_configured();

    tfp_dashboard_render_page_header(
        __('Payment Details', 'tfp-dashboard'),
        sprintf(esc_html__('%s, update your billing information', 'tfp-dashboard'), esc_html(tfp_dashboard_user_first_name() ?: $name)),
        __('Save or update your payment method securely without leaving the Discipleship platform.', 'tfp-dashboard')
    );
    ?>
    <div>
        <div class="tfp-dash-billing-detail__intro">
            <h2 class="tfp-dash-section__hi"><?php printf(esc_html__('Hi, %s — Update payment method', 'tfp-dashboard'), esc_html($name)); ?></h2>
            <p class="tfp-dash-section__lead"><?php esc_html_e('Your card details are securely collected by Stripe. The Discipleship platform never sees or stores your full card number.', 'tfp-dashboard'); ?></p>
        </div>

        <div class="tfp-dash-billing-detail__grid tfp-dash-billing-detail">
            <div class="tfp-dash-billing-detail__card-preview">
                <img src="<?php echo esc_url(TFP_DASH_URL . 'assets/images/credit.png'); ?>"
                     alt="Credit card"
                     class="tfp-dash-billing-card-visual tfp-dash-billing-card-visual--image">
            </div>

            <div class="tfp-dash-billing-detail__form">
                <?php if ($card) : ?>
                    <div class="tfp-profile-row">
                        <span><?php esc_html_e('Card on File', 'tfp-dashboard'); ?></span>
                        <strong data-tfp-card-brand><?php printf('%s ending in %s', esc_html($card['brand']), esc_html($card['last4'])); ?></strong>
                    </div>
                    <div class="tfp-profile-row">
                        <span><?php esc_html_e('Expiration', 'tfp-dashboard'); ?></span>
                        <strong data-tfp-card-expiry><?php echo esc_html($card['expiry']); ?></strong>
                    </div>
                <?php else : ?>
                    <p class="tfp-dash-section__lead" data-tfp-no-card><?php esc_html_e('No payment method saved yet.', 'tfp-dashboard'); ?></p>
                <?php endif; ?>

                <?php if ($stripe_ready) : ?>
                    <form class="tfp-dash-billing-secure-form" data-tfp-billing-form>
                        <label>
                            <?php esc_html_e('Card Details', 'tfp-dashboard'); ?>
                            <div class="tfp-stripe-card-element" data-tfp-stripe-card></div>
                        </label>
                        <button type="submit" class="tfp-dash-btn tfp-dash-btn--primary" data-tfp-billing-submit>
                            <?php echo $card ? esc_html__('Update Payment Method', 'tfp-dashboard') : esc_html__('Add Payment Method', 'tfp-dashboard'); ?>
                        </button>
                        <p class="tfp-dash-form__status" data-tfp-billing-status role="status"></p>
                    </form>
                <?php else : ?>
                    <p class="tfp-dash-form__status" data-state="error"><?php esc_html_e('Secure card updates are not configured yet. Please contact support.', 'tfp-dashboard'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="tfp-dash-billing-detail__next-step">
            <a href="<?php echo esc_url(tfp_dashboard_get_url('tfp-dashboard-profile')); ?>" class="tfp-dash-btn tfp-dash-btn--outline">
                <?php esc_html_e('Back to Profile', 'tfp-dashboard'); ?>
            </a>
            <?php if (!$has_paid) : ?>
                <a href="<?php echo esc_url(tfp_billing_pay_now_url($user_id)); ?>" class="tfp-dash-btn tfp-dash-btn--primary">
                    <?php esc_html_e('Pay Now', 'tfp-dashboard'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
