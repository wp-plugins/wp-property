<?php
/**
 * WP-Property Premium Admin Functions
 *
 * Intended for easing developers with setting up websites.
 *
 * @version 1.0
 * @copyright Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WP-Property
 * @subpackage Admin Functions
 */
 
	
	// Create admin page
	add_action('admin_menu', create_function('', "add_submenu_page('edit.php?post_type=property', 'Admin Tools', 'Admin Tools', 10, 'wpp_admin_tools', array('WPP_Admin_Tools','draw_admin_page'));"));	
	
	// Do admin_init hook for processing functions
	add_action('admin_init', array('WPP_Admin_Tools', 'do_admin_init'));
	
	// Do ajax functions hook for processing functions
	add_action('wp_ajax_wp_property_admin_ajax', array('WPP_Admin_Tools', 'admin_ajax'));
	
	
	/**
	 * WPP_Admin_Tools Class
	 * 
	 * Contains administrative functions
	 *
	 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
	 *
	 * @version 1.0
	 * @author Andy Potanin <andy.potnain@twincitiestech.com>
	 * @package WP-Property
	 * @subpackage Admin Functions
	 */	
	class WPP_Admin_Tools {
	
		/**
		 * Displays advanced management page
		 *
		 * This is an administrative function that occurs before headers are sent
		 *
		 * @source Regenerate Thumbnails Plugin
		 * @author Andy Potanin, via Viper007Bond 
 		 */		 
		function draw_admin_page() {
			global $wpdb;
		 
			
			$total_images = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'" );


			?>
			<style type="text/css">
			.wpp_class_lookup {
				border:1px solid #C6C6C6;
				background: #EFEFEF;
				padding: 10px;
			}
			</style>
			
			<script type="text/javascript">
			// <![CDATA[
			
					var _wpnonce = '<?php echo wp_create_nonce('wp_property_admin_ajax'); ?>';
					var image_array = ['<?php echo implode("','", $total_images); ?>'];
					var total_images = '<?php echo count($total_images); ?>';
				
				
				jQuery(document).ready(function(){
								
					
					jQuery("#wpp_admin_regenerate_thumbnails").click(function() {
					
						console.log('event triggered.');
						
						// Resize thumbnails
						
					});
					
					
					jQuery("#wp_property_admin_dummy_properties").click(function() {
						ud_json_processing();
						console.log('generate properties event triggered.');
						var wpp_property_type  = jQuery("#wp_property_admin_dummy_properties_property_type").val();
						var wpp_quantity  = jQuery("#wp_property_admin_dummy_properties_quantity").val();
						var wpp_location  = jQuery("#wp_property_admin_dummy_properties_location").val();
						var wpp_post_parent   = jQuery("#wp_property_admin_dummy_properties_parent").val();
						jQuery.post(ajaxurl, { action: 'wp_property_admin_ajax', wpp_action: 'generate_properties', wpp_property_type: wpp_property_type, wpp_quantity: wpp_quantity, wpp_location:wpp_location, wpp_post_parent: wpp_post_parent, _wpnonce: _wpnonce}, function(return_data) {
						
 							ud_json_response(return_data);
						
						}, "json");
 						
					});
					
					jQuery("#wp_property_admin_dummy_users").click(function() {
						ud_json_processing();
 						var wpp_users_quantity  = jQuery("#wp_property_admin_dummy_properties_users_quantity").val();
						var wpp_users_role  = jQuery("#wp_property_admin_dummy_properties_users_role").val();
						jQuery.post(ajaxurl, { action: 'wp_property_admin_ajax', wpp_action: 'generate_users', wpp_users_quantity: wpp_users_quantity, wpp_users_role:wpp_users_role, _wpnonce: _wpnonce}, function(return_data) {
 							ud_json_response(return_data);
						}, "json");
 						
					});
										
					jQuery("#wp_property_admin_remove_properties").click(function() {
						ud_json_processing();
 						jQuery.post(ajaxurl, { action: 'wp_property_admin_ajax', wpp_action: 'remove_properties', _wpnonce: _wpnonce}, function(return_data) {
												
 							ud_json_response(return_data);
						
						}, "json");
 						
					});										
					jQuery("#wp_property_admin_remove_users").click(function() {
						ud_json_processing();
 						jQuery.post(ajaxurl, { action: 'wp_property_admin_ajax', wpp_action: 'remove_users', _wpnonce: _wpnonce}, function(return_data) {
												
 							ud_json_response(return_data);
						
						}, "json");
 						
					});
					

 
					
				});
			// ]]>
			</script>
	
			
			
			<div class="wrap">
				<h2>Advanced Property Management</h2>
				 
 
		<div class="wpp_box">
			<div class="wpp_box_header">
				<strong>Generate Dummy Properties</strong>
				<p>Not much else to say.</p>
			</div>
			<div class="wpp_box_content">

				Generate <input  type="text" id='wp_property_admin_dummy_properties_quantity' value='2'> property type(s) of <input id='wp_property_admin_dummy_properties_property_type' value='building'> properties using <input id='wp_property_admin_dummy_properties_parent' value='0'> as parent,<br />
				and <input type="text"  id='wp_property_admin_dummy_properties_location' value='St. Paul, Minnesota'> as the location.		
				
			</div>

			<div class="wpp_box_footer">
				<input type="button" class="button" id="wp_property_admin_dummy_properties" value="<?php _e( 'Generate properties', 'wp-properties' ) ?>" /> | <input type="button" class="button hide-if-no-js" id="wp_property_admin_remove_properties" value="<?php _e( 'Remove all Dummy Properties', 'wp-properties' ) ?>" />
			</div>
		</div>
	
		<div class="wpp_box">
			<div class="wpp_box_header">
				<strong>Generate Dummy Users</strong>
				<p>Not much else to say.</p>
			</div>
			<div class="wpp_box_content">

			Generate <input type="text" id='wp_property_admin_dummy_properties_users_quantity' value='2'> users of

			<select id="wp_property_admin_dummy_properties_users_role">
			<?php wp_dropdown_roles(); ?>
			</select>	
			</div>

			<div class="wpp_box_footer">
				<input type="button" class="button"  id="wp_property_admin_dummy_users" value="<?php _e( 'Generate Users', 'wp-properties' ) ?>" />
				| <input type="button" class="button"   id="wp_property_admin_remove_users" value="<?php _e( 'Remove Dummy Users', 'wp-properties' ) ?>" />			</div>
		</div>
	
 	
		<div class="wpp_box">
			<div class="wpp_box_header">
				<strong>Generate Inquiries</strong>
				<p>Random users will leave inquiries on various properties.</p>
			</div>
			<div class="wpp_box_content">

			Generate <input type="text" id='wp_property_admin_dummy_properties_users_quantity' value='2'> users of

			<select id="wp_property_admin_dummy_properties_users_role">
			<?php wp_dropdown_roles(); ?>
			</select>	
			</div>

			<div class="wpp_box_footer">
				<input type="button" class="button"  id="wp_property_admin_dummy_users" value="<?php _e( 'Generate Users', 'wp-properties' ) ?>" />
				| <input type="button" class="button"   id="wp_property_admin_remove_users" value="<?php _e( 'Remove Dummy Users', 'wp-properties' ) ?>" />			</div>
		</div>
	
 
 		
			</div>
		
		
			<?php
		} 
		
		
	
		

		/**
		 * Performs actions in the admin_init actions
		 *
		 * This is an administrative function that occurs before headers are sent
		 *
		 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
		 */
				
		function do_admin_init() {
		
			
		}

		/**
		 * Performs AJAX actions
		 *
		 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
		 */
				
		function admin_ajax() {
			global $wpdb;


			if ( !current_user_can( 'manage_options' ) )
				die('-1');
				

			if(!wp_verify_nonce( $_REQUEST['_wpnonce'],'wp_property_admin_ajax'))
				die('-1');
				
				

			
			$wpp_action = $_REQUEST['wpp_action'];
			
			switch ($wpp_action) {
			
				case 'generate_users':
				
					$users_quantity	= $_REQUEST['wpp_users_quantity'];
					$user_role 		= $_REQUEST['wpp_users_role'];
					
					if(is_numeric($users_quantity) && $users_quantity > 0) {
						$count = 0;

						while($count < $users_quantity) {
							$count++;
							
							if(!WPP_Admin_Tools::generate_dummy_user("user_role=$user_role&billing_details=true"))
								break;
						}
							
						if($count == $users_quantity)
							UD_F::json(true, "Success, all $count users were created.");
						else 
							UD_F::json(false, "Error, only $count out of $users_quantity users were generated.");
							
							
					} else {
						UD_F::json(false, "An error occured, the system does not understand how many properties to create.");
					}
				
				break;			
				
				case 'generate_properties':
				
					$location 	= $_REQUEST['wpp_location'];
					$wpp_quantity 	= $_REQUEST['wpp_quantity'];
					$post_parent 	= $_REQUEST['wpp_post_parent'];
					$property_type 	= $_REQUEST['wpp_property_type'];
					
					if(is_numeric($wpp_quantity) && $wpp_quantity > 0) {
						$count = 0;

						while($count < $wpp_quantity) {
							$count++;
							
							if(!WPP_Admin_Tools::generate_dummy_property("post_parent=$post_parent&property_type=$property_type&location=$location"))
								break;
						}
							
						if($count == $wpp_quantity)
							UD_F::json(true, "Success, all $count properties were generated.");
						else 
							UD_F::json(false, "Error, only $count out of $wpp_quantity properties were generated.");
							
							
					} else {
						UD_F::json(false, "An error occured, the system does not understand how many properties to create.");
					}
				
				break;
			
				case 'remove_users':
					
					// get list of all dummy contets by looking for 'wp_dummy_content' meta
					$dummy_users = $wpdb->get_col("SELECT user_id from {$wpdb->prefix}usermeta WHERE meta_key = 'wp_dummy_content'");
					$dummy_count = count($dummy_users);
					if($dummy_count < 1)
						UD_F::json(true, "No dummy users exist in database.");
					
					set_time_limit(60);
					$success_count = 0;
					foreach($dummy_users as $id) {
					
						$success = wp_delete_user($id);
						
						if($success)
							$success_count++;
					}
					
					if($success_count == $dummy_count)
						UD_F::json(true, "Success, $dummy_count dummy users were found in database, and $success_count were removed.");
				

					if($success_count < $dummy_count)
						UD_F::json(false, "Warning - out of $dummy_count dummy users  found in database, only $success_count were removed.");
				
				break;			
				case 'remove_properties':
					
					// get list of all dummy contets by looking for 'wp_dummy_content' meta
					$dummy_properties = $wpdb->get_col("SELECT post_id from {$wpdb->prefix}postmeta WHERE meta_key = 'wp_dummy_content'");
					$dummy_count = count($dummy_properties);
					if($dummy_count < 1)
						UD_F::json(true, "No dummy properties exist in database.");
					
					set_time_limit(60);
					$success_count = 0;
					foreach($dummy_properties as $id) {
					
						$success = wp_delete_post($id, true);
						
						if($success)
							$success_count++;
					}
					
					if($success_count == $dummy_count)
						UD_F::json(true, "Success, $dummy_count dummy properties were found in database, and $success_count were removed.");
				

					if($success_count < $dummy_count)
						UD_F::json(false, "Warning - out of $dummy_count dummy properties  found in database, only $success_count were removed.");
				
				break;
	
			
			}
			
			
				
			// Resize thumbnails
			/*
			set_time_limit( 60 );
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $fullsizepath ) ) )
			*/
			
		
	
		
			die();
		}
	
		/**
		 * Inserts a dummy users into database
		 *
		 * @param string $args Optional list of arguments to overwrite the defaults.
		 * @since 1.0
		 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
		 * 
		 * @used_by WPP_Admin_Tools::admin_ajax(), must return true to contiue working
		 * @return bool True is success on single listing generate, False if fail. 
		 */
		 
		function generate_dummy_user($args = false) {
			global $wpdb;
			
			$defaults = array('user_role' => 'subscriber', 'billing_details' => false);
			
			if($args)
				extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

			// Generate Values
			$first_name = WPP_Admin_Tools::generate_dummy_content('first_name');
			$last_name = WPP_Admin_Tools::generate_dummy_content('last_name');
			$email = strtolower($first_name . "." . $last_name . WPP_Admin_Tools::generate_dummy_content('email_domain'));
			$company_name = WPP_Admin_Tools::generate_dummy_content('company_name');
			$streetaddress = WPP_Admin_Tools::generate_dummy_content('streetaddress');
			$city = WPP_Admin_Tools::generate_dummy_content('city');
			$state = WPP_Admin_Tools::generate_dummy_content('state');
			$zip = WPP_Admin_Tools::generate_dummy_content('zip');
			$phonenumber = WPP_Admin_Tools::generate_dummy_content('phonenumber');
			
			// role?
			
			
			$user_id = wp_create_user($email, wp_hash_password(rand(100000,900000)), $email);
			
			if(!$user_id)
				return false;
			
			update_user_meta($user_id, 'first_name', $first_name);
			update_user_meta($user_id, 'last_name', $last_name);
			// update_user_meta($user_id, 'company_name', $company_name);
			update_user_meta($user_id, 'streetaddress', $streetaddress);
			update_user_meta($user_id, 'city', $city);
			update_user_meta($user_id, 'state', $state);
			update_user_meta($user_id, 'zip', $zip);
			update_user_meta($user_id, 'phonenumber', $phonenumber);
			update_user_meta($user_id, 'is_dummy', true);
 
		
			return true;
		
		}	
		
		
		
		/**
		 * Inserts a dummy property in database
		 *
		 * @param string $args Optional list of arguments to overwrite the defaults.
		 * @since 1.0
		 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
		 * 
		 * @used_by WPP_Admin_Tools::admin_ajax(), must return true to contiue working
		 * @return bool True is success on single listing generate, False if fail. 
		 */
		 
		function generate_dummy_property($args = false) {
			global $wpdb;
			
			$defaults = array('post_parent' => 0, 'property_type' => 'building', 'location' => false);
			
			if($args)
				extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

			//figure out post parent
			if(empty($post_parent) || !is_numeric($post_parent))
				$post_parent = '0';
			
			$postarr[post_status] 	= 'publish';
			$postarr[post_type] 	= 'property';
			$postarr[post_parent] 	= $post_parent;
			$postarr[post_content] 	= WPP_Admin_Tools::generate_dummy_content('description');
			$postarr[post_title] 	= WPP_Admin_Tools::generate_dummy_content('building');
			$post_id = wp_insert_post($postarr);
			
			if(!$post_id)
				return false;
			
			update_post_meta($post_id, 'wp_dummy_content', true);
			update_post_meta($post_id, 'location', WPP_Admin_Tools::generate_dummy_content('location', $location));
			update_post_meta($post_id, 'area', WPP_Admin_Tools::generate_dummy_content('area'));
			update_post_meta($post_id, 'price', WPP_Admin_Tools::generate_dummy_content('price'));
			update_post_meta($post_id, 'bedrooms', WPP_Admin_Tools::generate_dummy_content('bedrooms'));
			update_post_meta($post_id, 'bathrooms', WPP_Admin_Tools::generate_dummy_content('bathrooms'));
			update_post_meta($post_id, 'deposit', WPP_Admin_Tools::generate_dummy_content('price'));
			update_post_meta($post_id, 'property_type', (!empty($property_type) ? $property_type : WPP_Admin_Tools::generate_dummy_content('property_type')));	
			update_post_meta($post_id, 'lease_terms', WPP_Admin_Tools::generate_dummy_content('price'));

		
		
			return true;
		
		}
		
		/**
		 * Generates dummy content based on the type of content passed
		 *
		 * Users various arrays based on property type to get common words
		 *
		 * @param string $type The type of property content that needs to be generated
		 * @since 1.1
		 * Copyright 2010 Andy Potanin <andy.potanin@twincitiestech.com>
		 * 
		 * @used_by WPP_Admin_Tools::generate_dummy_property(), must return true to contiue working
		 *
		 * @return string Content
		 */
		function generate_dummy_content($type = false, $content = false) {
			global $wp_properties;
			
			if(!$type) return false;
			
			foreach($wp_properties['property_types'] as $slug => $description)
				$data['property_types'][] = $slug;
				
				
			$data['title_1'] = array('Liberty','Crossing','Barclay','Place','Park','Denali','Village','Cape Harbor','Glenmeade','Point','Canterbury','Crosswinds');
			$data['title_2'] = array('Towhhomes', 'Park', 'South','North','East','West','Oaks','Rental','Village','Woods','Apartments','Town Center','Apartment Homes','Reserve','Forest Hills');
			$data['description_words'] = explode(' ', 'retreat - because after inside outside excellent from world customized discriminating tastes mind. offers bedroom apartments fine standard features gourmet kitchen frost-free refrigerator ice maker dishwasher range/oven disposal abundant kitchen cabinet storage energy-efficient heat pump patio or balcony outside storage area wall-to-wall carp');
			$data['street_names'] = array('Main St.', 'First Ave', 'Second Ave', 'Third Ave', 'Martin Luther King Blvd');
			
			$data['first_names'] 	= 	array('JAMES','JOHN','ROBERT','MICHAEL','WILLIAM','DAVID','RICHARD','CHARLES','JOSEPH','THOMAS','CHRISTOPHER','DANIEL','PAUL','MARK','DONALD','GEORGE','KENNETH','STEVEN','EDWARD','BRIAN','RONALD','ANTHONY','KEVIN','JASON','MATTHEW','GARY','TIMOTHY','JOSE','LARRY','JEFFREY','FRANK','SCOTT','ERIC','STEPHEN','ANDREW','RAYMOND','GREGORY','JOSHUA','JERRY','DENNIS','WALTER','PATRICK','PETER','HAROLD','DOUGLAS','HENRY','CARL','ARTHUR','RYAN','ROGER','JOE','JUAN','JACK','ALBERT','JONATHAN','JUSTIN','TERRY','GERALD','KEITH','SAMUEL','WILLIE','RALPH','LAWRENCE','NICHOLAS','ROY','BENJAMIN','BRUCE','BRANDON','ADAM','HARRY','FRED','WAYNE','BILLY','STEVE','LOUIS','JEREMY','AARON','RANDY','HOWARD','EUGENE','CARLOS','RUSSELL','BOBBY','VICTOR','MARTIN','ERNEST','PHILLIP','TODD','JESSE','CRAIG','ALAN','SHAWN','CLARENCE','SEAN','PHILIP','CHRIS','JOHNNY','EARL','JIMMY','ANTONIO','DANNY','BRYAN','TONY','LUIS','MIKE','STANLEY','LEONARD','NATHAN','DALE');
			$data['last_names'] 	= 	array('JOHNSON','WILLIAMS','JONES','BROWN','DAVIS','MILLER','WILSON','MOORE','TAYLOR','ANDERSON','THOMAS','JACKSON','WHITE','HARRIS','MARTIN','THOMPSON','GARCIA','MARTINEZ','ROBINSON','CLARK','RODRIGUEZ','LEWIS','LEE','WALKER','HALL','ALLEN','YOUNG','HERNANDEZ','KING','WRIGHT','LOPEZ','HILL','SCOTT','GREEN','ADAMS','BAKER','GONZALEZ','NELSON','CARTER','MITCHELL','PEREZ','ROBERTS','TURNER','PHILLIPS','CAMPBELL','PARKER','EVANS','EDWARDS','COLLINS');
			$data['email_domains'] 	= 	array('gmail.com','hotmail.com','yahoo.com','live.com');
			$data['city_names'] 	= 	array('Fairview','Midway','Oak Grove','Franklin','Riverside','Centerville','Mount Pleasant','Georgetown','Salem','Greenwood');
			$data['states'] 		= 	array('AL'=>"Alabama",'AK'=>"Alaska",'AZ'=>"Arizona",'AR'=>"Arkansas",'CA'=>"California",'CO'=>"Colorado",'CT'=>"Connecticut",'DE'=>"Delaware",'DC'=>"District Of Columbia",'FL'=>"Florida",'GA'=>"Georgia",'HI'=>"Hawaii",'ID'=>"Idaho",'IL'=>"Illinois",'IN'=>"Indiana",'IA'=>"Iowa",'KS'=>"Kansas",'KY'=>"Kentucky",'LA'=>"Louisiana",'ME'=>"Maine",'MD'=>"Maryland",'MA'=>"Massachusetts",'MI'=>"Michigan",'MN'=>"Minnesota",'MS'=>"Mississippi",'MO'=>"Missouri",'MT'=>"Montana",'NE'=>"Nebraska",'NV'=>"Nevada",'NH'=>"New Hampshire",'NJ'=>"New Jersey",'NM'=>"New Mexico",'NY'=>"New York",'NC'=>"North Carolina",'ND'=>"North Dakota",'OH'=>"Ohio",'OK'=>"Oklahoma",'OR'=>"Oregon",'PA'=>"Pennsylvania",'RI'=>"Rhode Island",'SC'=>"South Carolina",'SD'=>"South Dakota",'TN'=>"Tennessee",'TX'=>"Texas",'UT'=>"Utah",'VT'=>"Vermont",'VA'=>"Virginia",'WA'=>"Washington",'WV'=>"West Virginia",'WI'=>"Wisconsin",'WY'=>"Wyoming");		
		
			switch($type) {
				
				case 'first_name':
					$random_key = array_rand($data[first_names], 1);
					return ucfirst(strtolower($data[first_names][$random_key]));
				break;
				
				case 'last_name':
					$random_key = array_rand($data[last_names], 1);
					return ucfirst(strtolower($data[first_names][$random_key]));
				break;
				
				case 'email_domain':
					$random_key = array_rand($data[email_domains], 1);
					return "@" . strtolower($data[email_domains][$random_key]);
				break;
				
				case 'city':
					$random_key = array_rand($data[city_names], 1);
					return ucfirst(strtolower($data[city_names][$random_key]));
				break;
				
				case 'state':
					$random_key = array_rand($data[states], 1);
					return ucfirst(strtolower($data[states][$random_key]));
				break;
				
				case 'zip':
					return rand(20000,70000);
				break;
				
				case 'phonenumber':
					
					$area_code 	= rand(100,800);
					$first 		= rand(100,999);
					$second 	= rand(1000,9999);
					return "($area_code) $first-$second";
				break;
				
				
				
				case 'description':	 
					shuffle($data['description_words']);
					return ucfirst(implode(' ', $data['description_words'])) . ".";
				break;
				
				case 'streetaddress':
					// Attempt to create addresses					
					$random_key = array_rand($data['street_names'], 1);					
					$house_address = (rand(10, 50) * 10);
					$street_name = $data['street_names'][$random_key];				
					return $house_address . " " . $street_name;
				
				break;

				case 'location':	 

					$streetaddress =  WPP_Admin_Tools::generate_dummy_content('streetaddress');
					$city_state = $content;
					return "$streetaddress, $city_state";
		 
				break;
				
				case 'building':
					
					$title_1 = array_rand($data['title_1'], 1);
					$title_2 = array_rand($data['title_2'], 1);					
					return  $data['title_1'][$title_1] . " " . $data['title_2'][$title_2];
				break;
				
				case 'price':
					return (rand(4,15) * 100);
				break;
				
				case 'bedrooms':
					return rand(1,4);
				break;
				
				case 'bathrooms':
					return rand(1,4);
				break;
								
				case 'area':
					return (rand(6,11) * 100);
				break;
				
				case 'property_type':
					$random_key = array_rand($data[property_types], 1);
					return $data[property_types][$random_key];
				break;
				
				
		
			}
		
			
		
		
		}
	
	}
	

