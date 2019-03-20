<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/Stripe/Classes
 * @version  3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_Stripe_Payment' ) ) {
	/**
	 * Class LP_Addon_Stripe_Payment
	 */
	class LP_Addon_Stripe_Advanced_Payment extends LP_Addon {

		/**
		 * @var string
		 */
		public $version = LP_ADDON_STRIPE_ADVANCED_PAYMENT_VER;

		/**
		 * @var string
		 */
		public $require_version = LP_ADDON_STRIPE_ADVANCED_PAYMENT_REQUIRE_VER;

		/**
		 * @var null
		 */
		protected $coupon_codes = null;

		/**
		 * LP_Addon_Stripe_Payment constructor.
		 */
		public function __construct() {

			parent::__construct();
		}

		/**
		 * Define Learnpress Stripe payment constants.
		 *
		 * @since 3.0.0
		 */
		protected function _define_constants() {
			define( 'LP_ADDON_STRIPE_ADVANCED_PAYMENT_PATH', dirname( LP_ADDON_STRIPE_ADVANCED_PAYMENT_FILE ) );
			define( 'LP_ADDON_STRIPE_ADVANCED_PAYMENT_INC', LP_ADDON_STRIPE_ADVANCED_PAYMENT_PATH . '/inc/' );
			define( 'LP_ADDON_STRIPE_ADVANCED_PAYMENT_URL', plugin_dir_url( LP_ADDON_STRIPE_ADVANCED_PAYMENT_FILE ) );
			define( 'LP_ADDON_STRIPE_ADVANCED_PAYMENT_TEMPLATE', LP_ADDON_STRIPE_ADVANCED_PAYMENT_PATH . '/templates/' );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 3.0.0
		 */
		protected function _includes() {
			include_once LP_ADDON_STRIPE_ADVANCED_PAYMENT_INC . 'functions.php';
			include_once LP_ADDON_STRIPE_ADVANCED_PAYMENT_INC . 'class-lp-gateway-stripe.php';
		}

		/**
		 * Init hooks.
		 */
		protected function _init_hooks() {
			// add payment gateway class
			add_filter( 'learn_press_payment_method', array( $this, 'add_payment' ) );
			add_filter( 'learn-press/payment-methods', array( $this, 'add_payment' ) );

			$this->coupon_codes = learn_press_get_coupon_codes();

			LP_Request_Handler::register_ajax( 'apply_coupon', array( $this, 'apply_coupon' ) );
		}

		/**
		 * Enqueue assets.
		 *
		 * @since 3.0.0
		 */
		protected function _enqueue_assets() {

			if ( learn_press_is_checkout() || is_singular( array( 'lp_course' ) ) ) {
				$user = learn_press_get_current_user();

				learn_press_assets()->enqueue_script( 'learn-press-payment', $this->get_plugin_url( 'assets/js/payment.js' ), array() );
				learn_press_assets()->enqueue_script( 'learn-press-stripe', $this->get_plugin_url( 'assets/js/stripe.js' ), array() );
				learn_press_assets()->enqueue_script( 'learn-press-coupon', $this->get_plugin_url( 'assets/js/coupon.js' ), array('jquery') );

				learn_press_assets()->enqueue_style( 'learn-press-stripe', $this->get_plugin_url( 'assets/css/style.css' ), array() );

				$data = array(
					'publish_key' => $this->settings['publish_key'],
					'plugin_url'  => plugins_url( '', LP_ADDON_STRIPE_ADVANCED_PAYMENT_FILE ),
					'test_mode'   => $this->settings['test_mode'],
					'card_name'   => $user->user->data->display_name
				);
				wp_localize_script( 'learn-press-stripe', 'learn_press_stripe_info', $data );
			}

			if( ! is_admin() ) {

				learn_press_assets()->enqueue_script( 'learn-press-coupon', $this->get_plugin_url( 'assets/js/coupon.js' ), array('jquery') );
				learn_press_assets()->enqueue_style( 'learn-press-stripe', $this->get_plugin_url( 'assets/css/style.css' ), array() );
			}

			
		}

		/**
		 * Add Stripe to payment system.
		 *
		 * @param $methods
		 *
		 * @return mixed
		 */
		public function add_payment( $methods ) {
			$methods['stripe'] = 'LP_Gateway_Stripe';

			return $methods;
		}

		public function apply_coupon() {

			sleep( 1 );

			if( ! isset( $_POST['coupon_code'] ) ) {
				return;
			}

	
			$coupon_code 	= (string) strtolower( sanitize_text_field( $_POST['coupon_code'] ) );
			$cart			= LP()->cart;

			if( ! $coupon_code ) {

				return;

			}

			if( $cart->is_empty() ) {

				$data = json_encode( array( 

						'error' => __( 'Coupon code can\'t be applied to an empty cart.', 'learnpress-stripe' ),
						'old_amount_formatted' => learn_press_format_price( $cart->total, true )
				) );
				wp_send_json_error( $data );
				die;

			}

			if( ! array_key_exists( $coupon_code, $this->coupon_codes ) ) {

				$data = json_encode( array( 

						'error' => __( 'Coupon code doesn\'t exist.', 'learnpress-stripe' ),
						'old_amount_formatted' => learn_press_format_price( $cart->total, true )
				) );
				wp_send_json_error( $data );
				die;

			}

			$amount = $cart->total;
			$discount = $this->coupon_codes[ $coupon_code ][ 'discount' ];
			$discount_amount = $amount * $discount;
			$discount_percentage = $discount * 100 . '%';
			$final_amount = $amount - intval( $discount_amount ); 
			$order_id = absint( LP()->session->get( 'order_awaiting_payment' ) );

			$cart->total = $final_amount;

			LP()->session->set( 'coupon_code', $coupon_code );

			learn_press_send_json(
				array(
					'old_amount'  				=> $amount,
					'old_amount_formatted'		=> learn_press_format_price( $amount, true ),
					'discount_percentage'   	=> $discount_percentage,
					'final_amount'				=> $final_amount,
					'final_amount_formatted'	=> learn_press_format_price( $final_amount, true ),
					'success'					=> true
				)
			);

		}

		/**
		 * Plugin links.
		 *
		 * @return array
		 */
		public function plugin_links() {
			$links[] = '<a href="' . admin_url( 'admin.php?page=learn-press-settings&tab=payments&section=stripe' ) . '">' . __( 'Settings', 'learnpress-stripe' ) . '</a>';

			return $links;
		}
	}
}