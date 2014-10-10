<?php
/*
Plugin Name: Emailmanager Mail Replacement
Plugin URI: http://tmertz.com/projects/emailmanagermailreplacement/
Description: A simple plugin that replaces the default wp_mail function with Emailmanager (<a href="http://emailmanager.com/">http://emailmanager.com/</a>). This plugin is proudly sponsored by <a href="http://stupid-studio.com/">Stupid Studio</a>.
Version: 1.3.2
Author: Thomas Mertz
Author URI: http://tmertz.com/
*/

require_once("functions.php");

$pm_wp_mail_db_version = "1.3.2";

####################################################################
#
# INSTALLATION
#
####################################################################
function pm_wp_mail_install () {
	global $wpdb;
	global $pm_wp_mail_db_version;
	
	$table_name = $wpdb->prefix . "pm_wp_mail_log";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
				`id` MEDIUMINT( 11 ) NOT NULL AUTO_INCREMENT ,
				`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
				`httpcode` INT NOT NULL ,
				`message` LONGTEXT ,
				PRIMARY KEY (  `id` ) ,
				INDEX (  `id` )
		);";
				
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		add_option("pm_wp_mail_db_version", $pm_wp_mail_db_version);
	}
	
	
	$installed_ver = get_option( "pm_wp_mail_db_version" );
	
	if( $installed_ver != $pm_wp_mail_db_version ) {
		$table_name = $wpdb->prefix . "pm_wp_mail_log";
		$sql = "CREATE TABLE " . $table_name . " (
			`id` MEDIUMINT( 11 ) NOT NULL AUTO_INCREMENT ,
			`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`httpcode` INT NOT NULL ,
			`message` LONGTEXT ,
			PRIMARY KEY (  `id` ) ,
			INDEX (  `id` )
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		update_option( "pm_wp_mail_db_version", $pm_wp_mail_db_version );
	}
}
register_activation_hook(__FILE__,'pm_wp_mail_install');

####################################################################
#
# UNINSTALLATION
#
####################################################################
function pm_wp_mail_uninstall() {
	delete_option( 'pm_wp_mail_key' );
	delete_option( 'pm_wp_mail_address' );
	delete_option( 'pm_wp_mail_type' );
	delete_option( 'pm_wp_mail_activate' );
	
	$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'pm_wp_mail_log;');
}
register_uninstall_hook(__FILE__,'pm_wp_mail_uninstall');

####################################################################
#
# SWITCHING OUT WP_MAIL() FOR OUR CUSTOM MAIL
#
####################################################################
if( !function_exists( 'wp_mail' ) ) {
	function wp_mail($to, $subject, $body) {
		
		global $wpdb;
		
		if ( strstr($to, ",") ) {
			
			$recipients = explode(",", $to);
			foreach ($recipients as $recipient) {
				
				$message = emailmanager_json_encode($recipient, $subject, $body);
			
				$headers = array(
					'Accept: application/json',
					'Content-Type: application/json',
					'X-Emailmanager-Server-Token: ' . get_option( 'pm_wp_mail_key' )
				);
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://trans.emailmanager.com/email');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/certificate/cacert.pem');
			
				$return = curl_exec($ch);
				$curlError = curl_error($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				
				emailmanager_return_log($httpCode,$return);
				
			}
			
		} else {
		
			$message = emailmanager_json_encode($to, $subject, $body);
			
			$headers = array(
				'Accept: application/json',
				'Content-Type: application/json',
				'X-Emailmanager-Server-Token: ' . get_option( 'pm_wp_mail_key' )
			);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://trans.emailmanager.com/email');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/certificate/cacert.pem');
		
			$return = curl_exec($ch);
			$curlError = curl_error($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			emailmanager_return_log($httpCode,$return);

		
		}
		
		if( $httpCode == 200 ) {
			return true;
		} else {
			return false;
		}
				
	}
}

####################################################################
#
# ADMINISTRATION OUTPUT
#
####################################################################
add_action('admin_menu', 'pm_wp_mail_plugin_menu');

function pm_wp_mail_plugin_menu() {
	add_menu_page('EmailManagerApp', 'EmailManagerApp', 'publish_posts', 'emailmanager-menu', 'pm_wp_mail_management','',150);
	add_submenu_page('emailmanager-menu', 'Logs', 'Logs', 'publish_posts', 'emailmanager-logs', 'pm_wp_mail_logs');
}

function pm_wp_mail_management() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have permission to access this page.') );
	}
	
	global $wpdb;
	global $current_user;
	
	echo "<div class=\"wrap\">\n";
	echo "<div id=\"icon-tools\" class=\"icon32\"></div><h2>EmailManagerApp</h2>\n";
	
	if( $_GET["action"] == "test") {
		$to = $current_user->user_email;
		wp_mail($to, 'EmailManagerApp Mail Replacement Test', 'This is a test. If you are receiving this email Emailmanager Mail Replacement is working as intended.');
	
		echo "<div id=\"setting-error-settings_updated\" class=\"updated settings-error\"><p><strong>Test email dispatched.</strong></p></div>";
	}
	
	if( $_POST["pm-wp-mail-update-options"] == "yes" ) {
		
		update_option( 'pm_wp_mail_activate', $_POST["pm_wp_mail_activate"] );
		update_option( 'pm_wp_mail_key', $_POST["pm_wp_mail_key"] );
		update_option( 'pm_wp_mail_address', $_POST["pm_wp_mail_address"] );
		update_option( 'pm_wp_mail_type', $_POST["pm_wp_mail_type"] );
		
		echo "<div id=\"setting-error-settings_updated\" class=\"updated settings-error\"><p><strong>Settings saved.</strong></p></div>";
	}

	echo "<form method=\"post\" action=\"" . $_SERVER["REQUEST_URI"] . "\">\n";
	echo "<table class=\"form-table\">\n";
	echo "<tbody>\n";
	
	echo "<tr valign=\"top\">\n";
	echo "<th scope=\"row\"><label for=\"pm_wp_mail_key\">API key</label></th>\n";
	echo "<td>\n";
	echo "<input type=\"text\" name=\"pm_wp_mail_key\" value=\"" . get_option( 'pm_wp_mail_key' ) . "\" size=\"60\" />\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr valign=\"top\">\n";
	echo "<th scope=\"row\"><label for=\"pm_wp_mail_address\">EmailManager email address</label></th>\n";
	echo "<td>\n";
	echo "<input type=\"text\" name=\"pm_wp_mail_address\" value=\"" . get_option( 'pm_wp_mail_address' ) . "\" size=\"60\" /><br /><span class=\"description\">Usually something like 'no-reply@yourdomain.com'.<br />Should match the sender signature you registered with EmailManagerApp.</span>\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr valign=\"top\">\n";
	echo "<th scope=\"row\"><label for=\"pm_wp_mail_type\">Email Type</label></th>\n";
	echo "<td>\n";
	echo "<select name=\"pm_wp_mail_type\" id=\"pm_wp_mail_type\">\n";
	if(get_option( 'pm_wp_mail_type' )==1) {
		echo "<option value=\"1\" selected=\"selected\">HTML</option>\n";
		echo "<option value=\"0\">Pure text</option>\n";
	} else {
		echo "<option value=\"1\">HTML</option>\n";
		echo "<option value=\"0\" selected=\"selected\">Pure text</option>\n";
	}
	echo "</select>\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr valign=\"top\">\n";
	echo "<th scope=\"row\"></th>\n";
	echo "<td>\n";
	echo "<a href=\"" . $_SERVER["REQUEST_URI"] . "&action=test\" class=\"button-secondary\">Send Test Message</a>\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "</tbody>\n";
	echo "</table>\n";
	echo "<p class=\"submit\"><input type=\"submit\" name=\"Submit\" class=\"button-primary\" value=\"Save Changes\"></p>\n";
	echo "<input type=\"hidden\" name=\"pm-wp-mail-update-options\" value=\"yes\" />\n";
	echo "</form>\n";
	
	echo "<hr style=\"border: 0; color: #ddd;background-color: #ddd;height: 1px;\" />";
	echo "<p style=\"text-align: right;\">This plugin is proudly sponsored by <a href=\"http://www.stupid-studio.com/\">Stupid Studio</a>.</p>";
	echo "<p style=\"text-align: right;\"><a href=\"http://www.stupid-studio.com/\"><img src=\"" . PluginUrl() . "stupid_logo.png\" style=\"border:0;\" alt=\"Stupid Studio\" /></a></p>";
	echo "</div>\n";
}

function pm_wp_mail_logs() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have permission to access this page.') );
	}
	
	global $wpdb;
	
	echo "<div class=\"wrap\">\n";
	echo "<div id=\"icon-tools\" class=\"icon32\"></div><h2>EmailManagerApp Logs</h2>\n";
	
	if( $_GET["action"] == "purge") {
		$table_name = $wpdb->prefix . "pm_wp_mail_log";
		$wpdb->query("DELETE FROM {$table_name} WHERE id <>'0'");
		echo "<div id=\"setting-error-settings_updated\" class=\"updated settings-error\"><p><strong>Your logs have been purged.</strong></p></div>";
	}
	
	echo "<p><form method=\"post\" action=\"/wp-admin/admin.php?page=emailmanager-logs&action=purge\"><input type=\"submit\" class=\"button-primary\" value=\"Purge Logs\" name=\"submit\"></form></p>";
	
	echo '<table class="widefat fixed" cellspacing="0">';
	echo '<thead>';
	echo '<tr class="thead">';
	echo '<th scope="col" class="manage-column" style="">Timestamp</th>';
	echo '<th scope="col" class="manage-column" style="">HTTP Code</th>';
	echo '<th scope="col" class="manage-column" style="">Error message</th>';
	echo '</tr>';
	echo '</thead>';

	echo '<tfoot>';
	echo '<tr class="thead">';
	echo '<th scope="col" class="manage-column" style="">Timestamp</th>';
	echo '<th scope="col" class="manage-column" style="">HTTP Code</th>';
	echo '<th scope="col" class="manage-column" style="">Error message</th>';
	echo '</tr>';
	echo '</tfoot>';

	echo '<tbody>';
	
	$table_name = $wpdb->prefix . "pm_wp_mail_log";
	$logs = $wpdb->get_results("SELECT timestamp,httpcode,message FROM {$table_name} ORDER BY timestamp DESC LIMIT 50");
	
	foreach($logs as $log)  {
	
	echo '  <tr>';
	echo '    <td>' . date('d-m-Y H:i',strtotime($log->timestamp)) . '</td>';
	echo '    <td>' . $log->httpcode . '</td>';
	echo '    <td>' . $log->message . '</td>';
	echo '  </tr>';
	
	
	}
	
	echo '</tbody>';
	
	echo '</table>';
	
	echo "<hr style=\"border: 0; color: #ddd;background-color: #ddd;height: 1px;\" />";
	echo "<p style=\"text-align: right;\">This plugin is proudly sponsored by <a href=\"http://www.stupid-studio.com/\">Stupid Studio</a>.</p>";
	echo "<p style=\"text-align: right;\"><a href=\"http://www.stupid-studio.com/\"><img src=\"" . PluginUrl() . "stupid_logo.png\" style=\"border:0;\" alt=\"Stupid Studio\" /></a></p>";
	
	echo "</div>";
}
?>