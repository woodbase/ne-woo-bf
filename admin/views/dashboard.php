<?php
/**
 * Dashboard view
 * Variables available:
 *  - total_products
 *  - synced_products
 *  - unsynced_products
 *  - last_sync
 */

if (!defined('ABSPATH')) exit;
?>

<div class="nebf-dashboard">

    <p><?php printf(esc_html__('Total products: %d', 'nebf-mvc'), $total_products); ?></p>
    <p><?php printf(esc_html__('Synced products: %d', 'nebf-mvc'), $synced_products); ?></p>
    <p><?php printf(esc_html__('Unsynced products: %d', 'nebf-mvc'), $unsynced_products); ?></p>
    <p><?php printf(esc_html__('Last sync: %s', 'nebf-mvc'), $last_sync ?: __('Never', 'nebf-mvc')); ?></p>

    <form method="post" style="margin-top:20px;">
        <?php wp_nonce_field('nebf_sync_products'); ?>
        <input type="hidden" name="nebf_sync_all" value="1">
        <input type="submit" class="button button-primary"
               value="<?php esc_attr_e('Sync all products to WooCommerce', 'nebf-mvc'); ?>">
    </form>

</div>
