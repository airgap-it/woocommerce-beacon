<?php
class WC_Beacon_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'beacon';
        $this->icon = esc_url(plugins_url( '/assets/svg/beacon.svg', __FILE__ ));
        $this->has_fields = true;
        $this->method_title = 'Beacon';
        $this->method_description = 'Use a Tezos based currency in you WooCommerce shop - configure supported currencies in the <a href="plugins.php?page=beacon-identifier">plugins settings</a> after setting the receiving address.';
        $this->supports = array('products');

        // Specify plugin configuration settings
        $this->declare_plugin_settings();

        // Load the settings.
        $this->init_settings();

        // Read settings
        $this->title = esc_attr($this->get_option('title'));
        $this->description = esc_attr($this->get_option('description'));
        $this->recipient = esc_attr($this->get_option('recipient'));
        $this->confirmations = esc_attr($this->get_option('confirmations'));

        // Save settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this,'process_admin_options'));

        // Load ui scripts
        add_action('wp_enqueue_scripts', array($this,'load_ui_scripts'));
    }

    /**
     * Calculates the current chart total
     * @return number   current chart total
     */
    protected function get_order_total()
    {
        $total = 0;
        $order_id = absint(get_query_var('order-pay'));
        if (0 < $order_id)
        {
            $order = wc_get_order($order_id);
            if ($order){
                $total = (float)$order->get_total();
            }            
        }
        elseif (0 < WC()->cart->total)
        {
            $total = (float)WC()->cart->total;
        }
        return $total;
    }

    /**
     * Define the plugins configuration fields
     */
    public function declare_plugin_settings()
    {

        $this->form_fields = array(
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Beacon',
                'desc_tip' => true,
            ) ,
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'Use beacon to pay with crypto.',
            ) ,
            'recipient' => array(
                'title' => 'Recipient Address',
                'type' => 'text',
                'default' => 'tz1P9on812SP1uG5cP9PQmPHrdKgaJgfakSc',
                'desc_tip' => 'Recipient address for test'
            ) ,
            'confirmations' => array(
                'title' => 'Min confirmations',
                'type' => 'number',
                'default' => '3',
                'desc_tip' => 'Min number of confirmations before payment gets accepted'
            ) ,
            'store_name' => array(
                'title' => 'Beacon Store Name',
                'type' => 'text',
                'default' => 'Beacon Store',
                'desc_tip' => 'Store name displayed for permission request'
            ) ,
            'payment_button_text' => array(
                'title' => 'Payment button text',
                'type' => 'text',
                'default' => 'Pay with crypto.',
                'desc_tip' => 'Set the payment button text'
            ) ,
        );

    }

    /**
     * Define payment form (hidden fields for transaction address and hash)
     */
    public function payment_fields()
    {
        if ($this->description)
        {
            // display the description with <p> tags etc.
            echo wpautop(wp_kses_post($this->description));
        }
        // Include forms template
        require_once(__DIR__."/form.php");
    }

    /**
     * Localize ui scripts before loading
     */
    public function load_ui_scripts()
    {
        // Assure we are no the checkout site 
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order']))
        {
            return;
        }

        // Assure a receiver address has been set
        if (empty($this->recipient))
        {
            wc_add_notice('Beacon - no receiver address defined - please contact the administrator', 'error');
            return;
        }

        // The website is SSL enabled
        if (!is_ssl() && $_SERVER['REMOTE_ADDR'] != '::1')
        {
            wc_add_notice('Beacon - not SSL enabled -  please contact the administrator', 'error');
            return;
        }

        $has_active_currency = false;
        $tokens = get_option('beacon_tokens');
        foreach ($tokens as $token)
        {
            if ($token['active']) $has_active_currency = true; 
        }

        // Assure to only work when a contract has been specified or when using native tez
        if (!$has_active_currency)
        {
            wc_add_notice('Beacon - no active currency - please contact the administrator', 'error');
            return;
        }

        // load the wallet beacon lib
        wp_enqueue_script('beacon_js', esc_attr(WP_PLUGIN_URL.'/beacon-gateway/assets/js/walletbeacon.min.js'));

        // load the frontend script
        wp_enqueue_script('woocommerce_beacon', esc_attr(WP_PLUGIN_URL.'/beacon-gateway/assets/js/beacon-gateway.js'));
        $params = array(
            'api_base' => esc_url('https://api.tzkt.io/v1/'),
            'amount' => esc_attr($this->get_order_total()),
            'recipient' => esc_attr($this->recipient),
            'confirmations' => esc_attr($this->confirmations),
            'store_name' => esc_attr($this->get_option('store_name')),
            'path' => esc_attr(WP_PLUGIN_URL.'/beacon-gateway/')
        );
        wp_localize_script('woocommerce_beacon', 'php_params', $params);
    }

    /**
     * Server side validation step before order is posted
     * @return order_id     number   Temporary order id
     */
    public function process_payment($order_id)
    {
        // Load temporary order
        $order = wc_get_order($order_id);
        
        // Sanitize input
        $transaction = sanitize_text_field($_POST['beacon_transactionHash']);

        if (empty($transaction))
        {
            wc_add_notice('Validation error - not transaction hash posted -  pay with beacon first', 'error');
            return false;
        }

        $currency = explode("-", esc_attr(sanitize_text_field($_POST['beacon-select'])));

        if (!beacon_is_valid_transaction($this->recipient, $transaction, $this->get_order_total(), $this->confirmations, $currency[0] == "Tezos", intval($currency[2])))
        {
            wc_add_notice('Validation error - not enough confirmations, incorrect amount or wrong receiver -  please contact the administrator', 'error');
            return false;
        }

        // Check if order with reference already exists
        global $wpdb;
        $sql = $wpdb->prepare( "SELECT count(*) as counter FROM {$wpdb->prefix}postmeta WHERE meta_key = 'beacon_transactionHash' AND meta_value = %s", $transaction );
        $counter = $wpdb->get_results( $sql );
        if($counter[0]->counter > 0){
            wc_add_notice('Transaction already used for payment -  please contact the administrator', 'error');
            return false;
        }

        // Append transaction hash to order & confirm order
        $order->update_meta_data('beacon_transactionHash', $transaction);

        $order->save();
        $order->payment_complete();
        $order->reduce_order_stock();

        // Display success message to user
        $order->add_order_note('Hey, your order is paid! Thank you!', true);

        // Empty cart
        global $woocommerce;
        $woocommerce->cart->empty_cart();

        // Redirect to the thank you page
        return array('result' => 'success','redirect' => $this->get_return_url($order)
        );
    }
}
