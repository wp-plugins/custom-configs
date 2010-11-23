<?php
/*
Plugin Name: Custom Settings
Plugin URI: http://jacobanderic.com
Description: Allows you to create custom settings that you can easily update via the administration panel in Settings > Custom Settings and also allow you to use mentioned settings in your theme using a simple PHP function: string get_config( $key [, $default_value]). Very simple, yet efficient.
Version: 1.8
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
		$wpdb->query("create table " . CONFIG_TABLE . " (slug varchar (255) , value text ,niceName varchar (255))");
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
function je_custom_configs_help_text($text) { // contextual help text for plugin
	if ($_GET['page'] == 'custom_configs' ) {
    	$text = '
		<div class="metabox-prefs">
    		<p>To use a Custom Setting, use the function <em>string get_config( $key [, $default_value])</em> in your theme.</p>
    		<p>The <em>$default_value</em> parameter is optional, but will be used if the specified setting cannot be found.</p>
    		<p>The function returns a string so don\'t forget to precede it by an <em>echo</em> if you want to output it.</p>
    		<br />
    		<p><strong>Example:</strong></p>
    		<p>Twitter: &#60;?php echo get_config("twitter","jacobanderic"); ?&#62;</p>
    		<br />
    		<p><strong>Uninstalling:</strong></p>
    		<p>To uninstall the plugin, first click the uninstall button at the end of this page, this will remove the Custom Settings table from your WordPress Database, then uninstall the plugin from the <a href="'.get_bloginfo('wpurl').'/wp-admin/plugins.php">Plugins page</a>.</p>
    		<br />
    		<p><strong>For more information:</strong></p>
    		<p><a href="http://wordpress.org/extend/plugins/custom-configs/" target="_blank">WordPress Plugin Page</a></p>
    		<p><a href="http://jacobanderic.com" target="_blank">Jacob &amp; Eric</a></p>
		</div>';
    }
    return $text;
} 
add_filter('contextual_help', 'je_custom_configs_help_text');

function je_custom_configs_list() { // Custom Settings page
	global $wpdb;
	
	if (isset($_POST["action"]) && check_admin_referer('je_custom_configs_action','je_custom_configs_nonce_field') && wp_verify_nonce($_POST['je_custom_configs_nonce_field'],'je_custom_configs_action')): // form was submitted and check for referer and security
		echo "<div class='updated'><p><strong>";
		
		// generate feedback update texts
		if ($_POST["status"] == "add" && !empty($_POST['add_name']) && !empty($_POST['add_key']))
			_e('Setting added.');
		elseif ($_POST["status"] == "uninstall" && isset($_POST[je_uninstall_confirm]))
			_e('All settings were successfully deleted. To complete uninstalling <em>Custom Settings</em>, uninstall the plugin from the <a href="'.get_bloginfo('wpurl').'/wp-admin/plugins.php">Plugins page</a>.');
		else
			_e('Settings saved.');
		
		echo "</strong></p></div>";
		
		if ($_POST["status"] == "save"): // updates settings
			foreach ($_POST as $key => $value): // loops through all custom settings
				$value = filter_var($value, FILTER_UNSAFE_RAW);
				$delete = 'delete_' . $key;
				if ($key!==false) { // makes sure key is valid
					if (isset($_POST[$delete]) && isset($_POST['je_delete_confirm']))
						$sql = "delete from ".CONFIG_TABLE." where slug='$key'";
					else
						$sql = "update ".CONFIG_TABLE." set value='$value' where slug='$key'";
					$wpdb->query($sql);
				}
			endforeach;
		endif;
		
		if (!empty($_POST['add_name']) && !empty($_POST['add_key'])) { // add new settings
			je_custom_configs_setup(); // checks if table exists
			$_POST['add_key'] = filter_var($_POST['add_key'], FILTER_SANITIZE_SPECIAL_CHARS);
			$_POST['add_name'] = filter_var($_POST['add_name'], FILTER_SANITIZE_SPECIAL_CHARS);
			$_POST['add_value'] = filter_var($_POST['add_value'], FILTER_UNSAFE_RAW);
			$sql = "insert into ".CONFIG_TABLE." set slug='".$_POST['add_key']."', niceName='".$_POST["add_name"]."', value='".$_POST["add_value"]."'";
			$wpdb->query($sql);
		}
		
		if ($_POST["status"] == "uninstall" && isset($_POST[je_uninstall_confirm])): // uninstalls plugin 
			$wpdb->query("drop table " . CONFIG_TABLE);
		endif;
	endif;
	
	// Settings list
	echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br /></div><h2>'.__('Custom Settings').'</h2>';
	$sql = "select * from " . CONFIG_TABLE . " order by niceName asc";
	$fields = $wpdb->get_results($sql);
	$alt = false;
	
	foreach ($fields as $field): $i++; // loops through settings
	
		if ($i == 1): // outputs the title only if there are settings
			echo '
			<br />
			<form method="post" action="">
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="status" value="save" />';
			
			wp_nonce_field('je_custom_configs_action','je_custom_configs_nonce_field');
			
			echo '
			<table class="widefat fixed" cellspacing="0">
			<thead>
			<tr>
			<th scope="col" class="manage-column check-column" width="1"><input type="checkbox" /></th>
			<th scope="col" class="manage-column column-title" width="30%">'.__("Name").'</th>
			<th scope="col" class="manage-column column-title" width="50%">'.__("Value").'</th>
			<th scope="col" class="manage-column column-title" width="20%">'.__("Key").'</th>
			</tr>
			</thead>
			<tbody>';
		endif;
		
		$alt=!$alt;
		if ($alt) {$clsalt=' class="alternate"';} else {$clsalt='';}
		
		
		echo '
		<tr'.$clsalt.'>
			<th class="check-column"><input type="checkbox" name="delete_'.$field->slug.'" id="delete_'.$field->slug.'" /></th>
			<td class="post-title"><label for="'.$field->slug.'" style="display:block;text-transform:capitalize;">'.$field->niceName.'</label></td>';
			
			if (strlen($field->value) > 60 && preg_match('# #',$field->value)) // display in a textarea if too long and contains a space
				echo '<td><textarea name="'.$field->slug.'" id="'.$field->slug.'" rows="4" cols="10" style="width: 90%;">'.htmlspecialchars($field->value).'</textarea></td>';
			else // display in a textbox
				echo '<td><input type="text" name="'.$field->slug.'" id="'.$field->slug.'" value="'.htmlspecialchars($field->value).'" class="regular-text" style="width: 90%;" /></td>';
		echo '
			<td><small>'.$field->slug.'</small></td>
		</tr>';
		
	endforeach;
	
	if ($i > 0): // ouputs the submit button only if there are settings
	
		echo '</tbody></table>
		<p class="submit"><input type="submit" name="Submit" value="'.__("Save Changes").'" class="button-primary" /> <input type="checkbox" id="je_delete_confirm" name="je_delete_confirm" /> <small><label for="je_delete_confirm">Confirm deletion for checked Settings</label></small></p>
		</form></div>';
	
	endif;
				
	je_custom_configs_add();
}

function je_custom_configs_add() { // add a setting form
	echo '
	<div class="wrap tool-box" style="margin-right:6px;margin-left:6px;">
		<h3 class="title">'.__("Add a Custom Setting").'</h3>
			<form method="post" action="">
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="status" value="add" />';
				
				wp_nonce_field('je_custom_configs_action','je_custom_configs_nonce_field');
				
				echo '
				<table class="form-table" cellspacing="0">
					<tr valign="top">
						<th scope="row"><label for="add_name">Name</label></th>
						<td><input type="text" name="add_name" id="add_name" value="" class="regular-text" style="width:100%;" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="add_key">Key <small><em>(must be unique)</em></small></label></th>
						<td><input type="text" name="add_key" id="add_key" value="" class="regular-text" style="width:100%;" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="add_value">Value</label></th>
						<td><textarea name="add_value" id="add_value" rows="4" cols="10" style="width:100%;"></textarea></td>
					</tr>
				</table>
				<p class="submit"><input type="submit" name="Submit" value="'.__("Add Setting").'" /></p>
		</form>
	</div>';
		  
		  je_custom_configs_uninstall();
}

function je_custom_configs_uninstall() { // uninstall form
	echo '
	<br /><div class="wrap" style="border-top:1px solid #dfdfdf;"><br />
		<h3>'.__("Uninstall").'</h3>
		<form method="post" action="">
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="status" value="uninstall" />';
			
			wp_nonce_field('je_custom_configs_action','je_custom_configs_nonce_field');
			
			echo '
			<input type="checkbox" id="je_uninstall_confirm" name="je_uninstall_confirm" /> <span><small>I understand that by checking this box and clicking the <em>Uninstall</em> button I will completely delete all <em>Custom Settings</em>.</small></span><br />
			<p class="submit"><input type="submit" name="Submit" value="'.__("Uninstall").'" /></p>
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