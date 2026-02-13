<?php
// $settings är redan definierade i class-pricing-admin.php
?>
<div class="wrap">
    <h1>Pricing Settings</h1>

    <!-- Global settings -->
    <form method="post">
        <?php wp_nonce_field('nebf_pricing_save', 'nebf_pricing_nonce'); ?>
        <table class="form-table">
            <tr>
                <th>Global margin</th>
                <td>
                    <select name="nebf_pricing_settings[default_type]">
                        <option value="percent" <?php selected($settings['default_type'], 'percent'); ?>>Percent (%)</option>
                        <option value="fixed" <?php selected($settings['default_type'], 'fixed'); ?>>Fixed (SEK)</option>
                    </select>
                    <input type="number" step="0.01" name="nebf_pricing_settings[default_value]" value="<?php echo esc_attr($settings['default_value']); ?>">
                </td>
            </tr>
            <tr>
                <th>Rounding</th>
                <td>
                    <select name="nebf_pricing_settings[rounding]">
                        <option value="none" <?php selected($settings['rounding'], 'none'); ?>>None</option>
                        <option value="99" <?php selected($settings['rounding'], '99'); ?>>99-ending</option>
                        <option value="9" <?php selected($settings['rounding'], '9'); ?>>9-ending</option>
                        <option value="whole" <?php selected($settings['rounding'], 'whole'); ?>>Whole number</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button('Save Settings'); ?>
    </form>

    <hr>

    <!-- Actions -->
    <h2>Actions</h2>
    <form method="post" style="display:inline-block;margin-right:10px;">
        <?php wp_nonce_field('nebf_recalculate_all', 'nebf_recalculate_nonce'); ?>
        <input type="submit" name="nebf_recalculate_all" class="button button-primary" value="Recalculate All Products">
    </form>

    <form method="post" style="display:inline-block;">
        <?php wp_nonce_field('nebf_reset_all', 'nebf_reset_nonce'); ?>
        <input type="submit" name="nebf_reset_all" class="button button-secondary" value="Reset All Product Overrides">
    </form>
</div>
