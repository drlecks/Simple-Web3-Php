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
use SWeb3\Accounts; 


//IMPORTANT
//Remember that this is an example showing how to execute the common features of calling / getting state from the ethereum rpc api
//This code does not represent a clean / efficient / performant aproach to implement them in a production environment
 
//create new account privateKey/address
$account1 = Accounts::create();

var_dump("ACCOUNT 1");
var_dump($account1);
 
//retrieve account (address) from private key 
$account2 = Accounts::privateKeyToAccount($account1->privateKey);

var_dump("ACCOUNT 2");
var_dump($account2);


var_dump('HASH "Hello World", should be: 0xa1de988600a42c4b4ab089b619297c17d53cffae5d5120d82d8a92d0bb3b78f2');
var_dump(Accounts::hashMessage("Hello World"));

var_dump('HASH "Some data", should be: 0x1da44b586eb0729ff70a73c326926f6ed5a25f5b056e7f47fbc6e58d86871655');
var_dump(Accounts::hashMessage('Some data'));


var_dump("Sign message 'Some data' Should be:");
var_dump("signature: 0xb91467e570a6466aa9e9876cbcd013baba02900b8979d43fe208a4a4f339f5fd6007e74cd82e037b800186422fc2da167c747ef045e5d18a5f5d4300f8e1a0291c"); 
$account3 = Accounts::privateKeyToAccount('0x4c0883a69102937d6231471b5dbb6204fe5129617082792ae468d01a3f362318');
$res = $account3->sign('Some data'); 
var_dump($res); 


var_dump("Reverse the last signed message. Should be:");  
var_dump("address: $account3->address");  
$address = Accounts::signedMessageToAddress('Some data', $res->signature);   
var_dump("res: $address");  
  

var_dump("Check Signature With Address:"); 
$state = Accounts::verifySignatureWithAddress('Some data', $res->signature, $account3->address);  
var_dump($state ? "OK": "ERROR");

//EXIT
exit(0);
     