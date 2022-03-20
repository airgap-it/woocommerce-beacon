<?php

/**
 * Helper method to do a GET request
 * @param  url      Url to request
 * @return object   JSON response
 */
function beacon_get_json($url)
{
    $response = wp_remote_get($url);
    return json_decode(wp_remote_retrieve_body( $response ), true);
}

/**
 * Verifies if enough confirmaitons have been collected
 * @param  transaction_hash   string   transaction hash
 * @param  is_native          boolean  extract native or fa2 token  
 * @return                    object    
 */
function beacon_get_blockchain_data($transaction_hash, $is_native, $decimals)
{
    $head = beacon_get_json("https://api.tzkt.io/v1/head")["level"];
    $operation = beacon_get_json("https://api.tzkt.io/v1/operations/".$transaction_hash);
    $response["confirmations"] = $head - $operation[0]["level"];
    if($is_native){
        $response["amount"] = $operation[0]["amount"] / 10**$decimals;
        $response["receiver"] = $operation[0]["target"]["address"];
    }else{
        $response["amount"] = $operation[0]["parameter"]["value"][0]["txs"][0]["amount"] / 10**$decimals;
        $response["receiver"] = $operation[0]["parameter"]["value"][0]["txs"][0]["to_"];
    }
    return $response;
}

/**
 * Verifies if enough confirmaitons have been collected
 * @param  receiver           string   receiver address
 * @param  transaction_hash   string   transaction hash
 * @param  amount             number   set amount to receive
 * @param  confirmation       number   min amount of confirmations to verify
 * @param  is_native          boolean  extract native or fa2 token   
 * @return                    boolean  
 */
function beacon_is_valid_transaction($receiver, $transaction_hash, $amount, $confirmations, $is_native, $decimals){
    $response = beacon_get_blockchain_data($transaction_hash, $is_native, $decimals);
    return $response["receiver"] === $receiver && $response["amount"] === $amount && $response['confirmations'] >= $confirmations;
}

/**
 * Initialize backend admin ui
 */
function beacon_plugin_options() {
    global $title, $plugin_page ;

    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    $settings = get_option('woocommerce_beacon_settings');

    if ( !$settings["recipient"] )  {
        wp_die( __( '<a href="admin.php?page=wc-settings&tab=checkout&section=beacon">Recipient address</a> not configured' ) );
    }

    $tokens = json_decode(file_get_contents(plugin_dir_path(__FILE__) . "/assets/json/tokens.json", false) , true);
    if(!empty($_POST)){
        $new_tokens = [];
        foreach ($tokens as $key => $value) {
            $new_tokens[$value['identifier']] = $value;
            $new_tokens[$value['identifier']]['active'] = $_POST[$value['identifier']."_active"] == "on";
            $new_tokens[$value['identifier']]['rate'] = $_POST[$value['identifier']."_rate"];
        }
        update_option('beacon_tokens', $new_tokens);
	}

    $options = get_option('beacon_tokens');

    // Include forms template
    require_once(__DIR__."/admin.php");
}

/**
 * Initialize the configuration menu
 */
function beacon_menu() {
    add_plugins_page( 'Beacon configuration', 'Beacon configuration', 'manage_options', 'beacon-identifier', 'beacon_plugin_options' );
}

/**
 * Declare gateway class
 * @param  gateways     array   List of currenctly registered gateways
 * @return              array   Extended gateways list
 */
function beacon_register_gateway($gateways)
{
    $gateways[] = 'WC_Beacon_Gateway';
    return $gateways;
}

/**
 * Initialize gateway class
 */
function beacon_init_gateway()
{
    require __DIR__ . '/beacon-gateway.class.php';
}
