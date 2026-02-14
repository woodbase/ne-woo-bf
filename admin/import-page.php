<?php

add_action('admin_notices', function () {

    if (!isset($_GET['nebf_import'])) return;

    $result = nebf_import_products();

    if (is_wp_error($result)) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p>' . esc_html($result->get_error_message()) . '</p>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-success is-dismissible">';
        printf(
            '<p>%s</p>',
            esc_html(
                /* translators: %d = antal importerade produkter */
                sprintf(_n('%d produkt importerad som utkast.', '%d produkter importerade som utkast.', $result, 'ne-bf-woo'), $result)
            )
        );
        echo '</div>';
    }

});
