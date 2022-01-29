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
function beacon_get_blockchain_data($transaction_hash, $is_native)
{
    $head = beacon_get_json("https://api.tzkt.io/v1/head")["level"];
    $operation = beacon_get_json("https://api.tzkt.io/v1/operations/".$transaction_hash);
    $response["confirmations"] = $head - $operation[0]["level"];
    if($is_native){
        $response["amount"] = $operation[0]["amount"] / 1000000;
        $response["receiver"] = $operation[0]["target"]["address"];
    }else{
        $response["amount"] = $operation[0]["parameter"]["value"][0]["txs"][0]["amount"] / 10**12;
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
function beacon_is_valid_transaction($receiver, $transaction_hash, $amount, $confirmations, $is_native){
    $response = beacon_get_blockchain_data($transaction_hash, $is_native);
    return $response["receiver"] === $receiver && $response["amount"] === $amount && $response['confirmations'] >= $confirmations;
}

/**
 * Inject currencies into WooCommerce
 * @param  currencies   array   List additional currency         
 * @return              array   Modified currency list
 */
function beacon_register_currencies($currencies)
{
    $tokens = json_decode(file_get_contents(plugin_dir_path(__FILE__) . "/assets/json/tokens.json", false) , true);
    foreach ($tokens as $token)
    {
        $currencies[$token['identifier']] = $token['name'];
    }
    return $currencies;
}

/**
 * Inject currencies symbols into WooCommerce
 * @param  currency_symbol string    Symbol to inject
 * @param  currencies      array     List additional currency         
 * @return                 array   Modified currency symbols list
 */
function beacon_register_symbols($currency_symbol, $currency)
{
    $tokens = json_decode(file_get_contents(plugin_dir_path(__FILE__) . "/assets/json/tokens.json", false) , true);
    foreach ($tokens as $token)
    {
        if ($currency === $token['identifier'])
        {
            return $token['symbol'];
        }
    }
    return $currency_symbol;
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
