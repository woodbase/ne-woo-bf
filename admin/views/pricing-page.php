<?php
if (!defined('ABSPATH')) exit;

function nebf_render_pricing_tab()
{
    $settings = get_option('nebf_pricing_settings', [
        'default_type'  => 'percent',
        'default_value' => 30,
        'rounding'      => 'none',
    ]);

    settings_errors('nebf_pricing');
?>

    <h2><?php echo esc_html__('Prissättningsinställningar', 'ne-bf-woo'); ?></h2>

    <form method="post">
        <?php wp_nonce_field('nebf_pricing_nonce'); ?>

        <table class="form-table">

            <tr>
                <th><?php echo esc_html__('Global marginal', 'ne-bf-woo'); ?></th>
                <td>
                    <select name="default_type">
                        <option value="percent" <?php selected($settings['default_type'], 'percent'); ?>>
                            <?php echo esc_html__('Procent (%)', 'ne-bf-woo'); ?>
                        </option>
                        <option value="fixed" <?php selected($settings['default_type'], 'fixed'); ?>>
                            <?php echo esc_html__('Fast (SEK)', 'ne-bf-woo'); ?>
                        </option>
                    </select>

                    <input type="number"
                           step="0.01"
                           name="default_value"
                           value="<?php echo esc_attr($settings['default_value']); ?>">
                </td>
            </tr>

            <tr>
                <th><?php echo esc_html__('Avrundning', 'ne-bf-woo'); ?></th>
                <td>
                    <select name="rounding">
                        <option value="none" <?php selected($settings['rounding'], 'none'); ?>>
                            <?php echo esc_html__('Ingen', 'ne-bf-woo'); ?>
                        </option>
                        <option value="99" <?php selected($settings['rounding'], '99'); ?>>
                            <?php echo esc_html__('Slutar på 99', 'ne-bf-woo'); ?>
                        </option>
                        <option value="9" <?php selected($settings['rounding'], '9'); ?>>
                            <?php echo esc_html__('Slutar på 9', 'ne-bf-woo'); ?>
                        </option>
                        <option value="whole" <?php selected($settings['rounding'], 'whole'); ?>>
                            <?php echo esc_html__('Heltal', 'ne-bf-woo'); ?>
                        </option>
                    </select>
                </td>
            </tr>

        </table>

        <?php submit_button(__('Spara prissättningsinställningar', 'ne-bf-woo'), 'primary', 'nebf_save_pricing'); ?>
    </form>

    <hr>

    <form method="post">
        <?php submit_button(__('Beräkna alla produkter på nytt', 'ne-bf-woo'), 'secondary', 'nebf_recalculate_all'); ?>
    </form>

<?php
}
