<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'kadence_an_add_settings_page' );
add_action( 'admin_init', 'kadence_an_register_settings' );

function kadence_an_add_settings_page() {
    add_options_page(
        'Kadence Action Network Integration',
        'Kadence Action Network',
        'manage_options',
        'kadence-an-settings',
        'kadence_an_render_settings_page'
    );
}

function kadence_an_register_settings() {
    register_setting( 'kadence_an_settings_group', 'kadence_an_api_key' );
}

function kadence_an_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Kadence Action Network Integration</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'kadence_an_settings_group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Action Network API Key</th>
                    <td><input type="text" name="kadence_an_api_key" value="<?php echo esc_attr( get_option('kadence_an_api_key') ); ?>" size="50" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
} 