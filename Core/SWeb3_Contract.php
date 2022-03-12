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
use SWeb3\SWeb3;
use stdClass;
use Exception;
use phpseclib\Math\BigInteger as BigNumber;
 
class SWeb3_Contract
{
    private $s_web3;
    private $ABI;
    private $address;
    public $call_functions;
    public $send_functions;

    function __construct($sweb3, $contractAddress, $contractABI)
    {
        $this->sweb3 = $sweb3;
        $this->address = $contractAddress;

        $this->ABI = new ABI();
        $this->ABI->Init($contractABI); 

        $this->FetchFunctions();
    }


    function FetchFunctions()
    { 
        $this->call_functions = [];
        $this->send = [];

        foreach($this->ABI->functions as $key => $function)
        { 
            $function_name = $function->name;

            $stateMutability = "";
            if (isset($function->stateMutability)) $stateMutability = $function->stateMutability;

            if($stateMutability == 'constructor') {
                //constructor
            }
            else if($stateMutability == 'view' || $stateMutability == 'pure') {
                //call 
                $this->call_functions[$function_name] = $function;
            }
            else {
                //send 
                $this->send_functions[$function_name] = $function;
            } 
        }
    }


    function call($function_name, $callData = null, $extraParams = null)
    { 
        if(!$this->existsFunction($this->call_functions, $function_name)) {
            throw new Exception('ERROR: ' . $function_name . ' does not exist as a call function in this contract');  
        }

        $hashData = $this->ABI->EncodeData($function_name, $callData);
      
        if ($extraParams == null) $extraParams = new stdClass();
        $extraParams->to = $this->address;
        $extraParams->data = $hashData;
    
        $data = [$extraParams, 'latest'];
        $result = $this->sweb3->call('eth_call', $data);
         
        if(isset($result->result))
            return $this->ABI->DecodeData($function_name, $result->result);
        else 
            return $result;
    }


    function send($function_name, $sendData, $extraParams = null)
    { 
        if(!$this->existsFunction($this->send_functions, $function_name)) {
            throw new Exception('ERROR: ' . $function_name . ' does not exist as a send function in this contract');  
        }
 
        $hashData = $this->ABI->EncodeData($function_name, $sendData); 
        //var_dump($hashData);
       
        if ($extraParams == null) $extraParams = [];
        $extraParams['to'] =  $this->address;
        $extraParams['data'] =  $hashData; 

        if (!isset($extraParams['gasPrice'])) $extraParams['gasPrice'] =  $this->sweb3->gasPrice;
        if (!isset($extraParams['gasLimit'])) $extraParams['gasLimit'] =  $this->estimateGas($extraParams);
        //var_dump($extraParams);
   
        $result = $this->sweb3->send($extraParams);
        return $result;
    }


    function estimateGas($extraParams)
    {   
        $gasEstimateResult = $this->sweb3->call('eth_estimateGas', [$extraParams]); 
    
        if(!isset($gasEstimateResult->result)) { 
            throw new Exception('ERROR: estimateGas error: ' . $gasEstimateResult->error->message); 
        }

        $gasEstimate = $this->sweb3->utils->hexToDec($gasEstimateResult->result);

        return $gasEstimate;
    }


    function existsFunction($function_list, $function_name)
    {
        return array_key_exists($function_name, $function_list);
    }
}
