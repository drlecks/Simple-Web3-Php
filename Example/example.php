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

include_once("../Core/SWP.core.php");
include_once("example.config.php");
  

//general ethereum block information
$res = Ethereum_GetBlock(); 
PrintCallResult('Ethereum_FGetBlock', $res);

//GETTERS - direct public variables
$res = SWP_autoinc_tuple_a(); 
PrintCallResult('SWP_autoinc_tuple_a', $res);

$res = SWP_public_uint(); 
PrintCallResult('SWP_public_uint', $res);

$res = SWP_public_int(); 
PrintCallResult('SWP_public_int', $res);

$res = SWP_public_string(); 
PrintCallResult('SWP_public_string', $res);


//GETTERS

//returns tuple[]
$res = SWP_GetAllTuples_B(); 
PrintCallResult('SWP_GetAllTuples_B', $res);

//input uint
//returns tuple
$res = SWP_GetTuple_A(); 
PrintCallResult('SWP_GetTuple_A', $res);

//input tuple
//returns bool
$res = SWP_ExistsTuple_B();  
PrintCallResult('SWP_ExistsTuple_B', $res);

//input string[]
//returns tuple[]
$res = SWP_GetTuples_B(); 
PrintCallResult('SWP_GetTuples_B', $res);


exit(0);










function Ethereum_GetBlock()
{
    $method = 'eth_blockNumber';
    $data = [];
    $result = Ethereum_FGet($method, $data);

    return $result;
}
 
function SWP_autoinc_tuple_a()
{  
    $callData = [];
    return Ethereum_FContractCall(SWP_Contract_Address, SWP_Contract_ABI, 'autoinc_tuple_a', $callData);
}

function SWP_public_uint()
{  
    $callData = [];
    return Ethereum_FContractCall(SWP_Contract_Address, SWP_Contract_ABI, 'public_uint', $callData);
}

function SWP_public_int()
{  
    $callData = [];
    return Ethereum_FContractCall(SWP_Contract_Address, SWP_Contract_ABI, 'public_int', $callData);
}

function SWP_public_string()
{  
    $callData = [];
    return Ethereum_FContractCall(SWP_Contract_Address, SWP_Contract_ABI, 'public_string', $callData);
}


function SWP_GetAllTuples_B()
{  
    $callData = [];
    return Ethereum_FContractCall(SWP_Contract_Address, SWP_Contract_ABI, 'GetAllTuples_B', $callData);
}

function SWP_GetTuple_A()
{  
    $callData = [3]; //uint
    return Ethereum_FContractCall(SWP_Contract_Address, SWP_Contract_ABI, 'GetTuple_A', $callData);
}

function SWP_ExistsTuple_B()
{  
    $callData = [];
    $callData []= new stdClass(); //tuple(uint256, string, string[])
    $callData[0]->uint_b = 3; 
    $callData[0]->string_b = 'text3'; 
    $callData[0]->string_array_b = ['text3', 'text3']; 
    return Ethereum_FContractCall(SWP_Contract_Address, SWP_Contract_ABI, 'ExistsTuple_B', $callData);
}

function SWP_GetTuples_B()
{  
    $callData = [['text1', 'text2']]; //string[]
    return Ethereum_FContractCall(SWP_Contract_Address, SWP_Contract_ABI, 'GetTuples_B', $callData);
}
 
 
function PrintCallResult($callName, $result)
{
    echo "<br/> Call -> <b>". $callName . "</b><br/>";
    echo "Result -> ". json_encode($result) . "<br/>";
}