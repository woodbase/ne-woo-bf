<?php
$this->notices->display();
?>

<div class="nebf-dashboard-cards">

    <div class="nebf-card">
        <h2><?php esc_html_e('Total Products', 'nebf-mvc'); ?></h2>
        <p class="nebf-number"><?php echo esc_html($total_products ?? 0); ?></p>
    </div>

    <div class="nebf-card">
        <h2><?php esc_html_e('Synced to WooCommerce', 'nebf-mvc'); ?></h2>
        <p class="nebf-number"><?php echo esc_html($synced_products ?? 0); ?></p>
    </div>

    <div class="nebf-card">
        <h2><?php esc_html_e('Not Synced', 'nebf-mvc'); ?></h2>
        <p class="nebf-number"><?php echo esc_html($unsynced_products ?? 0); ?></p>
    </div>

    <div class="nebf-card">
        <h2><?php esc_html_e('Last Sync', 'nebf-mvc'); ?></h2>
        <p><?php echo esc_html($last_sync ?? __('Never', 'nebf-mvc')); ?></p>
    </div>

</div>

<p>
    <a href="<?php echo esc_url(add_query_arg([
        'page' => 'nebf-mvc',
        'tab'  => 'products'
    ], admin_url('admin.php'))); ?>"
       class="button button-primary">
        <?php esc_html_e('Manage Products', 'nebf-mvc'); ?>
    </a>
</p>
