<?php
function nebf_get_status_icon($status)
{
    switch ($status) {
        case 'publish':
            return '<span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="Publicerad"></span>';

        case 'draft':
            return '<span class="dashicons dashicons-edit" style="color: #ffb900;" title="Utkast"></span>';

        default:
            return '<span class="dashicons dashicons-warning" style="color: #dc3232;" title="Okänd status"></span>';
    }
}

function nebf_get_product_thumbnail($product, $size = 'thumbnail')
{
    $image_id = $product->get_image_id();

    if ($image_id) {
        return wp_get_attachment_image($image_id, $size, false, [
            'style' => 'width:50px;height:auto;border-radius:4px;'
        ]);
    }

    return '<span class="dashicons dashicons-format-image" style="font-size:32px;color:#ccc;"></span>';
}

function nebf_render_product_details($product)
{
?>
    <div style="padding:16px; background:#f9f9f9; border-left:4px solid #2271b1;">
        <h3><?php echo esc_html($product->get_name()); ?></h3>

        <p>
            <strong>Status:</strong>
            <?php echo esc_html($product->get_status()); ?>
        </p>

        <p>
            <strong>SKU:</strong>
            <?php echo esc_html($product->get_sku()); ?>
        </p>

        <p>
            <strong>Beskrivning:</strong><br>
            <?php echo wp_kses_post($product->get_description() ?: 'Ingen beskrivning'); ?>
        </p>

        <p>
            <strong>Kort beskrivning:</strong><br>
            <?php echo wp_kses_post($product->get_short_description() ?: '–'); ?>
        </p>

        <p>
            <strong>Lager:</strong>
            <?php
            echo $product->get_manage_stock()
                ? intval($product->get_stock_quantity())
                : 'Ej lagerstyrd';
            ?>
        </p>

        <p>
            <strong>Pris:</strong>
            <?php echo wc_price($product->get_price()); ?>
        </p>

        <p>
            <strong>Attribut:</strong><br>
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
                echo 'Inga attribut';
            }
            ?>
        </p>

        <p>
            <a href="<?php echo esc_url(get_edit_post_link($product->get_id())); ?>"
                class="button button-secondary">
                Öppna i WooCommerce
            </a>
        </p>
    </div>
<?php
}

function nebf_get_wc_products($args = [])
{
    $products = wc_get_products([
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
    ]);

    $data = [];

    foreach ($products as $product) {
        $data[] = [
            'id'        => $product->get_id(),
            'name'      => $product->get_name(),
            'sku'       => $product->get_sku(),
            'price'     => $product->get_price(),
            'stock'     => $product->get_stock_quantity(),
            'status'    => $product->get_status(),
            'image'     => wp_get_attachment_image_url(
                $product->get_image_id(),
                'thumbnail'
            ),
            'type'      => $product->get_type(),
            'weight'    => $product->get_weight(),
            'categories' => wc_get_product_category_list($product->get_id()),
            'permalink' => get_edit_post_link($product->get_id()),
            'raw'       => $product, // för detaljvisning
        ];
    }

    return $data;
}
