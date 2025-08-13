<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Remove Gravity Forms hooks and functions

function kadence_an_add_form_settings_menu( $menu_items, $form_id ) {
    $menu_items[] = array(
        'name'  => 'kadence_an_settings',
        'label' => __( 'Action Network', 'kadence-an' ),
        'icon'  => 'fa-bolt'
    );
    return $menu_items;
}

function kadence_an_form_settings_page( $settings, $form ) {
    $current_tab = rgget( 'subview' );
    if ( $current_tab !== 'kadence_an_settings' ) {
        return $settings;
    }

    // Get saved settings
    $an_settings = rgar( $form, 'kadence_an_settings' );
    $enabled = rgar( $an_settings, 'enabled' );
    $endpoint = rgar( $an_settings, 'endpoint', '' );
    $tags = rgar( $an_settings, 'tags', '' );
    $field_map = rgar( $an_settings, 'field_map', array() );

    // Get GF fields for dropdowns
    $fields = array();
    foreach ( $form['fields'] as $field ) {
        if ( ! empty( $field->label ) ) {
            $fields[ $field->id ] = esc_html( $field->label );
        }
    }
    $field_options = '<option value="">-- Select Field --</option>';
    foreach ( $fields as $id => $label ) {
        $field_options .= sprintf( '<option value="%s">%s</option>', esc_attr( $id ), esc_html( $label ) );
    }

    ob_start();
    ?>
    <h3><?php _e( 'Action Network Integration', 'kadence-an' ); ?></h3>
    <table class="form-table">
        <tr>
            <th scope="row">Enable Integration</th>
            <td><input type="checkbox" name="kadence_an_settings[enabled]" value="1" <?php checked( $enabled, 1 ); ?> /></td>
        </tr>
        <tr>
            <th scope="row">Endpoint Override</th>
            <td><input type="text" name="kadence_an_settings[endpoint]" value="<?php echo esc_attr( $endpoint ); ?>" size="50" placeholder="https://actionnetwork.org/api/v2/people/" /></td>
        </tr>
        <tr>
            <th scope="row">Tags (comma-separated)</th>
            <td><input type="text" name="kadence_an_settings[tags]" value="<?php echo esc_attr( $tags ); ?>" size="50" /></td>
        </tr>
        <tr>
            <th scope="row">First Name Field</th>
            <td><select name="kadence_an_settings[field_map][first_name]">
                <?php echo str_replace( 'value=\"' . esc_attr( rgar( $field_map, 'first_name' ) ) . '\"', 'value=\"' . esc_attr( rgar( $field_map, 'first_name' ) ) . '\" selected', $field_options ); ?>
            </select></td>
        </tr>
        <tr>
            <th scope="row">Last Name Field</th>
            <td><select name="kadence_an_settings[field_map][last_name]">
                <?php echo str_replace( 'value=\"' . esc_attr( rgar( $field_map, 'last_name' ) ) . '\"', 'value=\"' . esc_attr( rgar( $field_map, 'last_name' ) ) . '\" selected', $field_options ); ?>
            </select></td>
        </tr>
        <tr>
            <th scope="row">Email Field</th>
            <td><select name="kadence_an_settings[field_map][email]">
                <?php echo str_replace( 'value=\"' . esc_attr( rgar( $field_map, 'email' ) ) . '\"', 'value=\"' . esc_attr( rgar( $field_map, 'email' ) ) . '\" selected', $field_options ); ?>
            </select></td>
        </tr>
        <tr>
            <th scope="row">Phone Field</th>
            <td><select name="kadence_an_settings[field_map][phone]">
                <?php echo str_replace( 'value=\"' . esc_attr( rgar( $field_map, 'phone' ) ) . '\"', 'value=\"' . esc_attr( rgar( $field_map, 'phone' ) ) . '\" selected', $field_options ); ?>
            </select></td>
        </tr>
        <tr>
            <th scope="row">Address Field</th>
            <td><select name="kadence_an_settings[field_map][address]">
                <?php echo str_replace( 'value=\"' . esc_attr( rgar( $field_map, 'address' ) ) . '\"', 'value=\"' . esc_attr( rgar( $field_map, 'address' ) ) . '\" selected', $field_options ); ?>
            </select></td>
        </tr>
    </table>
    <?php
    return ob_get_clean();
}

function kadence_an_save_form_settings( $form, $settings ) {
    if ( isset( $_POST['kadence_an_settings'] ) ) {
        $form['kadence_an_settings'] = $_POST['kadence_an_settings'];
    }
    return $form;
} 