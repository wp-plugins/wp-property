<?php
/*
Name: Inquiry Management
Class: class_inquiry
Version: 2.1
Description: A big map for property overview.
*/


add_action('wpp_init', array('wpp_inquiry', 'init'));


/**
 * WP-Directory Inquiry Class
 *
 *
 * Handles additional inquiries, displays back-end UI, etc.
 *
 *
 *
 * @version 1.0
 * @package WP-Directory
 * @subpackage Inquiry
 */
class wpp_inquiry {

	function init() {
		global $inquiry_screen_id, $wpp_inquiry, $wp_roles;
		$inquiry_screen_id = 'property_page_inquiries';

		wpp_inquiry::load_default_structure();
		wpp_inquiry::check_tables();

		// When under property_type, screen->id: property_page_inquiries
		add_filter("manage_property_page_inquiries_columns", array('wpp_inquiry', "edit_columns"));
 		add_filter("get_comment", array('wpp_inquiry', 'get_inquiry'), 0,2);

		// Ajax calls
		add_action('wp_ajax_wpp_inquiry_delete_single', array('wpp_inquiry', 'delete_single'));
		add_action('wp_ajax_wpp_property_inquiry', array('wpp_inquiry', 'property_inquiry'));
		add_action('wp_ajax_nopriv_wpp_property_inquiry', array('wpp_inquiry', 'property_inquiry'));

		// Header function for single property pages
		add_action('wp_head_single_property', array('wpp_inquiry', 'wp_head'));

		// Header function for overview pages
		//add_action('wp_head_property_overview', array('wpp_inquiry', 'wp_head'));

		add_action('admin_head', array("wpp_inquiry", "overview_page_scripts"));
		
		
		// Add inquiry overview page under Properties nav menu
		add_action("admin_menu", array("wpp_inquiry", "admin_menu"));

		// Add Inquiry page to Property Settings page array
		add_filter('wpp_settings_nav', array('wpp_inquiry', 'settings_nav'));

		// Add Settings Page
		add_action('wpp_settings_content_inquiry', array('wpp_inquiry', 'settings_page'));
		
				
		// Create role if doesn't exist
		if(($wp_roles) && !$wp_roles->get_role( 'wpp_prospect' )) {
			$wp_roles->add_role( 'wpp_prospect', "Prospect", array());
 		}

	}

	/**
	 * Insert into header on inquiry overview page
	 *
	 * This function is not called on amdin side
	 *
	 * @Version 1.4
	 */
	function overview_page_scripts() {
		global $current_screen, $wp_properties;
 		if($current_screen->id == 'property_page_inquiries') { ?>
		 <style type="text/css">
			.check-column {display:none; }
			.column-inquiry_phonenumber {width: 100px; }
			.column-inquiry_main {width: 200px; }
			.column-inquiry_date {width: 100px; }
			.column-inquiry_property {width: 250px; }
		 
		 </style>
		 
		 
		 <?php }
	}

	/**
	 * Inserted into head on single property pages
	 *
	 * This function is not called on amdin side
	 *
	 * @Version 1.4
	 */
	function wp_head() {
		global $wp_properties, $post;


if($wp_properties[configuration][feature_settings][inquiry][disable_css] != 'true') { ?>
 <style type='text/css'>
	.wpp_contact_form ol {list-style-type: none; margin: 0;}
	.wpp_contact_form ol li label {display: inline-block;width: 155px; font-size: 1.1em; vertical-align:top;}
	
	.wpp_inquiry_thank_you_message { 
		background:none repeat scroll 0 0 #F5F5F5;
		border:1px solid #DDDDDD;
		display:none;
		font-size:1.3em;
		line-height:1.4em;
		margin-bottom:25px;
		padding:7px;
 	}
	.wpp_inquiry_error_response { 
		background:none repeat scroll 0 0 #FFCBCB;
		border:1px solid #A4A4A4;
		display:none;
		margin-bottom:25px;
		padding:7px;
		width:408px;
	}	
	.wpp_contact_form #wppc_sendbutton {
		margin-left: 155px;
		-moz-border-radius:11px 11px 11px 11px;
		-moz-box-sizing:content-box;
		border-style:solid;
		border-width:1px;
		cursor:pointer;
		font-size:1.2em;
		line-height:13px;
		padding:3px 8px;
		text-decoration:none;
		background-color:#FFFFFF;
		border-color:#BBBBBB;
		color:#464646;
	}
	.wpp_contact_form input[type=text], .wpp_contact_form textarea {width: 250px; font-size: 1.4em; padding: 5px;margin-bottom: 20px;}
	.wpp_contact_form .wpp_inquiry_required_field {border-color: #AA9797  !important; }
</style>
<?php }
 
		$contact_fields = $wp_properties[configuration][feature_settings][inquiry][contact_fields]; ?>
<script type='text/javascript'>
	jQuery(document).ready(function() {

	var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

	jQuery("#wppc_sendbutton").click(function() {

	jQuery.post(ajaxurl, {
		action:"wpp_property_inquiry",
		post_id: '<?php echo $post->ID; ?>',
		_ajax_nonce: '<?php echo wp_create_nonce('wpp_inquiry_' . $post->ID); ?>',
		<?php foreach($contact_fields as $slug => $label): ?>
		<?php echo $slug;?>: jQuery("#wpp_inquiry_<?php echo $slug; ?>").val(),
		<?php endforeach; ?>
		security: jQuery("#wpp_inquiry_security").val()

	}, function(response){
	if(response.error == 'true') {
	jQuery(".wpp_inquiry_error_response").html(response.message);
	jQuery(".wpp_inquiry_error_response").show();
	}

	if(response.success == 'true') {
	jQuery(".wpp_contact_form").hide();
	jQuery(".wpp_inquiry_thank_you_message").html(response.message);
	jQuery(".wpp_inquiry_thank_you_message").show();
	}


	}, "json");

	return;

	});

	});
</script>

<?php






	}


	/**
	 * Adds inquiry manu to settings page navigation
	 *
	 *
	 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
	 */		
	function settings_nav($tabs = '') {
		$tabs['inquiry'] = array(
			'slug' => 'inquiry',
			'title' => 'Inquiry'
		);
		
		return $tabs;

	}

	function settings_page() {
		global $wp_properties, $wpdb, $wpp_inquiry;
		$current_user = wp_get_current_user();
		$current_user_id = $current_user->ID;

		// Get defaults
		$wp_properties[configuration][feature_settings][inquiry] = UD_F::array_merge_recursive_distinct($wp_properties[configuration][feature_settings][inquiry], $wpp_inquiry);

	?>
	<table class="form-table">
		<tr>
			<th>General Settings</th>
			<td>
			<p>
			<?php echo UD_UI::checkbox("name=wpp_settings[configuration][feature_settings][inquiry][automatic_account_creation]&label=Automatically create WordPress user accounts for inquirers.", $wp_properties[configuration][feature_settings][inquiry][automatic_account_creation]); ?><br />
			<span class="description">User accounts will be created with the role of "Prospect".</span>
			</p>
			<p>
			<?php echo UD_UI::checkbox("name=wpp_settings[configuration][feature_settings][inquiry][disable_css]&label=Disable default CSS.", $wp_properties[configuration][feature_settings][inquiry][disable_css]); ?><br />
			<span class="description">If you want to use your own stylesheet to customize the form, disable this.</span>
			</p>
			</td>
		</tr>

		<tr>
			<th>Inquiry Form</th>
			<td>


			<p>The following fields will appear on the front-end of your website.
			</p>
			<table id="wpp_inquiry_fields" class="ud_ui_dynamic_table widefat">
				<thead>
					<tr>
						<th>Title</th>
						<th>Type</th>
						<th>Required</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
			<?php
				$wpp_inquiry_fields = $wp_properties[configuration][feature_settings][inquiry][contact_fields];

				foreach($wpp_inquiry_fields as $slug => $field):


				// Hide rows without values
				if(empty($field[title]) || empty($field[type]))
					$hidden = 'hidden';
				else
					$hidden = '';

 			?>
				<tr class="wpp_dynamic_table_row <?php echo $hidden; ?>" slug="<?php echo $slug; ?>">
					<td >
						<input class="slug_setter" type="text" name="wpp_settings[configuration][feature_settings][inquiry][contact_fields][<?php echo $slug; ?>][title]" value="<?php echo $field[title]; ?>" />
					</td>
					<td>
						<input type="text" name="wpp_settings[configuration][feature_settings][inquiry][contact_fields][<?php echo $slug; ?>][type]" value="<?php echo $field[type]; ?>" />
					</td>
					<td>
						<?php echo UD_UI::checkbox("name=wpp_settings[configuration][feature_settings][inquiry][contact_fields][$slug][required]", $wp_properties[configuration][feature_settings][inquiry][contact_fields][$slug][required]); ?>
					</td>

					<td><span class="wpp_delete_row wpp_link">Delete</span></td>
				</tr>


			<?php endforeach; // foreach($wpp_inquiry_fields as $field): ?>

				<tfoot>
					<tr>
						<td colspan='4'>
						<input type="button" class="wpp_add_row button-secondary" value="Add Row" />
						</td>
					</tr>
				</tfoot>
				</tbody>
			</table>

			<p>
			Message to Display After Inquiry Submission:<br />
			<textarea name="wpp_settings[configuration][feature_settings][inquiry][form_submission_success]" class="large-text code"><?php echo $wp_properties[configuration][feature_settings][inquiry][form_submission_success]; ?></textarea>
			<span class="description">Message that displays after form submission. You may use field names in this message. For example, if you have a field called <b>"Your Name"</b>, type <b>"Thank you, %Your Name%, we will respond shortly."</b>. You can also use <b>"%Property Name%</b> to print the name of the property inquired about.</span>
			</p>
			<p>
				Security question: <?php echo UD_UI::input("name=wpp_settings[configuration][feature_settings][inquiry][security_question]", $wp_properties[configuration][feature_settings][inquiry][security_question]); ?>, and answer <?php echo UD_UI::input("name=wpp_settings[configuration][feature_settings][inquiry][security_var]", $wp_properties[configuration][feature_settings][inquiry][security_var]); ?><br />
				<span class="description">Inquirers will have to manually enter the answer, this helps prevent spam since robots lack cognitive abilities.</span><br />

			</p>

			<p>
				Submit Button text: <?php echo UD_UI::input("name=wpp_settings[configuration][feature_settings][inquiry][form_submit_button]", $wp_properties[configuration][feature_settings][inquiry][form_submit_button]); ?>
 
			</p>
			
			</td>
		</tr>

		<tr>
			<th>Notification Settings</th>
			<td>

			<?php $admin_users = $wpdb->get_col("SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key='{$wpdb->prefix}capabilities' AND meta_value like '%administrator%'"); ?>
 			Send Email Notifications to:<br />
			<?php foreach($admin_users as $user_id):?>
			<?php $this_user = get_userdata($user_id);

			// Get users that can recieve texts
			if($this_user->can_receive_sms && !empty($this_user->sms_number))
				$sms_recipients[$user_id] = $this_user->sms_number;

			?>
			<?php echo UD_UI::checkbox("name=wpp_settings[configuration][feature_settings][inquiry][email_notify][$user_id]&label={$this_user->user_email}.", $wp_properties[configuration][feature_settings][inquiry][email_notify][$user_id]); ?><br />
			<?php endforeach; ?>
			<span class="description">To add a recipient to the list, create an administrator account for them.</span>
			</p>

			<p>
			<?php if(is_array($sms_recipients)): ?>
 			Send Text Message Notifications to:<br />
			<?php foreach($admin_users as $user_id => $sms_number):?>
			<?php echo UD_UI::checkbox("name=wpp_settings[configuration][feature_settings][inquiry][sms_notify][$user_id]&label={$sms_number}.", $wp_properties[configuration][feature_settings][inquiry][sms_notify][$user_id]); ?><br />
			<?php endforeach; ?>
			<span class="description">This feature requires you install the <a href="http://twincitiestech.com/plugins/wp-text-message/">WP-Text-Message plugin</a>.</span>
			<?php endif; ?>
			</p>
		</td>
		</tr>
		<tr>
		<th>Notification Message</th>
		<td>
			<p>
			Sent From Name <?php echo UD_UI::input("style=width: 200px;&name=wpp_settings[configuration][feature_settings][inquiry][notification_settings][from_name]",
				$wp_properties[configuration][feature_settings][inquiry][notification_settings][from_name]); ?>,
			Sent From Email: <?php echo UD_UI::input("style=width: 250px;&name=wpp_settings[configuration][feature_settings][inquiry][notification_settings][from_email]",
				$wp_properties[configuration][feature_settings][inquiry][notification_settings][from_email]); ?>
			</p>
			<p>
			Subject: <?php echo UD_UI::input("style=width: 250px;&name=wpp_settings[configuration][feature_settings][inquiry][notification_settings][subject]",
				$wp_properties[configuration][feature_settings][inquiry][notification_settings][subject]); ?>
			</p>

			<p>
				Message Template: <br />
				<textarea name="wpp_settings[configuration][feature_settings][inquiry][notification_settings][message_template]" class="large-text code"><?php
				echo $wp_properties[configuration][feature_settings][inquiry][notification_settings][message_template]; ?></textarea>
				<span class="description">You may use whatever fields you use in your form in the email.  For example, if you have a field called <b>"Your Name"</b>, type <b>%Your Name%</b> in the message template, and the customer's actual name will be inserted. You can also use <b>"%Property Name%</b> to print the name of the property inquired about.</span>
			</p>
 			</td>
		</tr>
	</table>
	<?php

	}


	// Ajax function
	function delete_single() {
		global $wpdb;

		$inquiry_id = $_REQUEST['inquiry_id'];

		if(empty($inquiry_id))
			die();

		// Delete main row
		$wpdb->query("DELETE FROM {$wpdb->prefix}wpp_inquiries WHERE ID = '$inquiry_id'");

		// Delete meta
		$wpdb->query("DELETE FROM {$wpdb->prefix}wpp_inquiries_meta WHERE inquiry_id = '$inquiry_id'");

		echo json_encode(array("success" => "true"));
		die();


	}

	function load_default_structure() {
		global $wpp_inquiry, $wp_properties;

		// Do not load default contact fields if they already exist in $wp_properties
		if(!is_array($wp_properties[configuration][feature_settings][inquiry][contact_fields])) {

			$wpp_inquiry['contact_fields'] = array(
				'name' => array(
					'title' => 'Name',
					'type' => 'input',
					'required' => 'true'),
				'phonenumber' => array(
					'title' => 'Phone Number',
					'type' => 'input',
					'required' => 'false'),
				'email_address' => array(
					'title' => 'Email Address',
					'type' => 'input',
					'required' => 'true'),
				'content' => array(
					'title' => 'Message',
					'type' => 'textarea',
					'required' => 'true')
				);

		}

		$wpp_inquiry[security_var] 						= 	12;
		$wpp_inquiry[security_question] 				= 	'5 + 7 =';
		$wpp_inquiry[form_submission_success] 		=  'Your message has been sent. Thank you!';
		$wpp_inquiry[form_submit_button] 				= 	'Submit';

		$wpp_inquiry[notification_settings][from_name] 				= 	get_bloginfo('blogname');
		$wpp_inquiry[notification_settings][from_email] 				= 	get_bloginfo('admin_email');
		$wpp_inquiry[notification_settings][message_template] 		= 	"Inquiry Received from %Name%.\nMessage: %Message%.";
		$wpp_inquiry[notification_settings][subject] 					= 	"Inquiry from %Name%";


		// Merge global settings with default settings
		$wpp_inquiry = UD_F::array_merge_recursive_distinct($wpp_inquiry, $wp_properties[configuration][feature_settings][inquiry]);
		$wpp_inquiry = apply_filters('wpp_inquiry_api', $wpp_inquiry);



	}

	function check_tables() {
		global $wpdb;

		if(!is_admin())
			return;

		$wpp_inquiries_table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wpp_inquiries'");

 		if(!$wpp_inquiries_table) {
			UD_F::log("WPP Inquiry meta table not found, installing.");

	 		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			UD_F::log("WPP Inquiry table not found, installing.");

			  $sql = "CREATE TABLE {$wpdb->prefix}wpp_inquiries (
			ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			property_ID int(11) NOT NULL DEFAULT '0',
			author_ip varchar(100) NOT NULL DEFAULT '',
			date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			date_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (ID));";

			dbDelta($sql);

		}

		$wpp_inquiries_meta_table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wpp_inquiries_meta'");

		if(!$wpp_inquiries_meta_table) {

	 		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			UD_F::log("WPP Inquiry table not found, installing.");

			 $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpp_inquiries_meta (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			inquiry_id bigint(20) unsigned NOT NULL DEFAULT '0',
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
			PRIMARY KEY (meta_id),
			KEY inquiry_id (inquiry_id),
			KEY meta_key (meta_key)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8";

			dbDelta($sql);

 		}

		return true;
	}

	function admin_menu(){
		add_submenu_page('edit.php?post_type=property', "Inquiries", "Inquiries", 10, 'inquiries',array('wpp_inquiry', 'inquiry_page'));

	}


	function inquiry_page() {

		$inquiries = wpp_inquiry::get_inquiries();


 ?>

 <script type="text/javascript">
	jQuery(document).ready(function() {

		jQuery(".wpp_delete_inquiry").click(function() {

			var parent = jQuery(this).parent();
			var inquiry_id = jQuery('.inquiry_id', parent).val();


			jQuery.post(ajaxurl, {
				action: 'wpp_inquiry_delete_single',
				inquiry_id: inquiry_id
			}, function(result) {

					if(result.success = 'true') {
						jQuery("#inquiry-" + inquiry_id).fadeOut().remove();
					}


			}, "json");


			return false;
		});


	});

 </script>


<div class="wrap">
<h2>Property Inquiries</h2>

<pre>
<!--<?php  print_r($inquiries); ?>-->
</pre>
<?php if(is_array($inquiries)): ?>
<table class="widefat comments fixed" cellspacing="0">
<thead>
	<tr>
<?php print_column_headers('property_page_inquiries'); ?>
	</tr>
</thead>

<tfoot>
	<tr>
<?php print_column_headers('property_page_inquiries', false); ?>
	</tr>
</tfoot>

<tbody id="the-comment-list" class="list:comment">
<?php
	foreach ($inquiries as $inquiry)
		wpp_inquiry::inquiry_row($inquiry);

?>
</tbody>

</table>
<?php else: ?>
No inquiries.
<?php endif; ?>

</div> <?php

	}

	function get_inquiries() {
		global $wpdb, $wpp_inquiry;

		$inquiries = $wpdb->get_col("SELECT ID FROM {$wpdb->prefix}wpp_inquiries");

		// Fix results
		if(count($inquiries) < 1)
			return false;



		foreach($inquiries as $id) {
 			$return[$id] = wpp_inquiry::get_inquiry($id);
		}

		return $return;

	}


 	function get_inquiry($id) {
	 	global $wpdb, $wpp_inquiry;

	 	if(!is_numeric($id))
	 		return false;


	 	$inquiry = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wpp_inquiries WHERE ID = '$id'");

		// Set real ID
		$id = $inquiry->ID;

		// Create object
		$return = new StdClass;

		// Write variables
		$return->ID = $id;
		$return->property_id = $inquiry->property_ID;
		$return->property_name = get_the_title($inquiry->property_ID);
		$return->date = $inquiry->date;

		// Get meta
		foreach($wpp_inquiry[contact_fields] as $slug => $data) {
			$meta_value = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}wpp_inquiries_meta WHERE meta_key = '$slug' AND inquiry_id = '{$id}'");

			if(!empty($meta_value))
				$return->$slug = $meta_value;
		}


		return $return;
	}

/*
	Ajax call for contact message for a property
*/
	function property_inquiry() {
		global $wpp_inquiry;


 		$post_id 		= 	$_REQUEST['post_id'];
		$security_var 	=	$_REQUEST['security'];
		$nonce			=	$_REQUEST['_ajax_nonce'];
		$inquiry_data	=	$_REQUEST;

		// Nonce validation
		if(!wp_verify_nonce($nonce , 'wpp_inquiry_' . $post_id)) {
			echo json_encode(array('error' => 'true', 'message' => 'Something went wrong... Please contact website administrator.'));
			die();
		}


		// Robot prevention
		if(trim($security_var) != $wpp_inquiry[security_var]) {
			echo json_encode(array('error' => 'true', 'message' => 'Validation failed, please be sure to enter the correct number above. The security question is: ' . $wpp_inquiry[security_question]));
			die();
		}

		// DO validation
		foreach($wpp_inquiry[contact_fields] as $slug => $data) {

 				if(empty($inquiry_data[$slug]) && $data[required] == 'true') {
					$fail = true;
					$failure_data[] = $data[title];

				}

		}

		// Check if vaildation failed
		if($fail && is_array($failure_data)) {
			$missing_fields = implode(", ", $failure_data);
			echo json_encode(array('error' => 'true', 'message' => "The validation failed, the following field(s) are required: $missing_fields."));
			die();
		}

		// Build $data variable
		$insert_inquiry_data[author_IP] 	= $_SERVER['REMOTE_ADDR'];
		$insert_inquiry_data[property_ID]	= $post_id;

		// Hookable data
		foreach($wpp_inquiry[contact_fields] as $slug => $data) {
			if(!empty($inquiry_data[$slug]))
				$insert_inquiry_data[$slug] = $inquiry_data[$slug];
		}

		// Insert inquiry
		$inquiry_id = wpp_inquiry::insert_inquiry($insert_inquiry_data);



		// Allows for custom response message
		$property_obj = WPP_F::get_property($insert_inquiry_data[property_ID]);
		$property_string = "$property_obj[post_title]";

		if($property_obj[parent_id])
		$property_string .= " at {$property_obj[parent_title]}.";

		// Load Default Values
		$replace_tags[] 		= "%Property Name%";
		$replace_values[] 	= $property_string;

		// Build array of variables to be replaced
		foreach($wpp_inquiry[contact_fields] as $slug => $slug_data) {
			$replace_tags[] 		= "%". $slug_data[title] . "%";
			$replace_values[] 	= stripslashes($insert_inquiry_data[$slug]);
		}

		$response = str_replace($replace_tags, $replace_values, $wpp_inquiry[form_submission_success]);



		if($inquiry_id)
			echo json_encode(array('success' => 'true', 'message' => nl2br($response)));

		if(!$inquiry_id)
			echo json_encode(array('error' => 'true', 'message' => 'There was a problem sending your message, contact website administrator.'));


		die();
	}


/*
	Modeled after wp_insert_comment
*/
	function insert_inquiry($inquiry_data) {
		global $wpdb, $wpp_inquiry, $wp_properties;
		extract(stripslashes_deep($inquiry_data), EXTR_SKIP);

		if ( ! isset($inquiry_data[author_IP]) )
			$author_IP = $_SERVER['REMOTE_ADDR'];
		if ( ! isset($inquiry_data[date]) )
			$date = current_time('mysql');
		if ( ! isset($inquiry_data[date_gmt]) )
			$date_gmt = get_gmt_from_date($date);


		$property_id = $inquiry_data[property_ID];

		if(empty($property_id))
			return false;

		// Insert primary data
 		$wpdb->insert($wpdb->prefix . 'wpp_inquiries', array('property_id' => $property_id, 'author_ip' => $author_IP, 'date' => $date, 'date_gmt' => $date_gmt));
		$inquiry_id = (int) $wpdb->insert_id;


		// Insert meta
		foreach($wpp_inquiry[contact_fields] as $slug => $slug_data) {
			wpp_inquiry::insert_meta($inquiry_id, $slug, $inquiry_data[$slug]);
		}

		// Create user account.
		if($wp_properties[configuration][feature_settings][inquiry][automatic_account_creation] == 'true') {
		
		
			
			// Find email address
			foreach($wpp_inquiry[contact_fields] as $slug => $slug_data) {
				UD_F::log("Inquiry: Checking if  ".trim($inquiry_data[$slug])." is an email address.");
				if(UD_F::check_email_address(trim($inquiry_data[$slug]))) {
 					$email_address = trim($inquiry_data[$slug]);
				}
			}
			
			if(!empty($email_address) && !email_exists($email_address)) {
			//UD_F::log("Inquiry: email address found: $email_address");
	
				
				// Insert user
				if($user_id = wp_insert_user(array('user_login' => $email_address, 'user_email' => $email_address, 'user_pass' => UD_F::createRandomPassword(), 'role' => 'wpp_prospect'))) {
					UD_F::log("Inquiry: New user account created, email address: $email_address, user_id: $user_id");

					foreach($wpp_inquiry[contact_fields] as $slug => $slug_data) {
						update_usermeta($user_id, "wpp_$slug" . trim($inquiry_data[$slug]));
					}
					
					
				}
				
			} else {
				//UD_F::log("Inquiry: email address not found or already exists: $email_address");
			}
		
		
		}		
		
 		//Sent notifications
		$notify = $wp_properties[configuration][feature_settings][inquiry][email_notify];

		if(is_array($notify)) {

			$property_obj = WPP_F::get_property($inquiry_data[property_ID]);
			$property_string = "$property_obj[post_title]";

			if($property_obj[parent_id])
				$property_string .= " at {$property_obj[parent_title]}.";

			// Load Default Values
			$replace_tags[] 		= "%Property Name%";
			$replace_values[] 	= $property_string;

			// Build array of variables to be replaced
			foreach($wpp_inquiry[contact_fields] as $slug => $slug_data) {
				$replace_tags[] 		= "%". $slug_data[title] . "%";
				$replace_values[] 	= stripslashes($inquiry_data[$slug]);
			}


			$subject = $wp_properties[configuration][feature_settings][inquiry][notification_settings][subject];
			$from_name = $wp_properties[configuration][feature_settings][inquiry][notification_settings][from_name];
			$from_email = $wp_properties[configuration][feature_settings][inquiry][notification_settings][from_email];
			$template = $wp_properties[configuration][feature_settings][inquiry][notification_settings][message_template];

			$subject = str_replace($replace_tags, $replace_values, $subject);
			$message = str_replace($replace_tags, $replace_values, $template);

			$headers = "From: $from_name <$from_email>" . "\r\n\\";

			foreach($notify as $user_id => $notify) {

				if($notify != 'true')
					continue;

				$user_data = get_userdata($user_id);

				wp_mail( $user_data->user_email, $subject, $message, $headers);


			}

		}

 		do_action('wpp_insert_inquiry', $id, $comment);
 		return $inquiry_id;
	}


	function insert_meta($inquiry_id, $meta_key, $meta_value) {
		global $wpdb;

		// Check if meta exists
		$exists = $wpdb->get_var("SELECT meta_id FROM {$wpdb->prefix}wpp_inquiries_meta WHERE meta_key = '$meta_key' AND inquiry_id = '$inquiry_id'");

		if($exists) {
			// Update
			$wpdb->update($wpdb->prefix . 'wpp_inquiries_meta', array( 'meta_value' => $meta_value), array( 'inquiry_id' => $inquiry_id, 'meta_key' => $meta_key ));
			$meta_id = $exists;
		}

		if(!$exists) {
			// Insert
		 		$wpdb->insert($wpdb->prefix . 'wpp_inquiries_meta', array('inquiry_id' => $inquiry_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value));
				$meta_id = (int) $wpdb->insert_id;
		}


		return $meta_id;
	}






	function edit_columns() {
		global $wpp_inquiry;


		$columns['cb'] =  "<input type=\"checkbox\" />";
		$columns['inquiry_main'] =  "Lead";



		foreach($wpp_inquiry[contact_fields] as $slug => $data) {
			$columns['inquiry_' . $slug] = $data[title];
		}

		$columns['inquiry_date'] =  "Date";
		$columns['inquiry_property'] =  "Property";

		return $columns;
	}


function inquiry_row( $inquiry_obj) {
	global $inquiry_screen_id;


	/*
		stdClass Object
		(
			[ID] => 2
			[property_id] => 194
			[property_name] => Two Bedroom
			[date] => 2010-08-06 23:00:48
			[name] => Andy
			[phonenumber] => 6513995473
			[email_address] => asdjk@dlkfj.com
		)

*/

	if(!is_object($inquiry_obj))
		return;


 	$property_obj = WPP_F::get_property($inquiry_obj->property_id);

 	echo "<tr id='inquiry-$inquiry_obj->ID' class='$the_comment_status'>";
	$columns = get_column_headers("{$inquiry_screen_id}");
 	$hidden = get_hidden_columns("{$inquiry_screen_id}");

	foreach ( $columns as $column_name => $column_display_name ) {
		$class = "class=\"$column_name column-$column_name\"";

		$style = '';
		if ( in_array($column_name, $hidden) )
			$style = ' style="display:none;"';

		$attributes = "$class$style";

 		switch ($column_name) {
			case 'cb':
 				echo '<th scope="row" class="check-column">';
				echo "<input type='checkbox' name='delete_comments[]' value='$inquiry_obj->ID' />";
				echo '</th>';
			break;

			case 'inquiry_main':
				// Check if user account exists for email
				$user_id = email_exists($inquiry_obj->email_address);
			
 				echo "<td $attributes>\n";
				if($user_id)
					echo "<a href='" . admin_url("user-edit.php?user_id=$user_id") ."'>{$inquiry_obj->name}</a><br />";
				else
					echo $inquiry_obj->name. "<br />";
				
 				echo ($inquiry_obj->phonenumber ? $inquiry_obj->phonenumber . "<br />" : "");
 				//echo "<a href='mailto:{$inquiry_obj->email_address}'>{$inquiry_obj->email_address}</a>";
 				echo $inquiry_obj->email_address;

				echo '<div class="row-actions">';
				echo '<input type="hidden" class="inquiry_id" value="'.$inquiry_obj->ID.'"/>';
				echo '<a href="#"  class="editinline wpp_delete_inquiry">Delete</a>';
				echo '</span></div>';

				echo "</td>";
			break;

 			case 'inquiry_property':
				echo "<td $attributes>\n";
				echo "<a href='".admin_url("post.php?post={$property_obj[ID]}&action=edit")."'>{$property_obj[post_title]}</a>";

				if($property_obj[parent_id])
					echo " at <a href='".admin_url("post.php?post={$property_obj[parent_id]}&action=edit")."'>{$property_obj[parent_title]}</a>";

				echo "</td>";
			break;

  			case 'inquiry_date':
				echo "<td $attributes>\n";
 				echo date(get_option('date_format'), strtotime($inquiry_obj->date));
				echo "</td>";
			break;

			default:
				echo "<td $attributes>\n";
				$slug = str_replace("inquiry_", "", $column_name);
				echo $inquiry_obj->$slug;
				echo "</td>\n";
				break;
		}
	}
	echo "</tr>\n";
}


/*
	Draws contact form
*/
	function contact_form() {
		global $post, $wpp_inquiry;



		// 1. Try general template in theme folder
		if(file_exists(TEMPLATEPATH . "/inquiry_form.php")) {
			include TEMPLATEPATH . "/inquiry_form.php";
			return;
		}

		// 2. Try custom template in plugin folder
		if(file_exists(WPP_Templates . "/inquiry_form.php")) {
			include WPP_Templates . "/inquiry_form.php";
			return;
		}

		// 3. No tempalte found, use default
		wpp_inquiry::draw_default_form();

		return;
	}


	function draw_default_form() {
		global $wpp_inquiry, $post;

		$contact_fields = $wpp_inquiry[contact_fields];

	?>


<div class="wpp_contact_form clearfix">

<form id="wpp_cform" class="wpp_cform" method="post" action="#" enctype="multipart/form-data">
	<ol class="cf-ol">

		<?php foreach($contact_fields as $slug => $data):
			$required = ($data[required] == 'true' ? ' wpp_inquiry_required_field ' : '');

		?>
		<li class="" id="wppc_li_<?php echo $slug; ?>">

			<label for="wppc_<?php echo $slug; ?>">
				<span><?php echo $data['title']; ?></span>
			</label>

			<?php if($data['type'] == 'input'): ?>
				<input type="text" value="" class="<?php echo $required; ?> single wpp_inquiry_element_<?php echo $slug; ?>"   id="wpp_inquiry_<?php echo $slug; ?>" name="wpp_inquiry[<?php echo $slug; ?>]">
			<?php endif; ?>

			<?php if($data['type'] == 'textarea'): ?>
				<textarea class="<?php echo $required; ?>  wpp_inquiry_element_<?php echo $slug; ?>" id="wpp_inquiry_<?php echo $slug; ?>" name="wpp_inquiry[<?php echo $slug; ?>]" rows="8" cols="30"></textarea>
			<?php endif; ?>

 		</li>
		<?php do_action('wpp_inquiry_form'); ?>
		<?php endforeach; ?>


		<li class="" id="wppc_li_security">
			<label for="wppc_security">
				<span><?php echo $wpp_inquiry[security_question]; ?> </span>
			</label>

			<input type="text" value="" id="wpp_inquiry_security" autocomplete="OFF" name="wpp_inquiry[security]" />
 		</li>
		
		<li>
		<div class="wpp_inquiry_error_response"></div>
		</li>

	</ol>


	<input type="button" value="<?php echo $wpp_inquiry[form_submit_button]; ?>" class="sendbutton" id="wppc_sendbutton" name="sendbutton">
</form>

</div>

<div class="wpp_inquiry_thank_you_message">
	<?php echo $wpp_inquiry[form_submission_success]; ?>
</div>


<?php


	}
}