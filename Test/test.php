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
include_once("inc/inc.wtest.php");

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

$res = ABI::EncodeParameter_External('address', '0x00112233445566778899');
WTest::check('address (0x00112233445566778899)', $res == "0x0000000000000000000000000000000000000000000000112233445566778899"); 

$res = ABI::EncodeParameter_External('bytes32[]', ['0xdf3234', '0xfdfd']);
WTest::check('bytes32[] ([\'0xdf3234\', \'0xfdfd\'])', $res == "0x00000000000000000000000000000000000000000000000000000000000000200000000000000000000000000000000000000000000000000000000000000002df32340000000000000000000000000000000000000000000000000000000000fdfd000000000000000000000000000000000000000000000000000000000000");
  

//
WTest::printTitle('ABI - EncodeParameters_External'); 

$res = ABI::EncodeParameters_External(['uint256','string'], ['2345675643', 'Hello!%']);
WTest::check('[\'uint256\',\'string\'] ([\'2345675643\', \'Hello!%\'])', $res == '0x000000000000000000000000000000000000000000000000000000008bd02b7b0000000000000000000000000000000000000000000000000000000000000040000000000000000000000000000000000000000000000000000000000000000748656c6c6f212500000000000000000000000000000000000000000000000000');
 
$vals = json_decode('{"x1": true,"x2": ["0x0000000000000000000000000000000000000000000000000000000000000001","0x0000000000000000000000000000000000000000000000000000000000000002"],"x3": 0,"x4": 20}');
$res = ABI::EncodeParameters_External(["bool","bytes32[2]","uint256","uint8"],array_values((array)$vals));
WTest::check('bytes32[2] -> fixed length array (fixed bytes)', $res == '0x00000000000000000000000000000000000000000000000000000000000000010000000000000000000000000000000000000000000000000000000000000001000000000000000000000000000000000000000000000000000000000000000200000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000014');
 

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


  
//
WTest::printTitle('ABI - ENCODE / DECODE'); 

$original = 'sadfsaSAEFAW435456¿?!*_¨:_*'; 
$encoded = ABI::EncodeParameter_External('bytes32', $original);  
$decoded = ABI::DecodeParameter_External('bytes32', $encoded);  
WTest::check('bytes32  (sadfsaSAEFAW435456¿?!*_¨:_*) ', $original == $decoded); 

//
WTest::printTitle('ABI - EncodeGroup'); 

$abi_tuples_raw = '[{"inputs":[{"components":[{"internalType":"uint256","name":"uint_a","type":"uint256"},{"internalType":"bool","name":"boolean_a","type":"bool"}],"internalType":"struct contract_test_mirror_tuple.Tuple_A","name":"t","type":"tuple"}],"name":"Mirror_TupleA","outputs":[{"components":[{"internalType":"uint256","name":"uint_a","type":"uint256"},{"internalType":"bool","name":"boolean_a","type":"bool"}],"internalType":"struct contract_test_mirror_tuple.Tuple_A","name":"","type":"tuple"}],"stateMutability":"pure","type":"function"},{"inputs":[{"components":[{"internalType":"uint256","name":"uint_c","type":"uint256"},{"internalType":"string","name":"string_c","type":"string"}],"internalType":"struct contract_test_mirror_tuple.Tuple_C[]","name":"t","type":"tuple[]"}],"name":"Mirror_TupleArray","outputs":[{"components":[{"internalType":"uint256","name":"uint_c","type":"uint256"},{"internalType":"string","name":"string_c","type":"string"}],"internalType":"struct contract_test_mirror_tuple.Tuple_C[]","name":"","type":"tuple[]"}],"stateMutability":"pure","type":"function"},{"inputs":[{"components":[{"internalType":"string","name":"string_b1","type":"string"},{"internalType":"string","name":"string_b2","type":"string"}],"internalType":"struct contract_test_mirror_tuple.Tuple_B","name":"t","type":"tuple"}],"name":"Mirror_TupleB","outputs":[{"components":[{"internalType":"string","name":"string_b1","type":"string"},{"internalType":"string","name":"string_b2","type":"string"}],"internalType":"struct contract_test_mirror_tuple.Tuple_B","name":"","type":"tuple"}],"stateMutability":"pure","type":"function"},{"inputs":[{"components":[{"internalType":"uint256","name":"uint_c","type":"uint256"},{"internalType":"string","name":"string_c","type":"string"}],"internalType":"struct contract_test_mirror_tuple.Tuple_C","name":"t","type":"tuple"}],"name":"Mirror_TupleC","outputs":[{"components":[{"internalType":"uint256","name":"uint_c","type":"uint256"},{"internalType":"string","name":"string_c","type":"string"}],"internalType":"struct contract_test_mirror_tuple.Tuple_C","name":"","type":"tuple"}],"stateMutability":"pure","type":"function"}]';
$abi_tuples = new ABI();
$abi_tuples->Init($abi_tuples_raw);

$d = new stdClass();
$d->uint_a = 123;
$d->boolean_a = false;
$res = $abi_tuples->EncodeData('Mirror_TupleA', $d); 
WTest::check('Mirror_TupleA (full static)', $res == '0x1cdf9093000000000000000000000000000000000000000000000000000000000000007b0000000000000000000000000000000000000000000000000000000000000000');
 
$d = new stdClass();
$d->string_b1 = 'aaa';
$d->string_b2 = 'bbb';
$res = $abi_tuples->EncodeData('Mirror_TupleB', $d); 
WTest::check('Mirror_TupleB (full dynamic)', $res == '0x68116cf20000000000000000000000000000000000000000000000000000000000000020000000000000000000000000000000000000000000000000000000000000004000000000000000000000000000000000000000000000000000000000000000800000000000000000000000000000000000000000000000000000000000000003616161000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000036262620000000000000000000000000000000000000000000000000000000000');

$d = new stdClass();
$d->uint_c = 123;
$d->string_c = 'ccc';
$res = $abi_tuples->EncodeData('Mirror_TupleC', $d); 
WTest::check('Mirror_TupleC (static/dynamic mix)', $res == '0x445cf8270000000000000000000000000000000000000000000000000000000000000020000000000000000000000000000000000000000000000000000000000000007b000000000000000000000000000000000000000000000000000000000000004000000000000000000000000000000000000000000000000000000000000000036363630000000000000000000000000000000000000000000000000000000000');
		
$ta1 = new stdClass(); $ta1->uint_c = 111; $ta1->string_c = 'aaa'; 
$ta2 = new stdClass(); $ta2->uint_c = 222; $ta2->string_c = 'bbb'; 
$res = $abi_tuples->EncodeData('Mirror_TupleArray', [$ta1, $ta2]); 
WTest::check('Mirror_TupleArray', $res == '0xf82fd7c900000000000000000000000000000000000000000000000000000000000000200000000000000000000000000000000000000000000000000000000000000002000000000000000000000000000000000000000000000000000000000000004000000000000000000000000000000000000000000000000000000000000000c0000000000000000000000000000000000000000000000000000000000000006f00000000000000000000000000000000000000000000000000000000000000400000000000000000000000000000000000000000000000000000000000000003616161000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000de000000000000000000000000000000000000000000000000000000000000004000000000000000000000000000000000000000000000000000000000000000036262620000000000000000000000000000000000000000000000000000000000');


WTest::printTitle('ABI - DecodeGroup'); 


$res = $abi_tuples->DecodeData('Mirror_TupleA', '0x000000000000000000000000000000000000000000000000000000000000007b0000000000000000000000000000000000000000000000000000000000000000');
WTest::check('Mirror_TupleA (full static)', $res->tuple_1->uint_a->toString() == '123' && !$res->tuple_1->bool_a);

$res = $abi_tuples->DecodeData('Mirror_TupleB', '0x0000000000000000000000000000000000000000000000000000000000000020000000000000000000000000000000000000000000000000000000000000004000000000000000000000000000000000000000000000000000000000000000800000000000000000000000000000000000000000000000000000000000000003616161000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000036262620000000000000000000000000000000000000000000000000000000000');
WTest::check('Mirror_TupleB (full dynamic)', $res->tuple_1->string_b1 == 'aaa' && $res->tuple_1->string_b2 == 'bbb');

$res = $abi_tuples->DecodeData('Mirror_TupleC', '0x0000000000000000000000000000000000000000000000000000000000000020000000000000000000000000000000000000000000000000000000000000007b000000000000000000000000000000000000000000000000000000000000004000000000000000000000000000000000000000000000000000000000000000036363630000000000000000000000000000000000000000000000000000000000');
WTest::check('Mirror_TupleC (static/dynamic mix)', $res->tuple_1->uint_c->toString() == '123' && $res->tuple_1->string_c == 'ccc');

$res = $abi_tuples->DecodeData('Mirror_TupleArray', '0x00000000000000000000000000000000000000000000000000000000000000200000000000000000000000000000000000000000000000000000000000000002000000000000000000000000000000000000000000000000000000000000004000000000000000000000000000000000000000000000000000000000000000c0000000000000000000000000000000000000000000000000000000000000006f00000000000000000000000000000000000000000000000000000000000000400000000000000000000000000000000000000000000000000000000000000003616161000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000de000000000000000000000000000000000000000000000000000000000000004000000000000000000000000000000000000000000000000000000000000000036262620000000000000000000000000000000000000000000000000000000000');
WTest::check('Mirror_TupleArray', $res->array_1[0]->uint_c->toString() == '111' && $res->array_1[0]->string_c == 'aaa' && $res->array_1[1]->uint_c->toString() == '222' && $res->array_1[1]->string_c == 'bbb');


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
WTest::check('privateKeyToAccount', $account3->address == '0x2c7536e3605d9c16a7a3d7b1898e529396a65c23');  

$res_sign = $account3->sign('Some data');  
WTest::check('sign "Some data"', $res_sign->signature == '0xb91467e570a6466aa9e9876cbcd013baba02900b8979d43fe208a4a4f339f5fd6007e74cd82e037b800186422fc2da167c747ef045e5d18a5f5d4300f8e1a0291c');  
 
$res = Accounts::signedMessageToAddress('Some data', $res_sign->signature);    
WTest::check('signedMessageToAddress', $res == $account3->address);  
 
$res = Accounts::verifySignatureWithAddress('Some data', $res_sign->signature, $account3->address);   
WTest::check('verifySignatureWithAddress', $res);  


//RLP
//https://toolkit.abdk.consulting/ethereum#rlp

WTest::printTitle('RLP');

use Web3p\RLP\RLP; 
$rlp  = new RLP;
$data = ["0x00112233445566778899", "0xaaaa"];
$res = $rlp->encode($data);
WTest::check('RLP encode address leading zero', $res == 'ce8a0011223344556677889982aaaa');   