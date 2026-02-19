<?php
/**
 * Settings view for BeautyFort integration
 * Variables available: none
 */

if (!defined('ABSPATH')) exit;
?>

<div class="nebf-settings">

    <h2><?php esc_html_e('Plugin Settings', 'nebf-mvc'); ?></h2>

    <p><?php esc_html_e('Configure your credentials and product name preferences for the Nordic Equilibro - BeautyFort integration.', 'nebf-mvc'); ?></p>

    <form method="post">
        <?php wp_nonce_field('nebf_save_settings'); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="nebf_username"><?php esc_html_e('Username', 'nebf-mvc'); ?></label>
                    </th>
                    <td>
                        <input name="nebf_username" type="text" id="nebf_username"
                               value="<?php echo esc_attr(get_option('nebf_username', '')); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Your BeautyFort username.', 'nebf-mvc'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="nebf_api_key"><?php esc_html_e('API Key', 'nebf-mvc'); ?></label>
                    </th>
                    <td>
                        <input name="nebf_api_key" type="password" id="nebf_api_key"
                               value="<?php echo esc_attr(get_option('nebf_api_key', '')); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Your BeautyFort API key.', 'nebf-mvc'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Separate Brand from Product Name', 'nebf-mvc'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="nebf_separate_brand"
                                   value="1" <?php checked(get_option('nebf_separate_brand', 0), 1); ?>>
                            <?php esc_html_e('Enable to store brand separately and not prepend it to product names.', 'nebf-mvc'); ?>
                        </label>
                    </td>
                </tr>

            </tbody>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'nebf-mvc'); ?>">
        </p>

    </form>

</div>
