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
 
class SWeb3_Contract
{
    private $sweb3;
    private $ABI;
    private $address;
    private $bytecode;
  

    function __construct(SWeb3 $sweb3, string $contractAddress, $contractABI)
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
  

    function call(string $function_name, $callData = null, $extraParams = null)
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
            return $this->DecodeData($function_name, $result->result);
        else 
            return $result;
    }


    function send(string $function_name, $sendData, $extraParams = null)
    { 
        if (!$this->ABI->isSendFunction($function_name)) {
            throw new Exception('ERROR: ' . $function_name . ' does not exist as a send function (changing state transaction) in this contract');  
        }
 
        $hashData = $this->ABI->EncodeData($function_name, $sendData); 
       
        if ($extraParams == null) $extraParams = [];
		$extraParams['from'] =  $this->sweb3->personal->address;
        $extraParams['to'] =  $this->address;
        $extraParams['data'] =  $hashData; 

        if (!isset($extraParams['gasLimit'])) $extraParams['gasLimit'] =  $this->estimateGas($extraParams);
     

        $result = $this->sweb3->send($extraParams);
        return $result;
    }


	function DecodeData(string $function_name, $data)
	{
		return $this->ABI->DecodeData($function_name, $data);
	}


    function estimateGas($extraParams)
    {    
        $gasEstimateResult = $this->sweb3->call('eth_estimateGas', [$extraParams]); 
    
        if(!isset($gasEstimateResult->result)) { 
            throw new Exception('ERROR: estimateGas error: ' . $gasEstimateResult->error->message . ' (' . $gasEstimateResult->error->code . ')'); 
        }

        $gasEstimate = $this->sweb3->utils->hexToBn($gasEstimateResult->result);

        return $gasEstimate;
    }

 
    function deployContract(array $inputs = [], array $extra_params = [])
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

            $extra_params['gasLimit'] = $this->sweb3->utils->hexToBn($gasEstimateResult->result); 
        }

        //get gas price
        if(!isset($extra_params['gasPrice']))  $extra_params['gasPrice'] = $this->sweb3->getGasPrice(); 
         
        return $this->sweb3->send($extra_params); 
    }


	//EVENT LOGS

	//returns event ABI from event hash (encoded event name in transaction logs -> topics[0])
	function GetEventFromLog($log_object)
	{
		return  $this->ABI->GetEventFromHash($log_object->topics[0]);
	}


	//returns decoded topics/data from event object (in transaction logs )
	function DecodeEvent($event_object, $log)
	{ 
        return $this->ABI->DecodeEvent($event_object, $log);
	}


    //returns all event logs. each with 2 extra parameters "decoded_data" and "event_anme"
    function getLogs(string $minBlock = null, string $maxBlock = null, $topics = null)
    {
        $result = $this->sweb3->getLogs($this->address, $minBlock, $maxBlock, $topics);
        $logs = $result->result;

        foreach($logs as $log) 
        {
            $event = $this->GetEventFromLog($log);
            if($event != null)
			{
                $log->event_name = $event->name; 
				$log->decoded_data = $this->DecodeEvent($event, $log);
            }
            else  {
                $log->event_name = 'unknown'; 
            }
        }
 
        return $logs;
    }

	
}
