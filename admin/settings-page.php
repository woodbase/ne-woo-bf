<?php
if (!defined('ABSPATH')) exit;

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
});

/**
 * Rendera admin-sidan
 */
function nebf_settings_page()
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

/**
 * TAB: Inställningar
 */
function nebf_render_settings_tab()
{
    $last_import       = get_option('nebf_last_import');
    $last_import_count = get_option('nebf_last_import_count');

    // Test API
    if (isset($_POST['nebf_test_api'])) {
        check_admin_referer('nebf_test_api_nonce');
        $test = nebf_test_connection();

        echo '<div class="notice ' . ($test === true ? 'notice-success' : 'notice-error') . '"><p>';
        echo esc_html($test === true ? 'API-anslutning OK' : $test);
        echo '</p></div>';
    }

    // Import
    if (isset($_POST['nebf_import_products'])) {
        check_admin_referer('nebf_import_nonce');

        $result = nebf_import_products();

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            update_option('nebf_last_import', current_time('mysql'));
            update_option('nebf_last_import_count', $result['total']);

            echo '<div class="notice notice-success"><p>';
            echo intval($result['total']) . ' produkter importerades som utkast.';
            echo '</p></div>';
        }
    }
?>

    <form method="post" action="options.php">
        <?php settings_fields('nebf_settings_group'); ?>

        <table class="form-table">
            <tr>
                <th>API Username</th>
                <td>
                    <input type="text" name="nebf_api_username"
                        value="<?php echo esc_attr(get_option('nebf_api_username')); ?>"
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th>API Secret</th>
                <td>
                    <input type="password" name="nebf_api_secret"
                        value="<?php echo esc_attr(get_option('nebf_api_secret')); ?>"
                        class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">Test mode</th>
                <td>
                    <label>
                        <input type="checkbox"
                            name="nebf_api_testmode"
                            value="1"
                            <?php checked(get_option('nebf_api_testmode'), '1'); ?>>
                        Använd testläge (sandbox)
                    </label>
                </td>
            </tr>

        </table>

        <?php submit_button('Spara inställningar'); ?>
    </form>

    <hr>

    <form method="post">
        <?php wp_nonce_field('nebf_import_nonce'); ?>
        <input type="submit" name="nebf_import_products"
            class="button button-primary"
            value="Importera produkter">
    </form>

    <?php if ($last_import): ?>
        <p>
            Senaste import: <?php echo esc_html($last_import); ?>
            (<?php echo intval($last_import_count); ?> produkter)
        </p>
    <?php endif; ?>

    <form method="post" style="margin-top:1em;">
        <?php wp_nonce_field('nebf_test_api_nonce'); ?>
        <input type="submit" name="nebf_test_api"
            class="button"
            value="Testa API-anslutning">
    </form>
<?php
}

/**
 * TAB: Produkter
 */
function nebf_render_products_tab()
{
    if (!class_exists('WooCommerce')) {
        echo '<p>WooCommerce är inte aktivt.</p>';
        return;
    }

    $products = wc_get_products([
        'limit'  => 200,
        'status' => ['draft', 'publish'],
        'orderby' => 'date',
        'order'  => 'DESC'
    ]);

    if (empty($products)) {
        echo '<p>Inga produkter hittades.</p>';
        return;
    }
?>

    <h2>Importerade produkter</h2>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Namn</th>
                <th>Status</th>
                <th>Lager</th>
                <th>Pris</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo esc_html($product->get_sku()); ?></td>
                    <td><?php echo esc_html($product->get_name()); ?></td>
                    <td><?php echo esc_html($product->get_status()); ?></td>
                    <td><?php echo esc_html($product->get_stock_quantity() ?? 0); ?></td>
                    <td><?php echo esc_html($product->get_regular_price()); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php
}
