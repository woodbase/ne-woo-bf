<?php
if (!defined('ABSPATH')) exit;

/**
 * Hjälpfunktioner för BeautyFort
 * Samlar alla funktioner som används i admin och produktimport.
 */

/**
 * Hämtar cachelagrade produkter
 */
function nebf_get_cached_products() {
    $products = get_option('nebf_beautyfort_products', []);
    return is_array($products) ? $products : [];
}

/**
 * Genererar sorteringslänk för tabellkolumner
 */
function nebf_sort_link($column, $current_orderby, $current_order, $label) {
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
        'orderby'    => $column,
        'order'      => $order,
        'paged'      => 1,
        'per_page'   => $_GET['per_page'] ?? 100,
        's'          => $_GET['s'] ?? '',
        'brand'      => $_GET['brand'] ?? '',
        'collection' => $_GET['collection'] ?? '',
        'status'     => $_GET['status'] ?? '',
    ]);

    return '<a href="' . esc_url($url) . '">' . esc_html__($label, 'nebf') . $arrow . '</a>';
}

/**
 * Formaterar ett fält (sträng eller array)
 */
function nebf_format_field($field) {
    if (empty($field)) return '—';
    if (is_array($field)) {
        return implode('<br>', array_map('esc_html', $field));
    }
    return esc_html($field);
}

/**
 * Rensar produktnamn från varumärke
 */
function nebf_clean_product_name($name, $brand) {
    $strip = get_option('nebf_strip_brand_from_name', 1);

    if (!$strip || !$brand || !$name) return $name;

    $patterns = [
        '/^' . preg_quote($brand, '/') . '\s*[-–]?\s*/i',
    ];

    return trim(preg_replace($patterns, '', $name));
}

/**
 * Hämtar produkter från WooCommerce
 */
function nebf_get_wc_products($args = []) {
    $products = wc_get_products(array_merge([
        'limit'    => 200,
        'status'   => ['publish', 'draft'],
        'orderby'  => 'date',
        'order'    => 'DESC',
        'meta_query' => [
            [
                'key'     => '_nebf_product_id',
                'compare' => 'EXISTS',
            ]
        ]
    ], $args));

    $data = [];
    foreach ($products as $product) {
        $data[] = [
            'id'         => $product->get_id(),
            'name'       => $product->get_name(),
            'sku'        => $product->get_sku(),
            'price'      => $product->get_price(),
            'stock'      => $product->get_stock_quantity(),
            'status'     => $product->get_status(),
            'image'      => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
            'type'       => $product->get_type(),
            'weight'     => $product->get_weight(),
            'categories' => wc_get_product_category_list($product->get_id()),
            'permalink'  => get_edit_post_link($product->get_id()),
            'raw'        => $product, // för detaljvisning
        ];
    }

    return $data;
}

/**
 * Renderar produktdetaljer (HTML)
 */
function nebf_render_product_details($product) {
    ?>
    <div style="padding:16px; background:#f9f9f9; border-left:4px solid #2271b1;">
        <h3><?php echo esc_html($product->get_name()); ?></h3>

        <p><strong><?php esc_html_e('Status', 'nebf'); ?>:</strong> <?php echo esc_html($product->get_status()); ?></p>
        <p><strong><?php esc_html_e('SKU', 'nebf'); ?>:</strong> <?php echo esc_html($product->get_sku()); ?></p>
        <p><strong><?php esc_html_e('Beskrivning', 'nebf'); ?>:</strong><br><?php echo wp_kses_post($product->get_description() ?: __('Ingen beskrivning', 'nebf')); ?></p>
        <p><strong><?php esc_html_e('Kort beskrivning', 'nebf'); ?>:</strong><br><?php echo wp_kses_post($product->get_short_description() ?: '–'); ?></p>
        <p><strong><?php esc_html_e('Lager', 'nebf'); ?>:</strong>
            <?php
            echo $product->get_manage_stock()
                ? intval($product->get_stock_quantity())
                : __('Ej lagerstyrd', 'nebf');
            ?>
        </p>
        <p><strong><?php esc_html_e('Pris', 'nebf'); ?>:</strong> <?php echo wc_price($product->get_price()); ?></p>

        <p><strong><?php esc_html_e('Attribut', 'nebf'); ?>:</strong><br>
            <?php
            $attributes = $product->get_attributes();
            if ($attributes) {
                echo '<ul>';
                foreach ($attributes as $attr) {
                    echo '<li><strong>' .
                        esc_html(wc_attribute_label($attr->get_name())) .
                        ':</strong> ' .
                        esc_html(implode(', ', $attr->get_options())) .
                        '</li>';
                }
                echo '</ul>';
            } else {
                echo __('Inga attribut', 'nebf');
            }
            ?>
        </p>

        <p>
            <a href="<?php echo esc_url(get_edit_post_link($product->get_id())); ?>" class="button button-secondary">
                <?php esc_html_e('Öppna i WooCommerce', 'nebf'); ?>
            </a>
        </p>
    </div>
    <?php
}

/**
 * Hämtar thumbnail för produkt
 */
function nebf_get_product_thumbnail($product, $size = 'thumbnail') {
    $image_id = $product->get_image_id();

    if ($image_id) {
        return wp_get_attachment_image($image_id, $size, false, [
            'style' => 'width:50px;height:auto;border-radius:4px;'
        ]);
    }

    return '<span class="dashicons dashicons-format-image" style="font-size:32px;color:#ccc;"></span>';
}

/**
 * Statusikon för produkt
 */
function nebf_get_status_icon($status) {
    switch ($status) {
        case 'publish':
            return '<span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="' . esc_attr__('Publicerad', 'nebf') . '"></span>';
        case 'draft':
            return '<span class="dashicons dashicons-edit" style="color: #ffb900;" title="' . esc_attr__('Utkast', 'nebf') . '"></span>';
        default:
            return '<span class="dashicons dashicons-warning" style="color: #dc3232;" title="' . esc_attr__('Okänd status', 'nebf') . '"></span>';
    }
}
