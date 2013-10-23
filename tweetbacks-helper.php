<?php
/*
Plugin Name: Tweetbacks Helper
Plugin Script: tweetbacks-helper.php
Plugin URI: http://sudarmuthu.com/wordpress/tweetbacks-helper
Description: Helper Plugin for Tweetbacks Plugin to help it detect more tweets
Version: 0.9
License: GPL
Author: Sudar
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
Author URI: http://sudarmuthu.com/
Text Domain: tweetbacks-helper

=== RELEASE NOTES ===
Checkout readme for release notes

*/
/*  Copyright 2010  Sudar Muthu  (email : sudar@sudarmuthu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Tweetbacks Helper
 */
class TweetbacksHelper {

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'tweetbacks-helper', false, dirname(plugin_basename(__FILE__)) .  '/languages' );

        // Register hooks
        add_action( 'admin_menu', array(&$this, 'register_settings_page') );
        add_action('admin_init', array(&$this, 'add_settings') );

        /* Use the admin_menu action to define the custom boxes */
        add_action('admin_menu', array(&$this, 'add_custom_box') );

        // Add some JavaScript which is needed
        add_action('admin_head', array(&$this, 'add_scripts'));

        /* Use the save_post action to do something with the data entered */
        add_action('save_post', array(&$this, 'save_postdata'));
        add_action('edit_post', array(&$this, 'save_postdata'));
        add_action('publish_post', array(&$this, 'save_postdata'));
        add_action('edit_page_form', array(&$this, 'save_postdata'));

        $plugin = plugin_basename(__FILE__);
        add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));

        // remove automatic scheduler if needed.
        $options = get_option('tweetbacks-helper-options');
        if ($options['auto-enabled'] == "0") {
            remove_action('wp_footer','yoast_schedule_tweetbacks');
        }
    }

    /**
     * Register the settings page
     */
    function register_settings_page() {
        add_options_page( __('Tweetbacks Helper', 'tweetbacks-helper'), __('Tweetbacks Helper', 'tweetbacks-helper'), 8, 'tweetbacks-helper', array(&$this, 'settings_page') );
    }

    /**
     * add options
     */
    function add_settings() {
        // Register options
        register_setting( 'tweetbacks-helper', 'tweetbacks-helper-options', array(&$this, 'validate_options'));
    }

    /**
     * Validate options
     * @param <type> $input
     * @return <type> 
     */
    function validate_options($input) {
        // it can have only 1 or 0 as value
        $input['auto-enabled'] = ($input['auto-enabled'] == "1") ? "1" : "0";
        $input['cron-enabled'] = ($input['cron-enabled'] == "1") ? "1" : "0";

        return $input;
    }

    /**
     * hook to add action links
     * @param <type> $links
     * @return <type>
     */
    function add_action_links( $links ) {
        // Add a link to this plugin's settings page
        $settings_link = '<a href="options-general.php?page=tweetbacks-helper">' . __("Settings", 'tweetbacks-helper') . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Dipslay the Settings page
     */
    function settings_page() {
?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e( 'Tweetbacks Helper Settings', 'tweetbacks-helper' ); ?></h2>

            <form id="smdf_form" method="post" action="options.php">
                <?php settings_fields('tweetbacks-helper'); ?>
<?php 
                $options = get_option('tweetbacks-helper-options');
                $options['auto-enabled'] = ($options['auto-enabled'] == "") ? "1" : $options['auto-enabled'];
                $options['cron-enabled'] = ($options['cron-enabled'] == "") ? "0" : $options['cron-enabled'];
?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="tweetbacks-helper-options[auto-enabled]"><?php _e( 'Built-in Tweetback scheduler', 'tweetbacks-helper' ); ?></label></th>
                        <td>
                            <input type="radio" name="tweetbacks-helper-options[auto-enabled]" id="tweetbacks-helper-options[auto-enabled]" value="1"  <?php checked('1', $options['auto-enabled']); ?> /> <?php _e("Enabled", 'tweetbacks-helper');?>
                            <input type="radio" name="tweetbacks-helper-options[auto-enabled]" id="tweetbacks-helper-options[auto-enabled]" value="0"  <?php checked('0', $options['auto-enabled']); ?> /> <?php _e("Disabled", 'tweetbacks-helper');?>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="tweetbacks-helper-options[cron-enabled]"><?php _e( 'Manual cron scheduler', 'tweetbacks-helper' ); ?></label></th>
                        <td>
                            <input type="radio" name="tweetbacks-helper-options[cron-enabled]" id="tweetbacks-helper-options[cron-enabled]" value="1"  <?php checked('1', $options['cron-enabled']); ?> /> <?php _e("Enabled", 'tweetbacks-helper');?>
                            <input type="radio" name="tweetbacks-helper-options[cron-enabled]" id="tweetbacks-helper-options[cron-enabled]" value="0"  <?php checked('0', $options['cron-enabled']); ?> /> <?php _e("Disabled", 'tweetbacks-helper');?> <br />
                            <?php _e("For manual cron scheduler to work, you should ping the following url from your crontab.", 'tweetbacks-helper'); ?><br />
                            <code><?php echo plugin_dir_url(__FILE__); ?>cron.php</code>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input type="submit" name="tweetbacks-helper-submit" class="button-primary" value="<?php _e('Save Changes', 'tweetbacks-helper') ?>" />
                </p>
            </form>
        </div>
<?php
        // Display credits in Footer
        add_action( 'in_admin_footer', array(&$this, 'add_footer_links'));

    }

    /**
     * Adds Footer links. Based on http://striderweb.com/nerdaphernalia/2008/06/give-your-wordpress-plugin-credit/
     */
    function add_footer_links() {
        $plugin_data = get_plugin_data( __FILE__ );
        printf('%1$s ' . __("plugin", 'tweetbacks-helper') .' | ' . __("Version", 'tweetbacks-helper') . ' %2$s | '. __('by', 'tweetbacks-helper') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
    }

    function add_scripts() {
?>
<script>
        jQuery(document).ready( function () {
            jQuery('#add_short_url').click(function (){
                // Add a new set of textboxes everytime the add another button is clicked.
                var max_count = Number(jQuery('#su_max_count').val());
                max_count = max_count + 1;
                jQuery('#add_short_url').before('<input type = "text" name = "shorturl_type_'+ max_count +'" value = "" size = "8" /> - <input type = "text" name = "shorturl_url_'+ max_count +'" value = "" size = "25" /><br />');
                jQuery('#su_max_count').val(max_count);
            });
        });
</script>
<?php
    }

    /* Adds a custom section to the "advanced" Post and Page edit screens */
    function add_custom_box() {
        add_meta_box( 'tweetback-helper', __( 'Tweetbacks Helper', 'tweetbacks-helper' ),
                    array(&$this ,'inner_custom_box'), 'post', 'side' );

        add_meta_box( 'tweetback-helper', __( 'Tweetbacks Helper', 'tweetbacks-helper' ),
                    array(&$this ,'inner_custom_box'), 'page', 'side' );
    }

    /* Prints the inner fields for the custom post/page section */
    function inner_custom_box() {
        global $post;
        // Use nonce for verification

        echo '<input type="hidden" name="noncename" id="noncename" value="' .
        wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

        // The actual fields for data entry
        $i = 1;
        if (!empty($post->ID) && $post->ID > 0) {
            $shorturls = get_post_meta($post->ID, 'shorturls', true);
            if (is_array($shorturls)) {
                foreach ($shorturls as $type => $url) {
?>
                    <input type = "text" name = "shorturl_type_<?php echo $i; ?>" value = "<?php echo $type; ?>" size = "6" /> -
                    <input type = "text" name = "shorturl_url_<?php echo $i; ?>" value = "<?php echo $url;  ?>" size = "21" />
                    <br />
<?php
                    $i++;
                }
            }
        }
?>
        <input type = "text" name = "shorturl_type_<?php echo $i; ?>" value = "" size = "6" /> -
        <input type = "text" name = "shorturl_url_<?php echo $i; ?>"  value = "" size = "21" />

        <br />
        <input type = "button" class="button" name = "Add" value = "<?php _e('Add another', 'tweetbacks-helper');?>" id ="add_short_url"/>
        <input type = "hidden" name = "su_max_count" id ="su_max_count" value = "<?php echo $i; ?>" />
<?php
    }

    /* When the post is saved, saves our custom data */
    function save_postdata( $post_id ) {

        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times

        if ( !wp_verify_nonce( $_POST['noncename'], plugin_basename(__FILE__) )) {
            return $post_id;
        }

        if ( 'page' == $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ))
                return $post_id;
        } else {
            if ( !current_user_can( 'edit_post', $post_id ))
                return $post_id;
        }

        // OK, we're authenticated: we need to find and save the data

        $max_count = $_POST['su_max_count'];

        $short_urls = array();

        for ($i = 1 ; $i <= $max_count ; $i++) {
            $type_tpl = "shorturl_type_$i";
            $url_tpl  = "shorturl_url_$i";

            if ($_POST[$type_tpl] != "" && $_POST[$url_tpl] != "") {
                $short_urls[$_POST[$type_tpl]] = $_POST[$url_tpl];
            }
        }

        update_post_meta($post_id, "shorturls", $short_urls);
    }

    // PHP4 compatibility
    function TweetbacksHelper() {
        $this->__construct();
    }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'TweetbacksHelper', 999 ); function TweetbacksHelper() { global $TweetbacksHelper; $TweetbacksHelper = new TweetbacksHelper(); }
?>
