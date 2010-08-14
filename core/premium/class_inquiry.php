<?php
/**
 * WP-Directory Inquiry Class
 *
 * Handles additional inquiries, displays back-end UI, etc. 
 *
 * @version 1.0
 * @package WP-Directory
 * @subpackage Inquiry
 */
 
 
 			
			
 
add_action('wpp_init', array('WPP_Inquiry', 'init'));
 
 
class WPP_Inquiry {

	function init() {
		global $inquiry_screen_id, $wpp_inquiry;
		$inquiry_screen_id = 'property_page_inquiries';
		
		

		
		WPP_Inquiry::load_default_structure(); 
		WPP_Inquiry::check_tables(); 
		

			
			
		
		// When under property_type, screen->id: property_page_inquiries	
		add_filter("manage_property_page_inquiries_columns", array('WPP_Inquiry', "edit_columns"));
		
		add_action('wp_ajax_wpp_inquiry_delete_single', array('WPP_Inquiry', 'delete_single'));
		
		
		add_action('wp_ajax_wpp_property_inquiry', array('WPP_Inquiry', 'property_inquiry'));
		add_action('wp_ajax_nopriv_wpp_property_inquiry', array('WPP_Inquiry', 'property_inquiry'));
 		
 		add_filter("get_comment", array('WPP_Inquiry', 'get_inquiry'), 0,2); 
		

		
		add_action("admin_menu", array("WPP_Inquiry", "admin_menu"));
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
		global $wpp_inquiry;

		$wpp_inquiry['contact_fields'] = array(
			'name' => array(	
				'title' => 'Name',
				'type' => 'input',
				'required' => true),
			'phonenumber' => array(	
				'title' => 'Phone Number',
				'type' => 'input',
				'required' => false),
			'email_address' => array(	
				'title' => 'Email Address',
				'type' => 'input',
				'required' => true),
			'content' => array(	
				'title' => 'Message',
				'type' => 'textarea',
				'required' => true)
			);
	 	$security_var = 12;
					
		$wpp_inquiry[contact_fields] = 					$wpp_inquiry['contact_fields'];
		$wpp_inquiry[security_var] 						= 	12;
		$wpp_inquiry[security_question] 				= 	'5 + 7 =';
		$wpp_inquiry[form_submission_success] 		=  'Your message has been sent. Thank you!<br />You may also call us at 763-258-9988.';
		$wpp_inquiry[form_submit_button] 				= 	'Submit';

		
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
		add_submenu_page('edit.php?post_type=property', "Inquiries", "Inquiries", 10, 'inquiries',array('WPP_Inquiry', 'inquiry_page')); 

	}


	function inquiry_page() {
	
		$inquiries = WPP_Inquiry::get_inquiries();
 
  
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
		WPP_Inquiry::inquiry_row($inquiry); 

?>
</tbody>
 
</table>
<?php else: ?>
No inquiries yet.
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
 			$return[$id] = WPP_Inquiry::get_inquiry($id);
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
				
 				if(empty($inquiry_data[$slug]) && $data[required]) {
					$fail = true;
					$failure_data[] = $data[title];
				
				}
	
		}
		
		// Check if vaildation failed
		if($fail && is_array($failure_data)) {
			$missing_fields = implode(",", $failure_data);
			echo json_encode(array('error' => 'true', 'message' => "The validation failed, the following field(s) are required: $missing_fields"));
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
		$inquiry_id = WPP_Inquiry::insert_inquiry($insert_inquiry_data);
 
		if($inquiry_id)
			echo json_encode(array('success' => 'true', 'message' => $wpp_inquiry[form_submission_success]));
		
		if(!$inquiry_id)
			echo json_encode(array('error' => 'true', 'message' => 'There was a problem sending your message, contact website administrator.'));
		
		
		die();
	}
	

/*
	Modeled after wp_insert_comment
*/
	function insert_inquiry($inquiry_data) {
		global $wpdb, $wpp_inquiry;
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
			WPP_Inquiry::insert_meta($inquiry_id, $slug, $inquiry_data[$slug]);
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
		$columns['inquiry_property'] =  "Date";
		
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
				if ( $user_can ) echo "<input type='checkbox' name='delete_comments[]' value='$inquiry_obj->ID' />";
				echo '</th>';
			break;
		 
			case 'inquiry_main':
 				echo "<td $attributes>\n";
 				echo $inquiry_obj->name. "<br />";
 				echo ($inquiry_obj->phonenumber ? $inquiry_obj->phonenumber . "<br />" : "");
 				echo "<a href='mailto:{$inquiry_obj->email_address}'>{$inquiry_obj->email_address}</a>";
				
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
		WPP_Inquiry::draw_default_form();
		
		return;
	}
	
	
	function draw_default_form() {
		global $wpp_inquiry, $post;
		
		$contact_fields = $wpp_inquiry[contact_fields];
	
	?>
	
	
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
						jQuery(".contact_form").hide();
						jQuery(".thank_you_message").html(response.message);
						jQuery(".thank_you_message").show();
					}
					
				
			}, "json");
			
			return;
		
		});
	
	});
</script>

<div class="contact_form clearfix">
						
<form id="wpp_cform" class="wpp_cform" method="post" action="#" enctype="multipart/form-data">
	<ol class="cf-ol">
	
		<?php foreach($contact_fields as $slug => $data): ?>
		<li class="" id="wppc_li_<?php echo $slug; ?>">
			
			<label for="wppc_<?php echo $slug; ?>">
				<span><?php echo $data['title']; ?></span>
			</label>
			
			<?php if($data['type'] == 'input'): ?>
				<input type="text" value="" class="single wpp_inquiry_element_<?php echo $slug; ?>"   id="wpp_inquiry_<?php echo $slug; ?>" name="wpp_inquiry[<?php echo $slug; ?>]">
			<?php endif; ?>
			
			<?php if($data['type'] == 'textarea'): ?>
				<textarea class="wpp_inquiry_element_<?php echo $slug; ?>" id="wpp_inquiry_<?php echo $slug; ?>" name="wpp_inquiry[<?php echo $slug; ?>]" rows="8" cols="30"></textarea>
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

	</ol>
 	
	<div class="wpp_inquiry_error_response" style="display:none;"></div>
	
	<input type="button" value="<?php echo $wpp_inquiry[form_submit_button]; ?>" class="sendbutton" id="wppc_sendbutton" name="sendbutton">
</form>

</div>

<div class="thank_you_message hidden">
	<?php echo $wpp_inquiry[form_submission_success]; ?>
</div>


<?php
	
	
	}
}