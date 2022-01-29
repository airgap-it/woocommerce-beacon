<?php

// FIXME: Mock WP function
function plugin_dir_path(){
    return getcwd();
}

// FIXME: Mock WP function
function wp_remote_get($url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

// FIXME: Mock WP function
function wp_remote_retrieve_body($result){
    return $result;
}

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase {

    public function test_beacon_get_json(){
        $response = beacon_get_json('https://pong.papers.ch');
        $this->assertEquals($response['response'], 'pong');
    }

    public function test_beacon_get_blockchain_data(){
        $response = beacon_get_blockchain_data('onivncKpai4kCa4ajKBnsdhaJbURHpBKYj7bfHDzUw6mKb9T1Af', true);
        $this->assertEquals($response['amount'], 0.1);
        $this->assertEquals($response['receiver'], 'tz1LpZi5s8Pz25UQSvSv6izL3L6JNgkTktYz');
    }

    public function test_beacon_is_valid_transaction() {
        // Tests for native tez

        // test for correct
        $this->assertEquals(beacon_is_valid_transaction('tz1LpZi5s8Pz25UQSvSv6izL3L6JNgkTktYz', 'onivncKpai4kCa4ajKBnsdhaJbURHpBKYj7bfHDzUw6mKb9T1Af', 0.1, 5, true), true);
        // test for wrong amount
        $this->assertEquals(beacon_is_valid_transaction('tz1LpZi5s8Pz25UQSvSv6izL3L6JNgkTktYz', 'onivncKpai4kCa4ajKBnsdhaJbURHpBKYj7bfHDzUw6mKb9T1Af', 1, 5, true), false);
        // test for wrong receiver
        $this->assertEquals(beacon_is_valid_transaction('KT1HbQepzV1nVGg8QVznG7z4RcHseD5kwqBn', 'onivncKpai4kCa4ajKBnsdhaJbURHpBKYj7bfHDzUw6mKb9T1Af', 0.1, 5, true), false);
        // test for missing confirmations
        $this->assertEquals(beacon_is_valid_transaction('tz1LpZi5s8Pz25UQSvSv6izL3L6JNgkTktYz', 'onivncKpai4kCa4ajKBnsdhaJbURHpBKYj7bfHDzUw6mKb9T1Af', 0.1, PHP_INT_MAX, true), false);

        // Tests for fa2

        // test for correct
        $this->assertEquals(beacon_is_valid_transaction('tz1LpZi5s8Pz25UQSvSv6izL3L6JNgkTktYz', 'oo2ZDzg32r46mcbSALa5NXnnCyi8FhMQ22WQdgtK4exsr6EWspD', 1, 5, false), true);
        // test for wrong amount
        $this->assertEquals(beacon_is_valid_transaction('tz1LpZi5s8Pz25UQSvSv6izL3L6JNgkTktYz', 'oo2ZDzg32r46mcbSALa5NXnnCyi8FhMQ22WQdgtK4exsr6EWspD', 2, 5, false), false);
        // test for wrong receiver
        $this->assertEquals(beacon_is_valid_transaction('KT1HbQepzV1nVGg8QVznG7z4RcHseD5kwqBn', 'oo2ZDzg32r46mcbSALa5NXnnCyi8FhMQ22WQdgtK4exsr6EWspD', 1, 5, false), false);
        // test for missing confirmations
        $this->assertEquals(beacon_is_valid_transaction('tz1LpZi5s8Pz25UQSvSv6izL3L6JNgkTktYz', 'oo2ZDzg32r46mcbSALa5NXnnCyi8FhMQ22WQdgtK4exsr6EWspD', 1, PHP_INT_MAX, false), false);
    }

    public function test_beacon_register_currencies() {
        $currencies = beacon_register_currencies([]);
        $this->assertEquals($currencies, array(
            'Tezos' => 'Tezos',
            'uUSD' => 'youves uUSD',
            'uDEFI' => 'youves uDEFI'
        ));
    }

    public function test_beacon_register_symbols() {
        $symbol = beacon_register_symbols('tz', 'Tezos');
        $this->assertEquals($symbol, 'tz');
    }

    public function test_beacon_register_gateway() {
        $this->assertEquals(beacon_register_gateway([]), ['WC_Beacon_Gateway']);
    }
}

?>