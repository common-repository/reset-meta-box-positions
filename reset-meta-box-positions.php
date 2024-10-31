<?php
/*
Plugin Name: Reset Meta Box Positions
Plugin URI: http://www.wpgoplugins.com/
Description: Reset the meta box positions on admin post editor screen for all post types, including custom post types.
Version: 0.1.2
Author: David Gwyer
Author URI: http://www.wpgoplugins.com
*/

/*  Copyright 2009 David Gwyer (email : david@wpgoplugins.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Prefix rmbp = [r]eset [m]eta [b]ox [p]ositions

class WPGO_Reset_Meta_Box_Positions {
	
	protected $cpt;
	protected $options_page_slug;

	public function __construct() {
		register_uninstall_hook( __FILE__, array( 'WPGO_Reset_Meta_Box_Positions', 'delete_plugin_options' ) );
		add_action( 'admin_init', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'add_options_page' ) );
		add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( &$this, 'add_scripts' ) );
		add_action( 'wp_ajax_rmbp_reset_mb', array( &$this, 'ajax_reset_mb' ) );
	}

	// Process the Ajax request to reset meta boxes
	public function ajax_reset_mb() {

		if ( ! isset( $_POST['rmbp_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['rmbp_ajax_nonce'], 'rmbp_nonce' ) ) {
			die( 'You don\'t have permission to access the Ajax on this page!' );
		}

		global $wpdb;
		$current_user = wp_get_current_user();

		$cpt_str = array();
		if( isset( $_POST['chk_boxes'] ) ) {
			$chk_boxes = $_POST['chk_boxes'];
			foreach($chk_boxes as $chk_box) {
				array_push($cpt_str, get_post_type_object( $chk_box)->labels->name);
				delete_user_meta($current_user->ID, 'meta-box-order_' . $chk_box);
			}
		} else { die('no-chk-boxes'); }

		echo "Success! Meta box positions reset for the following post type(s): <span style='font-weight:bold;'>";
		foreach($cpt_str as $key => $value) {
			echo $value;

			if ($value !== end($cpt_str)) {
				echo ", ";
			}
		}
		echo "</span>";

		die();
	}

	// Add scripts to plugin admin page only
	public function add_scripts( $hook ) {

		if ( $hook != $this->options_page_slug ) {
			return;
		}

		//wp_enqueue_style( 'rmbp-css', plugin_dir_url( __FILE__ ) . 'css/rmbp.css' );
		wp_enqueue_script( 'rmbp-ajax', plugin_dir_url( __FILE__ ) . 'js/rmbp-ajax.js', array( 'jquery' ) );
		wp_localize_script( 'rmbp-ajax', 'rmbp_vars', array(
			'rmbp_nonce' => wp_create_nonce( 'rmbp_nonce' )
		) );
	}

	public function set_post_types() {
		$post_types = get_post_types(array('public' => true,'_builtin' => false));
		//$post_types = array();
		$tmp_post_types = array('post' => 'post', 'page' => 'page' );
		$this->cpt = array_merge($tmp_post_types, $post_types);
	}

	public function get_options() {
		$opt = get_option( 'rmbp_options', array() );

		if(empty($opt)) $opt = array(); // force it to be an array if empty

		// build defaults array rather than hard code
		// e.g. each default element: 'chk_post_mb_positions' => '0'
		$defaults = array();
		foreach($this->cpt as $key => $value) {
			$defaults['chk_' . $value . '_mb_positions'] = '0';
		}

		return array_merge($defaults,$opt);
	}

	/* Draw the menu page itself. */
	public function render_form() {
		$current_user = wp_get_current_user();
		?>
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br></div>
			<h2 id="rmbp-h2">Reset Meta Box Positions</h2>
			<form method="post" action="options.php">
				<?php settings_fields('rmbp_plugin_options'); ?>
				<?php $options = $this->get_options(); ?>

				<table class="form-table" style="margin-top:0;">
					<tr valign="top" id="cpt-chk-tr">
						<td style="padding-top:0;">
							<p style="margin-bottom:20px;">Select post types to reset meta box positions for admin user:&nbsp;&nbsp;<span style="font-weight:bold;"><?php echo $current_user->display_name;?></span></p>
							<?php
							// loop to output the options form check boxes.
							foreach( $options as $opt => $value ) :

								// only process chk boxes starting in 'chk_' and ending in '_mb_positions'
								if( !(substr( $opt, 0, 4 ) === "chk_" && substr( $opt, -13 ) === "_mb_positions") ) continue;

								// chop off 'chk_' and '_mb_positions' from beginning and end of string
								$cpt_tmp = substr( $opt, 4);
								$cpt_tmp = substr( $cpt_tmp, 0, -13);
								?>

								<label><input data-cpt="<?php echo $cpt_tmp; ?>" name="rmbp_options[<?php echo $opt; ?>]" value="1" type="checkbox" <?php if (isset($options[$opt])) { checked('1', $options[$opt]); } ?> /><span style="margin-left: 3px;"><?php echo get_post_type_object( $cpt_tmp)->labels->name; ?></span></label><br />

							<?php endforeach; ?>
						</td>
					</tr>
				</table>
				<div style="margin:0 0 0 0;"><input id="wpgo-reset-meta-boxes" class="button" type="button" value="Reset Positions"><span id="rmbp-spinner" class="spinner" style="float: none;"></span></div>
				<div style="margin:15px 0 30px 0;font-style:italic;" id="rmbp-response"></div>

				<p style="margin-top:15px;padding-bottom:10px;" class="submit">
					<input id="rmbp-submit" type="submit" class="button-primary" value="<?php _e('Save Plugin Settings') ?>" />
				</p>
				<div style="margin-bottom:25px;margin-top:15px;">Please <a href="https://wordpress.org/support/plugin/reset-meta-box-positions" target="_blank">report</a> any plugin issues, or suggest additional features. <span style="font-weight:bold;">All feedback welcome!</span></div>

				<hr>

				<table class="form-table">

          <tr valign="top">
						<th scope="row">Buy me a coffee?</th>
						<td colspan="3">
							<p>If you use this free Plugin on your website <b><em>please</em></b> consider making a <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ELFNNYY3LLLL2" target="_blank">donation</a> to support continued development. Thank you so much.<span style="margin-left:5px;" class="dashicons dashicons-smiley"></span></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">Read all about it!</th>
						<td colspan="3">
							<p>Signup to our plugin newsletter for news and updates about our latest work, and what's coming next.</p>
							<div style="margin-top:10px;"><input class="button" type="button" value="Subscribe here..." onClick="window.open('http://eepurl.com/bXZmmD')"></div>
						</td>
					</tr>

				</table>
			</form>
		</div>
		<?php
	}

	/* Display a Settings link on the main Plugins page. */
	public function plugin_action_links( $links, $file ) {

		if ( $file == plugin_basename( __FILE__ ) ) {
			$slug = 'options-general.php?page=reset-meta-box-positions/reset-meta-box-positions.php';
			$plugin_links = '<a href="' . get_admin_url().$slug . '">'.__('Settings').'</a>';
			/* Make the 'Settings' link appear first. */
			array_unshift( $links, $plugin_links );
		}

		/*if ( $file == plugin_basename( __FILE__ ) ) {
			$pccf_links = '<a style="color:#60a559;" href="https://wpgoplugins.com/plugins/reset-meta-box-positions-pro/" target="_blank" title="Go PRO - 100% money back guarantee"><span style="width:18px;height:18px;font-size:18px;" class="dashicons dashicons-flag"></span></a>';
			array_push( $links, $pccf_links );
		}*/

		return $links;
	}

	/* Delete options table entries ONLY when Plugin deactivated AND deleted. */
	public static function delete_plugin_options() {
		delete_option('rmbp_options');
	}

	/* Init Plugin options to white list our options. */
	public function init(){
		register_setting( 'rmbp_plugin_options', 'rmbp_options' );
		$this->set_post_types();
	}

	/* Add menu page. */
	public function add_options_page() {
		$this->options_page_slug = add_options_page('Reset Meta Box Positions', 'Reset Meta Box Positions', 'manage_options', __FILE__, array( &$this, 'render_form' ) );
	}
}
new WPGO_Reset_Meta_Box_Positions();