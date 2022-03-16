<?php

/**
 * This file is part of simple-web3-php package.
 * 
 * (c) Alex Cabrera  
 * 
 * @author Alex Cabrera
 * @license MIT 
 */

namespace SWeb3; 

use SWeb3\Utils;
use kornrunner\Ethereum\Transaction;

class Ethereum_CRPC
{
    public $jsonrpc;
    public $method;
    public $params = [];
    public $id;
}

class SWeb3 
{  
    private $provider;
    private $extra_curl_params;

    public $utils;

    public $gasPrice;
    public $chainId;

    private $do_batch;
    private $batched_calls;


    function __construct($url_provider, $extra_curl_params = null)
    {
        $this->provider = $url_provider;
        $this->extra_curl_params = $extra_curl_params; 

        $this->utils = new Utils(); 

        $this->do_batch = false; 
        $this->batched_calls = []; 
    }


    function call($method, $params = null)
    {
        if ($params != null) $params = $this->utils->forceAllNumbersHex($params);  

        //format api data
        $ethRequest = new Ethereum_CRPC();
        $ethRequest->id = 1;
        $ethRequest->jsonrpc = '2.0';
        $ethRequest->method = $method;
        $ethRequest->params = $params;  
        
        if ($this->do_batch) {
            $this->batched_calls []= $ethRequest;
            return true;
        }
        else {
            $sendData = json_encode($ethRequest);  
            return $this->makeCurl($sendData);
        } 
    } 


    function send($params)
    { 
        if ($params != null) $params = $this->utils->forceAllNumbersHex($params); 
        
        //prepare data
        $nonce = (isset($params['nonce'])) ? $params['nonce'] : '';
        $gasPrice = (isset($params['gasPrice'])) ? $params['gasPrice'] : '';
        $gasLimit = (isset($params['gasLimit'])) ? $params['gasLimit'] : '';
        $to = (isset($params['to'])) ? $params['to'] : '';
        $value = (isset($params['value'])) ? $params['value'] : '';
        $data = (isset($params['data'])) ? $params['data'] : '';
        $chainId = (isset($this->chainId)) ? $this->chainId : '0x0';

        //sign transaction
        $privateKey = SWP_PRIVATE_KEY;
        $transaction = new Transaction ($nonce, $gasPrice, $gasLimit, $to, $value, $data);
        $signedTransaction = '0x' . $transaction->getRaw ($privateKey, $chainId);
    
        //SEND RAW TRANSACTION
        //format api data
        $ethRequest = new Ethereum_CRPC();
        $ethRequest->id = 0;
        $ethRequest->jsonrpc = '2.0';
        $ethRequest->method = 'eth_sendRawTransaction';

        $ethRequest->params = [$signedTransaction]; 
         
        if ($this->do_batch) {
            $this->batched_calls []= $ethRequest;
            return true;
        }
        else {
            $sendData = json_encode($ethRequest);  
            return $this->makeCurl($sendData);
        } 
    } 



    private function makeCurl($sendData)
    {
        //prepare curl
        $tuCurl = curl_init();
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($tuCurl, CURLOPT_URL, $this->provider); 

        if ($this->extra_curl_params != null) {
            foreach ($this->extra_curl_params as $key => $param) {
                curl_setopt($tuCurl, $key, $param);
            }
        }

        curl_setopt($tuCurl, CURLOPT_PORT , 443);
        curl_setopt($tuCurl, CURLOPT_POST, 1);
        curl_setopt($tuCurl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-length: ".strlen($sendData)));
        curl_setopt($tuCurl, CURLOPT_POSTFIELDS, $sendData);

        //execute call
        $tuData = curl_exec($tuCurl); 
        if (!curl_errno($tuCurl)) $info = curl_getinfo($tuCurl);  
        else { echo 'Curl send error: ' . curl_error($tuCurl); }

        curl_close($tuCurl); 

        return json_decode($tuData);
    }


    function batch($new_batch)
    {
        $this->do_batch = $new_batch; 
    }


    function executeBatch()
    {
        if (!$this->do_batch) { 
            return '{"error" : "SWeb3 not batching calls"}';
        }
        if (count($this->batched_calls) <= 0) { 
            return '{"error" : "SWeb3 no batched calls"}';
        }
 
        $sendData = json_encode($this->batched_calls);
        $this->batched_calls = [];

        return $this->makeCurl($sendData);
    }

 

    function getNonce($address)
    {
        $transactionCount = $this->call('eth_getTransactionCount', [$address, 'pending']);   
        return $this->utils->hexToDec($transactionCount->result);
    }


    function refreshGasPrice()
    {
        $gasPriceResult = $this->call('eth_gasPrice'); 
        $this->gasPrice = $this->utils->hexToDec($gasPriceResult->result);     
        
        return $this->gasPrice;
    }
}



 
function str_contains(string $haystack, string $needle)
{
    return empty($needle) || strpos($haystack, $needle) !== false;
}
 


 

