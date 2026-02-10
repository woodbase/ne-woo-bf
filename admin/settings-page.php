<?php
/**
 * TAB: Inställningar
 */
function nebf_render_settings_tab()
{
    // Rensa cache manuellt
if (isset($_POST['nebf_clear_cache'])) {
    check_admin_referer('nebf_clear_cache_nonce');
    delete_transient('nebf_beautyfort_products');

    echo '<div class="notice notice-success"><p>';
    echo 'Produktcachen har rensats.';
    echo '</p></div>';
}

    $last_fetch       = get_option('nebf_last_fetch');
    $last_fetch_count = get_option('nebf_last_fetch_count');

    // Test API
    if (isset($_POST['nebf_test_api'])) {
        check_admin_referer('nebf_test_api_nonce');
        $result = nebf_test_api_connection();

        $test = !is_wp_error($result);

        echo '<div class="notice ' . ($test ? 'notice-success' : 'notice-error') . '"><p>';
        echo esc_html($test ? 'API-anslutning OK' : $result->get_error_message());
        echo '</p></div>';
    }

    // Hämta produkter (cache)
    if (isset($_POST['nebf_import_products'])) {
        check_admin_referer('nebf_import_nonce');

        $result = nebf_import_products();

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' .
                esc_html($result->get_error_message()) .
                '</p></div>';
        } else {
            update_option('nebf_last_fetch', current_time('mysql'));
            update_option('nebf_last_fetch_count', $result['total']);

            echo '<div class="notice notice-success"><p>';
            echo intval($result['total']) . ' produkter hämtades från BeautyFort och är redo för urval.';
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
                    <input type="text"
                           name="nebf_api_username"
                           value="<?php echo esc_attr(get_option('nebf_api_username')); ?>"
                           class="regular-text">
                </td>
            </tr>

            <tr>
                <th>API Secret</th>
                <td>
                    <input type="password"
                           name="nebf_api_secret"
                           value="<?php echo esc_attr(get_option('nebf_api_secret')); ?>"
                           class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">Cache time (hours)</th>
                <td>
                    <input type="number"
                           name="nebf_cache_time"
                           value="<?php echo esc_attr(get_option('nebf_cache_time', 1)); ?>"
                           min="-1"
                           class="small-text">
                    <p class="description">
                        Hur länge produkterna ska cachas innan ny hämtning.<br>
                        <strong>-1</strong> = permanent cache (hämtas endast manuellt).
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">Produktnamn</th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="nebf_strip_brand_from_name"
                               value="1"
                               <?php checked(1, get_option('nebf_strip_brand_from_name', 1)); ?>>
                        Ta bort varumärke från produktnamn vid import
                    </label>
                    <p class="description">
                        Om markerad tas varumärket bort från början av produktnamnet
                        (t.ex. <em>Maria Åkerberg – Shea Balm</em> → <em>Shea Balm</em>).
                    </p>
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
        <input type="submit"
               name="nebf_import_products"
               class="button button-primary"
               value="Hämta produkter från BeautyFort">
    </form>
<form method="post" style="margin-top:0.5em;">
    <?php wp_nonce_field('nebf_clear_cache_nonce'); ?>
    <input type="submit"
           name="nebf_clear_cache"
           class="button"
           value="Rensa cache">
</form>

    <?php if ($last_fetch): ?>
        <p>
            Senast hämtad data:
            <?php echo esc_html($last_fetch); ?>
            (<?php echo intval($last_fetch_count); ?> produkter)
        </p>
    <?php endif; ?>
    <p>
    <strong>Cache-status:</strong>
    <?php echo esc_html(nebf_get_cache_status()); ?>
</p>


    <form method="post" style="margin-top:1em;">
        <?php wp_nonce_field('nebf_test_api_nonce'); ?>
        <input type="submit"
               name="nebf_test_api"
               class="button"
               value="Testa API-anslutning">
    </form>
<?php
}
