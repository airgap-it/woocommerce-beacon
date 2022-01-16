<?php
class WC_Beacon_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'beacon';
        $this->icon = get_site_url().'/wp-content/plugins/beacon-gateway/assets/svg/beacon.svg';
        $this->has_fields = true;
        $this->method_title = 'Beacon';
        $this->method_description = 'Leverage the beacon network to pay via Crypto';
        $this->supports = array('products');

        // Specify plugin configuration settings
        $this->declare_plugin_settings();

        // Load the settings.
        $this->init_settings();

        // Read settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->recipient = $this->get_option('recipient');
        $this->confirmations = $this->get_option('confirmations');

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
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Beacon Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ) ,
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
        include ('form.php');
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

        // Assure the plugin is enabled
        if ('no' === $this->enabled)
        {
            return;
        }

        // Assure a receiver address has been set
        if (empty($this->recipient))
        {
            wc_add_notice('Plugin is not properly configured - no receiver address - please contact the administrator', 'error');
            return;
        }

        // The website is SSL enabled
        if (!is_ssl() && $_SERVER['REMOTE_ADDR'] != '::1')
        {
            wc_add_notice('Plugin is not properly configured - not SSL enabled -  please contact the administrator', 'error');
            return;
        }

        // load active token configuration
        $symbol = get_woocommerce_currency_symbol();
        $contract = "";
        $token_id = 0;
        $tokens = json_decode(file_get_contents(plugin_dir_path(__FILE__) . "/assets/json/tokens.json", false) , true);
        foreach ($tokens as $token)
        {
            if ($token['symbol'] === $symbol)
            {
                $contract = $token['contract'];
                $token_id = $token['token_id'];
                $decimals = $token['decimals'];
            }
        }

        // Assure to only work when a contract has been specified or when using native tez
        if (empty($contract) || $token_id == - 1)
        {
            wc_add_notice('Plugin is not properly configured - please contact the administrator', 'error');
            return;
        }

        // load the wallet beacon lib
        wp_enqueue_script('beacon_js', '/wp-content/plugins/beacon-gateway/assets/js/walletbeacon.min.js');

        // load the frontend script
        wp_enqueue_script('woocommerce_beacon', '/wp-content/plugins/beacon-gateway/assets/js/beacon-gateway.js');
        $params = array(
            'api_base' => 'https://api.tzkt.io/v1/',
            'amount' => $this->get_order_total() ,
            'contract' => $contract,
            'decimals' => $decimals,
            'token_id' => $token_id,
            'recipient' => $this->recipient,
            'confirmations' => $this->confirmations,
            'store_name' => $this->get_option('store_name') ,
            'currency_symbol' => $symbol,
            'path' => get_site_url()
        );
        wp_localize_script('woocommerce_beacon', 'php_params', $params);
    }

    /*
     * Fields validation, more in Step 5
    */
    // public function validate_fields()
    // {

    //     // if( empty( $_POST[ 'beacon_transactionHash' ]) ) {
    //     //     wc_add_notice(  'No beacon hash defined', 'error' );
    //     //     return false;
    //     // }
    //     return true;

    // }

    /**
     * Server side validation step before order is posted
     * @return order_id     number   Temporary order id
     */
    public function process_payment($order_id)
    {
        // Load temporary order
        $order = wc_get_order($order_id);

        if (empty($_POST['beacon_transactionHash']))
        {
            wc_add_notice('Validation error - not transaction hash posted -  please contact the administrator', 'error');
            return false;
        }

        if (!is_valid_transaction($_POST['beacon_transactionHash'], $this->confirmations))
        {
            wc_add_notice('Validation error - not enough confirmations, incorrect amount or wrong receiver -  please contact the administrator', 'error');
            return false;
        }

        // Append transaction hash to order & confirm order
        $order->update_meta_data('beacon_transactionHash', $_POST['beacon_transactionHash']);
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