<?php
if (!defined('ABSPATH')) exit;
require_once plugin_dir_path(__FILE__) . 'products-page.php';
require_once plugin_dir_path(__FILE__) . 'settings-page.php';
/**
 * Registrera inställningar
 */
add_action('admin_init', function () {
    register_setting(
        'nebf_settings_group',
        'nebf_api_username',
        ['sanitize_callback' => 'sanitize_text_field']
    );

    register_setting(
        'nebf_settings_group',
        'nebf_api_secret',
        ['sanitize_callback' => 'sanitize_text_field']
    );
    register_setting(
        'nebf_settings_group',
        'nebf_api_testmode',
        [
            'sanitize_callback' => function ($value) {
                return $value === '1' ? '1' : '0';
            }
        ]
    );
    register_setting(
        'nebf_settings',
        'nebf_strip_brand_from_name',
        [
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => function ($value) {
                return $value ? 1 : 0;
            }
        ]
    );
});

/**
 * Rendera admin-sidan
 */
function nebf_admin_page()
{
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'products';
    $base_url   = admin_url('admin.php?page=nordic-equilibro-beautyfort');
?>
    <div class="wrap">
        <h1>Nordic Equilibro – Produkthantering</h1>

        <h2 class="nav-tab-wrapper">
            <a href="<?php echo esc_url($base_url . '&tab=products'); ?>"
                class="nav-tab <?php echo $active_tab === 'products' ? 'nav-tab-active' : ''; ?>">
                Produkter
            </a>

            <a href="<?php echo esc_url($base_url . '&tab=settings'); ?>"
                class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                Inställningar
            </a>
        </h2>

        <?php
        if ($active_tab === 'settings') {
            nebf_render_settings_tab();
        } else {
            nebf_render_products_tab();
        }
        ?>
    </div>
<?php
}
