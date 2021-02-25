<?php
/**
 * Part of Woo Mercado Pago Module
 * Author - Mercado Pago
 * Developer
 * Copyright - Copyright(c) MercadoPago [https://www.mercadopago.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * @package MercadoPago
 * @category Includes
 * @author Mercado Pago
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_WooMercadoPago_Pix_Gateway
 */
class WC_WooMercadoPago_Pix_Gateway extends WC_WooMercadoPago_Payment_Abstract {

	const ID = 'woo-mercado-pago-pix';

	/**
	 * WC_WooMercadoPago_PixGateway constructor.
	 *
	 * @throws WC_WooMercadoPago_Exception Load payment exception.
	 */
	public function __construct() {
		$this->id = self::ID;

		if ( ! $this->validate_section() ) {
			return;
		}

		$this->description        = __( 'Accept cash payments within the custom checkout and expand your customers purchase options.', 'woocommerce-mercadopago' );
		$this->form_fields        = array();
		$this->method_title       = __( 'Mercado Pago - Custom Checkout', 'woocommerce-mercadopago' );
		$this->title              = __( 'Pay with PIX', 'woocommerce-mercadopago' );
		$this->method_description = $this->get_method_mp_description( $this->description );
		$this->date_expiration    = (int) $this->get_option_mp( 'date_expiration', 3 );
		$this->type_payments      = $this->get_option_mp( 'type_payments', 'no' );
		$this->payment_type       = 'pix';
		$this->checkout_type      = 'custom';
		$this->activated_payment  = $this->get_activated_payment();
		$this->field_forms_order  = $this->get_fields_sequence();
		parent::__construct();
		$this->form_fields         = $this->get_form_mp_fields( 'Pix' );
		$this->hook                = new WC_WooMercadoPago_Hook_Pix( $this );
		$this->notification        = new WC_WooMercadoPago_Notification_Webhook( $this );
		$this->currency_convertion = true;
	}

	/**
	 * Get form mp fields
	 *
	 * @param string $label Label.
	 * @return array
	 */
	public function get_form_mp_fields( $label ) {
		if ( is_admin() && $this->is_manage_section() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script(
				'woocommerce-mercadopago-pix-config-script',
				plugins_url( '../assets/js/pix_config_mercadopago' . $suffix . '.js', plugin_dir_path( __FILE__ ) ),
				array(),
				WC_WooMercadoPago_Constants::VERSION,
				false
			);
		}

		if ( empty( $this->checkout_country ) ) {
			$this->field_forms_order = array_slice( $this->field_forms_order, 0, 7 );
		}

		if ( ! empty( $this->checkout_country ) && empty( $this->get_access_token() ) && empty( $this->get_public_key() ) ) {
			$this->field_forms_order = array_slice( $this->field_forms_order, 0, 22 );
		}

		$form_fields                        = array();
		$form_fields['checkout_pix_header'] = $this->field_checkout_pix_header();
		if ( ! empty( $this->checkout_country ) && ! empty( $this->get_access_token() ) && ! empty( $this->get_public_key() ) ) {
			$form_fields['checkout_pix_options_title']           = $this->field_checkout_pix_options_title();
			$form_fields['checkout_pix_payments_title']          = $this->field_checkout_pix_payments_title();
			$form_fields['checkout_pix_payments_description']    = $this->field_checkout_pix_options_description();
			$form_fields['checkout_pix_payments_advanced_title'] = $this->field_checkout_pix_payments_advanced_title();
			$form_fields['date_expiration']                      = $this->field_date_expiration();
			foreach ( $this->field_pix_payments() as $key => $value ) {
				$form_fields[ $key ] = $value;
			}
		}

		$form_fields_abs = parent::get_form_mp_fields( $label );
		if ( 1 === count( $form_fields_abs ) ) {
			return $form_fields_abs;
		}
		$form_fields_merge = array_merge( $form_fields_abs, $form_fields );
		$fields            = $this->sort_form_fields( $form_fields_merge, $this->field_forms_order );

		return $fields;
	}

	/**
	 * Get fields sequence
	 *
	 * @return array
	 */
	public function get_fields_sequence() {
		return array(
			// Necessary to run.
			'title',
			'description',
			// Checkout de pagos con dinero en efectivo<br> Aceptá pagos al instante y maximizá la conversión de tu negocio.
			'checkout_pix_header',
			'checkout_steps',
			// ¿En qué país vas a activar tu tienda?
			'checkout_country_title',
			'checkout_country',
			'checkout_btn_save',
			// Carga tus credenciales.
			'checkout_credential_title',
			'checkout_credential_mod_test_title',
			'checkout_credential_mod_test_description',
			'checkout_credential_mod_prod_title',
			'checkout_credential_mod_prod_description',
			'checkout_credential_prod',
			'checkout_credential_link',
			'checkout_credential_title_test',
			'checkout_credential_description_test',
			'_mp_public_key_test',
			'_mp_access_token_test',
			'checkout_credential_title_prod',
			'checkout_credential_description_prod',
			'_mp_public_key_prod',
			'_mp_access_token_prod',
			// No olvides de homologar tu cuenta.
			'checkout_homolog_title',
			'checkout_homolog_subtitle',
			'checkout_homolog_link',
			// Set up the payment experience in your store.
			'checkout_pix_options_title',
			'mp_statement_descriptor',
			'_mp_category_id',
			'_mp_store_identificator',
			'_mp_integrator_id',
			// Advanced settings.
			'checkout_advanced_settings',
			'_mp_debug_mode',
			'_mp_custom_domain',
			// Configure the personalized payment experience in your store.
			'checkout_pix_payments_title',
			'checkout_payments_subtitle',
			'checkout_pix_payments_description',
			'enabled',
			WC_WooMercadoPago_Helpers_CurrencyConverter::CONFIG_KEY,
			'field_pix_payments',
			'date_expiration',
			// Advanced configuration of the personalized payment experience.
			'checkout_pix_payments_advanced_title',
			'checkout_payments_advanced_description',
			'gateway_discount',
			'commission',
			// Support session.
			'checkout_support_title',
			'checkout_support_description',
			'checkout_support_description_link',
			'checkout_support_problem',
			// Everything ready for the takeoff of your sales?
			'checkout_ready_title',
			'checkout_ready_description',
			'checkout_ready_description_link',
		);
	}

	/**
	 * Get activated payment
	 *
	 * @return array
	 */
	public static function get_activated_payment() {
		$activated_payment          = array();
		$get_payment_methods_ticket = get_option( '_all_payment_methods_ticket', '' );

		if ( ! empty( $get_payment_methods_ticket ) ) {
			$saved_optons = get_option( 'woocommerce_woo-mercado-pago-ticket_settings', '' );

			if ( ! is_array( $get_payment_methods_ticket ) ) {
				$get_payment_methods_ticket = json_decode( $get_payment_methods_ticket, true );
			}

			foreach ( $get_payment_methods_ticket as $payment_methods_ticket ) {
				if ( ! isset( $saved_optons[ 'ticket_payment_' . $payment_methods_ticket['id'] ] )
					|| 'yes' === $saved_optons[ 'ticket_payment_' . $payment_methods_ticket['id'] ] ) {
					array_push( $activated_payment, $payment_methods_ticket );
				}
			}
		}
		return $activated_payment;
	}

	/**
	 * Field checkout pix header
	 *
	 * @return array
	 */
	public function field_checkout_pix_header() {
		return array(
			'title' => sprintf(
				/* translators: %s checkout */
				__( 'Checkout of payments with cash %s', 'woocommerce-mercadopago' ),
				'<div class="mp-row">
                <div class="mp-col-md-12 mp_subtitle_header">
                ' . __( 'Accept face-to-face payments, do not leave anyone out!', 'woocommerce-mercadopago' ) . '
                 </div>
              <div class="mp-col-md-12">
                <p class="mp-text-checkout-body mp-mb-0">
                  ' . __( 'Include this preferred purchase option by some customers.', 'woocommerce-mercadopago' ) . '
                </p>
              </div>
            </div>'
			),
			'type'  => 'title',
			'class' => 'mp_title_header',
		);
	}

	/**
	 * Field checkout pix options title
	 *
	 * @return array
	 */
	public function field_checkout_pix_options_title() {
		return array(
			'title' => __( 'Configure Mercado Pago for WooCommerce', 'woocommerce-mercadopago' ),
			'type'  => 'title',
			'class' => 'mp_title_bd',
		);
	}

	/**
	 * Field checkout pix options description
	 *
	 * @return array
	 */
	public function field_checkout_pix_options_description() {
		return array(
			'title' => __( 'Enable Mercado Pago for cash payments in your store and <br> select the options available to your customers.', 'woocommerce-mercadopago' ),
			'type'  => 'title',
			'class' => 'mp_small_text',
		);
	}

	/**
	 * Field checkout pix payments
	 *
	 * @return array
	 */
	public function field_checkout_pix_payments_title() {
		return array(
			'title' => __( 'Set payment preferences with cash', 'woocommerce-mercadopago' ),
			'type'  => 'title',
			'class' => 'mp_title_bd',
		);
	}

	/**
	 * Field checkout pix payments advanced title
	 *
	 * @return array
	 */
	public function field_checkout_pix_payments_advanced_title() {
		return array(
			'title' => __( 'Advanced configuration of the cash payment experience', 'woocommerce-mercadopago' ),
			'type'  => 'title',
			'class' => 'mp_subtitle_bd',
		);
	}

	/**
	 * Field date expiration
	 *
	 * @return array
	 */
	public function field_date_expiration() {
		return array(
			'title'       => __( 'Payment Due', 'woocommerce-mercadopago' ),
			'type'        => 'number',
			'description' => __( 'In how many days will cash payments expire.', 'woocommerce-mercadopago' ),
			'default'     => '',
		);
	}

	/**
	 * Fielf pix payments
	 *
	 * @return array
	 */
	public function field_ticket_payments() {
		$ticket_payments      = array();
		$ticket_payments_sort = array();

		$get_payment_methods_ticket = get_option( '_all_payment_methods_ticket', '[]' );

		$count_payment = 0;

		if ( ! is_array( $get_payment_methods_ticket ) ) {
			$get_payment_methods_ticket = json_decode( $get_payment_methods_ticket, true );
		}

		foreach ( $get_payment_methods_ticket as $payment_method_ticket ) {
			$element = array(
				'label'             => $payment_method_ticket['name'],
				'id'                => 'woocommerce_mercadopago_' . $payment_method_ticket['id'],
				'default'           => 'yes',
				'type'              => 'checkbox',
				'class'             => 'ticket_payment_method_select',
				'custom_attributes' => array(
					'data-translate' => __( 'All payment methods', 'woocommerce-mercadopago' ),
				),
			);

			$count_payment++;

			if ( 1 === $count_payment ) {
				$element['title']    = __( 'Payment methods', 'woocommerce-mercadopago' );
				$element['desc_tip'] = __( 'Choose the available payment methods in your store.', 'woocommerce-mercadopago' );
			}
			if ( count( $get_payment_methods_ticket ) === $count_payment ) {
				$element['description'] = __( 'Activate the available payment methods to your clients.', 'woocommerce-mercadopago' );
			}

			$ticket_payments[ 'ticket_payment_' . $payment_method_ticket['id'] ] = $element;
			$ticket_payments_sort[] = 'ticket_payment_' . $payment_method_ticket['id'];
		}

		$index = 0;
		foreach ( $this->field_forms_order as $k => $field ) {
			$index++;
			if ( 'field_ticket_payments' === $field ) {
				unset( $this->field_forms_order[ $k ] );
				array_splice( $this->field_forms_order, $index, 0, $ticket_payments_sort );
				break;
			}
		}

		return $ticket_payments;
	}

	/**
	 * Payment fields
	 */
	public function payment_fields() {
		// add css.
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'woocommerce-mercadopago-basic-checkout-styles',
			plugins_url( '../assets/css/basic_checkout_mercadopago' . $suffix . '.css', plugin_dir_path( __FILE__ ) ),
			array(),
			WC_WooMercadoPago_Constants::VERSION
		);

		$amount    = $this->get_order_total();
		$discount  = $amount * ( $this->gateway_discount / 100 );
		$comission = $amount * ( $this->commission / 100 );
		$amount    = $amount - $discount + $comission;

		$logged_user_email = ( 0 !== wp_get_current_user()->ID ) ? wp_get_current_user()->user_email : null;
		$address           = get_user_meta( wp_get_current_user()->ID, 'billing_address_1', true );
		$address_2         = get_user_meta( wp_get_current_user()->ID, 'billing_address_2', true );
		$address          .= ( ! empty( $address_2 ) ? ' - ' . $address_2 : '' );
		$country           = get_user_meta( wp_get_current_user()->ID, 'billing_country', true );
		$address          .= ( ! empty( $country ) ? ' - ' . $country : '' );

		try {
			$currency_ratio = WC_WooMercadoPago_Helpers_CurrencyConverter::get_instance()->ratio( $this );
		} catch ( Exception $e ) {
			$currency_ratio = WC_WooMercadoPago_Helpers_CurrencyConverter::DEFAULT_RATIO;
		}

		$parameters = array(
			'amount'               => $amount,
			'payment_methods'      => $this->activated_payment,
			'site_id'              => $this->get_option_mp( '_site_id_v1' ),
			'discount_action_url'  => $this->discount_action_url,
			'payer_email'          => esc_js( $logged_user_email ),
			'currency_ratio'       => $currency_ratio,
			'woocommerce_currency' => get_woocommerce_currency(),
			'account_currency'     => $this->site_data['currency'],
			'febraban'             => ( 0 !== wp_get_current_user()->ID ) ?
				array(
					'firstname' => esc_js( wp_get_current_user()->user_firstname ),
					'lastname'  => esc_js( wp_get_current_user()->user_lastname ),
					'docNumber' => '',
					'address'   => esc_js( $address ),
					'number'    => '',
					'city'      => esc_js( get_user_meta( wp_get_current_user()->ID, 'billing_city', true ) ),
					'state'     => esc_js( get_user_meta( wp_get_current_user()->ID, 'billing_state', true ) ),
					'zipcode'   => esc_js( get_user_meta( wp_get_current_user()->ID, 'billing_postcode', true ) ),
				) :
				array(
					'firstname' => '',
					'lastname'  => '',
					'docNumber' => '',
					'address'   => '',
					'number'    => '',
					'city'      => '',
					'state'     => '',
					'zipcode'   => '',
				),
			'images_path'          => plugins_url( '../assets/images/', plugin_dir_path( __FILE__ ) ),
		);

		wc_get_template( 'checkout/pix-checkout.php', $parameters, 'woo/mercado/pago/module/', WC_WooMercadoPago_Module::get_templates_path() );
	}

	/**
	 * Process payment
	 *
	 * @param int $order_id Order Id.
	 * @return array|string[]
	 */
	public function process_payment( $order_id ) {
		// @todo need fix Processing form data without nonce verification
		// @codingStandardsIgnoreLine
		$pix_checkout = $_POST['mercadopago_pix'];
		$this->log->write_log( __FUNCTION__, 'Pix POST: ' . wp_json_encode( $pix_checkout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

		$order  = wc_get_order( $order_id );
		$amount = $this->get_order_total();
		if ( method_exists( $order, 'update_meta_data' ) ) {
			$order->update_meta_data( '_used_gateway', get_class( $this ) );
			if ( ! empty( $this->gateway_discount ) ) {
				$discount = $amount * ( $this->gateway_discount / 100 );
				$order->update_meta_data( 'Mercado Pago: discount', __( 'discount of', 'woocommerce-mercadopago' ) . ' ' . $this->gateway_discount . '% / ' . __( 'discount of', 'woocommerce-mercadopago' ) . ' = ' . $discount );
			}

			if ( ! empty( $this->commission ) ) {
				$comission = $amount * ( $this->commission / 100 );
				$order->update_meta_data( 'Mercado Pago: comission', __( 'fee of', 'woocommerce-mercadopago' ) . ' ' . $this->commission . '% / ' . __( 'fee of', 'woocommerce-mercadopago' ) . ' = ' . $comission );
			}
			$order->save();
		} else {
			update_post_meta( $order_id, '_used_gateway', get_class( $this ) );

			if ( ! empty( $this->gateway_discount ) ) {
				$discount = $amount * ( $this->gateway_discount / 100 );
				update_post_meta( $order_id, 'Mercado Pago: discount', __( 'discount of', 'woocommerce-mercadopago' ) . ' ' . $this->gateway_discount . '% / ' . __( 'discount of', 'woocommerce-mercadopago' ) . ' = ' . $discount );
			}

			if ( ! empty( $this->commission ) ) {
				$comission = $amount * ( $this->commission / 100 );
				update_post_meta( $order_id, 'Mercado Pago: comission', __( 'fee of', 'woocommerce-mercadopago' ) . ' ' . $this->commission . '% / ' . __( 'fee of', 'woocommerce-mercadopago' ) . ' = ' . $comission );
			}
		}

		// Check for brazilian FEBRABAN rules.
		if ( 'MLB' === $this->get_option_mp( '_site_id_v1' ) ) {
			if ( ! isset( $pix_checkout['firstname'] ) || empty( $pix_checkout['firstname'] ) ||
				! isset( $pix_checkout['lastname'] ) || empty( $pix_checkout['lastname'] ) ||
				! isset( $pix_checkout['docNumber'] ) || empty( $pix_checkout['docNumber'] ) ||
				( 14 !== strlen( $pix_checkout['docNumber'] ) && 18 !== strlen( $pix_checkout['docNumber'] ) ) ||
				! isset( $pix_checkout['address'] ) || empty( $pix_checkout['address'] ) ||
				! isset( $pix_checkout['number'] ) || empty( $pix_checkout['number'] ) ||
				! isset( $pix_checkout['city'] ) || empty( $pix_checkout['city'] ) ||
				! isset( $pix_checkout['state'] ) || empty( $pix_checkout['state'] ) ||
				! isset( $pix_checkout['zipcode'] ) || empty( $pix_checkout['zipcode'] ) ) {
				wc_add_notice(
					'<p>' .
					__( 'There was a problem processing your payment. Are you sure you have correctly filled out all the information on the payment form?', 'woocommerce-mercadopago' ) .
					'</p>',
					'error'
				);
				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}
		}

		if ( isset( $pix_checkout['amount'] ) && ! empty( $pix_checkout['amount'] ) &&
			isset( $pix_checkout['paymentMethodId'] ) && ! empty( $pix_checkout['paymentMethodId'] ) ) {
			$response = $this->create_preference( $order, $pix_checkout );

			if ( is_array( $response ) && array_key_exists( 'status', $response ) ) {
				if ( 'pending' === $response['status'] ) {
					if ( 'pending_waiting_payment' === $response['status_detail'] || 'pending_waiting_transfer' === $response['status_detail'] ) {
						WC()->cart->empty_cart();
						// WooCommerce 3.0 or later.
						if ( method_exists( $order, 'update_meta_data' ) ) {
							$order->update_meta_data( '_transaction_details_pix', $response['transaction_details']['external_resource_url'] );
							$order->save();
						} else {
							update_post_meta( $order->get_id(), '_transaction_details_pix', $response['transaction_details']['external_resource_url'] );
						}
						// Shows some info in checkout page.
						$order->add_order_note(
							'Mercado Pago: ' .
							__( 'The customer has not paid yet.', 'woocommerce-mercadopago' )
						);
						if ( 'bank_transfer' !== $response['payment_type_id'] ) {
							$order->add_order_note(
								'Mercado Pago: ' .
								__( 'To print the pix again click', 'woocommerce-mercadopago' ) .
								' <a target="_blank" href="' .
								$response['transaction_details']['external_resource_url'] . '">' .
								__( 'here', 'woocommerce-mercadopago' ) .
								'</a>',
								1,
								false
							);
						}

						return array(
							'result'   => 'success',
							'redirect' => $order->get_checkout_order_received_url(),
						);
					}
				}
			} else {
				// Process when fields are imcomplete.
				wc_add_notice(
					'<p>' .
					__( 'A problem occurred when processing your payment. Are you sure you have correctly filled in all the information on the checkout form?', 'woocommerce-mercadopago' ) . ' MERCADO PAGO: ' .
					WC_WooMercadoPago_Module::get_common_error_messages( $response ) .
					'</p>',
					'error'
				);
				return array(
					'result'   => 'fail',
					'redirect' => '',
				);
			}
		} else {
			// Process when fields are incomplete.
			wc_add_notice(
				'<p>' .
				__( 'A problem occurred when processing your payment. Please try again.', 'woocommerce-mercadopago' ) .
				'</p>',
				'error'
			);
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

	/**
	 * Create preference
	 *
	 * @param object $order Order.
	 * @param array  $pix_checkout Picket checkout.
	 * @return string|array
	 */
	public function create_preference( $order, $pix_checkout ) {
		$preferences_pix = new WC_WooMercadoPago_Preference_pix( $this, $order, $pix_checkout );
		$preferences     = $preferences_pix->get_preference();
		try {
			$checkout_info = $this->mp->post( '/v1/payments', wp_json_encode( $preferences ) );
			$this->log->write_log( __FUNCTION__, 'Created Preference: ' . wp_json_encode( $checkout_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
			if ( $checkout_info['status'] < 200 || $checkout_info['status'] >= 300 ) {
				$this->log->write_log( __FUNCTION__, 'mercado pago gave error, payment creation failed with error: ' . $checkout_info['response']['message'] );
				return $checkout_info['response']['message'];
			} elseif ( is_wp_error( $checkout_info ) ) {
				$this->log->write_log( __FUNCTION__, 'WordPress gave error, payment creation failed with error: ' . $checkout_info['response']['message'] );
				return $checkout_info['response']['message'];
			} else {
				$this->log->write_log( __FUNCTION__, 'payment link generated with success from mercado pago, with structure as follow: ' . wp_json_encode( $checkout_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
				return $checkout_info['response'];
			}
		} catch ( WC_WooMercadoPago_Exception $ex ) {
			$this->log->write_log( __FUNCTION__, 'payment creation failed with exception: ' . wp_json_encode( $ex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
			return $ex->getMessage();
		}
	}

	/**
	 * Is available?
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! parent::is_available() ) {
			return false;
		}

		$payment_methods = $this->activated_payment;
		if ( 0 === count( $payment_methods ) ) {
			$this->log->write_log( __FUNCTION__, 'pix unavailable, no active payment methods. ' );
			return false;
		}

		return true;
	}

	/**
	 * Get Id
	 *
	 * @return string
	 */
	public static function get_id() {
		return self::ID;
	}
}
