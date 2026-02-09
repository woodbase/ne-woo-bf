<?php
// Funktion för att hantera både strängar och array
        function nebf_format_field($field) {
            if (empty($field)) return '—';
            if (is_array($field)) {
                // Slå ihop med radbrytningar
                return implode('<br>', array_map('esc_html', $field));
            }
            return esc_html($field);
        }
        

function nebf_render_products_tab()
{
    $products = get_transient('nebf_beautyfort_products');

    if (false === $products || empty($products)) {
        echo '<p>Ingen BeautyFort-data laddad. Klicka på “Hämta produkter från BeautyFort”.</p>';
        return;
    }

    // Hantera "per page", "page", "orderby" och "order"
    $per_page = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 100;
    $page     = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $orderby  = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'fullname';
    $order    = isset($_GET['order']) ? strtoupper($_GET['order']) : 'ASC';

    // Sortera array
    usort($products, function ($a, $b) use ($orderby, $order) {
        if ($orderby === 'status') {
            $valA = !empty(get_posts(['post_type' => 'product', 'meta_key' => '_beautyfort_id', 'meta_value' => $a['bf_id'], 'fields' => 'ids'])) ? 1 : 0;
            $valB = !empty(get_posts(['post_type' => 'product', 'meta_key' => '_beautyfort_id', 'meta_value' => $b['bf_id'], 'fields' => 'ids'])) ? 1 : 0;
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

    $total_products = count($products);
    $total_pages    = ceil($total_products / $per_page);
    $offset = ($page - 1) * $per_page;
    $products_page = array_slice($products, $offset, $per_page);

    // Formulär för antal per sida
    ?>
    <form method="get" style="margin-bottom:15px;">
        <input type="hidden" name="page" value="<?= esc_attr($_GET['page'] ?? '') ?>">
        <label for="per_page">Produkter per sida:</label>
        <input type="number" id="per_page" name="per_page" value="<?= esc_attr($per_page) ?>" min="1" max="100">
        <button type="submit" class="button">Uppdatera</button>
    </form>
    <?php

    // Sorteringslänkar
    function nebf_sort_link($column, $current_orderby, $current_order, $label)
    {
        $order = 'ASC';
        $arrow = ' ⇅';

        if ($current_orderby === $column) {
            if ($current_order === 'ASC') {
                $order = 'DESC';
                $arrow = ' ▲';
            } else {
                $order = 'ASC';
                $arrow = ' ▼';
            }
        }

        $url = add_query_arg([
            'orderby' => $column,
            'order'   => $order,
            'paged'   => 1,
            'per_page'=> $_GET['per_page'] ?? 10,
        ]);

        return '<a href="' . esc_url($url) . '" style="text-decoration:none;">' . esc_html($label) . $arrow . '</a>';
    }

    echo '<table class="widefat striped nebf-products-table">
        <thead>
            <tr>
                <th>Välj</th>
                <th>' . nebf_sort_link('status', $orderby, $order, 'Status') . '</th>
                <th>Bild</th>
                <th>' . nebf_sort_link('fullname', $orderby, $order, 'Namn') . '</th>
                <th>' . nebf_sort_link('sku', $orderby, $order, 'SKU') . '</th>
                <th>' . nebf_sort_link('brand', $orderby, $order, 'Varumärke') . '</th>
                <th>' . nebf_sort_link('collection', $orderby, $order, 'Kollektion') . '</th>
                <th>' . nebf_sort_link('price', $orderby, $order, 'Pris') . '</th>
                <th>' . nebf_sort_link('stock_level', $orderby, $order, 'Lager') . '</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($products_page as $index => $product):
if(!empty($product['description'])){
    error_log(print_r($product['description'], true));
}
        $existing = get_posts([
            'post_type'  => 'product',
            'meta_key'   => '_beautyfort_id',
            'meta_value' => $product['bf_id'],
            'fields'     => 'ids',
        ]);

        $is_imported = !empty($existing);

        $image = !empty($product['thumbnail_url'])
            ? '<img src="' . esc_url($product['thumbnail_url']) . '" width="50" height="50">'
            : '—';

        $accordion_id = 'accordion-' . $index;
        ?>

        <!-- Huvudrad -->
        <tr class="product-row <?= $is_imported ? 'is-imported' : ''; ?>" data-accordion="<?= $accordion_id; ?>">
            <td>
                <?php if ($is_imported): ?>
                    —
                <?php else: ?>
                    <input type="checkbox" name="import_ids[]" value="<?= esc_attr($product['bf_id']); ?>">
                <?php endif; ?>
            </td>
            <td><?= $is_imported ? '🟢' : '⚪'; ?></td>
            <td><?= $image; ?></td>
            <td><?= esc_html($product['fullname'] ?? '—'); ?></td>
            <td><?= esc_html($product['sku'] ?? '—'); ?></td>
            <td><?= esc_html($product['brand'] ?? '—'); ?></td>
            <td><?= esc_html($product['collection'] ?? '—'); ?></td>
            <td><?= function_exists('wc_price') ? wc_price($product['price'] ?? 0) : esc_html($product['price'] ?? '—'); ?></td>
            <td><?= esc_html($product['stock_level'] ?? '—'); ?></td>
        </tr>

        <!-- Accordion-rad -->
<tr class="accordion-content" id="<?= $accordion_id; ?>" style="display:none;">
    <td colspan="9" style="background:#f9f9f9;">
        <strong>Beskrivning:</strong> <?= nebf_format_field($product['description'] ?? null); ?><br>
        <strong>EAN:</strong> <?= nebf_format_field($product['barcode'] ?? null); ?><br>
        <strong>Volym:</strong> <?= nebf_format_field($product['size'] ?? null); ?><br>
        <strong>Färg:</strong> <?= nebf_format_field($product['color'] ?? null); ?><br>
        <strong>Ingredienser:</strong> <?= nebf_format_field($product['ingredients'] ?? null); ?><br>
        <strong>Övrigt:</strong> <?= nebf_format_field($product['extra_info'] ?? null); ?><br>

        <strong>Bild:</strong>
        <?php if (!empty($product['high_res_image_url'])): ?>
            <img src="<?= esc_url($product['high_res_image_url']); ?>" alt="<?= esc_attr($product['fullname']); ?>" style="max-width:150px;">
        <?php elseif (!empty($product['thumbnail_url'])): ?>
            <img src="<?= esc_url($product['thumbnail_url']); ?>" alt="<?= esc_attr($product['fullname']); ?>" style="max-width:100px;">
        <?php else: ?>
            — 
        <?php endif; ?><br>

        <strong>Senast köpt:</strong> <?= nebf_format_field($product['last_purchased_date'] ?? null); ?><br>
        <strong>Senast pris:</strong> <?= nebf_format_field($product['last_purchased_price'] ?? null); ?><br>
        <strong>BF ID:</strong> <?= nebf_format_field($product['bf_id'] ?? null); ?><br>
        <strong>SKU:</strong> <?= nebf_format_field($product['sku'] ?? null); ?>
    </td>
</tr>


    <?php endforeach;

    echo '</tbody></table>';

    // Föregående / Nästa navigering
    if ($total_pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">';

        if ($page > 1) {
            $prev_url = add_query_arg([
                'paged'   => $page - 1,
                'per_page'=> $per_page,
                'orderby' => $orderby,
                'order'   => $order
            ]);
            echo '<a class="page-numbers prev" href="' . esc_url($prev_url) . '">&laquo; Föregående</a> ';
        }

        echo ' Sida ' . $page . ' av ' . $total_pages . ' ';

        if ($page < $total_pages) {
            $next_url = add_query_arg([
                'paged'   => $page + 1,
                'per_page'=> $per_page,
                'orderby' => $orderby,
                'order'   => $order
            ]);
            echo '<a class="page-numbers next" href="' . esc_url($next_url) . '">Nästa &raquo;</a>';
        }

        echo '</div></div>';
    }
}
