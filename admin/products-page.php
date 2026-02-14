<?php
if (!defined('ABSPATH')) exit;

function nebf_render_products_page()
{
    // --- Hämta produkter från DB ---
    if (function_exists('nebf_get_cached_products')) {
        $products = nebf_get_cached_products();
    } else {
        $products = []; // fallback
        error_log('NEBF ERROR: nebf_get_cached_products() finns inte!');
    }

    error_log('NEBF: Rendering products page, products loaded: ' . count($products));

    if (!$products) {
        echo '<p>' . esc_html__('Ingen BeautyFort-data laddad. Klicka på “Hämta produkter från BeautyFort”.', 'ne-bf-woo') . '</p>';
        return;
    }

    // --- SYNKA ---
    if (isset($_POST['nebf_sync_selected'])) {

        $products = nebf_get_cached_products();

        foreach ($_POST['selected_products'] as $bf_id) {
            foreach ($products as $product) {
                if ($product['bf_id'] === $bf_id) {
                    $sale_price = floatval($_POST['sale_prices'][$bf_id] ?? 0);
                    nebf_sync_product_to_woo($product, $sale_price);
                }
            }
        }

        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . esc_html__('Produkter synkade till WooCommerce!', 'ne-bf-woo') . '</p>';
        echo '</div>';
    }

    // --- Pagination & sortering ---
    $per_page = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 100;
    $page     = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $orderby  = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'fullname';
    $order    = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';

    // --- Filter & sök ---
    $search        = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $brand_f       = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
    $collection_f  = isset($_GET['collection']) ? sanitize_text_field($_GET['collection']) : '';
    $status_f      = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

    // --- Dropdown-data ---
    $brands      = array_unique(array_filter(array_column($products, 'brand')));
    sort($brands);
    $collections = array_unique(array_filter(array_column($products, 'collection')));
    sort($collections);

?>
    <!-- ========================= -->
    <!-- FORMULÄR / FILTER -->
    <form method="get" style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <input type="hidden" name="page" value="<?= esc_attr($_GET['page'] ?? '') ?>">
        <input type="number" name="per_page" value="<?= esc_attr($per_page) ?>" min="1" max="500">
        <input type="search" id="nebf-live-search" placeholder="<?= esc_attr__('Live-sök namn / SKU / varumärke', 'ne-bf-woo'); ?>" value="<?= esc_attr($search); ?>">

        <select name="brand">
            <option value=""><?= esc_html__('Alla varumärken', 'ne-bf-woo'); ?></option>
            <?php foreach ($brands as $b): ?>
                <option value="<?= esc_attr($b); ?>" <?= selected($brand_f, $b, false); ?>><?= esc_html($b); ?></option>
            <?php endforeach; ?>
        </select>

        <select name="collection">
            <option value=""><?= esc_html__('Alla kollektioner', 'ne-bf-woo'); ?></option>
            <?php foreach ($collections as $c): ?>
                <option value="<?= esc_attr($c); ?>" <?= selected($collection_f, $c, false); ?>><?= esc_html($c); ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status">
            <option value=""><?= esc_html__('Alla statusar', 'ne-bf-woo'); ?></option>
            <option value="imported" <?= selected($status_f, 'imported', false); ?>><?= esc_html__('Importerade', 'ne-bf-woo'); ?></option>
            <option value="not_imported" <?= selected($status_f, 'not_imported', false); ?>><?= esc_html__('Ej importerade', 'ne-bf-woo'); ?></option>
        </select>

        <button class="button button-primary"><?= esc_html__('Uppdatera', 'ne-bf-woo'); ?></button>
        <button id="nebf-select-all" class="button"><?= esc_html__('Välj alla', 'ne-bf-woo'); ?></button>
    </form>

    <form method="POST">
        <button type="submit" name="nebf_sync_selected" class="button button-primary">
            <?= esc_html__('Synka till WooCommerce', 'ne-bf-woo'); ?>
        </button>

        <!-- ========================= -->
        <!-- PRODUKTTABELL -->
        <table class="widefat striped nebf-products-table">
            <thead>
                <tr>
                    <th><?= esc_html__('Välj', 'ne-bf-woo'); ?></th>
                    <th><?= nebf_sort_link('status', $orderby, $order, __('Status', 'ne-bf-woo')); ?></th>
                    <th><?= esc_html__('Bild', 'ne-bf-woo'); ?></th>
                    <th><?= nebf_sort_link('fullname', $orderby, $order, __('Namn', 'ne-bf-woo')); ?></th>
                    <th><?= nebf_sort_link('sku', $orderby, $order, __('SKU', 'ne-bf-woo')); ?></th>
                    <th><?= nebf_sort_link('brand', $orderby, $order, __('Varumärke', 'ne-bf-woo')); ?></th>
                    <th><?= nebf_sort_link('collection', $orderby, $order, __('Kollektion', 'ne-bf-woo')); ?></th>
                    <th><?= nebf_sort_link('price', $orderby, $order, __('Kostpris', 'ne-bf-woo')); ?></th>
                    <th><?= esc_html__('Försäljningspris', 'ne-bf-woo'); ?></th>
                    <th><?= esc_html__('Marginal %', 'ne-bf-woo'); ?></th>
                    <th><?= nebf_sort_link('stock_level', $orderby, $order, __('Lager', 'ne-bf-woo')); ?></th>
                    <th class="nebf-expand-col"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products_page as $i => $product): 
                // ... behåll resten av loopen oförändrad, bara rubrikerna ovan behöver i18n
            ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </form>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?= paginate_links([
                    'base' => add_query_arg(array_merge($_GET, ['paged' => '%#%'])),
                    'format' => '',
                    'current' => $page,
                    'total' => $total_pages,
                    'prev_text' => '«',
                    'next_text' => '»'
                ]) ?>
            </div>
        </div>
    <?php endif; ?>

<?php
}
