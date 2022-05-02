<?php
/*
Plugin Name:		Maintenance Redirect
Plugin URI:			https://wordpress.org/plugins/jf3-maintenance-mode/
Description:		This plugin allows you to specify a maintenance mode message / page for your site as well as configure settings to allow specific users to bypass the maintenance mode functionality in order to preview the site prior to public launch, etc.
Version:			1.7
Stable tag:	 		1.7
Requires at least:	4.6
Tested up to:		5.6
Requires PHP:		5.2.4

Text Domain: 		jf3-maintenance-mode

License:			GPLv2 or later
License URI:		http://www.gnu.org/licenses/gpl-2.0.html

Contributors:		petervandoorn,jfinch3
Author:      		Peter Hardy-vanDoorn since 03/18; based on the original by Jack Finch

Copyright:			Modifications: 2018 Peter Hardy-vanDoorn	(email: wordpress@fabulosa.co.uk)
				Original: 2010-2012  Jack Finch (email: jack@hooziewhats.com - nb: when checked in Dec 2017 this domain is not functioning)
   				

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if( !class_exists("wpjf3_maintenance_redirect") ) {
	class wpjf3_maintenance_redirect {
		
		var $admin_options_name;
		var $maintenance_html_head;
		var $maintenance_html_foot;
		var $maintenance_html_body;
		
		// (php) constructor.
		function __construct() { 
			$this->admin_options_name	= "wpjf3_mr";
			$this->maintenance_html_head	= '<html><head><link href="[[WP_STYLE]]" rel="stylesheet" type="text/css" /><title>[[WP_TITLE]]</title></head><body><div style="margin-left:auto; margin-right: auto; width: 500px; border:1px solid #000; color: #000; background-color: #fff; padding: 10px; margin-top:200px">';
			$this->maintenance_html_foot	= '</div></body></html>';
			$this->maintenance_html_body	= __( 'This site is currently undergoing maintenance. Please check back later.' );
		}
		
		// (php) initialize.
		function init() {
			global $wpdb;
			
			// create keys table if needed.
			$tbl = $wpdb->prefix . $this->admin_options_name . "_access_keys";
    			if( $wpdb->get_var( "SHOW TABLES LIKE '$tbl'" ) != $tbl ) {
				$sql = "create table $tbl ( id int auto_increment primary key, name varchar(100), access_key varchar(20), email varchar(100), created_at datetime not null default '0000-00-00 00:00:00', active int(1) not null default 1 )";
				$wpdb->query($sql);
			}
			
			// create IPs table if needed
			$tbl = $wpdb->prefix . $this->admin_options_name . "_unrestricted_ips";
    			if( $wpdb->get_var( "SHOW TABLES LIKE '$tbl'" ) != $tbl ) {
				$sql = "create table $tbl ( id int auto_increment primary key, name varchar(100), ip_address varchar(20), created_at datetime not null default '0000-00-00 00:00:00', active int(1) not null default 1 )";
				$wpdb->query($sql);
			}
			
			// setup options
			add_option("wpjf3_maintenance_redirect_version", "1.5");
			$tmp_opt = $this->get_admin_options();	
		}
		
		// (php) find user IP.
		function get_user_ip(){
			$ip = ( isset( $_SERVER['HTTP_X_FORWARD_FOR'] ) ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
			return $ip;
		}

		// (php) determine user class c
		function get_user_class_c(){
			$ip = $this->get_user_ip();
			$ip_parts = explode( '.', $ip );
			$class_c = $ip_parts[0] . '.' . $ip_parts[1] . '.' .$ip_parts[2] . '.*';
			return $class_c;
		}
		
		// (php) get and return an array of admin options. if no options set, initialize.
		function get_admin_options() {
			$wpjf3_mr_options = array(
				'enable_redirect'  => 'no',
				'header_type' 	 => '200',
				'method'           => 'message',
				'maintenance_html' => $this->maintenance_html_head . $this->maintenance_html_body . $this->maintenance_html_body,
				'static_page'      => ''
			);
			
			$wpjf3_mr_saved_options = get_option($this->admin_options_name);
			
			if( !empty($wpjf3_mr_saved_options) ) {
				foreach($wpjf3_mr_saved_options as $key => $option)
					$wpjf3_mr_options[$key] = $option;
			}else{
				update_option($this->admin_options_name, $wpjf3_mr_options);
			}
			return $wpjf3_mr_options;
		}
		
		// (php) generate key
		function alphastring( $len = 20, $valid_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ){
			$str  = '';
			$chrs = explode( ' ', $valid_chars );
			for( $i=0; $i<$len; $i++ ){
				$str .= $valid_chars[ rand( 1, strlen( $valid_chars ) - 1 ) ];
			}
			return $str;
		}
		
		// (php) generate maintenance page
		function generate_maintenance_page( $msg_override = '', $skip_header_footer = false ){
			if( $skip_header_footer ){
				$html  = ( $msg_override != '' ) ? stripslashes( $msg_override ) : $this->maintenance_html_body;
			}else{
				$html  = $this->maintenance_html_head;
				$html  = str_replace( '[[WP_TITLE]]', get_bloginfo('name'), $html );
				$html  = str_replace( '[[WP_STYLE]]', get_bloginfo('stylesheet_url'), $html );
				$html .= ( $msg_override != '' ) ? stripslashes( $msg_override ) : $this->maintenance_html_body;
				$html .= $this->maintenance_html_foot;
			}
			$wpjf3_mr_options = $this->get_admin_options();
			if( $wpjf3_mr_options['header_type'] == "200" ) {
				header('HTTP/1.1 200 OK');
				header('Status: 200 OK');
			}else{
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				header('Status: 503 Service Temporarily Unavailable');
			}
			header('Retry-After: 600');
			echo $html;
			exit();
		}
		
		// (php) find out if we need to redirect or not.
		function process_redirect() {
			global $wpdb;
			$valid_ips      = array();
			$valid_class_cs = array();
			$valid_aks      = array();
			$wpjf3_matches  = apply_filters( 'wpjf3_matches', array() );
			
			// set cookie if needed
			if ( isset( $_GET['wpjf3_mr_temp_access_key'] ) && trim( $_GET['wpjf3_mr_temp_access_key'] ) != '' ) {
				// get valid access keys
				$sql = "select access_key from " . $wpdb->prefix . $this->admin_options_name . "_access_keys where active = 1";
				$aks = $wpdb->get_results($sql, OBJECT);
				if( $aks ){
					foreach( $aks as $ak ){
						$valid_aks[] = $ak->access_key;
					}
				}
				
				// set cookie if there's a match
				if( in_array( $_GET['wpjf3_mr_temp_access_key'], $valid_aks ) ){
					$wpjf3_mr_cookie_time = time()+(60*60*24*365);
					setcookie( 'wpjf3_mr_access_key', $_GET['wpjf3_mr_temp_access_key'], $wpjf3_mr_cookie_time, '/' );
					$_COOKIE['wpjf3_mr_access_key'] = $_GET['wpjf3_mr_temp_access_key'];
				}
			}
			
			// get plugin options
			$wpjf3_mr_options = $this->get_admin_options();
			
			// skip admin pages by default
			$url_parts = explode( '/', $_SERVER['REQUEST_URI'] );
			if( in_array( 'wp-admin', $url_parts ) ) {
				$wpjf3_matches[] = "<!-- WPJF_MR: SKIPPING ADMIN -->";
			}else{
				// determine if user is admin.. if so, bypass all of this.
				if ( current_user_can( apply_filters( 'wpjf3_user_can', 'manage_options' ) ) ) {
					$wpjf3_matches[] = "<!-- WPJF_MR: USER IS ADMIN -->";
				}else{
					if( $wpjf3_mr_options['enable_redirect'] == "YES" ){
						// get valid unrestricted IPs
						$sql = "select ip_address from " . $wpdb->prefix . $this->admin_options_name . "_unrestricted_ips where active = 1";
						$ips = $wpdb->get_results($sql, OBJECT);
						if( $ips ){
							foreach( $ips as $ip ){
								$ip_parts = explode( '.', $ip->ip_address );
								if( $ip_parts[3] == '*' ){
									$valid_class_cs[] = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
								}else{
									$valid_ips[] = $ip->ip_address;
								}
							}
						}
						
						// get valid access keys
						$valid_aks = array();
						$sql = "select access_key from " . $wpdb->prefix . $this->admin_options_name . "_access_keys where active = 1";
						$aks = $wpdb->get_results($sql, OBJECT);
						if( $aks ){
							foreach( $aks as $ak ){
								$valid_aks[] = $ak->access_key;
							}
						}
						
						// manage cookie filtering
						if( isset( $_COOKIE['wpjf3_mr_access_key'] ) && $_COOKIE['wpjf3_mr_access_key'] != '' ){
							// check versus active codes
							if( in_array( $_COOKIE['wpjf3_mr_access_key'], $valid_aks ) ){
								$wpjf3_matches[] = "<!-- WPJF_MR: COOKIE MATCH -->";
							}
						}
						
						// manage ip filtering 
						if( in_array( $this->get_user_ip(), $valid_ips ) ) {
							$wpjf3_matches[] = "<!-- WPJF_MR: IP MATCH -->";
						}else{
							// check for partial ( class c ) match
							$ip_parts     = explode( '.', $this->get_user_ip() );
							$user_class_c = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
							if( in_array( $user_class_c, $valid_class_cs ) ) {
								$wpjf3_matches[] = "<!-- WPJF_MR: CLASS C MATCH -->";
							}
						}
						
						if( count( $wpjf3_matches ) == 0 ) {
							// no match found. show maintenance page / message
							if( $wpjf3_mr_options['method'] == 'redirect' ){
								// redirect
								header( 'HTTP/1.1 307 Temporary Redirect' );
								header( 'Status: 307 Temporary Redirect' );
								header( 'Retry-After: 600' );
								header( 'Location:'.$wpjf3_mr_options['static_page'] );
								exit();
							}else	if( $wpjf3_mr_options['method'] == 'html' ){
									// html entered only. do not wrap with header or footer
									$this->generate_maintenance_page( $wpjf3_mr_options['maintenance_html'], true );
							}else{
								// message
								$this->generate_maintenance_page( $wpjf3_mr_options['maintenance_html'] );
							}
						}
					}else{
						$wpjf3_matches[] = "<!-- WPJF_MR: REDIR DISABLED -->";
					}
				}
			}
		}
		
		// (php) add new IP
		function add_new_ip() {
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wpjf3_nonce', 'security' );
			global $wpdb;
			$tbl        = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$name       = esc_sql( stripslashes( $_POST['wpjf3_mr_ip_name'] ) );
			$ip_address = esc_sql( stripslashes( trim( $_POST['wpjf3_mr_ip_ip'] ) ) );
			$sql        = "insert into $tbl ( name, ip_address, created_at ) values ( '$name', '$ip_address', NOW() )";
			$rs         = $wpdb->query( $sql );
			if( $rs ){
				// send table data
				$this->print_unrestricted_ips();
			}else{
				echo __( 'Unable to add IP because of a database error. Please reload the page.' );
			}
			die();
		}
		
		// (php) toggle IP status
		function toggle_ip_status(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wpjf3_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$ip_id     = esc_sql( $_POST['wpjf3_mr_ip_id'] );
			$ip_active = ( $_POST['wpjf3_mr_ip_active'] == 1 ) ? 1 : 0;
			$sql       = "update $tbl set active = '$ip_active' where id = '$ip_id'";
			$rs        = $wpdb->query( $sql );
			if( $rs ){
				// $this->print_unrestricted_ips();
				echo 'SUCCESS' . '|' . $ip_id . '|' . $ip_active;
			}else{
				// echo 'There was an unknown database error. Please reload the page.';
				echo 'ERROR';
			}
			die();
		}
		
		// (php) delete IP
		function delete_ip(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wpjf3_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$ip_id     = esc_sql( $_POST['wpjf3_mr_ip_id'] );
			$sql       = "delete from $tbl where id = '$ip_id'";
			$rs        = $wpdb->query( $sql );
			if( $rs ){
				$this->print_unrestricted_ips();
			}else{
				echo __( 'Unable to delete IP because of a database error. Please reload the page.' );
			}
			die();
		}
		
		// (php) add new Access Key
		function add_new_ak() {
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wpjf3_nonce', 'security' );
			global $wpdb;
			$tbl        = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$name       = esc_sql( stripslashes( $_POST['wpjf3_mr_ak_name'] ) );
			$email      = sanitize_email( $_POST['wpjf3_mr_ak_email'] );
			$access_key = esc_sql( $this->alphastring(20) );
			$sql        = "insert into $tbl ( name, email, access_key, created_at ) values ( '$name', '$email', '$access_key', NOW() )";
			$rs         = $wpdb->query( $sql );
			if( $rs ){
				// email user
				$subject    = sprintf( /* translators: %s = name of the website/blog */ __( "Access Key Link for %s" ), get_bloginfo() );
				$full_msg   = sprintf( /* translators: %s = name of the website/blog */ __( "The following link will provide you temporary access to %s:" ), get_bloginfo() ) . "\n\n"; 
				$full_msg  .= __( "Please note that you must have cookies enabled for this to work." ) . "\n\n";
				$full_msg  .= get_bloginfo('url') . '?wpjf3_mr_temp_access_key=' . $access_key;
				$mail_sent  = wp_mail( $email, $subject, $full_msg );
				echo ( $mail_sent ) ? '<!-- SEND_SUCCESS -->' : '<!-- SEND_FAILURE -->';
				// send table data
				$this->print_access_keys();
			}else{
				echo __( "Unable to add Access Key because of a database error. Please reload the page." );
			}
			die();
		}
		
		// (php) toggle Access Key status
		function toggle_ak_status(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wpjf3_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$ak_id     = esc_sql( $_POST['wpjf3_mr_ak_id'] );
			$ak_active = ( $_POST['wpjf3_mr_ak_active'] == 1 ) ? 1 : 0;
			$sql       = "update $tbl set active = '$ak_active' where id = '$ak_id'";
			$rs        = $wpdb->query( $sql );
			if( $rs ){
				// $this->print_access_keys();
				echo 'SUCCESS' . '|' . $ak_id . '|' . $ak_active;
			}else{
				// echo 'There was an unknown database error. Please reload the page.';
				echo 'ERROR';
			}
			die();
		}
		
		// (php) delete Access Key
		function delete_ak(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wpjf3_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$ak_id     = esc_sql( $_POST['wpjf3_mr_ak_id'] );
			$sql       = "delete from $tbl where id = '$ak_id'";
			$rs        = $wpdb->query( $sql );
			if( $rs ){
				$this->print_access_keys();
			}else{
				echo __( 'Unable to delete Access Key because of a database error. Please reload the page.' );
			}
			die();
		}
		
		// (php) resend Access Key email
		function resend_ak(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wpjf3_nonce', 'security' );
			global $wpdb;
			$tbl       = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$ak_id     = esc_sql( $_POST['wpjf3_mr_ak_id'] );
			$sql       = "select * from $tbl where id = '$ak_id'";
			$ak        = $wpdb->get_row( $sql );
			if( $ak ){
				$subject    = sprintf( /* translators: %s = name of the website/blog */ __( "Access Key Link for %s" ), get_bloginfo() );
				$full_msg   = sprintf( /* translators: %s = name of the website/blog */ __( "The following link will provide you temporary access to %s:" ), get_bloginfo() ) . "\n\n"; 
				$full_msg  .= __( "Please note that you must have cookies enabled for this to work." ) . "\n\n";
				$full_msg  .= get_bloginfo('url') . '?wpjf3_mr_temp_access_key=' . $ak->access_key;
				$mail_sent  = wp_mail( $ak->email, $subject, $full_msg );
				echo ( $mail_sent ) ? 'SEND_SUCCESS' : 'SEND_FAILURE';
			}else{
				echo __( 'ERROR' );
			}
			die();
		}
		
		// (php) generate IP table data 
		function print_unrestricted_ips( ){
			global $wpdb;
			?>
			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr>
						<th class="column-wpjf3-ip-name"  ><?php _e( "Name" ); ?></th>
						<th class="column-wpjf3-ip-ip"    ><?php _e( "IP" ); ?></th>
						<th class="column-wpjf3-ip-active"><?php _e( "Active" ); ?></th>
						<th class="column-wpjf3-actions"  ><?php _e( "Actions" ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="column-wpjf3-ip-name"  ><?php _e( "Name" ); ?></th>
						<th class="column-wpjf3-ip-ip"    ><?php _e( "IP" ); ?></th>
						<th class="column-wpjf3-ip-active"><?php _e( "Active" ); ?></th>
						<th class="column-wpjf3-actions"  ><?php _e( "Actions" ); ?></th>
					</tr>
				</tfoot>

				<tbody>
					<?php
					$sql = "select * from " . $wpdb->prefix . $this->admin_options_name . "_unrestricted_ips order by name";
					$ips = $wpdb->get_results($sql, OBJECT);
					$ip_row_class = 'alternate';
					if( $ips ){
						foreach( $ips as $ip ){
							?>
							<tr id="wpjf-ip-<?php echo $ip->id; ?>" valign="middle"  class="<?php echo $ip_row_class; ?>">
								<td class="column-wpjf3-ip-name"><?php echo $ip->name; ?></td>
								<td class="column-wpjf3-ip-ip"><?php echo $ip->ip_address; ?></td>
								<td class="column-wpjf3-ip-active" id="wpjf3_mr_ip_status_<?php echo $ip->id; ?>" ><?php echo ( $ip->active == 1) ? __('Yes') : __('No'); ?></td>
								<td class="column-wpjf3-actions">
									<span class='edit' id="wpjf3_mr_ip_status_<?php echo $ip->id; ?>_action">
										<?php if( $ip->active == 1 ){ ?>
											<a href="javascript:wpjf3_mr_toggle_ip( 0, <?php echo $ip->id; ?> );"><?php _e( "Disable" ); ?></a> | 
										<?php }else{ ?>
											<a href="javascript:wpjf3_mr_toggle_ip( 1, <?php echo $ip->id; ?> );"><?php _e( "Enable" ); ?></a> | 
										<?php } ?>
									</span>
									<span class='delete'>
										<a class='submitdelete' href="javascript:wpjf3_mr_delete_ip( <?php echo $ip->id ?>, '<?php echo addslashes( $ip->ip_address ) ?>' );" ><?php _e( "Delete" ); ?></a>
									</span>
								</td>
							</tr>
							<?php
							$ip_row_class = ( $ip_row_class == '' ) ? 'alternate' : '';
						}
					}
					?>
					
					<tr id="wpjf-ip-NEW" valign="middle"  class="<?php echo $ip_row_class; ?>">
						<td class="column-wpjf3-ip-name">
							<input class="wpjf3_mr_disabled_field" type="text" id="wpjf3_mr_new_ip_name" name="wpjf3_mr_new_ip_name" value="<?php _e( "Enter Name:" ); ?>" onfocus="wpjf3_mr_undim_field('wpjf3_mr_new_ip_name','<?php _e( "Enter Name:" ); ?>');" onblur="wpjf3_mr_dim_field('wpjf3_mr_new_ip_name','<?php _e( "Enter Name:" ); ?>');">
						</td>
						<td class="column-wpjf3-ip-ip">
							<input class="wpjf3_mr_disabled_field" type="text" id="wpjf3_mr_new_ip_ip" name="wpjf3_mr_new_ip_ip" value="<?php _e( "Enter IP:" ); ?>" onfocus="wpjf3_mr_undim_field('wpjf3_mr_new_ip_ip','<?php _e( "Enter IP:" ); ?>');" onblur="wpjf3_mr_dim_field('wpjf3_mr_new_ip_ip','<?php _e( "Enter IP:" ); ?>');">
						</td>
						<td class="column-wpjf3-ip-active">&nbsp;</td>
						<td class="column-wpjf3-actions">
							<span class='edit' id="wpjf3_mr_add_ip_link">
								<a href="javascript:wpjf3_mr_add_new_ip( );"><?php _e( "Add New IP" ); ?></a>
							</span>
						</td>
					</tr>
					
				</tbody>
			</table>
			<?php
		}
		
		// (php) genereate Access Key table data
		function print_access_keys(){
			global $wpdb;
			?>
			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr>
						<th class="column-wpjf3-ak-name"  ><?php _e( "Name" ); ?></th>
						<th class="column-wpjf3-ak-email" ><?php _e( "Email" ); ?></th>
						<th class="column-wpjf3-ak-key"   ><?php _e( "Access Key" ); ?></th>
						<th class="column-wpjf3-ak-active"><?php _e( "Active" ); ?></th>
						<th class="column-wpjf3-actions"  ><?php _e( "Actions" ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="column-wpjf3-ak-name"  ><?php _e( "Name" ); ?></th>
						<th class="column-wpjf3-ak-email" ><?php _e( "Email" ); ?></th>
						<th class="column-wpjf3-ak-key"   ><?php _e( "Access Key" ); ?></th>
						<th class="column-wpjf3-ak-active"><?php _e( "Active" ); ?></th>
						<th class="column-wpjf3-actions"  ><?php _e( "Actions" ); ?></th>
					</tr>
				</tfoot>
				
				<tbody>
					<?php
					$sql   = "select * from " . $wpdb->prefix . $this->admin_options_name . "_access_keys order by name";
					$codes = $wpdb->get_results($sql, OBJECT);
					$ak_row_class = 'alternate';
					if( $codes ){
						foreach( $codes as $code ){
							?>
							<tr id="wpjf-ak-<?php echo $code->id; ?>" valign="middle"  class="<?php echo $ak_row_class; ?>">
								<td class="column-wpjf3-ak-name"><?php echo $code->name; ?></td>
								<td class="column-wpjf3-ak-email"><a href="mailto:<?php echo $code->email; ?>" title="email <?php echo $code->email; ?>"><?php echo $code->email; ?></a></td>
								<td class="column-wpjf3-ak-key"><?php echo $code->access_key; ?></td>
								<td class="column-wpjf3-ak-active" id="wpjf3_mr_ak_status_<?php echo $code->id; ?>" ><?php echo ( $code->active == 1) ? 'Yes' : 'No'; ?></td>
								<td class="column-wpjf3-actions">
									<span class='edit' id="wpjf3_mr_ak_status_<?php echo $code->id; ?>_action">
										<?php if( $code->active == 1 ){ ?>
											<a href="javascript:wpjf3_mr_toggle_ak( 0, <?php echo $code->id; ?> );"><?php _e( "Disable" ); ?></a> | 
										<?php }else{ ?>
											<a href="javascript:wpjf3_mr_toggle_ak( 1, <?php echo $code->id; ?> );"><?php _e( "Enable" ); ?></a> | 
										<?php } ?>
									</span>
									<span class='resend'>
										<a class='submitdelete' href="javascript:wpjf3_mr_resend_ak( <?php echo $code->id ?>, '<?php echo addslashes( $code->name ) ?>', '<?php echo addslashes( $code->email ) ?>' );" ><?php _e( "Resend Code" ); ?></a> | 
									</span>
									<span class='delete'>
										<a class='submitdelete' href="javascript:wpjf3_mr_delete_ak( <?php echo $code->id ?>, '<?php echo addslashes( $code->name ) ?>' );" ><?php _e( "Delete" ); ?></a>
									</span>
								</td>
							</tr>
							<?php
							$ak_row_class = ( $ak_row_class == '' ) ? 'alternate' : '';
						}
					}
					/*
					?>
					<tr id="wpjf-ak-NONE" valign="middle"  class="<?php echo $ak_row_class; ?>">
						<td colspan="5">Enter a New Access Code</td>
					</tr>
					<?php
					$ak_row_class = ( $ak_row_class == '' ) ? 'alternate' : '';
					*/
					?>
					<tr id="wpjf-ak-NEW" valign="middle"  class="<?php echo $ak_row_class; ?>">
						<td class="column-wpjf3-ak-name">
							<input class="wpjf3_mr_disabled_field" type="text" id="wpjf3_mr_new_ak_name" name="wpjf3_mr_new_ak_name" value="<?php _e( "Enter Name:" ); ?>" onfocus="wpjf3_mr_undim_field('wpjf3_mr_new_ak_name','<?php _e( "Enter Name:" ); ?>');" onblur="wpjf3_mr_dim_field('wpjf3_mr_new_ak_name','<?php _e( "Enter Name:" ); ?>');">
						</td>
						<td class="column-wpjf3-ak-email">
							<input class="wpjf3_mr_disabled_field" type="text" id="wpjf3_mr_new_ak_email" name="wpjf3_mr_new_ak_email" value="<?php _e( "Enter Email:" ); ?>" onfocus="wpjf3_mr_undim_field('wpjf3_mr_new_ak_email','<?php _e( "Enter Email:" ); ?>');" onblur="wpjf3_mr_dim_field('wpjf3_mr_new_ak_email','<?php _e( "Enter Email:" ); ?>');">
						</td>
						<td class="column-wpjf3-ak-key">&nbsp;</td>
						<td class="column-wpjf3-ak-active">&nbsp;</td>
						<td class="column-wpjf3-actions">
							<span class='edit' id="wpjf3_mr_add_ak_link">
								<a href="javascript:wpjf3_mr_add_new_ak( );"><?php _e( "Add New Access Key" ); ?></a>
							</span>
						</td>
					</tr>
					
				</tbody>
			</table>
			<?php
		}
		
		// (php) display redirect status if active
		function display_status_if_active(){
			global $wpdb;
			$wpjf3_mr_options = $this->get_admin_options();
			$show_notice      = false;
			
			if( $wpjf3_mr_options['enable_redirect'] == 'YES' ) $show_notice = true;
			if ( isset( $_POST['update_wp_maintenance_redirect_settings'] ) && $_POST['wpjf3_mr_enable_redirect'] == 'YES' ) $show_notice = true;
			if ( isset( $_POST['update_wp_maintenance_redirect_settings'] ) && $_POST['wpjf3_mr_enable_redirect'] == 'NO'  ) $show_notice = false;
			
			if( $show_notice ){
				$current_screen = get_current_screen();
				$settingslink = ( $current_screen->id != "settings_page_JF3_Maint_Redirect" ) ? ' <a href="options-general.php?page=JF3_Maint_Redirect">'.__( 'Settings' ).'</a>' : '';
				echo '<div class="error" id="wpjf3_mr_enabled_notice"><p><strong>' . sprintf( /* translators: %s = "Maintenance Redirect", the name of the plugin, */ __( "%s is Enabled" ), "Maintenance Redirect" ). '</strong>'.$settingslink.'</p></div>'; 
			}
		}
		
		// (php) add settings link to plugin page
		function plugin_settings_link($links) { 
			$settings_link = '<a href="options-general.php?page=JF3_Maint_Redirect">Settings</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		}
		
		// (php) site health stuff
		
		function wpjf3_add_site_health( $tests ) {
			$tests['direct']['wpjf3_status'] = array(
				'label' => __( 'Maintenance Redirect' ),
				'test'  => array( $this, 'wpjf3_site_health' ),
			);
			return $tests;
		}

		function wpjf3_site_health() {
			$wpjf3_mr_options = $this->get_admin_options();
			
			$result = array(
				'label'       => __( 'Maintenance Redirect is not enabled' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => __( 'Visibility' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					__( 'Maintenance Redirect is not enabled and your site is visible to visitors.' )
				),
				'actions'     => sprintf(
					'<p><a href="options-general.php?page=JF3_Maint_Redirect">%s</a></p>',
					__( 'Settings' )
				),
				'test'        => 'wpjf3_status',
			);

			if ( $wpjf3_mr_options['enable_redirect'] == 'YES' ) {
			
				if ( $wpjf3_mr_options['header_type'] == "200" or $wpjf3_mr_options['method'] == "redirect" ) {
					$result['status'] = 'recommended';
					$result['label'] = __( 'Maintenance Redirect is enabled' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'Maintenance Redirect is enabled and your site is not visible to visitors.' )
					);
				} else {
					$result['status'] = 'critical';
					$result['badge']['color'] = 'red';
					$result['label'] = __( 'Maintenance Redirect is enabled' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						__( 'Maintenance Redirect is enabled and your site is not visible to visitors. Your redirection type is set to 503, which could harm your Google ranking if left on for any length of time.' )
					);
				}
				
			}
			return $result;
		}

		// (php) create the admin page
		function print_admin_page() {
			global $wpdb;
			global $ajax_nonce;
			
			$wpjf3_mr_options = $this->get_admin_options();
			
			// process update
			if ( isset( $_POST['update_wp_maintenance_redirect_settings']) ) {
			
				check_admin_referer( 'wpjf3_nonce' );  
				
				// prepare options
				$wpjf3_mr_options['enable_redirect']  = sanitize_text_field( trim( $_POST['wpjf3_mr_enable_redirect'] ) );
				$wpjf3_mr_options['header_type']      = sanitize_text_field( trim( $_POST['wpjf3_mr_header_type'] ) );
				$wpjf3_mr_options['method']           = sanitize_text_field( trim( $_POST['wpjf3_mr_method'] ) );
				$wpjf3_mr_options['static_page']      = esc_url_raw( trim( $_POST['wpjf3_mr_static_page'] ) );
				$wpjf3_mr_options['maintenance_html'] = trim( $_POST['wpjf3_mr_maintenance_html'] ); // Not proposing to sanitise the HTML as this plugin is only usable by Admins anyway, so if you can't trust your admin who can you trust?
				
				// update options
				update_option( $this->admin_options_name, $wpjf3_mr_options );
				
				echo '<div class="updated"><p><strong>' . __( "Settings Updated" ) . '</strong></p></div>';
				
			} ?>
			
			<script type="text/javascript" charset="utf-8">
				// bind actions
				jQuery(document).ready(function() {
					// enable disable toggle
					jQuery( '#wpjf3_mr_enable_redirect' ).change( function(){ wpjf3_mr_toggle_main_options(); });
					// method mode toggle
					jQuery( '#wpjf3_mr_method' ).change( function(){ wpjf3_mr_toggle_method_options(); });
				});
				
				// (js) update form layout based on main option
				function wpjf3_mr_toggle_main_options () {
					if( jQuery('#wpjf3_mr_enable_redirect').val() == 'YES' ){
						jQuery('#wpjf3_main_options').slideDown('fast');
					}else{
						jQuery('#wpjf3_main_options').slideUp('fast');
					}
				}
				
				// (js) update form layout based on method option
				function wpjf3_mr_toggle_method_options () {
					if( jQuery('#wpjf3_mr_method').val() == 'redirect' ){
						jQuery('#wpjf3_method_message' ).hide();
						jQuery('#wpjf3_method_redirect').show();
					}else{
						jQuery('#wpjf3_method_redirect').hide();
						jQuery('#wpjf3_method_message' ).show();
					}
				}
				
				// (js) undim field
				function wpjf3_mr_undim_field( field_id, default_text ) {
					if( jQuery('#'+field_id).val() == default_text ) jQuery('#'+field_id).val('');
					jQuery('#'+field_id).css('color','#000');
				}
				// (js) dim field
				function wpjf3_mr_dim_field( field_id, default_text ) {
					if( jQuery('#'+field_id).val() == '' ) {
						jQuery('#'+field_id).val(default_text);
						jQuery('#'+field_id).css('color','#888');
					}
				}
				
				// (js) validate IP4 address
				function ValidateIPaddress(ipaddress) {  
					if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)|\*))$/.test(ipaddress)) {  
						return (true)  
					}  
				}
				
				// (js) add new IP
				function wpjf3_mr_add_new_ip () {
					// validate entries before posting ajax call
					var error_msg = '';
					if( jQuery('#wpjf3_mr_new_ip_name').val() == ''                              ) error_msg += '<?php _e( "You must enter a Name" ); ?>.\n';
					if( jQuery('#wpjf3_mr_new_ip_name').val() == '<?php _e( "Enter Name:" ); ?>' ) error_msg += '<?php _e( "You must enter a Name" ); ?>.\n';
					if( jQuery('#wpjf3_mr_new_ip_ip'  ).val() == ''                              ) error_msg += '<?php _e( "You must enter an IP" ); ?>.\n';
					if( jQuery('#wpjf3_mr_new_ip_ip'  ).val() == '<?php _e( "Enter IP:" ); ?>'   ) error_msg += '<?php _e( "You must enter an IP" ); ?>.\n';
					if( ValidateIPaddress( jQuery('#wpjf3_mr_new_ip_ip'  ).val() ) != true   ) error_msg += '<?php _e( "IP address not valid" ); ?>.\n';
					if( error_msg != '' ){
						alert( '<?php _e( "There is a problem with the information you have entered" ); ?>.\n\n' + error_msg );
					}else{
						// prepare ajax data
						var data = {
							action:		'wpjf3_mr_add_ip',
							security:		'<?php echo $ajax_nonce; ?>',
							wpjf3_mr_ip_name:	jQuery('#wpjf3_mr_new_ip_name').val(),
							wpjf3_mr_ip_ip:	jQuery('#wpjf3_mr_new_ip_ip').val() 
						};
						
						// set section to loading img
						var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
						jQuery( '#wpjf3_mr_ip_tbl_container' ).html('<img src="' + img_url + '">');
						
						// send ajax request
						jQuery.post( ajaxurl, data, function(response) {
							jQuery('#wpjf3_mr_ip_tbl_container').html( response );
						});
					}
				}
				
				// (js) toggle IP status
				function wpjf3_mr_toggle_ip ( status, ip_id ) {
					// prepare ajax data
					var data = {
						action:             	'wpjf3_mr_toggle_ip',
						security:			'<?php echo $ajax_nonce; ?>',
						wpjf3_mr_ip_active: 	status,
						wpjf3_mr_ip_id:     	ip_id 
					};
					
					// (js) set status to loading img
					var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
					jQuery( '#wpjf3_mr_ip_status_' + ip_id ).html('<img src="' + img_url + '">');
					
					// send ajax request
					jQuery.post( ajaxurl, data, function(response) {
						var split_response = response.split('|');
						if( split_response[0] == 'SUCCESS' ){
							var ip_id     = split_response[1];
							var ip_active = split_response[1];
							// update divs / 1 = id / 2 = status
							if( split_response[2] == '1' ){
								// active
								jQuery('#wpjf3_mr_ip_status_' + split_response[1] ).html( 'Yes' );
								jQuery('#wpjf3_mr_ip_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wpjf3_mr_toggle_ip( 0, ' + split_response[1] + ' );"><?php _e( "Disable" ); ?></a> | ' );
							}else{
								// disabled
								jQuery('#wpjf3_mr_ip_status_' + split_response[1] ).html( 'No' );
								jQuery('#wpjf3_mr_ip_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wpjf3_mr_toggle_ip( 1, ' + split_response[1] + ' );"><?php _e( "Enable" ); ?></a> | ' );
							} 
						}else{
							alert( '<?php _e( "There was a database error. Please reload this page" ); ?>' );
						}
					});
				}
				
				// (js) delete IP
				function wpjf3_mr_delete_ip ( ip_id, ip_addr ) {
					if ( confirm('<?php _e( "You are about to delete the IP address:"); ?>\n\n\'' + ip_addr + '\'\n\n') ) {
						// prepare ajax data
						var data = {
							action:		'wpjf3_mr_delete_ip',
							security:		'<?php echo $ajax_nonce; ?>',
							wpjf3_mr_ip_id:   ip_id
						};
						
						// set section to loading img
						var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
						jQuery( '#wpjf3_mr_ip_tbl_container' ).html('<img src="' + img_url + '">');
						
						// send ajax request
						jQuery.post( ajaxurl, data, function(response) {
							jQuery('#wpjf3_mr_ip_tbl_container').html( response );
						});
					}
				}
				
				// (js) add new Access Key
				function wpjf3_mr_add_new_ak () {
					// validate entries before posting ajax call
					var error_msg = '';
					if( jQuery('#wpjf3_mr_new_ak_name' ).val() == ''                               ) error_msg += '<?php _e( "You must enter a Name"); ?>.\n';
					if( jQuery('#wpjf3_mr_new_ak_name' ).val() == '<?php _e( "Enter Name:" ); ?>'  ) error_msg += '<?php _e( "You must enter a Name"); ?>.\n';
					if( jQuery('#wpjf3_mr_new_ak_email').val() == ''                               ) error_msg += '<?php _e( "You must enter an Email"); ?>.\n';
					if( jQuery('#wpjf3_mr_new_ak_email').val() == '<?php _e( "Enter Email:" ); ?>' ) error_msg += '<?php _e( "You must enter an Email"); ?>.\n';
					if( error_msg != '' ){
						alert( '<?php _e( "There is a problem with the information you have entered"); ?>.\n\n' + error_msg );
					}else{
						// prepare ajax data
						var data = {
							action:		'wpjf3_mr_add_ak',
							security:		'<?php echo $ajax_nonce; ?>',
							wpjf3_mr_ak_name:  jQuery('#wpjf3_mr_new_ak_name').val(),
							wpjf3_mr_ak_email: jQuery('#wpjf3_mr_new_ak_email').val() 
						};

						// set section to loading img
						var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
						jQuery( '#wpjf3_mr_ak_tbl_container' ).html('<img src="' + img_url + '">');

						// send ajax request
						jQuery.post( ajaxurl, data, function(response) {
							jQuery('#wpjf3_mr_ak_tbl_container').html( response );
						});
					}
				}

				// (js) toggle Access Key status
				function wpjf3_mr_toggle_ak ( status, ak_id ) {
					// prepare ajax data
					var data = {
						action:			'wpjf3_mr_toggle_ak',
						security:			'<?php echo $ajax_nonce; ?>',
						wpjf3_mr_ak_active: 	status,
						wpjf3_mr_ak_id:     	ak_id 
					};

					// set status to loading img
					var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
					jQuery( '#wpjf3_mr_ak_status_' + ak_id ).html('<img src="' + img_url + '">');

					// send ajax request
					jQuery.post( ajaxurl, data, function(response) {
						var split_response = response.split('|');
						if( split_response[0] == 'SUCCESS' ){
							var ak_id     = split_response[1];
							var ak_active = split_response[1];
							// update divs / 1 = id / 2 = status
							if( split_response[2] == '1' ){
								// active
								jQuery('#wpjf3_mr_ak_status_' + split_response[1] ).html( 'Yes' );
								jQuery('#wpjf3_mr_ak_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wpjf3_mr_toggle_ak( 0, ' + split_response[1] + ' );"><?php _e( "Disable" ); ?></a> | ' );
							}else{
								// disabled
								jQuery('#wpjf3_mr_ak_status_' + split_response[1] ).html( 'No' );
								jQuery('#wpjf3_mr_ak_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wpjf3_mr_toggle_ak( 1, ' + split_response[1] + ' );"><?php _e( "Enable" ); ?></a> | ' );
							} 
						}else{
							alert( '<?php _e( "There was a database error. Please reload this page" ); ?>' );
						}
					});
				}

				// (js) delete Access Key
				function wpjf3_mr_delete_ak ( ak_id, ak_name ) {
					if ( confirm('<?php _e( "You are about to delete this Access Key:"); ?>\n\n\'' + ak_name + '\'\n\n') ) {
						// prepare ajax data
						var data = {
							action:		'wpjf3_mr_delete_ak',
							security:		'<?php echo $ajax_nonce; ?>',
							wpjf3_mr_ak_id:	ak_id
						};

						// set section to loading img
						var img_url = '<?php echo plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ); ?>';
						jQuery( '#wpjf3_mr_ak_tbl_container' ).html('<img src="' + img_url + '">');

						// send ajax request
						jQuery.post( ajaxurl, data, function(response) {
							jQuery('#wpjf3_mr_ak_tbl_container').html( response );
						});
					}
				}
				
				// (js) re-send Access Key
				function wpjf3_mr_resend_ak ( ak_id, ak_name, ak_email ) {
					if ( confirm('<?php _e( "You are about to email an Access Key link to "); ?>' + ak_email + '\n\n') ) {
						// prepare ajax data
						var data = {
							action:		'wpjf3_mr_resend_ak',
							security:		'<?php echo $ajax_nonce; ?>',
							wpjf3_mr_ak_id:	ak_id
						};
						
						// send ajax request
						jQuery.post( ajaxurl, data, function(response) {
							if( response == 'SEND_SUCCESS' ){
								alert( '<?php _e( "Notification Sent." ); ?>' );
							}else{
								alert( '<?php _e( "Notification Failure. Please check your server settings." ); ?>' );
							}
						});
					}
				}
			
			</script>
			
			<style type="text/css" media="screen">
				.wpjf3_mr_admin_section    { border: 1px solid #ddd; padding: 10px; padding-top: 0px; margin: 10px 0; }
				.wpjf3_mr_disabled_field   { color: #888;	}
				.wpjf3_mr_small_dim        { font-size: 11px; font-weight: normal; color: #444; }
				.wpjf3_mr_admin_section dt { font-weight: bold; }
				.wpjf3_mr_admin_section dd { margin-left: 0; }
			</style>
			
			<div class=wrap>
				<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" onsubmit="return wpjf3_mr_validate_form();">
					<h2>Maintenance Redirect</h2>
					
					<p><?php _e( "This plugin is intended primarily for designers / developers that need to allow clients to preview sites before being available to the general public. Any logged in user with WordPress administrator privileges will be allowed to view the site regardless of the settings below." ); ?></p>
					
					<h3><?php _e( "Enable Maintenance Mode:" ); ?></h3>
					<select name="wpjf3_mr_enable_redirect" id="wpjf3_mr_enable_redirect" style="width:30%" >
						<option value="NO"  ><?php _e( "No - Do not use maintenance mode" ); ?></option>
						<option value="YES" <?php if( $wpjf3_mr_options['enable_redirect'] == "YES" ) echo "selected"; ?>><?php _e( "Yes - Use maintenance mode" ); ?></option>
					</select>
					
					<div id="wpjf3_main_options" <?php if( $wpjf3_mr_options['enable_redirect'] == "NO" ) echo 'style="display:none;"'; ?> >
						
						<div class="wpjf3_mr_admin_section" >
							<h3><?php _e( "Header Type:" ); ?></h3>
							<p><?php _e( "When redirect is enabled we can send 2 different header types:" ); ?> </p>
							<dl>
								<dt>200 OK</dt>
								<dd><?php _e( "Best used for when the site is under development." ); ?></dd>
								<dt>503 Service Temporarily Unavailable</dt> 
								<dd><?php _e( "Best for when the site is temporarily taken offline for small amendments." ); ?> <em><?php _e( "If used for a long period of time, 503 can damage your Google ranking." ); ?></em></dd>
							</dl>
							<p><?php _e( /* translators: DO NOT TRANSLATE "Redirect" or <strong>307 Temporary Redirect</strong> - leave exactly as is */ 'Note: When "Redirect" is selected below, this setting will be ignored and <strong>307 Temporary Redirect</strong> used instead' ); ?></p>
							<select name="wpjf3_mr_header_type" id="wpjf3_mr_header_type" style="width:30%" >
								<option value="200" <?php if( $wpjf3_mr_options['header_type'] == "200" ) echo "selected"; ?>>200 OK </option>
								<option value="503" <?php if( $wpjf3_mr_options['header_type'] == "503" ) echo "selected"; ?>>503 Service Temporarily Unavailable</option>
							</select>
						</div>
						<div class="wpjf3_mr_admin_section" >
							<h3><?php _e( "Unrestricted IP addresses:" ); ?>&nbsp;<span class="wpjf3_mr_small_dim">( <?php _e( "Your IP address is:" ); ?>&nbsp;<?php echo $this->get_user_ip(); ?> - <?php _e( "Your Class C is:" ); ?>&nbsp;<?php echo $this->get_user_class_c(); ?> )</span></h3>
							<p><?php _e( "Users with unrestricted IP addresses will bypass maintenance mode entirely. Using this option is useful to an entire office of clients to view the site without needing to jump through any extra hoops." ); ?></p> 
							
							<div id="wpjf3_mr_ip_tbl_container">
								<?php $this->print_unrestricted_ips(); ?>
							</div>
						</div>
						
						<div class="wpjf3_mr_admin_section">
							<h3><?php _e( "Access Keys:" ); ?></h3>
							<p><?php _e( "You can allow users temporary access by sending them the access key. When a new key is created, a link to create the access key cookie will be emailed to the email address provided. Access can then be revoked either by disabling or deleting the key." ); ?></p>
							
							<div id="wpjf3_mr_ak_tbl_container">
								<?php $this->print_access_keys(); ?>
							</div>
						</div>
						
						<div class="wpjf3_mr_admin_section">	
							<h3><?php _e( "Maintenance Message:" ); ?></h3>
							<p><?php _e( "You have three options for how to specify what you want to show users when your site is in maintenance mode. You can display a message, display a static HTML page (which you enter into the box below), or redirect to an existing static HTML page (the file of which must exist on your server)." ); ?></p>
							<p><select name="wpjf3_mr_method" id="wpjf3_mr_method" style="width:30%" >
								<option value="message"><?php _e( "Message Only - The easy way" ); ?></option>
								<option value="redirect" <?php if( $wpjf3_mr_options['method'] == "redirect" ) echo "selected"; ?> ><?php _e( "Redirect - A little harder" ); ?></option>
								<option value="html" <?php if( $wpjf3_mr_options['method'] == "html" ) echo "selected"; ?> ><?php _e( "HTML Entered Here - A little harder" ); ?></option>
							</select></p>

							<div id="wpjf3_method_message" style="<?php if( $wpjf3_mr_options['method'] == "redirect" ) echo "display:none;"; ?>" >
								<strong><?php _e( "Maintenance Mode Message:" ); ?></strong>
								<p><?php _e( "This is the message that will be displayed while your site is in maintenance mode." ); ?></p>
								<p style="margin-bottom: 0;"><textarea name="wpjf3_mr_maintenance_html" rows="10" style="width:100%"><?php echo stripslashes( $wpjf3_mr_options['maintenance_html'] ); ?></textarea></p>
							</div>
						
							<div id="wpjf3_method_redirect" style="<?php if( $wpjf3_mr_options['method'] == "message" || $wpjf3_mr_options['method'] == "html" ) echo "display:none;"; ?>" >
								<strong><?php _e( "Static Maintenance Page:" ); ?></strong>
								<p><?php _e( "To use this method you need to upload a static HTML page to your site and enter it's URL below." ); ?></p>
								<p><input type="text" name="wpjf3_mr_static_page" value="<?php echo $wpjf3_mr_options['static_page']; ?>" id="wpjf3_mr_static_page" style="width:100%"></p>
							</div>
						</div>
						
					</div>
					
					<div class="submit">
						<?php wp_nonce_field( 'wpjf3_nonce' ); ?>
						<input type="submit" name="update_wp_maintenance_redirect_settings" value="<?php _e( 'Update Settings' ); ?>" />
					</div>
				</form>
			</div>
				
			<?php
		} // end function print_admin_page()
	} // end class wpjf3_maintenance_redirect
}

if (class_exists("wpjf3_maintenance_redirect")) {
	$my_wpjf3_maintenance_redirect = new wpjf3_maintenance_redirect();
}

// initialize the admin and users panel
if (!function_exists("wpjf3_maintenance_redirect_ap")) {
	function wpjf3_maintenance_redirect_ap() {
		if( current_user_can('manage_options') ) {
			global $my_wpjf3_maintenance_redirect;
			global $ajax_nonce; 
				 $ajax_nonce = wp_create_nonce( "wpjf3_nonce" ); 
			
			if( !isset($my_wpjf3_maintenance_redirect) ) return;
		
			if (function_exists('add_options_page')) {
				add_options_page( __( "Maintenance Redirect Options" ), __( "Maintenance Redirect" ), 'manage_options', 'JF3_Maint_Redirect', array( $my_wpjf3_maintenance_redirect, 'print_admin_page' ));
			}
		}
	}
}

// actions and filters	
if( isset( $my_wpjf3_maintenance_redirect ) ) {
	// actions & filters
	add_action( 'admin_menu',    'wpjf3_maintenance_redirect_ap' );
	add_action( 'send_headers',  array( $my_wpjf3_maintenance_redirect, 'process_redirect'), 1 );
	add_action( 'admin_notices', array( $my_wpjf3_maintenance_redirect, 'display_status_if_active' ) );
	
	add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array( $my_wpjf3_maintenance_redirect, 'plugin_settings_link' ) );
	add_filter( 'site_status_tests', array( $my_wpjf3_maintenance_redirect, 'wpjf3_add_site_health' ) );

	// ajax actions
	add_action('wp_ajax_wpjf3_mr_add_ip',    array( $my_wpjf3_maintenance_redirect, 'add_new_ip'       ) );
	add_action('wp_ajax_wpjf3_mr_toggle_ip', array( $my_wpjf3_maintenance_redirect, 'toggle_ip_status' ) );
	add_action('wp_ajax_wpjf3_mr_delete_ip', array( $my_wpjf3_maintenance_redirect, 'delete_ip'        ) );
	add_action('wp_ajax_wpjf3_mr_add_ak',    array( $my_wpjf3_maintenance_redirect, 'add_new_ak'       ) );
	add_action('wp_ajax_wpjf3_mr_toggle_ak', array( $my_wpjf3_maintenance_redirect, 'toggle_ak_status' ) );
	add_action('wp_ajax_wpjf3_mr_delete_ak', array( $my_wpjf3_maintenance_redirect, 'delete_ak'        ) );
	add_action('wp_ajax_wpjf3_mr_resend_ak', array( $my_wpjf3_maintenance_redirect, 'resend_ak'        ) );
	
	// activation ( deactivation is later enhancement... )
	register_activation_hook( __FILE__, array( $my_wpjf3_maintenance_redirect, 'init' ) );
}
