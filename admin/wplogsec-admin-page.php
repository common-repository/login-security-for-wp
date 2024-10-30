<?php

?>
<style>
<?php include_once ( plugin_dir_path( __FILE__ ) . '.././css/admin.css'); ?> 
</style>
   
    <div class="wrap">
       <h2> <span class="dashicons dashicons-shield admin-logo"></span> WP Login Security <span class="dashicons dashicons-shield admin-logo"></span></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'wplogsec_settings_group' );
            do_settings_sections( 'wplogsec_settings_page' );
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wplogsec_url"> <?PHP _e('Custom Login-URL:', 'login-security-for-wp'); ?>  </label></th>
                    <td><?php echo ( get_site_url()) . ('/'); ?>
					<input type="text" id="wplogsec_url" name="wplogsec_url" value="<?php echo esc_attr( get_option( 'wplogsec_url', '' ) ); ?>" > <b>  <?php _e('Only letters and numbers are allowed.', 'login-security-for-wp' ); ?> </b>
					</td>
                </tr>
            </table>

            <?php
            submit_button();
			
			
            ?>
        </form>
    </div>
<?php	