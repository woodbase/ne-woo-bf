<?php

function nebf_render_api_view()
{
?>
    <div class="wrap">
        <h1><?php _e('BeautyFort – Lagerdata', 'ne-bf-woo'); ?></h1>

        <?php
        $data = nebf_get_stock_file(); // <-- API-funktion

        if (is_wp_error($data)) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html($data->get_error_message());
            echo '</p></div>';
            return;
        }

        if (empty($data)) {
            echo '<p>' . esc_html__('Inget data returnerades.', 'ne-bf-woo') . '</p>';
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
