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

use stdClass;
use Exception;
use SWeb3\Utils;
use kornrunner\Ethereum\Transaction;

class Ethereum_CRPC
{
    public $jsonrpc;
    public $method;
    public $params = [];
    public $id;
}

class PersonalData 
{
    private $sweb3;
    public $address;
    public $privateKey; 

    function __construct($sweb3, $address, $privateKey)
    {
        $this->sweb3 = $sweb3;
        $this->address = $address;
        $this->privateKey = $privateKey;
    }

    function getNonce()
    {
        return $this->sweb3->getNonce($this->address);     
    }
}


class SWeb3 
{  
    private $provider;
    private $extra_curl_params;

    public $utils;

    public $personal;
    public $gasPrice;
    public $chainId;

    private $do_batch;
    private $batched_calls;


    function __construct($url_provider, $extra_curl_params = null)
    {
        $this->provider = $url_provider;
        $this->extra_curl_params = $extra_curl_params; 

        $this->utils = new Utils(); 
        $this->gasPrice = null; 

        $this->do_batch = false; 
        $this->batched_calls = []; 
    }


    function setPersonalData($address, $privKey)
    {
        $this->personal = new PersonalData($this, $address, $privKey); 
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
        $gasPrice = (isset($params['gasPrice'])) ? $params['gasPrice'] : $this->getGasPrice();
        $gasLimit = (isset($params['gasLimit'])) ? $params['gasLimit'] : '';
        $to = (isset($params['to'])) ? $params['to'] : '';
        $value = (isset($params['value'])) ? $params['value'] : '';
        $data = (isset($params['data'])) ? $params['data'] : '';
        $chainId = (isset($this->chainId)) ? $this->chainId : '0x0';

        //sign transaction 
        $transaction = new Transaction ($nonce, $gasPrice, $gasLimit, $to, $value, $data);
        $signedTransaction = '0x' . $transaction->getRaw ($this->personal->privateKey, $chainId);
    
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

        curl_setopt($tuCurl, CURLOPT_POST, 1);
        curl_setopt($tuCurl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-length: ".strlen($sendData)));
        curl_setopt($tuCurl, CURLOPT_POSTFIELDS, $sendData);

        //execute call
        $tuData = curl_exec($tuCurl); 
        if (!curl_errno($tuCurl)) 
            $info = curl_getinfo($tuCurl);  
        else 
            throw new Exception('Curl send error: ' . curl_error($tuCurl));   

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

        if(!isset($transactionCount->result)) {
            throw new Exception('getNonce error. from address: ' . $address);   
        }

        return $this->utils->hexToDec($transactionCount->result);
    }
 


    function getGasPrice($force_refresh = false)
    {
        if ($this->gasPrice == null || $force_refresh) {
            $gasPriceResult = $this->call('eth_gasPrice'); 

            if(!isset($gasPriceResult->result)) {
                throw new Exception('getGasPrice error. ');   
            }

            $this->gasPrice = $this->utils->hexToDec($gasPriceResult->result); 
        }
             
        return $this->gasPrice;
    }

    //general info: https://docs.alchemy.com/alchemy/guides/eth_getlogs
    //default blocks: from-> 0x0 to-> latest
    //TOPICS: https://eth.wiki/json-rpc/API#a-note-on-specifying-topic-filters 
    function getLogs($related_address, $minBlock = null, $maxBlock = null, $topics = null)
    { 
        $data = new stdClass();
        $data->address = $related_address;

        $data->fromBlock = ($minBlock != null) ? $minBlock : '0x0';
        $data->toBlock = ($maxBlock != null) ? $maxBlock : 'latest';
        if ($topics != null) $data->topics = $topics;
 
        $result = $this->call('eth_getLogs', [$data]); 
 
        if(!isset($result->result) || !is_array($result->result)) {
            throw new Exception('getLogs error: ' . $result);   
        }

        return $result;
    }
}



 
function str_contains(string $haystack, string $needle)
{
    return empty($needle) || strpos($haystack, $needle) !== false;
}
 


 

