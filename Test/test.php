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
include_once("test.core.php");

use stdClass;
use SWeb3\Accounts; 
use SWeb3\ABI; 
use SWeb3\SWeb3; 
use SWeb3\SWeb3_Contract; 

use WTest; 

?> <style>

body {
	font-family: Calibri, 'Trebuchet MS', sans-serif; 
}

p {
	font-size: 14px;
	line-height: 10px;
	font-weight: normal; 
}

</style><?


//INIT
echo '<h1>Simple-Web3-PHP Test</h1>';



//ABI

//
WTest::printTitle('ABI - EncodeParameter_External');

$res = ABI::EncodeParameter_External('uint256', '2345675643');
WTest::check('uint256 (2345675643)', $res == "0x000000000000000000000000000000000000000000000000000000008bd02b7b");
 
$res = ABI::EncodeParameter_External('bytes32', '0xdf3234');
WTest::check('bytes32 (0xdf3234)', $res == "0xdf32340000000000000000000000000000000000000000000000000000000000"); 
 
$res = ABI::EncodeParameter_External('bytes', '0xdf3234');
WTest::check('bytes (0xdf3234)', $res == "0x00000000000000000000000000000000000000000000000000000000000000200000000000000000000000000000000000000000000000000000000000000003df32340000000000000000000000000000000000000000000000000000000000");

$res = ABI::EncodeParameter_External('bytes32[]', ['0xdf3234', '0xfdfd']);
WTest::check('bytes32[] ([\'0xdf3234\', \'0xfdfd\'])', $res == "0x00000000000000000000000000000000000000000000000000000000000000200000000000000000000000000000000000000000000000000000000000000002df32340000000000000000000000000000000000000000000000000000000000fdfd000000000000000000000000000000000000000000000000000000000000");
  

//
WTest::printTitle('ABI - EncodeParameters_External'); 

$res = ABI::EncodeParameters_External(['uint256','string'], ['2345675643', 'Hello!%']);
WTest::check('[\'uint256\',\'string\'] ([\'2345675643\', \'Hello!%\'])', $res == '0x000000000000000000000000000000000000000000000000000000008bd02b7b0000000000000000000000000000000000000000000000000000000000000040000000000000000000000000000000000000000000000000000000000000000748656c6c6f212500000000000000000000000000000000000000000000000000');
 


//
WTest::printTitle('ABI - DecodeParameter_External'); 

$res = ABI::DecodeParameter_External('uint256', '0x0000000000000000000000000000000000000000000000000000000000000010');
WTest::check('uint256 (16)', $res->toString() == "16"); 
 
$res = ABI::DecodeParameter_External('string', '0x0000000000000000000000000000000000000000000000000000000000000020000000000000000000000000000000000000000000000000000000000000000848656c6c6f212521000000000000000000000000000000000000000000000000');
WTest::check('string (Hello!%!)', $res == "Hello!%!"); 


$res = ABI::DecodeParameter_External('bytes', '0x00000000000000000000000000000000000000000000000000000000000000200000000000000000000000000000000000000000000000000000000000000003df32340000000000000000000000000000000000000000000000000000000000');
WTest::check('bytes (df3234) ', bin2hex($res) == ("df3234")); 



$res = ABI::DecodeParameter_External('bytes32[]', '0x00000000000000000000000000000000000000000000000000000000000000200000000000000000000000000000000000000000000000000000000000000002df32340000000000000000000000000000000000000000000000000000000000fdfd000000000000000000000000000000000000000000000000000000000000');
$res_format = [bin2hex($res[0]), bin2hex($res[1])];
WTest::check('bytes32[] ([\'df3234\', \'fdfd\']) ', $res_format == ['df3234','fdfd']); 
 

//UTILS

//
WTest::printTitle('Utils - units conversion'); 

$res = Utils::fromWeiToString('1001', 'kwei');  
WTest::check('1001 wei -> kwei (1.001) ', $res == '1.001'); 

$res = Utils::toWeiString('1.001', 'kwei');  
WTest::check('1.001 kwei -> wei (1.001) ', $res == '1001'); 

$res = Utils::toEtherString('100000000000000000', 'wei');
WTest::check('100000000000000000 wei-> ether (0.1) ', $res == '0.1');  
   


//ACCOUNT
WTest::printTitle('Accounts');

 
$res = Accounts::hashMessage("Hello World");
WTest::check('hashMessage "Hello World"', $res == 'a1de988600a42c4b4ab089b619297c17d53cffae5d5120d82d8a92d0bb3b78f2');  
 
$res = Accounts::hashMessage('Some data');
WTest::check('hashMessage "Some data"', $res == '1da44b586eb0729ff70a73c326926f6ed5a25f5b056e7f47fbc6e58d86871655');  

$account3 = Accounts::privateKeyToAccount('0x4c0883a69102937d6231471b5dbb6204fe5129617082792ae468d01a3f362318');
$res_sign = $account3->sign('Some data');  
WTest::check('sign "Some data"', $res_sign->signature == '0xb91467e570a6466aa9e9876cbcd013baba02900b8979d43fe208a4a4f339f5fd6007e74cd82e037b800186422fc2da167c747ef045e5d18a5f5d4300f8e1a0291c');  
 
$res = Accounts::signedMessageToAddress('Some data', $res_sign->signature);    
WTest::check('signedMessageToAddress', $res == $account3->address);  
 
$res = Accounts::verifySignatureWithAddress('Some data', $res_sign->signature, $account3->address);   
WTest::check('verifySignatureWithAddress', $res);  


 