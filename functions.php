<?
/**
 * Helper method to do a GET request
 * @param  url      Url to request
 * @return object   JSON response
 */
function beacon_get_json($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return json_decode($result, true);
}

/**
 * Verifies if enough confirmaitons have been collected
 * @param  receiver           string   tz1 address hash
 * @param  confirmations      number   amount of min required confirmations    
 * @return                    object    
 */
function beacon_get_blockchain_data($receiver, $transaction_hash, $amount, $confirmations)
{
    $head = beacon_get_json("https://api.tzkt.io/v1/head")["level"];
    $operation = beacon_get_json("https://api.tzkt.io/v1/operations/".$transaction_hash);
    $response["confirmations"] = $head - $operation[0]["level"];
    if($GLOBALS["IS_NATIVE_TZ"]){
        $response["amount"] = $operation[0]["amount"] / 1000000;
        $response["correct_address"] = $operation[0]["target"]["address"] === $GLOBALS["RECEIVER"];
    }else{
        $response["amount"] = $operation[0]["parameter"]["value"][0]["txs"][0]["amount"] / 10**12;
        $response["correct_address"] = $operation[0]["parameter"]["value"][0]["txs"][0]["to_"] === $GLOBALS["RECEIVER"] && $operation[0]["parameter"]["value"][0]["txs"][0]["token_id"] === $GLOBALS["TOKEN_ID"];
    }
    $response["correct_amount"] = $response["amount"] === $amount;
    return json_encode($response);
}

/**
 * Verifies if enough confirmaitons have been collected
 * @param  transaction_hash   string   tz1 address hash
 * @param  confirmations      number   amount of min required confirmations    
 * @return                    boolean  
 */
function beacon_is_valid_transaction($transaction_hash, $confirmations){
    $response = beacon_get_blockchain_data($transaction_hash);
    return $response['correct_address'] && $response['correct_amount'] && $response['confirmations'] >= $confirmations;
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
        $currencies[$token['identifier']] = __($token['name'], 'woocommerce');
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
