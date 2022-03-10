<?php

/**
 * This file is part of simple-web3-php package.
 * 
 * (c) Alex Cabrera  
 * 
 * @author Alex Cabrera
 * @license MIT 
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

include_once("../Core/sweb3.class.php");
include_once("../Core/sweb3_contract.class.php");
include_once("example.config.php");

use SWeb3\SWeb3;
use SWeb3\Utils;
use SWeb3\SWeb3_Contract;
use phpseclib\Math\BigInteger as BigNumber;


//send 0.001 eth to 0x3Fc47d792BD1B0f423B0e850F4E2AD172d408447
/* SEND PARAMS: 
from: DATA, 20 Bytes - The address the transaction is send from.
to: DATA, 20 Bytes - (optional when creating new contract) The address the transaction is directed to.
gasLimit: QUANTITY - (optional, default: 90000) Integer of the gas provided for the transaction execution. It will return unused gas.
gasPrice: QUANTITY - (optional, default: To-Be-Determined) Integer of the gasPrice used for each paid gas
value: QUANTITY - (optional) Integer of the value sent with this transaction
data: DATA - (null for sending ether, only for contract interacting) The compiled code of a contract OR the hash of the invoked method signature and encoded parameters. For details see Ethereum Contract ABI
nonce: QUANTITY - (optional) Integer of a nonce. This allows to overwrite your own pending transactions that use the same nonce
*/ 

$extra_curl_params = [];
//INFURA ONLY: Prepare extra curl params, to add infura private key to the request
$extra_curl_params[CURLOPT_USERPWD] = ':'.INFURA_PROJECT_SECRET;

//initialize SWeb3 main object
$sweb3 = new SWeb3(ETHEREUM_NET_ENDPOINT, $extra_curl_params);
//send chain id, important for transaction signing 0x1 = main net, 0x3 ropsten... full list = https://chainlist.org/
$sweb3->chainId = '0x3';//ropsten
   

//refresh gas price 
//if you don't provide explicit gas price, the system will update current gas price from the net (call)
$gasPrice = $sweb3->refreshGasPrice();

//GENERAL OPERATIONS
//uncomment all functions you want to execute. mind that every call will make a state changing transaction to the selected net.

//SendETH();

//CONTRACT
//uncomment all functions you want to execute. mind that every call will make a state changing transaction to the selected net.

//initialize contract from address and ABI string
$contract = new SWeb3_contract($sweb3, SWP_Contract_Address, SWP_Contract_ABI);
Contract_Set_public_uint();
//Contract_AddTupleA();
//Contract_AddTupleA_Params();
//AddTuple_B();


exit(0);



function SendETH()
{
    global $sweb3;

    //estimate gas cost
    $sendParams = [ 
        'from' => SWP_ADDRESS,
        'to' => '0x3Fc47d792BD1B0f423B0e850F4E2AD172d408447', 
        'gasPrice' => $sweb3->gasPrice,
        'value' => $sweb3->utils->toWei('0.001', 'ether')
    ]; 

    //get function estimateGas
    $gasEstimateResult = $sweb3->call('eth_estimateGas', [$sendParams]);
    $bigint_10000 = new BigNumber(10000);
    $gasEstimate = $sweb3->utils->hexToDec($gasEstimateResult->result)->add($bigint_10000);
 
    //prepare sending
    $sendParams['nonce'] = $sweb3->getNonce(SWP_ADDRESS); 
    $sendParams['gasLimit'] = $gasEstimate;

    $result = $sweb3->send($sendParams); 
    PrintCallResult('SendETH', $result);
}


function Contract_Set_public_uint()
{
    global $sweb3, $contract;

    //$contract->send always populates: gasPrice, gasPrice, IF AND ONLY IF they are not already defined in $extra_data 
    //$contract->send always populates: to (contract address), data (ABI encoded $sendData), these can NOT be defined from outside
    $extra_data = ['nonce' => $sweb3->getNonce(SWP_ADDRESS)];
    $result = $contract->send('Set_public_uint', time(),  $extra_data);
    
    PrintCallResult('Contract_Set_public_uint: ' . time(), $result);
}

function Contract_AddTupleA()
{
    global $sweb3, $contract;

    $send_data = new stdClass();
    $send_data->uint_a = time();
    $send_data->boolean_a = true;
    $send_data->address_a = SWP_ADDRESS;
    $send_data->bytes_a = 'Dynamic inserted tuple with SWP with tuple'; 

    $extra_data = ['nonce' => $sweb3->getNonce(SWP_ADDRESS)];
    $result = $contract->send('AddTupleA', $send_data,  $extra_data);
     
    PrintCallResult('Contract_AddTupleA: ' . time(), $result);
}

function Contract_AddTupleA_Params()
{
    global $sweb3, $contract;

    $send_data = [];
    $send_data['uint_a'] = time();
    $send_data['boolean_a'] = true;
    $send_data['address_a'] = SWP_ADDRESS;
    $send_data['bytes_a'] = 'Dynamic inserted tuple with SWP by params'; 

    $extra_data = ['nonce' => $sweb3->getNonce(SWP_ADDRESS)];
    $result = $contract->send('AddTupleA_Params', $send_data,  $extra_data);
     
    PrintCallResult('Contract_AddTupleA_Params: ' . time(), $result);
}


function AddTuple_B()
{
    global $sweb3, $contract;

    $send_data = new stdClass();
    $send_data->uint_b = time();
    $send_data->string_b = 'Dynamic inserted tuple with SWP with tuple'; 
    $send_data->string_array_b = ['Dynamic', 'inserted', 'tuple', 'with', 'SWP', 'with', 'tuple']; 

    $extra_data = ['nonce' => $sweb3->getNonce(SWP_ADDRESS)];
    $result = $contract->send('AddTuple_B', $send_data,  $extra_data);
     
    PrintCallResult('AddTuple_B: ' . time(), $result);
}
  


function PrintCallResult($callName, $result)
{
    echo "<br/> Call -> <b>". $callName . "</b><br/>";
    echo "Result -> ". json_encode($result) . "<br/>";
}