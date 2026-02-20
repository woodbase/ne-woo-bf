<?php
if (!defined('ABSPATH')) exit;

class NEBF_Pricing_Engine
{

    /*
    |--------------------------------------------------------------------------
    | Calculate price for a product
    |--------------------------------------------------------------------------
    */
    public static function calculate_price($product_id, $cost_price)
    {
        $cost_price = floatval($cost_price);
        if ($cost_price <= 0) return 0;

        $margin = self::get_margin_data($product_id);
        $rounding = self::get_settings()['rounding'] ?? 'none';

        return NEBF_Markup_Calculator::calculate($cost_price, $margin, $rounding);
    }

    /*
    |--------------------------------------------------------------------------
    | Get margin data (override or global)
    |--------------------------------------------------------------------------
    */
    public static function get_margin_data($product_id)
    {
        $override_enabled = get_post_meta($product_id, '_nebf_margin_override_enabled', true);

        if ($override_enabled === 'yes') {
            return [
                'type'  => get_post_meta($product_id, '_nebf_margin_type', true) ?: 'percent',
                'value' => floatval(get_post_meta($product_id, '_nebf_margin_value', true)),
            ];
        }

        return self::get_global_margin();
    }

    /*
    |--------------------------------------------------------------------------
    | Global margin
    |--------------------------------------------------------------------------
    */
    public static function get_global_margin()
    {
        $settings = self::get_settings();

        return [
            'type'  => $settings['default_type'],
            'value' => floatval($settings['default_value']),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Recalculate one product
    |--------------------------------------------------------------------------
    */
    public static function recalculate_product($product_id)
    {
        $product = wc_get_product($product_id);
        if (!$product) return false;

        $cost_price = get_post_meta($product_id, '_nebf_cost_price', true);
        if (!$cost_price) return false;

        $new_price = self::calculate_price($product_id, $cost_price);
        $product->set_regular_price($new_price);
        $product->save();

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Recalculate all products
    |--------------------------------------------------------------------------
    */
    public static function recalculate_all_products()
    {
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        $products = get_posts($args);

        foreach ($products as $product_id) {
            self::recalculate_product($product_id);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get settings (global)
    |--------------------------------------------------------------------------
    */
    public static function get_settings()
    {
        $defaults = [
            'default_type'  => 'percent',
            'default_value' => 30,
            'rounding'      => 'none',
        ];

        $saved = get_option('nebf_pricing_settings', []);

        return wp_parse_args($saved, $defaults);
    }
}
