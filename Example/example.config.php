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
 
 
//[INFURA START] DEFINE ONLY IF YOU ARE USING INFURA
//in case you are using infura, you can set these values (or *)
define('INFURA_PROJECT_ID', 'XXXXXXXXX');
define('INFURA_PROJECT_SECRET', 'XXXXXXXXX');
define('ETHEREUM_NET_NAME', 'ropsten'); //ropsten , mainnet
//[INFURA END]  

//REAL endpoint, this is what is really used internally
define('ETHEREUM_NET_ENDPOINT', 'https://'.ETHEREUM_NET_NAME.'.infura.io/v3/'.INFURA_PROJECT_ID); 

//swp_contract.sol is available on ropsten test net, address: 0x706986eEe8da42d9d85997b343834B38e34dc000
define('SWP_Contract_Address','0x706986eEe8da42d9d85997b343834B38e34dc000'); 
$SWP_Contract_ABI = '[{"inputs":[],"stateMutability":"nonpayable","type":"constructor"},{"inputs":[{"components":[{"internalType":"uint256","name":"uint_a","type":"uint256"},{"internalType":"bool","name":"boolean_a","type":"bool"},{"internalType":"address","name":"address_a","type":"address"},{"internalType":"bytes","name":"bytes_a","type":"bytes"}],"internalType":"struct SWP_contract.Tuple_A","name":"new_tuple_a","type":"tuple"}],"name":"AddTupleA","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"uint256","name":"uint_a","type":"uint256"},{"internalType":"bool","name":"boolean_a","type":"bool"},{"internalType":"address","name":"address_a","type":"address"},{"internalType":"bytes","name":"bytes_a","type":"bytes"}],"name":"AddTupleA_Params","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"components":[{"internalType":"uint256","name":"uint_b","type":"uint256"},{"internalType":"string","name":"string_b","type":"string"},{"internalType":"string[]","name":"string_array_b","type":"string[]"}],"internalType":"struct SWP_contract.Tuple_B","name":"new_tuple_b","type":"tuple"}],"name":"AddTuple_B","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"uint256","name":"uint_b","type":"uint256"},{"internalType":"string","name":"string_b","type":"string"},{"internalType":"string[]","name":"string_array_b","type":"string[]"}],"name":"AddTuple_B_Params","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"string","name":"a","type":"string"},{"internalType":"string","name":"b","type":"string"}],"name":"Compare","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"pure","type":"function"},{"inputs":[{"components":[{"internalType":"uint256","name":"uint_b","type":"uint256"},{"internalType":"string","name":"string_b","type":"string"},{"internalType":"string[]","name":"string_array_b","type":"string[]"}],"internalType":"struct SWP_contract.Tuple_B","name":"t1","type":"tuple"}],"name":"ExistsTuple_B","outputs":[{"internalType":"bool","name":"","type":"bool"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"GetAllTuples_B","outputs":[{"components":[{"internalType":"uint256","name":"uint_b","type":"uint256"},{"internalType":"string","name":"string_b","type":"string"},{"internalType":"string[]","name":"string_array_b","type":"string[]"}],"internalType":"struct SWP_contract.Tuple_B[]","name":"","type":"tuple[]"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"uint256","name":"id","type":"uint256"}],"name":"GetTuple_A","outputs":[{"components":[{"internalType":"uint256","name":"uint_a","type":"uint256"},{"internalType":"bool","name":"boolean_a","type":"bool"},{"internalType":"address","name":"address_a","type":"address"},{"internalType":"bytes","name":"bytes_a","type":"bytes"}],"internalType":"struct SWP_contract.Tuple_A","name":"","type":"tuple"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"string[]","name":"search","type":"string[]"}],"name":"GetTuples_B","outputs":[{"components":[{"internalType":"uint256","name":"uint_b","type":"uint256"},{"internalType":"string","name":"string_b","type":"string"},{"internalType":"string[]","name":"string_array_b","type":"string[]"}],"internalType":"struct SWP_contract.Tuple_B[]","name":"","type":"tuple[]"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"string[][]","name":"sa","type":"string[][]"}],"name":"Mirror_StringArray","outputs":[{"internalType":"string[][]","name":"","type":"string[][]"}],"stateMutability":"pure","type":"function"},{"inputs":[{"internalType":"int256","name":"new_int","type":"int256"}],"name":"Set_public_int","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"string","name":"new_string","type":"string"}],"name":"Set_public_string","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[{"internalType":"uint256","name":"new_uint","type":"uint256"}],"name":"Set_public_uint","outputs":[],"stateMutability":"nonpayable","type":"function"},{"inputs":[],"name":"autoinc_tuple_a","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"},{"inputs":[{"internalType":"uint256","name":"","type":"uint256"}],"name":"map_tuples_a","outputs":[{"internalType":"uint256","name":"uint_a","type":"uint256"},{"internalType":"bool","name":"boolean_a","type":"bool"},{"internalType":"address","name":"address_a","type":"address"},{"internalType":"bytes","name":"bytes_a","type":"bytes"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"public_int","outputs":[{"internalType":"int256","name":"","type":"int256"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"public_string","outputs":[{"internalType":"string","name":"","type":"string"}],"stateMutability":"view","type":"function"},{"inputs":[],"name":"public_uint","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"}]';
define('SWP_Contract_ABI', $SWP_Contract_ABI);


//SIGNING
define('SWP_ADDRESS', 'XXXXXXXXXX');
define('SWP_PRIVATE_KEY', 'XXXXXXXXXX');
