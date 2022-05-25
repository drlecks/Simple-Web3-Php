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
use SWeb3\Accounts; 
use SWeb3\Account; 
use SWeb3\Utils; 

//IMPORTANT
//Remember that this is an example showing how to execute the common features of calling / getting state from the ethereum rpc api
//This code does not represent a clean / efficient / performant aproach to implement them in a production environment
 
//create new account privateKey/address
$account1 = Accounts::create();

var_dump($account1);
 
//retrieve account (address) from private key 
$account2 = Accounts::privateKeyToAccount($account1->privateKey);

var_dump($account2);

//EXIT
exit(0);
     