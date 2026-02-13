<?php
if (!defined('ABSPATH')) exit;

class NEBF_Pricing_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'handle_actions']);
    }

    /**
     * Lägg till flik under BeautyFort
     */
    public function add_menu() {
        add_submenu_page(
            'beautyfort-dashboard',      // parent slug
            'Pricing',                   // page title
            'Pricing',                   // menu title
            'manage_options',            // capability
            'nebf-pricing',              // menu slug
            [$this, 'render_page']       // callback
        );
    }

    /**
     * Hantera POST-actions: Save, Recalculate, Reset
     */
    public function handle_actions() {

        // Save global settings
        if (isset($_POST['nebf_pricing_settings']) && check_admin_referer('nebf_pricing_save', 'nebf_pricing_nonce')) {
            update_option('nebf_pricing_settings', $_POST['nebf_pricing_settings']);
            add_settings_error('nebf_pricing_messages', 'saved', 'Settings saved', 'updated');
        }

        // Recalculate all products
        if (isset($_POST['nebf_recalculate_all']) && check_admin_referer('nebf_recalculate_all', 'nebf_recalculate_nonce')) {
            NEBF_Pricing_Engine::recalculate_all_products();
            add_settings_error('nebf_pricing_messages', 'recalculated', 'All product prices recalculated', 'updated');
        }

        // Reset all product overrides
        if (isset($_POST['nebf_reset_all']) && check_admin_referer('nebf_reset_all', 'nebf_reset_nonce')) {
            $products = get_posts([
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);

            foreach ($products as $id) {
                delete_post_meta($id, '_nebf_margin_override_enabled');
                delete_post_meta($id, '_nebf_margin_type');
                delete_post_meta($id, '_nebf_margin_value');
            }

            NEBF_Pricing_Engine::recalculate_all_products();
            add_settings_error('nebf_pricing_messages', 'reset', 'All product overrides reset', 'updated');
        }
    }

    /**
     * Rendera admin-sida
     */
    public function render_page() {
        $settings = get_option('nebf_pricing_settings', [
            'default_type'  => 'percent',
            'default_value' => 30,
            'rounding'      => 'none',
        ]);

        settings_errors('nebf_pricing_messages');

        // Inkludera HTML-vyn
        include plugin_dir_path(__FILE__) . 'views/pricing-page.php';
    }
}
