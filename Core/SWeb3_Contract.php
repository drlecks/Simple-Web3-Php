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
    private $bytecode;
  

    function __construct($sweb3, $contractAddress, $contractABI)
    {
        $this->sweb3 = $sweb3;
        $this->address = $contractAddress;

        $this->ABI = new ABI();
        $this->ABI->Init($contractABI);  
    }


    function setBytecode($bytecode)
    {
        $this->bytecode = $bytecode;
    }
  

    function call($function_name, $callData = null, $extraParams = null)
    {  
        if (!$this->ABI->isCallFunction($function_name)) {
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
        if (!$this->ABI->isSendFunction($function_name)) {
            throw new Exception('ERROR: ' . $function_name . ' does not exist as a send function (changing state transaction) in this contract');  
        }
 
        $hashData = $this->ABI->EncodeData($function_name, $sendData); 
       
        if ($extraParams == null) $extraParams = [];
        $extraParams['to'] =  $this->address;
        $extraParams['data'] =  $hashData; 

        if (!isset($extraParams['gasLimit'])) $extraParams['gasLimit'] =  $this->estimateGas($extraParams);
   
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

 
    function deployContract($inputs = [], $extra_params = [])
    {
        if(!isset($this->bytecode)) {
            throw new Exception('ERROR: you need to initialize bytecode to deploy the contract'); 
        }

        $count_expected = count($this->ABI->constructor->inputs);
        $count_received = count($inputs);
        if ($count_expected != $count_received) {
            throw new Exception('ERROR: contract constructor inputs number does not match... Expecting: ' . $count_expected . ' Received: ' . $count_received); 
        }

        $inputEncoded = $this->ABI->EncodeData('', $inputs); 
        $extra_params['data'] = '0x' . $this->bytecode . Utils::stripZero($inputEncoded);
 
        //get function estimateGas
        if(!isset($extra_params['gasLimit'])) {
            $gasEstimateResult = $this->sweb3->call('eth_estimateGas', [$extra_params]); 

            if(!isset($gasEstimateResult->result))
                throw new Exception('estimation error: ' . json_encode($gasEstimateResult));   

            $extra_params['gasLimit'] = $this->sweb3->utils->hexToDec($gasEstimateResult->result); 
        }

        //get gas price
        if(!isset($extra_params['gasPrice']))  $extra_params['gasPrice'] = $this->sweb3->getGasPrice(); 
         
        return $this->sweb3->send($extra_params); 
    }


    //returns all event logs. each with 2 extra parameters "decoded_data" and "event_anme"
    function getLogs($minBlock = null, $maxBlock = null, $topics = null)
    {
        $result = $this->sweb3->getLogs($this->address, $minBlock, $maxBlock, $topics);
        $logs = $result->result;

        foreach($logs as $log) 
        {
            $event = $this->ABI->GetEventFromHash($log->topics[0]);
            if($event != null) {
                $log->event_name = $event->name;

                $encoded = substr($log->data, 2);  
                $log->decoded_data = $this->ABI->DecodeGroup($event->inputs, $encoded, 0);
            }
            else  {
                $log->event_name = 'unknown'; 
            }
        }
 
        return $logs;
    }
}
