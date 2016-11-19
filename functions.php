<?php 

add_action( 'wp_enqueue_scripts', 'salient_child_enqueue_styles');
function salient_child_enqueue_styles() {
	
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css', array('font-awesome'));

    if (!is_admin()) {
	    wp_enqueue_script( 'tweenmax', get_stylesheet_directory_uri() . '/inc/js/scrollmagic/TweenMax.min.js', array('jquery'), '', false );

	    wp_enqueue_script( 'scroll-magic', get_stylesheet_directory_uri() . '/inc/js/scrollmagic/ScrollMagic.js', array('jquery', 'tweenmax'), '', false );
	    wp_enqueue_script( 'scroll-magic-jquery', get_stylesheet_directory_uri() . '/inc/js/scrollmagic/jquery.ScrollMagic.js', array('scroll-magic'), '', true );
	    
	    wp_enqueue_script( 'animation', get_stylesheet_directory_uri() . '/inc/js/scrollmagic/animation.gsap.js', array(), '', true );

	    wp_enqueue_script( 'scroll-magic-debug', get_stylesheet_directory_uri() . '/inc/js/scrollmagic/debug.addIndicators.min.js', array('scroll-magic'), '', true );

	    wp_enqueue_script( 'scroll-magic-init', get_stylesheet_directory_uri() . '/inc/js/scrollmagic/scrollmagic.init.js', array('scroll-magic'), '', true );
	}


    if ( is_rtl() ) 
   		wp_enqueue_style(  'salient-rtl',  get_template_directory_uri(). '/rtl.css', array(), '1', 'screen' );
}
	

	add_action( 'woocommerce_payment_complete', 'process_order', 10 );

	function process_order($order_id) {
		$order = new WC_Order( $order_id );
		$items = $order->get_items();

		$shipping_name = '';
		if ( !empty($order->shipping_first_name)) $shipping_name .= $order->shipping_first_name;
		else $shipping_name .= $order->billing_first_name;

		if ( !empty($order->shipping_last_name)) $shipping_name .= " ".$order->shipping_last_name;
		else $shipping_name .= " ".$order->billing_last_name;

		function http_build_query_for_curl( $arrays , &$new = Array( ) , $prefix = null ) {

			if ( is_object( $arrays ) ) {
				$arrays = get_object_vars( $arrays ) ;
			}

			foreach ( $arrays as $key => $value ) {
				$k = ( isset( $prefix ) ? $prefix . '[' . $key . ']' : $key ) ;
				if ( is_array( $value ) OR is_object( $value )  ) {
					http_build_query_for_curl( $value , $new , $k ) ;
				} else {
					$new[ $k ] = $value ;
				}
			}
		}

		// CREATE AN ARRAY TO HOLD PRINTDROPPER ITEM DATA
		$printdropper_item_array = Array();

		foreach ($items as $item) {
			$item_id = $item['product_id'];
			$woo_pd_print_size = $item['pa_dimensions'];
			$woo_pd_product_id = "";
			$woo_pd_order_error_code = null;

			switch ($woo_pd_print_size) {
			    case "27x40 (theatrical)":
			        $woo_pd_product_id = 389;
			        break;
			    case "27x40 Int. Shipping":
			        $woo_pd_product_id = 389;
			        break;
			    case "24x36":
			        $woo_pd_product_id = 15;
			        break;
		        case "20x30":
			        $woo_pd_product_id = 47;
			        break;
		        case "13x19":
			        $woo_pd_product_id = 9;
			        break;
			    case "11x17":
			        $woo_pd_product_id = 6;
			        break;
			    default:
			        $woo_pd_order_error_code = "Print product option not available. Submit order to fulfilment manually and/or contact site admin for more options.";
			}

			$artwork_url = get_post_meta( $item_id, '_woo_pd_print_file', true );
			$printdropper_item = Array( 
				'product_id' => $woo_pd_product_id,
				'quantity' => $item['qty'], // SPECIFY ITEM QUANTITY
				'artwork_url' => $artwork_url // THE FULL URL TO YOUR ARTWORK
			) ;
			echo $artwork_url;
			$printdropper_item_array[] = $printdropper_item;
		}

		$printdropper_item_array[] = $printdropper_item ;


		// BUILD PRINTDROPPER RETURN ADDRESS ARRAY
		$printdropper_return_address = Array(
			'name'		=> 'Kevin Deganhart',
			'address_1' => '557 Eastern Ave',
			'address_2' => '',
			'city' 		=> 'Brighton',
			'state' 	=> 'CO',
			'zip' 		=> '80601',
			'country' 	=> 'US',
		) ;


		// BUILD PRINTDROPPER TO ADDRESS ARRAY

		$printdropper_to_address = Array(
			'name'		=> $shipping_name, // THE NAME THAT WILL APPEAR IN THE TO ADDRESS
			'address_1' => $order->shipping_address_1,
			'address_2' => '',
			'city' 		=> $order->shipping_city,
			'state' 	=> $order->shipping_state,
			'zip' 		=> $order->shipping_postcode,
			'country' 	=> apply_filters('woo_pd_filter_country', $order->shipping_country ),
		) ;

		// BUILD PRINTDROPPER CURL REQUEST

		$printdropper_request = Array( 
			'api_version' => '1.0', // API VERSION 1.0 -- DO NOT ALTER THIS VALUE
			//'api_private_key' => 'redacted', // YOUR API PRIVATE KEY
			'api_test_key' => 'f0e675afd34046783dcc9851f0c10532', // YOUR API TEST KEY
			'return_address' => $printdropper_return_address , // RETURN ADDRESS
			'to_address' => $printdropper_to_address , // TO ADDRESS
			// 'logo_sticker_artwork_url' => NULL , // THE FULL URL TO YOUR LOGO STICKER ARTWORK
			// 'shipping_note_artwork_url' => NULL , // THE FULL URL TO YOUR SHIPPING NOTE ARTWORK
			'purchase_rush_processing' => 0 , // SET THIS VALUE TO 1 TO PURCHASE RUSH PROCESSING
			'purchase_express_shipping' => 0 , // SET THIS VALUE TO 1 TO PURCHASE EXPRESS SHIPPING
			'items' => $printdropper_item_array // ITEM ARRAY
		) ;

		http_build_query_for_curl( $printdropper_request , $printdropper_request_query ) ;


		// CREATE CURL HANDLE AND POST PRINTDROPPER JOB DATA 
		$printdropper_curl_handle = curl_init( "https://printdropper.com/api/create-job" ) ; 
		curl_setopt( $printdropper_curl_handle , CURLOPT_HEADER , 0 ) ;
		curl_setopt( $printdropper_curl_handle , CURLOPT_RETURNTRANSFER , 1 ) ;
		curl_setopt( $printdropper_curl_handle , CURLOPT_POSTFIELDS , $printdropper_request_query ) ;
		$printdropper_json_response = curl_exec( $printdropper_curl_handle ) ;
		curl_close( $printdropper_curl_handle ) ;
		$printdropper_response = json_decode( $printdropper_json_response ) ;


		// CHECK PRINTDROPPER RESPONSE FOR ERROR MESSAGE OR JOB ID - IF $printdropper_response->job_id > 0 THEN JOB HAS BEEN CREATED SUCCESSFULLY
		$printdropper_error = null ;
		if( $printdropper_response->error_code ) {
			$printdropper_error = 'PrintDropper Error #' . $printdropper_response->error_code . ': ' . $printdropper_response->error_message ;
			$order->update_status( 'wc-pdropper-error' );
			$order->add_order_note($printdropper_error);
			if ($woo_pd_order_error_code) {
				$order->add_order_note($woo_pd_order_error_code);
			}
			//update_post_meta($order_id, 'woo_pd_order_error_code', $items );
			return false;
		 // CONSULT THE ERROR MESSAGE FOR MORE DETAILS
		} else if( $printdropper_response->job_id > 0 ) {
		 // JOB CREATION SUCCESSFUL - YOU CAN SAVE $printdropper_response->job_id TO YOUR DATABASE TO QUERY THE STATUS OF YOUR ORDER IN THE FUTURE
		 $printdropper_error = 'Job has been sent to PrintDropper. PRINTDROPPER JOB ID: ' . $printdropper_response->job_id ; // PRINT SUCCESS MESSAGE -- NOT REQUIRED
 		 $order->update_status( 'wc-print-dropper' );
		 $order->add_order_note($printdropper_error);
		 //update_post_meta($order_id, 'woo_pd_order_error_code', $product_id );
		 exit( ) ; // EXIT PROGRAM ON SUCCESS -- NOT REQUIRED
		} else {
		 // THIS SHOULDN'T OCCUR BUT IF IT DOES PLEASE CONSULT ME AT oliver@printdropper.com
		 $printdropper_error = 'PrintDropper Error #503: Data was transmitted to PrintDropper but there was no response. Please consult site admin.' ;
		 $order->update_status( 'wc-pdropper-error' );
		 $order->add_order_note($printdropper_error);
		 // $order->add_order_note($woo_pd_product_id);
		 // update_post_meta($order_id, 'woo_pd_order_error_code', $attributes );
		 //update_post_meta($order_id, 'woo_pd_order_error_code', $print_size );
		}

	}


// Woocommerce stuff

	// Display Fields
	add_action( 'woocommerce_product_options_general_product_data', 'woo_pd_add_custom_general_fields' );

	// Save Fields
	add_action( 'woocommerce_process_product_meta', 'woo_pd_add_custom_general_fields_save' );

	function woo_pd_add_custom_general_fields() {
	  global $woocommerce, $post;
	  
	  echo '<div class="options_group">';
	  
	  // Text Field
		woocommerce_wp_text_input( 
			array( 
				'id'          => '_woo_pd_print_file', 
				'label'       => __( 'Print File', 'woocommerce' ), 
				'placeholder' => 'http://',
				'desc_tip'    => 'true',
				'description' => __( 'URL for full resolution print file for drop printer.', 'woocommerce' ) 
			)
		);
	  
	  echo '</div>';
		
	}

	function woo_pd_add_custom_general_fields_save( $post_id ){

	// Text Field
	$woocommerce_text_field = $_POST['_woo_pd_print_file'];
	if( !empty( $woocommerce_text_field ) )
		update_post_meta( $post_id, '_woo_pd_print_file', esc_attr( $woocommerce_text_field ) );
	}



// 	// Add Variation Settings
// add_action( 'woocommerce_product_after_variable_attributes', 'variation_settings_fields', 10, 3 );

// // Save Variation Settings
// add_action( 'woocommerce_save_product_variation', 'save_variation_settings_fields', 10, 2 );

// /**
//  * Create new fields for variations
//  *
// */
// function variation_settings_fields( $loop, $variation_data, $variation ) {

// 	// Select
// 	woocommerce_wp_select( 
// 	array( 
// 		'id'          => '_woo_pd_product_select[' . $variation->ID . ']', 
// 		'label'       => __( 'Select Product', 'woocommerce' ), 
// 		'description' => __( 'Choose size and finish', 'woocommerce' ),
// 		'value'       => get_post_meta( $variation->ID, '_woo_pd_product_select', true ),
// 		'options' => array(
// 			'54'	=> __( '3.5" x 5" Matte Print', 'woocommerce' ),
// 			'358'	=> __( '4" x 4" Matte Print', 'woocommerce' ),
// 			'125'	=> __( '4" x 6" Matte Print', 'woocommerce' ),
// 			'399'	=> __( '4" x 9" Matte Print', 'woocommerce' ),
// 			'131'	=> __( '5" x 5" Matte Print', 'woocommerce' ),
// 			'1'		=> __( '5" x 7" Matte Print', 'woocommerce' ),
// 			'53'	=> __( '5.8" x 8.3" Matte Print (A5) - 148 mm x 210 mm', 'woocommerce' ),
// 			'147'	=> __( '7" x 7" Matte Print', 'woocommerce' ),
// 			'139'	=> __( '7" x 16" Matte Print', 'woocommerce' ),
// 			'400'	=> __( '8" x 8" Matte Print', 'woocommerce' ),
// 			'2'		=> __( '8" x 10" Matte Print', 'woocommerce' ),
// 			'129'	=> __( '8" x 12" Matte Print', 'woocommerce' ),
// 			'128'	=> __( '8" x 20" Matte Print', 'woocommerce' ),
// 			'52'	=> __( '8.3" x 11.7" Matte Print (A4) - 210 mm x 297 mm', 'woocommerce' ),
// 			'3'		=> __( '8.5" x 11" Matte Print', 'woocommerce' ),
// 			'148'	=> __( '9" x 9" Matte Print', 'woocommerce' ),
// 			'4'		=> __( '9" x 12" Matte Print', 'woocommerce' ),
// 			'403'	=> __( '9.06" x 19.69" Matte Print - 230 mm x 500 mm', 'woocommerce' ),
// 			'394'	=> __( '9.5" x 17" Matte Print', 'woocommerce' ),
// 			'357'	=> __( '10" x 10" Matte Print', 'woocommerce' ),
// 			'141'	=> __( '10" x 12" Matte Print', 'woocommerce' ),
// 			'145'	=> __( '10" x 13" Matte Print', 'woocommerce' ),
// 			'132'	=> __( '10" x 22" Matte Print', 'woocommerce' ),
// 			'5'		=> __( '11" x 14" Matte Print', 'woocommerce' ),
// 			'6'		=> __( '11" x 17" Matte Print', 'woocommerce' ),
// 			'51'	=> __( '11.7" x 16.5" Matte Print (A3) - 297 mm x 420 mm', 'woocommerce' ),
// 			'390'	=> __( '11.81" x 15.75" Matte Print - 300 mm x 400 mm', 'woocommerce' ),
// 			'392'	=> __( '11.81" x 19.69" Matte Print - 300 mm x 500 mm', 'woocommerce' ),
// 			'7'		=> __( '12" x 12" Matte Print', 'woocommerce' ),
// 			'114'	=> __( '12" x 16" Matte Print', 'woocommerce' ),
// 			'8'		=> __( '12" x 18" Matte Print', 'woocommerce' ),
// 			'402'	=> __( '12" x 20" Matte Print', 'woocommerce' ),
// 			'401'	=> __( '12" x 24" Matte Print', 'woocommerce' ),
// 			'127'	=> __( '12" x 36" Matte Print', 'woocommerce' ),
// 			'361'	=> __( '12.6" x 12.6" Matte Print - 320 mm x 320 mm', 'woocommerce' ),
// 			'9'		=> __( '13" x 19" Matte Print', 'woocommerce' ),
// 			'384'	=> __( '14" x 14" Matte Print', 'woocommerce' ),
// 			'143'	=> __( '14" x 18" Matte Print', 'woocommerce' ),
// 			'395'	=> __( '14" x 20" Matte Print', 'woocommerce' ),
// 			'142'	=> __( '14" x 24" Matte Print', 'woocommerce' ),
// 			'385'	=> __( '15.75" x 19.69" Matte Print - 400 mm x 500 mm', 'woocommerce' ),
// 			'150'	=> __( '15.75" x 19.75" Matte Print', 'woocommerce' ),
// 			'123'	=> __( '16" x 16" Matte Print', 'woocommerce' ),
// 			'10'	=> __( '16" x 20" Matte Print', 'woocommerce' ),
// 			'154'	=> __( '16" x 24" Matte Print', 'woocommerce' ),
// 			'50'	=> __( '16.5" x 23.4" Matte Print (A2) - 420 mm x 594 mm', 'woocommerce' ),
// 			'11'	=> __( '18" x 24" Matte Print', 'woocommerce' ),
// 			'360'	=> __( '19.69" x 19.69" Matte Print - 500 mm x 500 mm', 'woocommerce' ),
// 			'126'	=> __( '19.69" x 27.56" Matte Print - 500 mm x 700 mm', 'woocommerce' ),
// 			'349'	=> __( '19.75" x 19.75" Matte Print', 'woocommerce' ),
// 			'122'	=> __( '20" x 20" Matte Print', 'woocommerce' ),
// 			'12'	=> __( '20" x 24" Matte Print', 'woocommerce' ),
// 			'13'	=> __( '20" x 28" Matte Print', 'woocommerce' ),
// 			'47'	=> __( '20" x 30" Matte Print', 'woocommerce' ),
// 			'149'	=> __( '22" x 28" Matte Print', 'woocommerce' ),
// 			'49'	=> __( '23.4" x 33.1" Matte Print (A1) - 594 mm x 841 mm', 'woocommerce' ),
// 			'146'	=> __( '23.63" x 35.44" Matte Print - 600 mm x 900 mm', 'woocommerce' ),
// 			'46'	=> __( '24" x 24" Matte Print', 'woocommerce' ),
// 			'14'	=> __( '24" x 30" Matte Print', 'woocommerce' ),
// 			'405'	=> __( '24" x 35.83" Matte Print - 610 mm x 910 mm', 'woocommerce' ),
// 			'15'	=> __( '24" x 36" Matte Print', 'woocommerce' ),
// 			'95'	=> __( '24" x 40" Matte Print', 'woocommerce' ),
// 			'151'	=> __( '24" x 42" Matte Print', 'woocommerce' ),
// 			'389'	=> __( '27" x 40" Matte Print', 'woocommerce' ),
// 			'356'	=> __( '27.55" x 39.37" Matte Print - 700 mm x 1,000 mm', 'woocommerce' ),
// 			'398'	=> __( '30" x 36" Matte Print', 'woocommerce' ),
// 			'391'	=> __( '30" x 40" Matte Print', 'woocommerce' ),
// 			'404'	=> __( '36" x 36" Matte Print', 'woocommerce' ),
// 			'393'	=> __( '36" x 48" Matte Print', 'woocommerce' ),
// 			'397'	=> __( '39.37" x 51.18" Matte Print - 1,000 mm x 1,300 mm', 'woocommerce' ),
// 			'386'	=> __( '42" x 60" Matte Print', 'woocommerce' ),
// 			'396'	=> __( '4" x 6" Foam Mounted Matte Print', 'woocommerce' ),
// 			'81'	=> __( '5" x 7" Foam Mounted Matte Print', 'woocommerce' ),
// 			'82'	=> __( '5.8" x 8.3" Foam Mounted Matte Print (A5)', 'woocommerce' ),
// 			'138'	=> __( '6" x 9" Foam Mounted Matte Print', 'woocommerce' ),
// 			'83'	=> __( '8" x 10" Foam Mounted Matte Print', 'woocommerce' ),
// 			'84'	=> __( '8.3" x 11.7" Foam Mounted Matte Print (A4)', 'woocommerce' ),
// 			'85'	=> __( '8.5" x 11" Foam Mounted Matte Print', 'woocommerce' ),
// 			'86'	=> __( '9" x 12" Foam Mounted Matte Print', 'woocommerce' ),
// 			'87'	=> __( '11" x 14" Foam Mounted Matte Print', 'woocommerce' ),
// 			'88'	=> __( '11" x 17" Foam Mounted Matte Print', 'woocommerce' ),
// 			'89'	=> __( '11.7" x 16.5" Foam Mounted Matte Print (A3)', 'woocommerce' ),
// 			'115'	=> __( '12" x 16" Foam Mounted Matte Print', 'woocommerce' ),
// 			'90'	=> __( '12" x 18" Foam Mounted Matte Print', 'woocommerce' ),
// 			'152'	=> __( '13" x 18" Foam Mounted Matte Print', 'woocommerce' ),
// 			'91'	=> __( '13" x 19" Foam Mounted Matte Print', 'woocommerce' ),
// 			'144'	=> __( '16" x 16" Foam Mounted Matte Print', 'woocommerce' ),
// 			'40'	=> __( '16" x 20" Foam Mounted Matte Print', 'woocommerce' ),
// 			'352'	=> __( '16" x 30" Foam Mounted Matte Print', 'woocommerce' ),
// 			'92'	=> __( '16.5" x 23.4" Foam Mounted Matte Print (A2)', 'woocommerce' ),
// 			'41'	=> __( '18" x 24" Foam Mounted Matte Print', 'woocommerce' ),
// 			'42'	=> __( '20" x 24" Foam Mounted Matte Print', 'woocommerce' ),
// 			'43'	=> __( '20" x 28" Foam Mounted Matte Print', 'woocommerce' ),
// 			'121'	=> __( '20" x 30" Foam Mounted Matte Print', 'woocommerce' ),
// 			'153'	=> __( '21" x 30.5" Foam Mounted Matte Print', 'woocommerce' ),
// 			'93'	=> __( '23.4" x 33.1" Foam Mounted Matte Print (A1)', 'woocommerce' ),
// 			'44'	=> __( '24" x 30" Foam Mounted Matte Print', 'woocommerce' ),
// 			'45'	=> __( '24" x 36" Foam Mounted Matte Print', 'woocommerce' ),
// 			'359'	=> __( '32" x 32" Foam Mounted Matte Print', 'woocommerce' ),
// 			'136'	=> __( '6" x 6" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'133'	=> __( '6" x 8" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'16'	=> __( '8" x 8" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'17'	=> __( '8" x 10" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'18'	=> __( '8" x 12" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'19'	=> __( '8" x 14" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'20'	=> __( '10" x 10" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'21'	=> __( '10" x 12" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'22'	=> __( '10" x 14" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'23'	=> __( '10" x 16" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'24'	=> __( '10" x 20" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'48'	=> __( '11" x 14" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'25'	=> __( '12" x 12" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'26'	=> __( '12" x 16" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'155'	=> __( '12" x 18" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'27'	=> __( '12" x 20" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'28'	=> __( '12" x 24" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'344'	=> __( '12" x 36" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'29'	=> __( '14" x 14" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'30'	=> __( '14" x 20" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'31'	=> __( '14" x 24" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'32'	=> __( '14" x 30" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'33'	=> __( '16" x 16" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'34'	=> __( '16" x 20" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'35'	=> __( '16" x 24" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'36'	=> __( '16" x 30" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'343'	=> __( '16" x 36" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'350'	=> __( '18" x 18" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'94'	=> __( '18" x 24" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'351'	=> __( '18" x 30" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'37'	=> __( '20" x 20" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'38'	=> __( '20" x 24" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'39'	=> __( '20" x 30" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'329'	=> __( '24" x 24" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'328'	=> __( '24" x 30" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'327'	=> __( '24" x 36" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'330'	=> __( '30" x 30" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'331'	=> __( '30" x 36" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'353'	=> __( '30" x 40" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'413'	=> __( '30" x 48" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'332'	=> __( '36" x 36" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'354'	=> __( '36" x 40" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'414'	=> __( '36" x 48" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'355'	=> __( '40" x 40" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'415'	=> __( '40" x 48" Canvas - 0.75" Depth', 'woocommerce' ),
// 			'137'	=> __( '6" x 6" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'134'	=> __( '6" x 8" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'55'	=> __( '8" x 8" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'56'	=> __( '8" x 10" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'57'	=> __( '8" x 12" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'58'	=> __( '8" x 14" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'59'	=> __( '10" x 10" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'60'	=> __( '10" x 12" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'61'	=> __( '10" x 14" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'62'	=> __( '10" x 16" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'63'	=> __( '10" x 20" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'64'	=> __( '11" x 14" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'65'	=> __( '12" x 12" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'66'	=> __( '12" x 16" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'67'	=> __( '12" x 18" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'68'	=> __( '12" x 20" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'69'	=> __( '12" x 24" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'348'	=> __( '12" x 36" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'70'	=> __( '14" x 14" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'71'	=> __( '14" x 20" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'72'	=> __( '14" x 24" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'73'	=> __( '14" x 30" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'74'	=> __( '16" x 16" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'75'	=> __( '16" x 20" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'76'	=> __( '16" x 24" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'77'	=> __( '16" x 30" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'124'	=> __( '18" x 24" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'78'	=> __( '20" x 20" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'79'	=> __( '20" x 24" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'80'	=> __( '20" x 30" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'333'	=> __( '24" x 24" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'334'	=> __( '24" x 30" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'335'	=> __( '24" x 36" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'336'	=> __( '30" x 30" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'337'	=> __( '30" x 36" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'387'	=> __( '30" x 40" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'416'	=> __( '30" x 48" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'338'	=> __( '36" x 36" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'417'	=> __( '36" x 48" Canvas - 1.5" Depth', 'woocommerce' ),
// 			'96'	=> __( '8" x 8" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'97'	=> __( '8" x 10" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'98'	=> __( '8" x 12" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'99'	=> __( '10" x 10" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'100'	=> __( '10" x 12" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'101'	=> __( '10" x 14" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'102'	=> __( '11" x 14" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'103'	=> __( '12" x 12" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'104'	=> __( '12" x 16" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'105'	=> __( '12" x 18" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'106'	=> __( '12" x 20" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'107'	=> __( '16" x 16" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'108'	=> __( '16" x 20" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'109'	=> __( '16" x 24" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'110'	=> __( '18" x 24" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'111'	=> __( '20" x 20" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'112'	=> __( '20" x 24" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'113'	=> __( '20" x 30" Rolled Canvas - 0.75" Edge', 'woocommerce' ),
// 			'156'	=> __( '3.5" x 5" Gloss Print', 'woocommerce' ),
// 			'157'	=> __( '4" x 6" Gloss Print', 'woocommerce' ),
// 			'158'	=> __( '5" x 5" Gloss Print', 'woocommerce' ),
// 			'159'	=> __( '5" x 7" Gloss Print', 'woocommerce' ),
// 			'160'	=> __( '5.8" x 8.3" Gloss Print (A5) - 148 mm x 210 mm', 'woocommerce' ),
// 			'161'	=> __( '7" x 7" Gloss Print', 'woocommerce' ),
// 			'162'	=> __( '7" x 16" Gloss Print', 'woocommerce' ),
// 			'163'	=> __( '8" x 10" Gloss Print', 'woocommerce' ),
// 			'164'	=> __( '8" x 12" Gloss Print', 'woocommerce' ),
// 			'165'	=> __( '8" x 20" Gloss Print', 'woocommerce' ),
// 			'166'	=> __( '8.3" x 11.7" Gloss Print (A4) - 210 mm x 297 mm', 'woocommerce' ),
// 			'167'	=> __( '8.5" x 11" Gloss Print', 'woocommerce' ),
// 			'168'	=> __( '9" x 9" Gloss Print', 'woocommerce' ),
// 			'169'	=> __( '9" x 12" Gloss Print', 'woocommerce' ),
// 			'170'	=> __( '10" x 12" Gloss Print', 'woocommerce' ),
// 			'171'	=> __( '10" x 13" Gloss Print', 'woocommerce' ),
// 			'172'	=> __( '10" x 22" Gloss Print', 'woocommerce' ),
// 			'173'	=> __( '11" x 14" Gloss Print', 'woocommerce' ),
// 			'174'	=> __( '11" x 17" Gloss Print', 'woocommerce' ),
// 			'175'	=> __( '11.7" x 16.5" Gloss Print (A3) - 297 mm x 420 mm', 'woocommerce' ),
// 			'176'	=> __( '12" x 12" Gloss Print', 'woocommerce' ),
// 			'177'	=> __( '12" x 16" Gloss Print', 'woocommerce' ),
// 			'178'	=> __( '12" x 18" Gloss Print', 'woocommerce' ),
// 			'179'	=> __( '12" x 36" Gloss Print', 'woocommerce' ),
// 			'180'	=> __( '13" x 19" Gloss Print', 'woocommerce' ),
// 			'181'	=> __( '14" x 18" Gloss Print', 'woocommerce' ),
// 			'182'	=> __( '14" x 24" Gloss Print', 'woocommerce' ),
// 			'183'	=> __( '15.75" x 19.75" Gloss Print', 'woocommerce' ),
// 			'184'	=> __( '16" x 16" Gloss Print', 'woocommerce' ),
// 			'185'	=> __( '16" x 20" Gloss Print', 'woocommerce' ),
// 			'186'	=> __( '16" x 24" Gloss Print', 'woocommerce' ),
// 			'187'	=> __( '16.5" x 23.4" Gloss Print (A2) - 420 mm x 594 mm', 'woocommerce' ),
// 			'188'	=> __( '18" x 24" Gloss Print', 'woocommerce' ),
// 			'189'	=> __( '19.69" x 27.56" Gloss Print - 500 mm x 700 mm', 'woocommerce' ),
// 			'190'	=> __( '20" x 20" Gloss Print', 'woocommerce' ),
// 			'191'	=> __( '20" x 24" Gloss Print', 'woocommerce' ),
// 			'192'	=> __( '20" x 28" Gloss Print', 'woocommerce' ),
// 			'193'	=> __( '20" x 30" Gloss Print', 'woocommerce' ),
// 			'194'	=> __( '22" x 28" Gloss Print', 'woocommerce' ),
// 			'195'	=> __( '23.4" x 33.1" Gloss Print (A1) - 594 mm x 841 mm', 'woocommerce' ),
// 			'196'	=> __( '23.63" x 35.44" Gloss Print - 600 mm x 900 mm', 'woocommerce' ),
// 			'197'	=> __( '24" x 24" Gloss Print', 'woocommerce' ),
// 			'198'	=> __( '24" x 30" Gloss Print', 'woocommerce' ),
// 			'199'	=> __( '24" x 36" Gloss Print', 'woocommerce' ),
// 			'200'	=> __( '24" x 40" Gloss Print', 'woocommerce' ),
// 			'201'	=> __( '24" x 42" Gloss Print', 'woocommerce' ),
// 			'202'	=> __( '3.5" x 5" Lustre Print', 'woocommerce' ),
// 			'203'	=> __( '4" x 6" Lustre Print', 'woocommerce' ),
// 			'204'	=> __( '5" x 5" Lustre Print', 'woocommerce' ),
// 			'205'	=> __( '5" x 7" Lustre Print', 'woocommerce' ),
// 			'206'	=> __( '5.8" x 8.3" Lustre Print (A5) - 148 mm x 210 mm', 'woocommerce' ),
// 			'207'	=> __( '7" x 7" Lustre Print', 'woocommerce' ),
// 			'208'	=> __( '7" x 16" Lustre Print', 'woocommerce' ),
// 			'209'	=> __( '8" x 10" Lustre Print', 'woocommerce' ),
// 			'210'	=> __( '8" x 12" Lustre Print', 'woocommerce' ),
// 			'211'	=> __( '8" x 20" Lustre Print', 'woocommerce' ),
// 			'212'	=> __( '8.3" x 11.7" Lustre Print (A4) - 210 mm x 297 mm', 'woocommerce' ),
// 			'213'	=> __( '8.5" x 11" Lustre Print', 'woocommerce' ),
// 			'214'	=> __( '9" x 9" Lustre Print', 'woocommerce' ),
// 			'215'	=> __( '9" x 12" Lustre Print', 'woocommerce' ),
// 			'388'	=> __( '10" x 10" Lustre Print', 'woocommerce' ),
// 			'216'	=> __( '10" x 12" Lustre Print', 'woocommerce' ),
// 			'217'	=> __( '10" x 13" Lustre Print', 'woocommerce' ),
// 			'218'	=> __( '10" x 22" Lustre Print', 'woocommerce' ),
// 			'219'	=> __( '11" x 14" Lustre Print', 'woocommerce' ),
// 			'220'	=> __( '11" x 17" Lustre Print', 'woocommerce' ),
// 			'221'	=> __( '11.7" x 16.5" Lustre Print (A3) - 297 mm x 420 mm', 'woocommerce' ),
// 			'222'	=> __( '12" x 12" Lustre Print', 'woocommerce' ),
// 			'223'	=> __( '12" x 16" Lustre Print', 'woocommerce' ),
// 			'224'	=> __( '12" x 18" Lustre Print', 'woocommerce' ),
// 			'225'	=> __( '12" x 36" Lustre Print', 'woocommerce' ),
// 			'226'	=> __( '13" x 19" Lustre Print', 'woocommerce' ),
// 			'227'	=> __( '14" x 18" Lustre Print', 'woocommerce' ),
// 			'228'	=> __( '14" x 24" Lustre Print', 'woocommerce' ),
// 			'229'	=> __( '15.75" x 19.75" Lustre Print', 'woocommerce' ),
// 			'230'	=> __( '16" x 16" Lustre Print', 'woocommerce' ),
// 			'231'	=> __( '16" x 20" Lustre Print', 'woocommerce' ),
// 			'232'	=> __( '16" x 24" Lustre Print', 'woocommerce' ),
// 			'233'	=> __( '16.5" x 23.4" Lustre Print (A2) - 420 mm x 594 mm', 'woocommerce' ),
// 			'234'	=> __( '18" x 24" Lustre Print', 'woocommerce' ),
// 			'235'	=> __( '19.69" x 27.56" Lustre Print - 500 mm x 700 mm', 'woocommerce' ),
// 			'236'	=> __( '20" x 20" Lustre Print', 'woocommerce' ),
// 			'237'	=> __( '20" x 24" Lustre Print', 'woocommerce' ),
// 			'238'	=> __( '20" x 28" Lustre Print', 'woocommerce' ),
// 			'239'	=> __( '20" x 30" Lustre Print', 'woocommerce' ),
// 			'240'	=> __( '22" x 28" Lustre Print', 'woocommerce' ),
// 			'241'	=> __( '23.4" x 33.1" Lustre Print (A1) - 594 mm x 841 mm', 'woocommerce' ),
// 			'242'	=> __( '23.63" x 35.44" Lustre Print - 600 mm x 900 mm', 'woocommerce' ),
// 			'243'	=> __( '24" x 24" Lustre Print', 'woocommerce' ),
// 			'244'	=> __( '24" x 30" Lustre Print', 'woocommerce' ),
// 			'245'	=> __( '24" x 36" Lustre Print', 'woocommerce' ),
// 			'246'	=> __( '24" x 40" Lustre Print', 'woocommerce' ),
// 			'247'	=> __( '24" x 42" Lustre Print', 'woocommerce' ),
// 			'273'	=> __( '5" x 7" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'274'	=> __( '5.8" x 8.3" Gatorboard Mounted Matte Print (A5)', 'woocommerce' ),
// 			'275'	=> __( '6" x 9" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'276'	=> __( '8" x 10" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'277'	=> __( '8.3" x 11.7" Gatorboard Mounted Matte Print (A4)', 'woocommerce' ),
// 			'278'	=> __( '8.5" x 11" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'279'	=> __( '9" x 12" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'280'	=> __( '11" x 14" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'281'	=> __( '11" x 17" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'282'	=> __( '11.7" x 16.5" Gatorboard Mounted Matte Print (A3)', 'woocommerce' ),
// 			'283'	=> __( '12" x 16" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'284'	=> __( '12" x 18" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'285'	=> __( '13" x 18" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'286'	=> __( '13" x 19" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'287'	=> __( '16" x 16" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'288'	=> __( '16" x 20" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'289'	=> __( '16.5" x 23.4" Gatorboard Mounted Matte Print (A2)', 'woocommerce' ),
// 			'290'	=> __( '18" x 24" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'291'	=> __( '20" x 24" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'292'	=> __( '20" x 28" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'293'	=> __( '20" x 30" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'294'	=> __( '21" x 30.5" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'295'	=> __( '23.4" x 33.1" Gatorboard Mounted Matte Print (A1)', 'woocommerce' ),
// 			'296'	=> __( '24" x 30" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'297'	=> __( '24" x 36" Gatorboard Mounted Matte Print', 'woocommerce' ),
// 			'298'	=> __( '5" x 7" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'299'	=> __( '5.8" x 8.3" Styrene Mounted Matte Print (A5)', 'woocommerce' ),
// 			'300'	=> __( '6" x 9" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'301'	=> __( '8" x 10" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'302'	=> __( '8.3" x 11.7" Styrene Mounted Matte Print (A4)', 'woocommerce' ),
// 			'303'	=> __( '8.5" x 11" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'304'	=> __( '9" x 12" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'305'	=> __( '11" x 14" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'306'	=> __( '11" x 17" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'307'	=> __( '11.7" x 16.5" Styrene Mounted Matte Print (A3)', 'woocommerce' ),
// 			'308'	=> __( '12" x 16" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'309'	=> __( '12" x 18" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'310'	=> __( '13" x 18" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'311'	=> __( '13" x 19" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'312'	=> __( '16" x 16" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'313'	=> __( '16" x 20" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'314'	=> __( '16.5" x 23.4" Styrene Mounted Matte Print (A2)', 'woocommerce' ),
// 			'315'	=> __( '18" x 24" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'316'	=> __( '20" x 24" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'317'	=> __( '20" x 28" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'318'	=> __( '20" x 30" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'319'	=> __( '21" x 30.5" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'320'	=> __( '23.4" x 33.1" Styrene Mounted Matte Print (A1)', 'woocommerce' ),
// 			'321'	=> __( '24" x 30" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'322'	=> __( '24" x 36" Styrene Mounted Matte Print', 'woocommerce' ),
// 			'406'	=> __( '3.25" x 6.5" 2.85" Round Porcelain Christmas Ornament With String', 'woocommerce' ),
// 			'407'	=> __( '3.75" x 9" 11oz White Ceramic Mug', 'woocommerce' ),
// 			'408'	=> __( '4.5" x 9" 15oz White Ceramic Mug', 'woocommerce' ),
// 			'409'	=> __( '8" x 10" Natural Maple Wood Print', 'woocommerce' ),
// 			'410'	=> __( '11" x 14" Natural Maple Wood Print', 'woocommerce' ),
// 			'411'	=> __( '8" x 10" Aluminum Gloss Metal Print', 'woocommerce' ),
// 			'412'	=> __( '11" x 14" Aluminum Gloss Metal Print', 'woocommerce' ),
// 			'140'	=> __( '4" x 6" Invitation', 'woocommerce' ),
// 			'130'	=> __( '5" x 7" Invitation', 'woocommerce' ),
// 			'323'	=> __( '8" x 10" Matte Print - Black Frame', 'woocommerce' ),
// 			'347'	=> __( '8.5" x 11" Matte Print - Black Frame', 'woocommerce' ),
// 			'324'	=> __( '11" x 14" Matte Print - Black Frame', 'woocommerce' ),
// 			'339'	=> __( '11" x 17" Matte Print - Black Frame', 'woocommerce' ),
// 			'342'	=> __( '12" x 16" Matte Print - Black Frame', 'woocommerce' ),
// 			'346'	=> __( '12" x 18" Matte Print - Black Frame', 'woocommerce' ),
// 			'345'	=> __( '13" x 19" Matte Print - Black Frame', 'woocommerce' ),
// 			'325'	=> __( '16" x 20" Matte Print - Black Frame', 'woocommerce' ),
// 			'326'	=> __( '18" x 24" Matte Print - Black Frame', 'woocommerce' ),
// 			'340'	=> __( '20" x 30" Matte Print - Black Frame', 'woocommerce' ),
// 			'341'	=> __( '24" x 36" Matte Print - Black Frame', 'woocommerce' ),
// 			'362'	=> __( '8" x 10" Matte Print - White Frame', 'woocommerce' ),
// 			'373'	=> __( '8" x 10" Matte Print - Wood Frame', 'woocommerce' ),
// 			'374'	=> __( '8.5" x 11" Matte Print - Wood Frame', 'woocommerce' ),
// 			'363'	=> __( '8.5" x 11" Matte Print - White Frame', 'woocommerce' ),
// 			'375'	=> __( '11" x 14" Matte Print - Wood Frame', 'woocommerce' ),
// 			'364'	=> __( '11" x 14" Matte Print - White Frame', 'woocommerce' ),
// 			'376'	=> __( '11" x 17" Matte Print - Wood Frame', 'woocommerce' ),
// 			'365'	=> __( '11" x 17" Matte Print - White Frame', 'woocommerce' ),
// 			'366'	=> __( '12" x 16" Matte Print - White Frame', 'woocommerce' ),
// 			'377'	=> __( '12" x 16" Matte Print - Wood Frame', 'woocommerce' ),
// 			'367'	=> __( '12" x 18" Matte Print - White Frame', 'woocommerce' ),
// 			'378'	=> __( '12" x 18" Matte Print - Wood Frame', 'woocommerce' ),
// 			'368'	=> __( '13" x 19" Matte Print - White Frame', 'woocommerce' ),
// 			'379'	=> __( '13" x 19" Matte Print - Wood Frame', 'woocommerce' ),
// 			'380'	=> __( '16" x 20" Matte Print - Wood Frame', 'woocommerce' ),
// 			'369'	=> __( '16" x 20" Matte Print - White Frame', 'woocommerce' ),
// 			'370'	=> __( '18" x 24" Matte Print - White Frame', 'woocommerce' ),
// 			'381'	=> __( '18" x 24" Matte Print - Wood Frame', 'woocommerce' ),
// 			'382'	=> __( '20" x 30" Matte Print - Wood Frame', 'woocommerce' ),
// 			'371'	=> __( '20" x 30" Matte Print - White Frame', 'woocommerce' ),
// 			'372'	=> __( '24" x 36" Matte Print - White Frame', 'woocommerce' ),
// 			'383'	=> __( '24" x 36" Matte Print - Wood Frame', 'woocommerce' ),
// 			)
// 		)
// 	);

// }

// /**
//  * Save new fields for variations
//  *
// */
// function save_variation_settings_fields( $post_id ) {
	
// 	// Select
// 	$select = $_POST['_woo_pd_product_select'][ $post_id ];
// 	if( ! empty( $select ) ) {
// 		update_post_meta( $post_id, '_woo_pd_product_select', esc_attr( $select ) );
// 	}

// }



/** 
 * Register new status
**/
function register_woo_pd_new_order_statuses() {
    register_post_status( 'wc-print-dropper', array(
        'label'                     => 'Submitted to PrintDropper',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Submitted to PrintDropper <span class="count">(%s)</span>', 'Submitted to PrintDropper <span class="count">(%s)</span>' )
    ) );

    register_post_status( 'wc-pdropper-error', array(
        'label'                     => 'PrintDropper Error',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'PrintDropper Error <span class="count">(%s)</span>', 'PrintDropper Error <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'register_woo_pd_new_order_statuses' );



// Add to list of WC Order statuses
function add_printdropper_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-print-dropper'] = 'Submitted to PrintDropper';
        	$new_order_statuses['wc-pdropper-error'] = 'PrintDropper Error';
        }
    }

    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_printdropper_to_order_statuses' );



/**
 * Adds icons for any custom order statuses
**/
add_action( 'wp_print_scripts', 'woo_pd_add_custom_order_status_icon' );
function woo_pd_add_custom_order_status_icon() {
	
	if( ! is_admin() ) { 
		return; 
	}
	
	?> <style>
		/* Add custom status order icons */
		.column-order_status mark.print-dropper,
		.column-order_status mark.pdropper-error {
			content: url(/wp-content/uploads/2016/11/printdropper.png);
		}

		.column-order_status mark.pdropper-error {
			-webkit-filter: hue-rotate(150deg);
		    filter: hue-rotate(150deg);
		}
	
		/* Repeat for each different icon; tie to the correct status */
 
	</style> <?php
}	

?>