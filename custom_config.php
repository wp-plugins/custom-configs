<?php
/*
Plugin Name: Custom Settings
Plugin URI: http://jacobanderic.com
Description: Allows you to create custom settings that you can easily update via the administration panel in Settings > Custom Settings and also allow you to use mentioned settings in your theme using a simple PHP function: string get_config( $key [, $default_value]). Very simple, yet efficient.
Version: 1.7
Author: Jacob & Eric
Author URI: http://jacobanderic.com
*/

/*  Copyright 2010  Jacob & Eric  (email : hello@jacobanderic.com)

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

/*
* Global Settings
* @since 091228
*/
global $wpdb;
define('CONFIG_TABLE', $wpdb->prefix . 'je_custom_config'); // defines plugin table

function je_custom_configs_setup() { // init function, checks if DB exists and create it otherwise
	global $wpdb;
	if ($wpdb->get_var("show tables like '" . CONFIG_TABLE . "'") != CONFIG_TABLE) {
		$create_table = "create table ".CONFIG_TABLE." (slug varchar (255) , value text ,niceName varchar (255))";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($create_table);
	}
}
register_activation_hook(__FILE__, 'je_custom_configs_setup');

function je_custom_configs_menu() { // Admin menu
	add_options_page('Custom Settings', 'Custom Settings', 'manage_options', 'custom_configs', 'je_custom_configs_list');
}
add_action('admin_menu', 'je_custom_configs_menu');

/*
* Plugin Functions
* @since 091228
*/
function je_custom_configs_help_text($text) { // help text for page
	if ($_GET['page'] == 'custom_configs' ) {
    	$text = '
		<div class="metabox-prefs">
    		<p>To use a Custom Setting, use the function <em>string get_config( $key [, $default_value])</em> in your theme.</p>
    		<p>The <em>$default_value</em> parameter is optional, but will be used if the specified setting cannot be found.</p>
    		<p>The function returns a strong so don\'t forget to precede it by an <em>echo</em> if you want to output it.</p>
    		<p><strong>Example:</strong></p>
    		<p>Twitter: <?php echo get_config("twitter","jacobanderic"); ?></p>
    		<p><strong>For more information:</strong></p>
    		<p><a href="http://wordpress.org/extend/plugins/custom-configs/" target="_blank">WordPress Plugin Page</a></p>
    		<p><a href="http://jacobanderic.com" target="_blank">Jacob & Eric</a></p>
		</div>';
    }
    return $text;
} 
add_filter('contextual_help', 'je_custom_configs_help_text');

function je_custom_configs_list() {
	global $wpdb;
	
	if (isset($_POST["action"])): // form was submitted
		echo "<div class='updated'><p><strong>";
		
		if ($_POST["status"] == "updated")
			_e('Settings saved.');
		else
			_e('Setting added.');
		
		echo "</strong></p></div>";
				
		foreach ($_POST as $key => $value): // loops through all custom settings
			$value = filter_var($value, FILTER_UNSAFE_RAW);
			$delete = 'delete_' . $key;
			if ($key!==false) { // makes sure key is valid
				if (isset($_POST[$delete]))
					$sql = "delete from ".CONFIG_TABLE." where slug='$key'";
				else
					$sql = "update ".CONFIG_TABLE." set value='$value' where slug='$key'";
				$wpdb->query($sql);
			}
		endforeach;
		
		if (!empty($_POST['add_name']) && !empty($_POST['add_key'])) { // checks if a new setting was added
			$_POST['add_key'] = filter_var($_POST['add_key'], FILTER_SANITIZE_SPECIAL_CHARS);
			$_POST['add_name'] = filter_var($_POST['add_name'], FILTER_SANITIZE_SPECIAL_CHARS);
			$_POST['add_value'] = filter_var($_POST['add_value'], FILTER_UNSAFE_RAW);
			$sql = "insert into ".CONFIG_TABLE." set slug='".$_POST['add_key']."', niceName='".$_POST["add_name"]."', value='".$_POST["add_value"]."'";
			$wpdb->query($sql);
		}
	endif;
	
	// Settings list
	echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br /></div>';
	$sql = "select * from " . CONFIG_TABLE . " order by niceName asc";
	$fields = $wpdb->get_results($sql);
	
	foreach ($fields as $field): $i++; // loops through settings
	
		if ($i == 1): // outputs the title only if there are settings
			echo '<h2>'.__('Custom Settings').'</h2>
			<form method="post" action="">
			<input type="hidden" name="action" value="update">
			<input type="hidden" name="status" value="updated">
			<table class="form-table">';
		endif;
	
		echo '
		<tr valign="top">
			<th scope="row"><label for="'.$field->slug.'">'.$field->niceName.'</label><br /><small>('.$field->slug.')</small></th>
			<td><input type="text" name="'.$field->slug.'" id="'.$field->slug.'" value="'.htmlspecialchars($field->value).'" class="regular-text" /></td>
			<td align="right"><small>Delete '.$field->slug.'?</small></td>
			<td valign="middle"><input type="checkbox" name="delete_'.$field->slug.'" id="je_delete" /></td>
		</tr>';
		
	endforeach;
	
	if ($i > 0): // ouputs the submit button only if there are settings
	
		echo '</table>
		<p class="submit"><input type="submit" name="Submit" value="'.__("Save Changes").'" class="button-primary" /></p>
		</form>
		</div>';
	
	endif;
				
	je_custom_configs_add();
}

// add setting form
function je_custom_configs_add() {
	global $wpdb;
	echo '<div class="wrap">
			<h2>'.__("Add a Custom Setting").'</h2>
				<form method="post" action="">
					<input type="hidden" name="action" value="update">
					<input type="hidden" name="status" value="add">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label for="add_name">Name</label></th>
							<td><input type="text" name="add_name" id="add_name" value="" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="add_key">Key (unique)</label></th>
							<td><input type="text" name="add_key" id="add_key" value="" class="regular-text" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="add_value">Value</label></th>
							<td><input type="text" name="add_value" id="add_value" value="" class="regular-text" /></td>
						</tr>
					</table>
					<p class="submit"><input type="submit" name="Submit" value="'.__("Add Setting").'" /></p>
				</form>
		  </div>';
}

/*
* Theme Functions
* @since 091228
*/
function get_config($key,$default=0) {
	global $wpdb;
	$sql = "select value from ".CONFIG_TABLE." where slug='$key' limit 0,1";
	$value = $wpdb->get_var($sql);
	if (empty($value))
		$value = $default;
	return $value;
}

?>