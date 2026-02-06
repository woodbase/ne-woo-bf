<?php

add_action('admin_menu', function () {
    add_submenu_page(
        'woocommerce',
        'Beautyfort API',
        'Beautyfort API',
        'manage_woocommerce',
        'nebf-api-view',
        'nebf_render-api_view'
    );
});

function nebf_render_api_view()
{
?>
    <div class="wrap">
        <h1>Beautyfort – Lagerdata</h1>

        <?php
        $data = nebf_get_stock_file(); // <-- din fungerande API-funktion

        if (is_wp_error($data)) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html($data->get_error_message());
            echo '</p></div>';
            return;
        }

        if (empty($data)) {
            echo '<p>Inget data returnerades.</p>';
            return;
        }
        ?>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <?php foreach (array_keys($data[0]) as $header): ?>
                        <th><?php echo esc_html($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td><?php echo esc_html($value); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
}
