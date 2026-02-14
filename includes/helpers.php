<?php
if (!defined('ABSPATH')) exit;

/**
 * Hjälpfunktioner för Beauty Fort
 */

/**
 * Statusikon för produkt
 */
function nebf_get_status_icon($status)
{
    switch ($status) {
        case 'publish':
            return '<span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="' . esc_attr(__('Publicerad', 'nordic-equilibro-beautyfort')) . '"></span>';
        case 'draft':
            return '<span class="dashicons dashicons-edit" style="color: #ffb900;" title="' . esc_attr(__('Utkast', 'nordic-equilibro-beautyfort')) . '"></span>';
        default:
            return '<span class="dashicons dashicons-warning" style="color: #dc3232;" title="' . esc_attr(__('Okänd status', 'nordic-equilibro-beautyfort')) . '"></span>';
    }
}

/**
 * Thumbnail för produkt
 */
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

/**
 * Renderar produktdetaljer
 */
function nebf_render_product_details($product)
{
?>
    <div style="padding:16px; background:#f9f9f9; border-left:4px solid #2271b1;">
        <h3><?php echo esc_html($product->get_name()); ?></h3>

        <p><strong><?php _e('Status:', 'nordic-equilibro-beautyfort'); ?></strong> <?php echo esc_html($product->get_status()); ?></p>
        <p><strong><?php _e('SKU:', 'nordic-equilibro-beautyfort'); ?></strong> <?php echo esc_html($product->get_sku()); ?></p>
        <p><strong><?php _e('Beskrivning:', 'nordic-equilibro-beautyfort'); ?></strong><br><?php echo wp_kses_post($product->get_description() ?: __('Ingen beskrivning', 'nordic-equilibro-beautyfort')); ?></p>
        <p><strong><?php _e('Kort beskrivning:', 'nordic-equilibro-beautyfort'); ?></strong><br><?php echo wp_kses_post($product->get_short_description() ?: '–'); ?></p>
        <p><strong><?php _e('Lager:', 'nordic-equilibro-beautyfort'); ?></strong>
            <?php
            echo $product->get_manage_stock()
                ? intval($product->get_stock_quantity())
                : __('Ej lagerstyrd', 'nordic-equilibro-beautyfort');
            ?>
        </p>
        <p><strong><?php _e('Pris:', 'nordic-equilibro-beautyfort'); ?></strong> <?php echo wc_price($product->get_price()); ?></p>

        <p><strong><?php _e('Attribut:', 'nordic-equilibro-beautyfort'); ?></strong><br>
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
                echo __('Inga attribut', 'nordic-equilibro-beautyfort');
            }
            ?>
        </p>

        <p>
            <a href="<?php echo esc_url(get_edit_post_link($product->get_id())); ?>"
                class="button button-secondary">
                <?php _e('Öppna i WooCommerce', 'nordic-equilibro-beautyfort'); ?>
            </a>
        </p>
    </div>
<?php
}

/**
 * Rensar produktnamn från varumärke
 */
function nebf_clean_product_name($name, $brand)
{
    $strip = get_option('nebf_strip_brand_from_name', 1);

    if (!$strip || !$brand || !$name) return $name;

    $patterns = [
        '/^' . preg_quote($brand, '/') . '\s*[-–]?\s*/i',
    ];

    return trim(preg_replace($patterns, '', $name));
}

/**
 * Formaterar ett fält (sträng eller array)
 */
function nebf_format_field($field)
{
    if (empty($field)) return __('—', 'nordic-equilibro-beautyfort');
    if (is_array($field)) {
        return implode('<br>', array_map('esc_html', $field));
    }
    return esc_html($field);
}
