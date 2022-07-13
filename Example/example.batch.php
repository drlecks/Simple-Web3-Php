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
  
use SWeb3\SWeb3; 
use SWeb3\SWeb3_Contract;
use phpseclib\Math\BigInteger as BigNumber;


//IMPORTANT
//Remember that this is an example showing how to execute the common features of calling / getting state from the ethereum rpc api
//This code does not represent a clean / efficient / performant aproach to implement them in a production environment


//INITIALIZE WEB3

$extra_curl_params = [];
//INFURA ONLY: Prepare extra curl params, to add infura private key to the request
$extra_curl_params[CURLOPT_USERPWD] = ':'.INFURA_PROJECT_SECRET;

//initialize SWeb3 main object
$sweb3 = new SWeb3(ETHEREUM_NET_ENDPOINT, $extra_curl_params);
//send chain id, important for transaction signing 0x1 = main net, 0x3 ropsten... full list = https://chainlist.org/
$sweb3->chainId = '0x3';//ropsten
$sweb3->setPersonalData(SWP_ADDRESS, SWP_PRIVATE_KEY); 

//enable batching
$sweb3->batch(true);

//we need the nonce for signing the send eth transaction
$sweb3->call('eth_gasPrice');   
$sweb3->call('eth_getTransactionCount', [$sweb3->personal->address, 'pending']);   
$res = $sweb3->executeBatch();

PrintCallResult('Gas price & nonce:', $res);

$gasPrice = $sweb3->utils->hexToBn($res[0]->result);  
$nonce = $sweb3->utils->hexToBn($res[1]->result); 


//CALL

//general ethereum block information 
$sweb3->call('eth_blockNumber', []); 
 
//contract: initialize contract from address and ABI string
$contract = new SWeb3_contract($sweb3, SWP_Contract_Address, SWP_Contract_ABI);
   
//contract: direct public variable
$contract->call('autoinc_tuple_a');  

//contract: input string[][] returns tuple[][] 
$contract->call('Mirror_StringArray', [['text1', 'text22'], ['text333', 'text4444'], ['text55555', 'text666666']]);


//SEND  
//send 0.001 eth
$sendParams = [ 
    'from' => $sweb3->personal->address,
    'to' => '0x3Fc47d792BD1B0f423B0e850F4E2AD172d408447', 
    'gasPrice' => $gasPrice,
    'gasLimit' => 21000, //good estimation for eth transaction only
    'nonce' => $nonce,
    'value' => $sweb3->utils->toWei('0.001', 'ether')
];   
$sweb3->send($sendParams); 


//EXECUTE 

//execute all batched calls
$res = $sweb3->executeBatch();

PrintCallResult('Batched calls:', $res);


PrintCallResult('contract => autoinc_tuple_a', $contract->DecodeData('autoinc_tuple_a', $res[1]->result));
PrintCallResult('contract => Mirror_StringArray', $contract->DecodeData('Mirror_StringArray', $res[2]->result));

exit(0);



    
 
 
function PrintCallResult($callName, $result)
{
    echo "<br/> Call -> <b>". $callName . "</b><br/>";

    if(is_array($result))
    {
        foreach($result as $key => $part) 
		{
			echo "Part [" . $key . "]-> ". PrintObject($part) . "<br/>";
		}
    }
    else {
        echo "Result -> ". PrintObject($result) . "<br/>";
    }
    
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