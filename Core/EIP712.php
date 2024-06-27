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
  
use SWeb3\Utils; 
use stdClass; 

 //https://eips.ethereum.org/EIPS/eip-712 
class EIP712
{
	private static function processTypes(array $types) : object
	{ 
		$finalTypes = new stdClass();

		$finalTypes->EIP712Domain = [
			(object) ['name' => 'name', "type" => "string"],
			(object) ['name' => 'version', "type" => "string"],
			(object) ['name' => 'chainId', "type" => "uint256"],
			(object) ['name' => 'verifyingContract', "type" => "address"]
		];

		foreach ($types as $k => $t) {
			$finalTypes->$k = $t;
		}

		return $finalTypes;
	}


	private static function encodeType($typeName, $types)
	{
		$result = $typeName . '(' . implode(',', array_map(function ($type) {
			return $type->type . ' ' . $type->name;
		}, $types->$typeName)) . ')'; 
		return Utils::sha3($result);
	}


	private static function encodeData($typeName, $data, $types)
	{
		$TYPEHASH = self::encodeType($typeName, $types);

		$types_inputs 	= ["bytes32"];
		$data_inputs 	= [$TYPEHASH];

		foreach ($types->$typeName as $field)
		{
			if ($field->type == "string" || Utils::string_contains($field->type, 'bytes')) {
				$types_inputs[] = "bytes32";
				$data_inputs[]  = Utils::sha3($data->{$field->name});
			}
			else {
				$types_inputs[] = $field->type;
				$data_inputs[]  = $data->{$field->name};
			}
		}

		$encoded = ABI::EncodeParameters_External($types_inputs, $data_inputs);
		return Utils::sha3($encoded);
	}


	/*
		$types = [ 
			"Message" => [ 
				(object) [ "name" => "myName1", "type" => "uint256"] ,
				(object) [ "name" => "myName2", "type" => "string"] 
			] 
		];
		$domain = (object) [ 
			"name" => "My DDapp", 
			"version" => "1", 
			"chainId" => 123, 
			"verifyingContract" => "0xabc...123"
		];
		$data = (object) [ 
			"myName1" => 123,
			"myName2" => "abc",
		];
	*/
	public static function signTypedData_digest(array $types, object $domain, object $message, string $primaryType = "") : string
	{ 
		if (empty($primaryType)) {
			$primaryType = array_keys($types)[0];
		} 

		$finalTypes 	= self::processTypes($types);  
		$finalDomain	= self::encodeData('EIP712Domain', $domain, $finalTypes);
		$finalHash 		= self::encodeData($primaryType, $message, $finalTypes);

		return Utils::sha3('0x1901' . Utils::stripZero($finalDomain) . Utils::stripZero($finalHash));
	}

}