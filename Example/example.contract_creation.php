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

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

include_once("../vendor/autoload.php");
include_once("example.config.php");
 
use stdClass; 
use SWeb3\SWeb3;
use SWeb3\Utils;
use SWeb3\SWeb3_Contract;
use phpseclib\Math\BigInteger as BigNumber;


//IMPORTANT
//Remember that this is an example showing how to execute the common features of sending signed transactions through the ethereum rpc api
//This code does not represent a clean / efficient / performant aproach to implement them in a production environment


$extra_curl_params = [];
//INFURA ONLY: Prepare extra curl params, to add infura private key to the request
$extra_curl_params[CURLOPT_USERPWD] = ':'.INFURA_PROJECT_SECRET;

//initialize SWeb3 main object
$sweb3 = new SWeb3(ETHEREUM_NET_ENDPOINT, $extra_curl_params);
//send chain id, important for transaction signing 0x1 = main net, 0x3 ropsten... full list = https://chainlist.org/
$sweb3->chainId = '0x3';//ropsten
//set personal data for dending & signing
$sweb3->setPersonalData(SWP_ADDRESS, SWP_PRIVATE_KEY); 
//refresh  nonce  
$nonce =  $sweb3->personal->getNonce();

//initialize contract with empty address and contract abi
//contract used available at Examples/swp_contract_create.sol
$creation_abi = '[{"inputs":[{"internalType":"uint256","name":"val","type":"uint256"}],"stateMutability":"nonpayable","type":"constructor"},{"inputs":[],"name":"example_int","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"}]';
$contract = new SWeb3_contract($sweb3, '', $creation_abi);

//set contract bytecode data
$contract_bytecode = '608060405234801561001057600080fd5b5060405161016838038061016883398181016040528101906100329190610054565b80600081905550506100a7565b60008151905061004e81610090565b92915050565b60006020828403121561006a5761006961008b565b5b60006100788482850161003f565b91505092915050565b6000819050919050565b600080fd5b61009981610081565b81146100a457600080fd5b50565b60b3806100b56000396000f3fe6080604052348015600f57600080fd5b506004361060285760003560e01c80633b5cbf1114602d575b600080fd5b60336047565b604051603e9190605a565b60405180910390f35b60005481565b6054816073565b82525050565b6000602082019050606d6000830184604d565b92915050565b600081905091905056fea2646970667358221220171f0c6da393c2e496a8253a903e831cfe3e35c23a55ab06552484e7f3f84ec664736f6c63430008070033';
$contract->setBytecode($contract_bytecode);
 
//call contract deployment (note the array wrapping in the constructor params)!
//if you don't provide explicit gas price, the system will update current gas price from the net (call)
$extra_params = [ 
    'from' => SWP_ADDRESS,
    'nonce' => $nonce
];  
$result = $contract->deployContract( [123123],  $extra_params); 

//check the result
if(isset($result->result))
{
    echo 'Transaction succesfully sent: ' . $result->result . '<br/>';

    $newAddress = '';
    //get the contract deployment transaction hash and wait untill it's finally confirmed
    do {
        echo 'Waiting 5 seconds...<br/>';
        sleep(5);
        $checkContract = $sweb3->call('eth_getTransactionReceipt', [$result->result]);
        if(isset($checkContract->result->contractAddress)) $newAddress = $checkContract->result->contractAddress;

    } while ($newAddress == '');

    echo 'Contract created at: ' . $newAddress . '<br/>';

    //get a contract on the recently created address and query the value that we inserted
    $contract = new SWeb3_contract($sweb3, $newAddress, $creation_abi);
    $resultContract = $contract->call('example_int');

    echo 'Contract value check: <br/>';
    echo json_encode($resultContract);
}
else 
{
    echo 'Transaction error <br/>';
    echo json_encode($result);
}