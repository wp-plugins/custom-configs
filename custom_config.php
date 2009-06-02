<?php
/*
Plugin Name: Custom Configs
Plugin URI: http://jacobanderic.com
Description: Lets you create configuration parameters that you can then update and easily use in your theme using <?php get_config('KEY'); ?>.
Version: 1.2
Author: Jacob Guite-St-Pierre
Author URI: http://jacobanderic.com
*/

/* GLOBAL SETTINGS */
global $wpdb;
define('CONFIG_TABLE', $wpdb->prefix . 'je_custom_config');

register_activation_hook('custom_config.php', 'je_setup');

function je_setup() {
	global $wpdb;
	
	$create_table = "create table ".CONFIG_TABLE." (  slug varchar (255) , value text ,niceName varchar (255) ) ";
	if($wpdb->get_var("SHOW TABLES LIKE '" . CONFIG_TABLE . "'") != CONFIG_TABLE) {
		$wpdb->query($create_table);
	}
}

function je_add_admin() {
	add_submenu_page('options-general.php', 'Custom Configs', 'Custom Configs', 'level_10', 'custom_configs', 'je_list_configs');
}


add_action('admin_menu', 'je_add_admin');

function je_list_configs(){
	global $wpdb;
	if(isset($_POST["action"])){
		echo "<br /><div class='updated'><h3>Saved!</h3></div>";
		foreach($_POST as $key => $value){
			$delete = str_replace("je_","je_delete_",$key);
			$key = str_replace("je_","",$key);
			if($key!==false){
				if (isset($_POST[$delete]))
					$sql = "delete from ".CONFIG_TABLE." where slug='$key'";
				else
					$sql = "update ".CONFIG_TABLE." set value='$value' where slug='$key'";
				$wpdb->query($sql);
			}
		}
		if (!empty($_POST['je_name']) && !empty($_POST['je_key']) && !empty($_POST['je_value'])) {
			$sql = "insert into ".CONFIG_TABLE." set slug='".$_POST['je_key']."', niceName='".$_POST["je_name"]."', value='".$_POST["je_value"]."'";
			$wpdb->query($sql);
		}
	}
	echo "<div class='wrap'>
			<h2>Custom Configs</h2>
				<form method='post' action='options-general.php?page=custom_configs'>
					<input type='hidden' name='action' value='update'>
					<table class='form-table'>
						";
				$sql = "select * from ".CONFIG_TABLE." order by niceName asc";
				$fields = $wpdb->get_results($sql);
				foreach ($fields as $field) {
				echo '<tr valign="top">
						<th scope="row"><label for="je_'.$field->slug.'"><strong>'.$field->niceName.':</strong></label><br /><small>('.$field->slug.')</small></th>
						<td><input name="je_'.$field->slug.'" type="text" id="je_'.$field->slug.'" value="'.htmlspecialchars($field->value).'" size="45"  /></td>
						<td align="right"><small>Delete '.$field->slug.'?</small></td>
						<td><input type="checkbox" name="je_delete_'.$field->slug.'" id="je_delete" /></td>
					</tr>';
				}
				echo "	</table>
					<p class='submit'><input type='submit' name='Submit' value='Update' /></p>
				</form>
				<p><em>To use a Custom Config use the function get_config('KEY');</em></p>
		  </div>";
		  
	je_add_config();
}

function je_add_config(){
	global $wpdb;
	echo "<div class='wrap'>
			<h2>Add a Custom Config</h2>
				<form method='post' action='options-general.php?page=custom_configs'>
					<input type='hidden' name='action' value='update'>
					<table class='form-table'>
						";
	
				echo "<tr valign='top'>
						<th scope='row'><label for='je_name'>Name : </label></th>
						<td><input name='je_name' type='text' id='je_name' value='' size='45'  /></td>
						</tr>";
						
				echo "<tr valign='top'>
						<th scope='row'><label for='je_key'>Key (unique) : </label></th>
						<td><input name='je_key' type='text' id='je_key' value='' size='45'  /></td>
						</tr>";
						
				echo "<tr valign='top'>
						<th scope='row'><label for='je_value'>Value : </label></th>
						<td><input name='je_value' type='text' id='je_value' value='' size='45'  /></td>
						</tr>";

				echo "	</table>
					<p class='submit'><input class='button-primary' type='submit' name='Submit' value='Add' /></p>
				</form>
		  </div>";
}

function get_config($key){
	global $wpdb;
	$sql = "select value from ".CONFIG_TABLE." where slug='$key' limit 0,1";
	return $wpdb->get_var($sql);
}

?>