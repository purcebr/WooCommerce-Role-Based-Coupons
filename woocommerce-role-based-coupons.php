<?php
/*
Plugin Name: WooCommerce Role Based Coupons
Plugin URI: http://bryanpurcell.com
Description: Restrict WooCommerce coupon codes by Wordpress user role.
Version: 1.0.1
Author: purcebr
Author URI: http://bryanpurcell.com
Requires at least: 3.1
Tested up to: 4.0

Copyright: © 2015 Bryan Purcell.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Required functions
 **/
if ( ! function_exists( 'is_woocommerce_active' ) ) require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 **/
		
if (is_woocommerce_active()) {
	
	/**
	 * Localisation
	 **/
	load_plugin_textdomain('wc_role_based_coupons', false, dirname( plugin_basename( __FILE__ ) ) . '/');
	
	/**
	 * woocommerce_min_max_quantities class
	 **/
	if (!class_exists('WC_Restrict_Coupons')) {
	 
		class WC_Restrict_Coupons {
			
			public function __construct() { 
				
				add_filter('woocommerce_edit_coupon_columns', array($this,'woocommerce_roles_edit_coupon_columns'));
				add_filter('woocommerce_coupon_is_valid',array($this,'woocommerce_coupon_is_valid'),1,3);
				add_action('woocommerce_coupon_loaded',array($this,'woocommerce_coupon_loaded'),1,2	);
				add_action('woocommerce_coupon_options', array($this,'woocommerce_edit_roles_coupon_columns'));
				add_action( 'woocommerce_process_shop_coupon_meta', array($this,'woocommerce_process_shop_coupon_meta') , 1, 2 );
		    } 
		    
			/**
			* Populate the Coupon object with excluded roles
			*
			* @access public
			* @param $coupon
			* @return void
			*/
			public function woocommerce_coupon_loaded($coupon){
				$coupon_id = $coupon->id;
				$exclusions = get_post_meta( $coupon_id, 'excluded_roles', true );
				
				//If the exclusions are saved in comma seperated format, explode into an array.

				if(!is_array($exclusions)) {
					$exclusions = explode(',', $exclusions);
				}

				$coupon->excluded_roles = $exclusions;
			}
			
			/* Check the current user role against the coupon's excluded roles
			*
			* @access public
			* @param mixed $column
			* @return array of role ids => role names
			*/
			public function woocommerce_coupon_is_valid($valid, $coupon){
				if($valid)
				{
					//if it's not valid anyway, we don't care about roles.
					$current_role = $this->get_current_user_role();
					if(in_array($current_role, $coupon->excluded_roles) || ($current_role == false && in_array('guest', $coupon->excluded_roles)))
						return false;
					else
						return true;
				}
				else
					return false;
			}
			
			/* Print the settings field for role exclusion on the coupon add/edit page.
			*
			* @access public
			* @param void
			* @return void
			*/
			public function woocommerce_edit_roles_coupon_columns(){
				echo '<div class="options_group">';
				global $woocommerce;
				global $post;
				?>

				<p class="form-field"><label for="product_ids"><?php _e( 'Excluded Roles', 'woocommerce' ) ?></label>
						<select id="excluded_roles" name="excluded_roles[]" class="chosen_select" multiple="multiple" data-placeholder="<?php _e( 'All Roles Allowed', 'woocommerce' ); ?>">
							<?php
								$roles = $this->get_roles();

								$excluded_roles = get_post_meta( $post->ID, 'excluded_roles', true );

								if ( $roles ) foreach ( $roles as $role )
									echo '<option value="' . strtolower($role['name']) . '"' . selected( in_array( strtolower($role['name']), $excluded_roles ), true, false ) . '>' . esc_html( $role['name'] ) . '</option>';
							?>
						</select> <img class="help_tip" data-tip='<?php _e( 'Wordpress Roles excluded from using this WooCommerce coupon.', 'woocommerce' ) ?>' src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
					<?php
				echo '</div>';
			}	
				    
			/* Updated saved excluded role settings for coupon when saved
			*
			* @access public
			* @param $post_id, $post
			* @return void
			*/
			public function woocommerce_process_shop_coupon_meta($post_id, $post)
			{
				$excluded_roles = isset( $_POST['excluded_roles'] ) ? array_map( 'strval', $_POST['excluded_roles'] ) : array();
				update_post_meta( $post_id, 'excluded_roles', $excluded_roles );
			}

			/* Get list of availabel WP roles
			*
			* @access public
			* @param void
			* @return $all_roles
			*/
			private function get_roles(){
				global $wp_roles;
				$all_roles = $wp_roles->roles;
				//Add guest to the list...
				$all_roles[] = array("name"=>"Guest");
				return $all_roles;
			}
			
			/* Get the current user role.
			*
			* @access private
			* @param void
			* @return $role
			*/
			private function get_current_user_role() {
				global $current_user, $wpdb;
				$role = $wpdb->prefix . 'capabilities';
				if($current_user->$role != false)
					$current_user->role = array_keys($current_user->$role);
				else
					return false;
					
				$role = $current_user->role[0];
				return $role;
			}
		}
		
		$WC_Restrict_Products = new WC_Restrict_Coupons();
	}
}