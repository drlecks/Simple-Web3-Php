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


    function __construct($url_provider, $extra_curl_params = null)
    {
        $this->provider = $url_provider;
        $this->extra_curl_params = $extra_curl_params; 

        $this->utils = new Utils(); 
    }


    function call($method, $params = null)
    {
        if ($params != null) $params = $this->utils->forceAllNumbersHex($params); 
        //var_dump($params);

        //format api data
        $ethRequest = new Ethereum_CRPC();
        $ethRequest->id = 1;
        $ethRequest->jsonrpc = '2.0';
        $ethRequest->method = $method;
        $ethRequest->params = $params; 
        $sendData = json_encode($ethRequest); 
        
        //prepare curl
        $tuCurl = curl_init();
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($tuCurl, CURLOPT_URL, $this->provider);
 
        //curl_setopt($tuCurl, CURLOPT_USERPWD, ':'.INFURA_PROJECT_SECRET); 
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
        if (!curl_errno($tuCurl)) {
            $info = curl_getinfo($tuCurl);
            //echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'];
        } 
        else { echo 'Curl call error: ' . curl_error($tuCurl); }
        
        curl_close($tuCurl);  
        return json_decode($tuData);
    } 


    function send($params)
    { 
        if ($params != null) $params = $this->utils->forceAllNumbersHex($params); 
        //var_dump($params);

        //SIGN TRANSACTION    
        $privateKey = SWP_PRIVATE_KEY;
        //if(!str_contains($privateKey, '0x')) $privateKey = '0x'.$privateKey;
    
        //$transaction = new EIP1559Transaction($params);
        //$signedTransaction = '0x' . $transaction->sign($privKey);

        $nonce = (isset($params['nonce'])) ? $params['nonce'] : '';
        $gasPrice = (isset($params['gasPrice'])) ? $params['gasPrice'] : '';
        $gasLimit = (isset($params['gasLimit'])) ? $params['gasLimit'] : '';
        $to = (isset($params['to'])) ? $params['to'] : '';
        $value = (isset($params['value'])) ? $params['value'] : '';
        $data = (isset($params['data'])) ? $params['data'] : '';
        $chainId = (isset($this->chainId)) ? $this->chainId : '0x0';

        $transaction = new Transaction ($nonce, $gasPrice, $gasLimit, $to, $value, $data);
        $signedTransaction = '0x' . $transaction->getRaw ($privateKey, $chainId);
    
        //SEND RAW TRANSACTION
        //format api data
        $ethRequest = new Ethereum_CRPC();
        $ethRequest->id = 0;
        $ethRequest->jsonrpc = '2.0';
        $ethRequest->method = 'eth_sendRawTransaction';

        $ethRequest->params = [$signedTransaction]; 
        $sendData = json_encode($ethRequest);  

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

 

    function getNonce($address)
    {
        $transactionCount = $this->call('eth_getTransactionCount', [SWP_ADDRESS, 'pending']);   
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
 


 

