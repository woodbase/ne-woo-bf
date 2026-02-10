<?php
if (!defined('ABSPATH')) exit;

function nebf_render_products_page()
{
    $products = get_transient('nebf_beautyfort_products');

    if (!$products) {
        echo '<p>Ingen BeautyFort-data laddad. Klicka på “Hämta produkter från BeautyFort”.</p>';
        return;
    }

    // Pagination & sortering
    $per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 100;
    $page     = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
    $orderby  = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'fullname';
    $order    = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';

    // Filter & sök
    $search        = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $brand_f       = isset($_GET['brand']) ? sanitize_text_field($_GET['brand']) : '';
    $collection_f  = isset($_GET['collection']) ? sanitize_text_field($_GET['collection']) : '';
    $status_f      = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

    // Dropdown-data
    $brands      = array_unique(array_filter(array_column($products, 'brand')));
    sort($brands);
    $collections = array_unique(array_filter(array_column($products, 'collection')));
    sort($collections);

    // =========================
    // FILTER
    $products = array_filter($products, function ($product) use ($search, $brand_f, $collection_f, $status_f) {

        // Status
        if ($status_f) {
            $existing = get_posts([
                'post_type'  => 'product',
                'meta_key'   => '_beautyfort_id',
                'meta_value' => $product['bf_id'],
                'fields'     => 'ids',
            ]);
            $is_imported = !empty($existing);

            if ($status_f === 'imported' && !$is_imported) return false;
            if ($status_f === 'not_imported' && $is_imported) return false;
        }

        // Varumärke
        if ($brand_f && strcasecmp($product['brand'] ?? '', $brand_f) !== 0) return false;

        // Kollektion
        if ($collection_f && strcasecmp($product['collection'] ?? '', $collection_f) !== 0) return false;

        // Sök
        if ($search) {
            $haystack = strtolower(
                ($product['fullname'] ?? '') . ' ' .
                ($product['sku'] ?? '') . ' ' .
                ($product['brand'] ?? '')
            );
            if (strpos($haystack, strtolower($search)) === false) return false;
        }

        return true;
    });

    // =========================
    // SORTERING
    usort($products, function ($a, $b) use ($orderby, $order) {
        if ($orderby === 'status') {
            $valA = !empty(get_posts([
                'post_type'  => 'product',
                'meta_key'   => '_beautyfort_id',
                'meta_value' => $a['bf_id'],
                'fields'     => 'ids'
            ])) ? 1 : 0;

            $valB = !empty(get_posts([
                'post_type'  => 'product',
                'meta_key'   => '_beautyfort_id',
                'meta_value' => $b['bf_id'],
                'fields'     => 'ids'
            ])) ? 1 : 0;
        } else {
            $valA = $a[$orderby] ?? '';
            $valB = $b[$orderby] ?? '';
        }

        if (is_numeric($valA) && is_numeric($valB)) {
            $cmp = $valA - $valB;
        } else {
            $cmp = strcasecmp((string)$valA, (string)$valB);
        }

        return $order === 'ASC' ? $cmp : -$cmp;
    });

    // =========================
    // PAGINATION
    $total_products = count($products);
    $total_pages    = ceil($total_products / $per_page);
    $offset         = ($page - 1) * $per_page;
    $products_page  = array_slice($products, $offset, $per_page);
?>

<!-- ========================= -->
<!-- FORMULÄR / FILTER -->
<form method="get" style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap;">
    <input type="hidden" name="page" value="<?= esc_attr($_GET['page'] ?? '') ?>">

    <input type="number" name="per_page" value="<?= esc_attr($per_page) ?>" min="1" max="500">
    <input type="search" id="nebf-live-search" placeholder="Live-sök namn / SKU / varumärke" value="<?= esc_attr($search); ?>">

    <select name="brand">
        <option value="">Alla varumärken</option>
        <?php foreach ($brands as $b): ?>
            <option value="<?= esc_attr($b); ?>" <?= selected($brand_f, $b, false); ?>><?= esc_html($b); ?></option>
        <?php endforeach; ?>
    </select>

    <select name="collection">
        <option value="">Alla kollektioner</option>
        <?php foreach ($collections as $c): ?>
            <option value="<?= esc_attr($c); ?>" <?= selected($collection_f, $c, false); ?>><?= esc_html($c); ?></option>
        <?php endforeach; ?>
    </select>

    <select name="status">
        <option value="">Alla statusar</option>
        <option value="imported" <?= selected($status_f, 'imported', false); ?>>Importerade</option>
        <option value="not_imported" <?= selected($status_f, 'not_imported', false); ?>>Ej importerade</option>
    </select>

    <button class="button button-primary">Uppdatera</button>
    <button id="nebf-select-all" class="button">Välj alla</button>
</form>

<!-- ========================= -->
<!-- PRODUKTTABELL -->
<table class="widefat striped nebf-products-table">
    <thead>
        <tr>
            <th>Välj</th>
            <th><?= nebf_sort_link('status', $orderby, $order, 'Status'); ?></th>
            <th>Bild</th>
            <th><?= nebf_sort_link('fullname', $orderby, $order, 'Namn'); ?></th>
            <th><?= nebf_sort_link('sku', $orderby, $order, 'SKU'); ?></th>
            <th><?= nebf_sort_link('brand', $orderby, $order, 'Varumärke'); ?></th>
            <th><?= nebf_sort_link('collection', $orderby, $order, 'Kollektion'); ?></th>
            <th><?= nebf_sort_link('price', $orderby, $order, 'Pris'); ?></th>
            <th><?= nebf_sort_link('stock_level', $orderby, $order, 'Lager'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products_page as $i => $product):
            $existing = get_posts([
                'post_type' => 'product',
                'meta_key' => '_beautyfort_id',
                'meta_value' => $product['bf_id'],
                'fields' => 'ids',
            ]);
            $is_imported = !empty($existing);
            $accordion_id = 'acc-' . $i;
        ?>
        <tr class="product-row" data-accordion="<?= esc_attr($accordion_id); ?>">
            <td><?= $is_imported ? '—' : '<input type="checkbox" value="' . esc_attr($product['bf_id']) . '">'; ?></td>
            <td><?= $is_imported ? '🟢' : '⚪'; ?></td>
            <td><?= !empty($product['thumbnail_url']) ? '<img src="' . esc_url($product['thumbnail_url']) . '" width="50">' : '—'; ?></td>
            <td><?= esc_html($product['fullname'] ?? '—'); ?></td>
            <td><?= esc_html($product['sku'] ?? '—'); ?></td>
            <td><?= esc_html($product['brand'] ?? '—'); ?></td>
            <td><?= esc_html($product['collection'] ?? '—'); ?></td>
            <td><?= function_exists('wc_price') ? wc_price($product['price'] ?? 0) : esc_html($product['price'] ?? '—'); ?></td>
            <td><?= esc_html($product['stock_level'] ?? '—'); ?></td>
        </tr>

        <tr id="<?= esc_attr($accordion_id); ?>" class="accordion-row" style="display:none;">
    <td colspan="9" style="background:#f6f7f7; padding:16px;">

        <table class="widefat striped" style="margin:0;">
            <tbody>
                <tr>
                    <th style="width:180px;">Beskrivning</th>
                    <td><?= nebf_format_field($product['description'] ?? null); ?></td>
                </tr>
                <tr>
                    <th>EAN</th>
                    <td><?= nebf_format_field($product['barcode'] ?? null); ?></td>
                </tr>
                <tr>
                    <th>Ingredienser</th>
                    <td><?= nebf_format_field($product['ingredients'] ?? null); ?></td>
                </tr>
                <tr>
                    <th>Volym</th>
                    <td><?= nebf_format_field($product['size'] ?? null); ?></td>
                </tr>
                <tr>
                    <th>Färg</th>
                    <td><?= nebf_format_field($product['color'] ?? null); ?></td>
                </tr>
                <tr>
                    <th>Övrigt</th>
                    <td><?= nebf_format_field($product['extra_info'] ?? null); ?></td>
                </tr>
                <tr>
                    <th>BF-ID</th>
                    <td><?= nebf_format_field($product['bf_id'] ?? null); ?></td>
                </tr>
                <tr>
                    <th>SKU</th>
                    <td><?= nebf_format_field($product['sku'] ?? null); ?></td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top:12px;">
            <strong>Bild</strong><br>
            <?php if (!empty($product['high_res_image_url'])): ?>
                <img src="<?= esc_url($product['high_res_image_url']); ?>" style="max-width:180px;">
            <?php elseif (!empty($product['thumbnail_url'])): ?>
                <img src="<?= esc_url($product['thumbnail_url']); ?>" style="max-width:120px;">
            <?php else: ?>
                —
            <?php endif; ?>
        </div>

    </td>
</tr>


        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($total_pages > 1): ?>
<div class="tablenav">
    <div class="tablenav-pages">
        Sida <?= $page ?> av <?= $total_pages ?>
    </div>
</div>
<?php endif; ?>
<?php
}
