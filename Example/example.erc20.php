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
//Remember that this is an example showing how to execute the common features of interacting with a erc20 contract through the ethereum rpc api
//This code does not represent a clean / efficient / performant aproach to implement them in a production environment


$extra_curl_params = [];
//INFURA ONLY: Prepare extra curl params, to add infura private key to the request
$extra_curl_params[CURLOPT_USERPWD] = ':'.INFURA_PROJECT_SECRET; 
//initialize SWeb3 main object
$sweb3 = new SWeb3(ETHEREUM_NET_ENDPOINT, $extra_curl_params); 
//send chain id, important for transaction signing 0x1 = main net, 0x3 ropsten... full list = https://chainlist.org/
$sweb3->chainId = '0x3';//ropsten


//GENERAL CONTRACT CONFIGURATION
$config = new stdClass();
$config->personalAdress = "0xaaa...aaa";
$config->personalPrivateKey = "... [private key] ...";
$config->erc20Address = "0x123...123";
$config->erc20ABI = '[contract json ABI]';
$config->transferToAddress = "0xbbb...bbb";


//SET MY PERSONAL DATA
$sweb3->setPersonalData($config->personalAdress, $config->personalPrivateKey); 
 

//CONTRACT 
//initialize contract from address and ABI string
$contract = new SWeb3_contract($sweb3, $config->erc20Address, $config->erc20ABI); 


//QUERY BALANCE OF ADDRESS 
$res = $contract->call('balanceOf', [$config->personalAdress]);
PrintCallResult('balanceOf Sender', $res);

$res = $contract->call('balanceOf', [$config->transferToAddress]);
PrintCallResult('balanceOf Receiver', $res);
  


/// WARNING: AFTER THIS LINE CODE CAN SPEND ETHER AS SENDING TOKENS IS A SIGNED TRANSACTION (STATE CHANGE)
//COMMENT THIS LINE BELOW TO ENABLE THE EXAMPLE

exit;

/// WARNING: END


  
//SEND TOKENS FROM ME TO OTHER ADDRESS

//nonce depends on the sender/signing address. it's the number of transactions made by this address, and can be used to override older transactions
//it's used as a counter/queue
//get nonce gives you the "desired next number" (makes a query to the provider), but you can setup more complex & efficient nonce handling ... at your own risk ;)
$extra_data = [ 'nonce' => $sweb3->personal->getNonce() ];

//be carefull here. This contract has 18 decimal like ethers. So 1 token is 10^18 weis. 
$value = Utils::toWei('1', 'ether');

//$contract->send always populates: gasPrice, gasLimit, IF AND ONLY IF they are not already defined in $extra_data 
//$contract->send always populates: to (contract address), from (sweb3->personal->address), data (ABI encoded $sendData), these can NOT be defined from outside
$result = $contract->send('transfer', [$config->transferToAddress, $value],  $extra_data);

PrintCallResult('transfer: ' . time(), $result); 
 






function PrintCallResult($callName, $result)
{
    echo "<br/> ERC20 Token -> <b>". $callName . "</b><br/>";

    echo "Result -> " . PrintObject($result) . "<br/>"; 
}


function PrintObject($x)
{ 
	if ($x instanceof BigNumber)
	{
		return $x . '';
	}
	
	if (is_object($x)) {
		$x = (array)($x); 
	}

	if (is_array($x))
	{
		$text = "[";
		$first = true;
		foreach($x as $key => $value)
		{
			if ($first)  	$first = false;
			else 			$text .= ", ";

			$text .= $key . " : " . PrintObject($value);
		}

		return $text . "]"; 
	}
	 
	return $x . '';
}