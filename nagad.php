<?php 
/*
Plugin Name: Nagad Manual Payment gateway 
Plugin URI:  https://zitengine.com
Description: nagad's cross-border payments platform empowers businesses, online sellers and freelancers to pay and get paid globally as easily as they do locally. nagad is money transfer system of International by facilitating money transfer through Online. This plugin depends on woocommerce and will provide an extra payment gateway through nagad in checkout page.
Version:     1.0
Author:      Md Zahedul Hoque
Author URI:  http://facebook.com/zitengine 
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: stb
*/
defined('ABSPATH') or die('Only a foolish person try to access directly to see this white page. :-) ');
define( 'zitengine_nagad__VERSION', '1.1' );
define( 'zitengine_nagad__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
/**
 * Plugin language
 */
add_action( 'init', 'zit_nagad_language_setup' );
function zit_nagad_language_setup() {
  load_plugin_textdomain( 'stb', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
/**
 * Plugin core start
 * Checked Woocommerce activation
 */
if( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
	
	/**
	 * nagad gateway register
	 */
	add_filter('woocommerce_payment_gateways', 'zitengine_nagad_payment_gateways');
	function zitengine_nagad_payment_gateways( $gateways ){
		$gateways[] = 'zitengine_nagad';
		return $gateways;
	}

	/**
	 * nagad gateway init
	 */
	add_action('plugins_loaded', 'zitengine_nagad_plugin_activation');
	function zitengine_nagad_plugin_activation(){
		
		class zitengine_nagad extends WC_Payment_Gateway {

			public $nagad_number;
			public $number_type;
			public $order_status;
			public $instructions;
			public $nagad_charge;

			public function __construct(){
				$this->id 					= 'zitengine_nagad';
				$this->title 				= $this->get_option('title', 'nagad P2P Gateway');
				$this->description 			= $this->get_option('description', 'nagad payment Gateway');
				$this->method_title 		= esc_html__("nagad", "stb");
				$this->method_description 	= esc_html__("nagad Payment Gateway Options", "stb" );
				$this->icon 				= plugins_url('images/nagad.png', __FILE__);
				$this->has_fields 			= true;

				$this->zitengine_nagad_options_fields();
				$this->init_settings();
				
				$this->nagad_number = $this->get_option('nagad_number');
				$this->number_type 	= $this->get_option('number_type');
				$this->order_status = $this->get_option('order_status');
				$this->instructions = $this->get_option('instructions');
				$this->nagad_charge = $this->get_option('nagad_charge');

				add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );
	            add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'zitengine_nagad_thankyou_page' ) );
	            add_action( 'woocommerce_email_before_order_table', array( $this, 'zitengine_nagad_number_instructions' ), 10, 3 );
			}


			public function zitengine_nagad_options_fields(){
				$this->form_fields = array(
					'enabled' 	=>	array(
						'title'		=> esc_html__( 'Enable/Disable', "stb" ),
						'type' 		=> 'checkbox',
						'label'		=> esc_html__( 'nagad Payment', "stb" ),
						'default'	=> 'yes'
					),
					'title' 	=> array(
						'title' 	=> esc_html__( 'Title', "stb" ),
						'type' 		=> 'text',
						'default'	=> esc_html__( 'Nagad', "stb" )
					),
					'description' => array(
						'title'		=> esc_html__( 'Description', "stb" ),
						'type' 		=> 'textarea',
						'default'	=> esc_html__( 'Please complete your nagad payment at first, then fill up the form below.', "stb" ),
						'desc_tip'    => true
					),
	                'order_status' => array(
	                    'title'       => esc_html__( 'Order Status', "stb" ),
	                    'type'        => 'select',
	                    'class'       => 'wc-enhanced-select',
	                    'description' => esc_html__( 'Choose whether status you wish after checkout.', "stb" ),
	                    'default'     => 'wc-on-hold',
	                    'desc_tip'    => true,
	                    'options'     => wc_get_order_statuses()
	                ),				
					'nagad_number'	=> array(
						'title'			=> esc_html__( 'nagad Number', "stb" ),
						'description' 	=> esc_html__( 'Add a nagad Number which will be shown in checkout page', "stb" ),
						'type'			=> 'number',
						'desc_tip'      => true
					),
					'number_type'	=> array(
						'title'			=> esc_html__( 'nagad Account Type', "stb" ),
						'type'			=> 'select',
						'class'       	=> 'wc-enhanced-select',
						'description' 	=> esc_html__( 'Select nagad account type', "stb" ),
						'options'	=> array(
							'Agent'		=> esc_html__( 'Agent', "stb" ),
							'Marchent'	=> esc_html__( 'Marchent', "stb" ),
							'Personal'	=> esc_html__( 'Personal', "stb" )
						),
						'desc_tip'      => true
					),
					'nagad_charge' 	=>	array(
						'title'			=> esc_html__( 'Enable nagad Charge', "stb" ),
						'type' 			=> 'checkbox',
						'label'			=> esc_html__( 'Add 2% nagad "Payment" charge to net price', "stb" ),
						'description' 	=> esc_html__( 'If a product price is 1000 then customer have to pay ( 1000 + 20 ) = 1020. Here 20 is nagad charge', "stb" ),
						'default'		=> 'no',
						'desc_tip'    	=> true
					),						
	                'instructions' => array(
	                    'title'       	=> esc_html__( 'Instructions', "stb" ),
	                    'type'        	=> 'textarea',
	                    'description' 	=> esc_html__( 'Instructions that will be added to the thank you page and emails.', "stb" ),
	                    'default'     	=> esc_html__( 'Thanks for purchasing through nagad. We will check and give you update as soon as possible.', "stb" ),
	                    'desc_tip'    	=> true
	                ),								
				);
			}


			public function payment_fields(){

				global $woocommerce;
				$nagad_charge = ($this->nagad_charge == 'yes') ? esc_html__(' Also note that 2% nagad "Payment" cost will be added with net price. Total amount you need to send us at', "stb" ). ' ' . get_woocommerce_currency_symbol() . $woocommerce->cart->total : '';
				echo wpautop( wptexturize( esc_html__( $this->description, "stb" ) ) . $nagad_charge  );
				echo wpautop( wptexturize( "Nagad ".$this->number_type." Number : ".$this->nagad_number ) );

				?>
					<p>
						<label for="nagad_number"><?php esc_html_e( 'Nagad Number', "stb" );?></label>
						<input type="text" name="nagad_number" id="nagad_number" placeholder="017XXXXXXXX">
					</p>
					<p>
						<label for="nagad_transaction_id"><?php esc_html_e( 'nagad Transaction ID', "stb" );?></label>
						<input type="text" name="nagad_transaction_id" id="nagad_transaction_id" placeholder="8N7A6D5EE7M">
					</p>
				<?php 
			}
			

			public function process_payment( $order_id ) {
				global $woocommerce;
				$order = new WC_Order( $order_id );
				
				$status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;
				// Mark as on-hold (we're awaiting the nagad)
				$order->update_status( $status, esc_html__( 'Checkout with nagad payment. ', "stb" ) );

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				$woocommerce->cart->empty_cart();

				// Return thankyou redirect
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			}	


	        public function zitengine_nagad_thankyou_page() {
			    $order_id = get_query_var('order-received');
			    $order = new WC_Order( $order_id );
			    if( $order->payment_method == $this->id ){
		            $thankyou = $this->instructions;
		            return $thankyou;		        
			    } else {
			    	return esc_html__( 'Thank you. Your order has been received.', "stb" );
			    }

	        }


	        public function zitengine_nagad_number_instructions( $order, $sent_to_admin, $plain_text = false ) {
			    if( $order->payment_method != $this->id )
			        return;        	
	            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method ) {
	                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
	            }
	        }

		}

	}

	/**
	 * Add settings page link in plugins
	 */
	add_filter( "plugin_action_links_". plugin_basename(__FILE__), 'zitengine_nagad_settings_link' );
	function zitengine_nagad_settings_link( $links ) {
		
		$settings_links = array();
		$settings_links[] ='<a href="https://www.facebook.com/zitengine/" target="_blank">' . esc_html__( 'Follow US', 'stb' ) . '</a>';
		$settings_links[] ='<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=zitengine_nagad' ) . '">' . esc_html__( 'Settings', 'stb' ) . '</a>';
        
        // add the links to the list of links already there
		foreach($settings_links as $link) {
			array_unshift($links, $link);
		}

		return $links;
	}	

	/**
	 * If nagad charge is activated
	 */
	$nagad_charge = get_option( 'woocommerce_zitengine_nagad_settings' );
	if( $nagad_charge['nagad_charge'] == 'yes' ){

		add_action( 'wp_enqueue_scripts', 'zitengine_nagad_script' );
		function zitengine_nagad_script(){
			wp_enqueue_script( 'stb-script', plugins_url( 'js/scripts.js', __FILE__ ), array('jquery'), '1.0', true );
		}

		add_action( 'woocommerce_cart_calculate_fees', 'zitengine_nagad_charge' );
		function zitengine_nagad_charge(){

		    global $woocommerce;
		    $available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
		    $current_gateway = '';

		    if ( !empty( $available_gateways ) ) {
		        if ( isset( $woocommerce->session->chosen_payment_method ) && isset( $available_gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
		            $current_gateway = $available_gateways[ $woocommerce->session->chosen_payment_method ];
		        } 
		    }
		    
		    if( $current_gateway!='' ){

		        $current_gateway_id = $current_gateway->id;

				if ( is_admin() && ! defined( 'DOING_AJAX' ) )
					return;

				if ( $current_gateway_id =='zitengine_nagad' ) {
					$percentage = 0.02;
					$surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;	
					$woocommerce->cart->add_fee( esc_html__('nagad Charge', 'stb'), $surcharge, true, '' ); 
				}
		       
		    }    	
		    
		}
		
	}

	/**
	 * Empty field validation
	 */
	add_action( 'woocommerce_checkout_process', 'zitengine_nagad_payment_process' );
	function zitengine_nagad_payment_process(){

	    if($_POST['payment_method'] != 'zitengine_nagad')
	        return;

	    $nagad_number = sanitize_text_field( $_POST['nagad_number'] );
	    $nagad_transaction_id = sanitize_text_field( $_POST['nagad_transaction_id'] );

	    $match_number = isset($nagad_number) ? $nagad_number : '';
	    $match_id = isset($nagad_transaction_id) ? $nagad_transaction_id : '';

		$validate_number = preg_match( '/^01[5-9]\d{8}$/', $match_number );
		$validate_id = preg_match( '/[a-zA-Z0-9]+/',  $match_id );

	    if( !isset($nagad_number) || empty($nagad_number) )
	        wc_add_notice( esc_html__( 'Please add Nagad Number', 'stb'), 'error' );

		if( !empty($nagad_number) && $validate_number == false )
	        wc_add_notice( esc_html__( 'Nagad Number not valid', 'stb'), 'error' );

	    if( !isset($nagad_transaction_id) || empty($nagad_transaction_id) )
	        wc_add_notice( esc_html__( 'Please add your nagad transaction ID', 'stb' ), 'error' );

		if( !empty($nagad_transaction_id) && $validate_id == false )
	        wc_add_notice( esc_html__( 'Only number or letter is acceptable', 'stb'), 'error' );

	}

	/**
	 * Update nagad field to database
	 */
	add_action( 'woocommerce_checkout_update_order_meta', 'zitengine_nagad_additional_fields_update' );
	function zitengine_nagad_additional_fields_update( $order_id ){

	    if($_POST['payment_method'] != 'zitengine_nagad' )
	        return;

	    $nagad_number = sanitize_text_field( $_POST['nagad_number'] );
	    $nagad_transaction_id = sanitize_text_field( $_POST['nagad_transaction_id'] );

		$number = isset($nagad_number) ? $nagad_number : '';
		$transaction = isset($nagad_transaction_id) ? $nagad_transaction_id : '';

		update_post_meta($order_id, '_nagad_number', $number);
		update_post_meta($order_id, '_nagad_transaction', $transaction);

	}

	/**
	 * Admin order page nagad data output
	 */
	add_action('woocommerce_admin_order_data_after_billing_address', 'zitengine_nagad_admin_order_data' );
	function zitengine_nagad_admin_order_data( $order ){
	    
	    if( $order->payment_method != 'zitengine_nagad' )
	        return;

		$number = (get_post_meta($order->id, '_nagad_number', true)) ? get_post_meta($order->id, '_nagad_number', true) : '';
		$transaction = (get_post_meta($order->id, '_nagad_transaction', true)) ? get_post_meta($order->id, '_nagad_transaction', true) : '';

		?>
		<div class="form-field form-field-wide">
			<img src='<?php echo plugins_url("images/nagad.png", __FILE__); ?>' alt="nagad">	
			<table class="wp-list-table widefat fixed striped posts">
				<tbody>
					<tr>
						<th><strong><?php esc_html_e('Nagad Number', 'stb') ;?></strong></th>
						<td>: <?php echo esc_attr( $number );?></td>
					</tr>
					<tr>
						<th><strong><?php esc_html_e('Transaction ID', 'stb') ;?></strong></th>
						<td>: <?php echo esc_attr( $transaction );?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php 
		
	}

	/**
	 * Order review page nagad data output
	 */
	add_action('woocommerce_order_details_after_customer_details', 'zitengine_nagad_additional_info_order_review_fields' );
	function zitengine_nagad_additional_info_order_review_fields( $order ){
	    
	    if( $order->payment_method != 'zitengine_nagad' )
	        return;

		$number = (get_post_meta($order->id, '_nagad_number', true)) ? get_post_meta($order->id, '_nagad_number', true) : '';
		$transaction = (get_post_meta($order->id, '_nagad_transaction', true)) ? get_post_meta($order->id, '_nagad_transaction', true) : '';

		?>
			<tr>
				<th><?php esc_html_e('nagad Number:', 'stb');?></th>
				<td><?php echo esc_attr( $number );?></td>
			</tr>
			<tr>
				<th><?php esc_html_e('Transaction ID:', 'stb');?></th>
				<td><?php echo esc_attr( $transaction );?></td>
			</tr>
		<?php 
		
	}	

	/**
	 * Register new admin column
	 */
	add_filter( 'manage_edit-shop_order_columns', 'zitengine_nagad_admin_new_column' );
	function zitengine_nagad_admin_new_column($columns){

	    $new_columns = (is_array($columns)) ? $columns : array();
	    unset( $new_columns['order_actions'] );
	    $new_columns['mobile_no'] 	= esc_html__('Send From', 'stb');
	    $new_columns['tran_id'] 	= esc_html__('Tran. ID', 'stb');

	    $new_columns['order_actions'] = $columns['order_actions'];
	    return $new_columns;

	}

	/**
	 * Load data in new column
	 */
	add_action( 'manage_shop_order_posts_custom_column', 'zitengine_nagad_admin_column_value', 2 );
	function zitengine_nagad_admin_column_value($column){

	    global $post;

	    $mobile_no = (get_post_meta($post->ID, '_nagad_number', true)) ? get_post_meta($post->ID, '_nagad_number', true) : '';
	    $tran_id = (get_post_meta($post->ID, '_nagad_transaction', true)) ? get_post_meta($post->ID, '_nagad_transaction', true) : '';

	    if ( $column == 'mobile_no' ) {    
	        echo esc_attr( $mobile_no );
	    }
	    if ( $column == 'tran_id' ) {    
	        echo esc_attr( $tran_id );
	    }
	}

} else {
	/**
	 * Admin Notice
	 */
	add_action( 'admin_notices', 'zitengine_nagad_admin_notice__error' );
	function zitengine_nagad_admin_notice__error() {
	    ?>
	    <div class="notice notice-error">
	        <p><a href="http://wordpress.org/extend/plugins/woocommerce/"><?php esc_html_e( 'Woocommerce', 'stb' ); ?></a> <?php esc_html_e( 'plugin need to active if you wanna use nagad plugin.', 'stb' ); ?></p>
	    </div>
	    <?php
	}
	
	/**
	 * Deactivate Plugin
	 */
	add_action( 'admin_init', 'zitengine_nagad_deactivate' );
	function zitengine_nagad_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		unset( $_GET['activate'] );
	}
}