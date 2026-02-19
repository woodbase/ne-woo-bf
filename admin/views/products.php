<?php
/**
 * Products view
 * Variables available:
 *  - products
 *  - page
 *  - total_pages
 *  - search_term
 */

if (!defined('ABSPATH')) exit;
?>

<form method="get" style="margin-bottom: 20px;">
    <input type="hidden" name="page" value="nebf-mvc">
    <input type="hidden" name="tab" value="products">
    <input type="text" name="s" value="<?php echo esc_attr($search_term ?? ''); ?>" placeholder="<?php esc_attr_e('Search products...', 'nebf-mvc'); ?>">
    <input type="submit" class="button" value="<?php esc_attr_e('Search', 'nebf-mvc'); ?>">
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
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo esc_html($product['id']); ?></td>
                    <td><?php echo esc_html($product['name']); ?></td>
                    <td><?php echo esc_html($product['sku']); ?></td>
                    <td><?php echo esc_html($product['price']); ?></td>
                    <td><?php echo !empty($product['visible']) ? esc_html__('Yes', 'nebf-mvc') : esc_html__('No', 'nebf-mvc'); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5"><?php esc_html_e('No products found.', 'nebf-mvc'); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if (!empty($total_pages) && $total_pages > 1): ?>
<div class="tablenav">
    <div class="tablenav-pages">
        <?php for ($i = 1; $i <= $total_pages; $i++): 
            $class = ($i === (int)($page ?? 1)) ? 'current' : '';
            $url = add_query_arg([
                'page' => 'nebf-mvc',
                'tab'  => 'products',
                'paged'=> $i,
                's'    => $search_term
            ], admin_url('admin.php'));
        ?>
            <a class="page-numbers <?php echo esc_attr($class); ?>" href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($i); ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>
