<div class="wrap">
    <h1><?php esc_html_e('Nordic Equilibro - BeautyFort integration', 'nebf-mvc'); ?></h1>

    <form method="get" style="margin-bottom: 20px;">
        <input type="hidden" name="page" value="nebf-mvc">
        <input type="text" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" placeholder="<?php esc_attr_e('Search products...', 'nebf-mvc'); ?>">
        <input type="submit" class="button" value="<?php esc_attr_e('Search', 'nebf-mvc'); ?>">
    </form>
<form method="post" style="margin-bottom:15px;">
    <?php wp_nonce_field('nebf_sync_products'); ?>
    <input type="hidden" name="nebf_sync_all" value="1">
    <input type="submit" class="button button-primary"
           value="<?php esc_attr_e('Sync all products to WooCommerce', 'nebf-mvc'); ?>">
</form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('ID', 'nebf-mvc'); ?></th>
                <th><?php esc_html_e('Name', 'nebf-mvc'); ?></th>
                <th><?php esc_html_e('SKU', 'nebf-mvc'); ?></th>
                <th><?php esc_html_e('Price', 'nebf-mvc'); ?></th>
                <th><?php esc_html_e('Visible', 'nebf-mvc'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($products): ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo esc_html($product['id']); ?></td>
                        <td><?php echo esc_html($product['name']); ?></td>
                        <td><?php echo esc_html($product['sku']); ?></td>
                        <td><?php echo esc_html($product['price']); ?></td>
                        <td><?php echo $product['visible'] ? esc_html__('Yes', 'nebf-mvc') : esc_html__('No', 'nebf-mvc'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5"><?php esc_html_e('No products found.', 'nebf-mvc'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($total_pages > 1): ?>
    <div class="tablenav">
        <div class="tablenav-pages">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php
                $class = ($i === $page) ? 'current' : '';
                $url = add_query_arg([
                    'page' => 'nebf-mvc',
                    'paged' => $i,
                    's' => $search_term
                ], admin_url('admin.php'));
                ?>
                <a class="page-numbers <?php echo esc_attr($class); ?>" href="<?php echo esc_url($url); ?>">
                    <?php echo esc_html($i); ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
<?php endif; ?>
</div>
