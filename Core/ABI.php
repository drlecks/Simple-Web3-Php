<?php

/**
 * This file is part of simple-web3-php package.
 * 
 * (c) Alex Cabrera  
 * 
 * @author Alex Cabrera
 * @license MIT 
 */

 //https://docs.soliditylang.org/en/develop/abi-spec.html

namespace SWeb3;

abstract class VariableType
{
    const None = 0;
    const Array = 1;
    const Tuple = 2;
    const String = 3;
    const Address = 4;
    const Int = 5;
    const UInt = 6;
	const Bool = 7;
	const Bytes = 8;
	const BytesFixed = 9;
}


use stdClass; 
use Exception;
use kornrunner\Keccak;
use phpseclib3\Math\BigInteger as BigNumber;

class ABI
{
    private $baseJSON;
    public $constructor;
    public $functions;
    public $events; 
    public $other_objects; 

    //dictionary of encoded signature => function
    public $events_encoded;

    const NUM_ZEROS = 64;
    

    public function Init(string $baseJSON)
    {
        $this->functions = [];
        $this->events = [];
        $this->other_objects = [];
        $this->events_encoded = []; 
        $parsedJSON = json_decode($baseJSON);

        foreach ($parsedJSON as $func)
         { 
            if($func->type == 'constructor') {
                $this->constructor = $func;
            }
            else if($func->type == 'event') {
                $this->events[$func->name] = $func; 
                $this->events_encoded[$this->GetSignatureFromEvent($func)] = $func;
            }
            else if($func->type == 'function') {
                $this->functions[$func->name] = $func; 
            } 
            else {
                $this->other_objects []= $func; 
            }  
        }
    }


    public function GetFunction(?string $function_name)
    {
        if (empty($function_name)) return $this->constructor;
 
		if(!empty($this->functions[$function_name])) {
            return $this->functions[$function_name];
        } 
		else {
            return null;
        }
    }


    public function GetEvent(?string $event_name)
    { 
		if (empty($event_hash)) return null;
 
		if(!empty($this->events[$event_name])) {
            return $this->events[$event_name];
        } 
		else {
            return null;
        }
    }


    public function GetEventFromHash(?string $event_hash)
    { 
		if (empty($event_hash)) return null;

		if (!empty($this->events_encoded[$event_hash])) {
            return $this->events_encoded[$event_hash];
        } 
		else {
            return null;
        }
    }
 

    private static function GetParameterType(?string $abi_string_type)
    {   
		//bytes
		if ($abi_string_type == 'bytes')    							return VariableType::Bytes; 
		else if  (Utils::string_contains($abi_string_type, 'bytes')) 	return VariableType::BytesFixed;

		//dynamic
        else if (Utils::string_contains($abi_string_type, 'tuple'))     return VariableType::Tuple;
        else if (Utils::string_contains($abi_string_type, 'string'))   	return VariableType::String;
         
		//static 
		else if (Utils::string_contains($abi_string_type, 'uint') )    	return VariableType::UInt;
        else if (Utils::string_contains($abi_string_type, 'int') )     	return VariableType::Int;
        else if (Utils::string_contains($abi_string_type, 'fixed') )   	return VariableType::Int;
        else if (Utils::string_contains($abi_string_type, 'bool'))     	return VariableType::Bool;
        else if (Utils::string_contains($abi_string_type, 'address'))  	return VariableType::Address;
        
		//error
		else {
			var_dump("parameter error: " . $abi_string_type); 
		}
		 
		return VariableType::Int;
    }


	private static function IsStaticParameter(int $vType) : bool
    { 
		return $vType == VariableType::UInt 
				|| $vType == VariableType::Int
				|| $vType == VariableType::Bool
				|| $vType == VariableType::Address
				|| $vType == VariableType::BytesFixed;
    }


	private static function ExistsDynamicParameter(array $components) : bool
	{ 
		foreach ($components as $comp) 
		{  
			if (is_string($comp)) 
			{
				$isStatic = self::IsStaticParameter(self::GetParameterType($comp)); 
			}
			else
			{
				if (isset($comp->components)) {
					$isStatic = !self::ExistsDynamicParameter($comp->components); 
				}
				else {
					$isStatic = self::IsStaticParameter(self::GetParameterType($comp->type)); 
				}
			}

			if (!$isStatic) {  
				return true; 
			}
		}

		return false;
	}


    public function isCallFunction($function_name)
    {
        $function = $this->GetFunction($function_name);
        if ($function == null) return false;

        $stateMutability = "";
        if (isset($function->stateMutability)) $stateMutability = $function->stateMutability; 

        return ($stateMutability == 'pure' || $stateMutability == 'view');
    }


    public function isSendFunction($function_name)
    {
        $function = $this->GetFunction($function_name); 
        if ($function == null) return false;

        $stateMutability = "";
        if (isset($function->stateMutability)) $stateMutability = $function->stateMutability; 

        return ($stateMutability != 'pure' && $stateMutability != 'view');
    }

	

/***************************************ENCODE  */


    public function EncodeData($function_name, $data)   
    { 
        $function = $this->GetFunction($function_name);  
        $data = $this->forceWrapperArray($function, $data); 

        $hashData = "0x";

        if($function_name != '') {
            //function signature (first 4 bytes) (not for constructor)
            $signature = $this->GetSignatureFromFunction($function);
            $sha3 = Keccak::hash($signature, 256);
            $hashData .= substr($sha3,0, 8);
        }
         
		if ($function !== null) {
			$hashData .= self::EncodeGroup($function->inputs, $data);
		}
         
        return $hashData;
    }


    public function GetSignatureFromEvent($function)
    { 
        $signature = $this->GetSignatureFromFunction($function); 
        return  '0x' . Keccak::hash($signature, 256);
    }

    
    private function GetSignatureFromFunction($function)
    {
        $signature = $function->name . $this->GetSignatureFromFunction_Inputs($function->inputs); 
        return $signature;
    }

 
    private function GetSignatureFromFunction_Inputs($function_inputs)
    {
        $signature = "(";
        foreach($function_inputs as $input)
        {
            $type = $input->type;
            if ($type == 'tuple') $type = $this->GetSignatureFromFunction_Inputs($input->components);
			else if ($type == 'tuple[]') $type = $this->GetSignatureFromFunction_Inputs($input->components) . '[]';
            else if ($type == 'uint' || $type == 'int') $type .= '256';
            else if ($type == 'uint[]') $type = 'uint256[]';
            else if ($type == 'int[]') $type = 'int256[]';

            $signature .= $type . ',';
        }

        if (count($function_inputs) > 0)  $signature = substr($signature, 0, -1); 
        $signature .= ')';

        return $signature;
    }


    private function forceWrapperArray($function, $data)
    {  
        if ($function === null || count($function->inputs) === 0)  {   
            $data = [];
        } 
        else if ($data === null)  {
            $data = [];
        } 
        else if(!is_array($data))  {
            $data = [$data];
        } 
        else 
        {
            $tempData = $data;
            $input = $function->inputs[0];
            $input_array_dimension = substr_count($input->type, '[');

            while ($input_array_dimension > 0) {
                if (is_array($tempData[0])) {
                    if($input_array_dimension == 1) break;
                    else $tempData = $tempData[0];
                }
                else { 
                    $data = [$data]; 
                    break;
                }

                $input_array_dimension--;
            }
        }

        return $data;
    }

     
    public static function EncodeGroup(array $inputs, $data) : string
    { 
        $hashData = ""; 
        $currentDynamicIndex = 0;
		{
			$staticInputCount = 0;
			foreach ($inputs as $input) 
			{
				$input_type = is_string($input) ? $input : $input->type;
				$varType = self::GetParameterType($input_type);
				
				// for non-tuple item, we'll have in-place value or offset
				if ($varType != VariableType::Tuple) {
					$staticInputCount++;
					continue;
				}
				
				// for tuple we'll have several in place values or one pointer to the start of several in-place values
				if (self::ExistsDynamicParameter($input->components)) {
					$staticInputCount++;
				} else {
					$staticInputCount += count($input->components);
				}
			}
			$currentDynamicIndex = $staticInputCount * self::NUM_ZEROS / 2;
		}
         
        //parameters
        $i = 0; 
        foreach ($inputs as $pos => $input) 
        {      
			$var_name = $pos;
			if (is_object($input)) {
				if (isset($input->name)) $var_name = $input->name; 
			}
			else if (is_string($input)){
				$var_name =  $input;
			} 
			
            $inputData = is_object($data) ? $data->$var_name : (isset($data[$pos]) ? $data[$pos] : null);   
            if (is_array($data) && $inputData === null) $inputData = $data[$var_name];
  
            $hashData .= self::EncodeInput($input, $inputData, 1, $currentDynamicIndex); 
 
            if (isset($input->hash)) $currentDynamicIndex += strlen($input->hash) / 2;
            $i++;
        } 

        foreach($inputs as $pos => $input) { 
            $hashData .= self::EncodeInput($input, null, 2, $currentDynamicIndex); 
        } 

        if (count($inputs) == 0) {
            $hashData .= self::NUM_ZEROS / 2;
        } 
  
        return $hashData;
    }


	public static function EncodeParameters_External(array $input_types, array $data) : string
    { 
		$inputs = array();
		foreach($input_types as $it) {
			$input = new stdClass();
			$input->name = $it;
			$input->type = $it;
			$inputs []= $input;
		}
 
        return '0x' . self::EncodeGroup($inputs, $data);
    }


	public static function EncodeParameter_External(string $input_name, $data) : string
    { 
        $hashData = "";
		$currentDynamicIndex = self::NUM_ZEROS / 2;

		$input = new stdClass();
		$input->type = $input_name;

		$hashData .= self::EncodeInput($input, $data, 1, $currentDynamicIndex); 

		if (isset($input->hash)) $currentDynamicIndex += strlen($input->hash) / 2;

        $hashData .= self::EncodeInput($input, null, 2, $currentDynamicIndex); 
 
        return '0x' . $hashData;
    }


	public static function EncodePacked(array $inputs, array $data) : string
	{
		$res = "";

		for ($i = 0; $i < count($inputs); $i++)
		{ 
			$type 		= $inputs[$i];
			$val 		= $data[$i];  
			$varType 	= self::GetParameterType($type);

			if (Utils::string_contains($type, '[')) 
			{
				throw new Exception($type . " - Not suported (EncodePacked)");
			}
			else if ($varType == VariableType::String || $varType == VariableType::Bytes || $varType == VariableType::BytesFixed) 
			{
				if (substr($val, 0, 2) == "0x") $res .= substr($val, 2);
				else 							$res .= bin2hex($val);
			}
			else if ($varType == VariableType::Int || $varType == VariableType::UInt) 
			{
				$x = dechex($val);
				$fixedLength = (int)preg_replace('/[^0-9]/', '', $type,) / 4;
				if ($fixedLength <= 0) $fixedLength = 64;
				$res .= str_pad($x, $fixedLength, '0', STR_PAD_LEFT);
			}
			else if ($varType == VariableType::Address) 
			{
				$res .= (substr($val, 0, 2) == "0x") ? substr($val, 2) : $val;
			}
			else 
			{
				throw new Exception($type . " - Not suported (EncodePacked)");
			}
		}

		return '0x' . $res;
	}
	


    private static function EncodeInput_Array($full_input, $inputData, $isStaticLength)
    { 
		$inputs = [];
		$currentDynamicIndex = count($inputData) * self::NUM_ZEROS / 2;

		//prepare clean input 
		$last_array_marker 	= strrpos($full_input->type, '[');  
		$clean_type 		= substr($full_input->type, 0, $last_array_marker); 
		 
		$clean_internalType = "";
		if (isset($full_input->internalType)) {
			$last_array_marker 	= strrpos($full_input->internalType, '[');  
			$clean_internalType = substr($full_input->internalType, 0, $last_array_marker); 
		}
		 
		$hashData = "";

		if (!$isStaticLength) {
			//add array length
			$hashData = self::EncodeInput_UInt(count($inputData));
		} 

        foreach ($inputData as $pos => $element) 
        {       
			$input = new stdClass(); 
			$input->type = $clean_type; 
			$input->internalType = $clean_internalType; 
			if (isset($full_input->components)) {
				$input->components = $full_input->components;
			} 
			$inputs []= $input;

            $hashData .= self::EncodeInput($input, $element, 1, $currentDynamicIndex);  

            if (isset($input->hash)) $currentDynamicIndex += strlen($input->hash) / 2; 
        }

        foreach($inputs as $pos => $input) 
        { 
			$data = $inputData[$pos];
            $hashData .= self::EncodeInput($input, $data, 2, $currentDynamicIndex);  
        }

        if (count($inputs) == 0) {
            $hashData .= self::NUM_ZEROS / 2;
        } 

        return $hashData;
    }

 
    private static function EncodeInput($input, $inputData, $round, &$currentDynamicIndex)
    { 
        $hash = "";

        if($round == 1)
        {     
	    	$input_type = is_string($input) ? $input : $input->type;
            $varType = self::GetParameterType($input_type);

            //dynamic
            if (Utils::string_contains($input_type, '['))
            {   
				//arrays with all static parameters have no initial array offset 
				$isStaticArray = self::IsStaticParameter($varType);
				if ($varType == VariableType::Tuple) {
					$isStaticArray = !self::ExistsDynamicParameter($input->components);
				}  
				$isStaticLength = $isStaticArray && !Utils::string_contains($input_type, '[]');  
                 
				$res = self::EncodeInput_Array($input, $inputData, $isStaticLength); 
				if (!$isStaticLength) {
					$input->hash = $res;
					return self::EncodeInput_UInt($currentDynamicIndex);
				}
				return $res;
            }
            else if ($varType == VariableType::Tuple)
            {
            	$res = self::EncodeGroup($input->components, $inputData);
				
				// if the tuple is dynamic, we return offset and add tuple's data at the end
				if (self::ExistsDynamicParameter($input->components)) {
					$input->hash = $res;
					return self::EncodeInput_UInt($currentDynamicIndex);
				}
				return $res;
            }
            else if ($varType == VariableType::String) {
                $input->hash = self::EncodeInput_String($inputData);
                $res = self::EncodeInput_UInt($currentDynamicIndex);  
                return $res;
            }
			else if ($varType == VariableType::Bytes) {
                $input->hash = self::EncodeInput_Bytes($inputData);
                $res = self::EncodeInput_UInt($currentDynamicIndex);  
                return $res;
            }
            //static
            else if ($varType == VariableType::UInt) { 
                return self::EncodeInput_UInt($inputData);
            }
			else if ($varType == VariableType::Int) { 
                return self::EncodeInput_Int($inputData);
            }
            else if ($varType == VariableType::Bool) { 
                return self::EncodeInput_Bool($inputData);
            }
            else if ($varType == VariableType::Address) { 
                return self::EncodeInput_Address($inputData);
            }  
			else if ($varType == VariableType::BytesFixed) { 
                return self::EncodeInput_BytesFixed($inputData);
            }  
        }
        else if($round == 2)
        {
            if (isset($input->hash) and $input->hash != '') { 
                return $input->hash;
            }
        }
 
        return  $hash;
    }

	private static function EncodeInput_UInt($data)
    {  
		if (is_string($data) && ctype_digit($data)) { 
			$bn = Utils::toBn($data);
			$hash = self::AddZeros($bn->toHex(true), true); 
		} 
		else if ($data instanceof BigNumber) { 
			$hash = self::AddZeros($data->toHex(true), true); 
		} 
		else if (is_int($data) || is_long($data)) {
			$hash = self::AddZeros(dechex($data), true); 
		} 
		else {
			throw new Exception("EncodeInput_UInt, not valid input type");
		}
       
        return  $hash;
    }

	private static function EncodeInput_Int($data)
    {   
		if (is_string($data) && ctype_digit($data)) { 
			$bn = Utils::toBn($data);
			$hash = self::AddNegativeF($bn->toHex(true), true); 
		} 
		else if ($data instanceof BigNumber) { 
			if($data->toString()[0] == '-')
				$hash = self::AddNegativeF($data->toHex(true), true); 
			else
				$hash = self::AddZeros($data->toHex(true), true); 
		} 
		else  if (is_int($data) || is_long($data)) {
			$hash = self::AddZerosOrF(dechex($data), true); 
		} 
		else {
			throw new Exception("EncodeInput_Int, not valid input type");
		}
		
        return  $hash;
    }

    private static function EncodeInput_Bool($data)
    { 
        $hash = $data ? '1' : '0';
        $hash = self::AddZeros($hash, true);  
        return  $hash;
    }

    private static function EncodeInput_Address($data)
    { 
        $hash = self::AddZeros(substr($data, 2), true); 
        return  $hash;
    }
 
	private static function EncodeInput_String($data)
    {  
        //length + hexa string
        $hash = self::EncodeInput_UInt(strlen($data)) . self::AddZeros(bin2hex($data), false);  

        return  $hash;
    }

	private static function EncodeInput_Bytes($data)
    { 
		$hexa = $data;
 
		//I'm not proud of this. Official parsers seem to handle 0x as 0x0 when input is type bytes
		//I think it can cause problems when you want to use bytes as a string, because you can't save the string "0x"
		//but looking at issue #50 it seems clear that the current evm behaviour is this.
		if ($data == '0x') {
			$data = '';
		}

		//if data is not a valid hexa, it means its a binary rep
		if (substr($data, 0, 2) != '0x' || !ctype_xdigit(substr($data, 2)) || strlen($data) % 2 != 0) { 
			$hexa = bin2hex($data); 
		}

		if (substr($hexa, 0, 2) == '0x') {
			$hexa = substr($hexa, 2);
		} 

        //length + hexa string
        $hash = self::EncodeInput_UInt(strlen($hexa) / 2) . self::AddZeros($hexa, false);  

        return  $hash;
    }


	private static function EncodeInput_BytesFixed($data)
    { 
		$hexa = $data;

		//if data is not a valid hexa, it means its a binary rep
		if (substr($data, 0, 2) != '0x' || !ctype_xdigit(substr($data, 2)) || strlen($data) % 2 != 0) { 
			$hexa = bin2hex($data); 
		}

		if (substr($hexa, 0, 2) == '0x') {
			$hexa = substr($hexa, 2);
		}

        //length + hexa string
        $hash = self::AddZeros($hexa, false);   

        return  $hash;
    }


    private static function AddZeros($data, $add_left)
    { 
		$remaining = strlen($data);
		if ($remaining > self::NUM_ZEROS) $remaining %= self::NUM_ZEROS;
        $total = self::NUM_ZEROS - $remaining;

        $res = $data;

        if ($total > 0) {
            for($i=0; $i < $total; $i++) {
                if($add_left)   $res = '0'.$res;
                else            $res .= '0';
            }
        }
         
        return $res;
    }
	

	private static function AddNegativeF($data, $add_left)
    { 
		$remaining = strlen($data);
		if ($remaining > self::NUM_ZEROS) $remaining %= self::NUM_ZEROS;
        $total = self::NUM_ZEROS - $remaining; 

        $res = $data;

        if ($total > 0) {
            for($i=0; $i < $total; $i++) {
                if($add_left)   $res = 'f'.$res;
                else            $res .= 'f';
            }
        }
         
        return $res;
    }


	private static function AddZerosOrF($data, $add_left)
    { 
		$valueToAdd = (strtolower($data[0]) == 'f' && strlen($data) == 16) ? 'f' : '0'; 

		$remaining = strlen($data);
		if ($remaining > self::NUM_ZEROS) $remaining %= self::NUM_ZEROS;
        $total = self::NUM_ZEROS - $remaining; 

        $res = $data;

        if ($total > 0) {
            for($i=0; $i < $total; $i++) {
                if($add_left)   $res = $valueToAdd.$res;
                else            $res .= $valueToAdd;
            }
        }
         
        return $res;
    }


/***************************************DECODE  */

    public function DecodeData($function_name, $encoded)
    { 
        $encoded = substr($encoded, 2);
        $function = $this->GetFunction($function_name);     

        $decoded = self::DecodeGroup($function->outputs, $encoded, 0);

        return $decoded;
    }


    public static function DecodeGroup($outputs, $encoded, $index)
    { 
        $group 			= new stdClass();
        $first_index 	= $index;
        $elem_index 	= 1;
        $tuple_count 	= 1;
        $array_count 	= 1; 
		$output_count 	= count($outputs); 
 
  
        foreach ($outputs as $output)
        {    
			$output_type 		= is_string($output) ? $output : $output->type;
            $varType 			= self::GetParameterType($output_type);
			$output_type_offset = self::GetOutputOffset($output);
			$var_name 			= '';  

            //dynamic
            if(Utils::string_contains($output->type, '['))
            {   
                $var_name 			= $output->name != '' ? $output->name : 'array_'.$array_count; 
				  
				//arrays with all static parameters have no initial array offset 
				$isStaticArray = self::IsStaticParameter($varType);
				if ($varType == VariableType::Tuple) {
					$isStaticArray = !self::ExistsDynamicParameter($output->components);
				}  
				$isStaticLength = $isStaticArray && !Utils::string_contains($output->type, '[]');

				$dynamic_data_start = 0; 
				if ($isStaticLength) 	$dynamic_data_start = $index;  
				else 					$dynamic_data_start = $first_index + self::DecodeInput_UInt_Internal($encoded, $index) * 2;   
  
                $group->$var_name = self::DecodeInput_Array($output, $encoded, $dynamic_data_start);  
                $array_count++; 
            }
            else if ($varType == VariableType::Tuple) 
			{  
                $var_name = $output->name != '' ? $output->name : 'tuple_'.$tuple_count; 

				//tuples with only static parameters have no initial tuple offset
				$hasDynamicParameters 	= self::ExistsDynamicParameter($output->components); 

                $dynamic_data_start 	= $index;  
				if ($hasDynamicParameters) { 
					$dynamic_data_start = $first_index + self::DecodeInput_UInt_Internal($encoded, $index) * 2; 
				}   

                $group->$var_name = self::DecodeGroup($output->components, $encoded, $dynamic_data_start);  
                $tuple_count++; 
            }
            else if ($varType == VariableType::String) 
			{ 
                $var_name 			= $output->name != '' ? $output->name : 'elem_'.$elem_index;
                $dynamic_data_start = $first_index + self::DecodeInput_UInt_Internal($encoded, $index) * 2;
                $group->$var_name 	= self::DecodeInput_String($encoded, $dynamic_data_start);  
            }
			else if ($varType == VariableType::Bytes) 
			{ 
                $var_name 			= $output->name != '' ? $output->name : 'elem_'.$elem_index;
                $dynamic_data_start = $first_index + self::DecodeInput_UInt_Internal($encoded, $index) * 2;
                $group->$var_name 	= self::DecodeInput_Bytes($encoded, $dynamic_data_start);  
            }
            //static
            else
            { 
				$var_name = 'result';
				if($output->name != '')  	$var_name = $output->name;
				else if($output_count > 1) 	$var_name = 'elem_'.$elem_index; 
 
                $group->$var_name = self::DecodeInput_Generic($varType, $encoded, $index);   
            }   

            $elem_index++;
            $index += $output_type_offset * self::NUM_ZEROS;  			
        }  

        return $group; 
    } 


	public static function DecodeParameter_External(string $output_type, string $encoded)
    { 
		$output = new stdClass();
		$output->type = $output_type; 
		$varType = self::GetParameterType($output_type);
		$dynamic_data_start = self::NUM_ZEROS;

		$res = "";

		if (substr($encoded, 0, 2) == '0x') {
			$encoded = substr($encoded, 2);
		}

		//dynamic
		if(Utils::string_contains($output->type, '['))
		{    
			//arrays with all static parameters have no initial array offset 
			$isStaticArray = self::IsStaticParameter($varType);
			if ($varType == VariableType::Tuple) {
				$isStaticArray = !self::ExistsDynamicParameter($output->components);
			}  
			$isStaticLength = $isStaticArray && !Utils::string_contains($output->type, '[]');

			$dynamic_data_start = 0; 
			if ($isStaticLength) 	$dynamic_data_start = 0;  
			else 					$dynamic_data_start = 0 + self::DecodeInput_UInt_Internal($encoded, 0) * 2;   

			$res = self::DecodeInput_Array($output, $encoded, $dynamic_data_start);  
		}
		else if ($varType == VariableType::Tuple) 
		{ 
			//tuples with only static parameters have no initial tuple offset 
			$res = self::DecodeGroup($output->components, $encoded, $dynamic_data_start);  
		}
		else if ($varType == VariableType::String) 
		{   
			$res = self::DecodeInput_String($encoded, $dynamic_data_start);  
		}
		else if ($varType == VariableType::Bytes) 
		{   
			$res = self::DecodeInput_Bytes($encoded, $dynamic_data_start);  
		}
		//simple 
		else
		{   
			$res = self::DecodeInput_Generic($varType, $encoded, 0);   
		}   

        return $res; 
    }   

	
    private static function DecodeInput_Array($output, $encoded, $index)
    { 
        $array = [];
        $first_index = $index;  
 
		$clean_output = clone $output;
		$last_array_marker 	= strrpos($clean_output->type, '[');  
		$clean_output->type 	= substr($clean_output->type, 0, $last_array_marker); 

        $varType 		= self::GetParameterType($clean_output->type);
		$isStaticType 	= self::IsStaticParameter($varType);
		if ($varType == VariableType::Tuple) {
			$isStaticType = !self::ExistsDynamicParameter($output->components);
		} 
 
		$length = 0;
		if ($isStaticType)  { 
			$last_array_marker_end 	= strrpos($output->type, ']');  
		  	$length 				= (int) substr($output->type, $last_array_marker + 1, $last_array_marker_end - $last_array_marker - 1);  
		} 

		if ($length <= 0)  { 
			$length 		= self::DecodeInput_UInt_Internal($encoded, $first_index); 
			$first_index 	+= self::NUM_ZEROS;
        	$index 			+= self::NUM_ZEROS;
		} 

		$element_offset = 1;
		if ($isStaticType) {
			$element_offset = self::GetOutputOffset($clean_output);
		}      
		    
        for ($i = 0; $i < $length; $i++)
        {   
            $res = "error"; 
            if (Utils::string_contains($clean_output->type, '[')) 
			{    
				$isStaticLength = $isStaticType && !Utils::string_contains($clean_output->type, '[]');
				//arrays with all static parameters have no initial array offset 
				$element_start = $index; 
				if ($isStaticLength) { 
					$element_start = $index; 
				}
				else {
					$element_start = $first_index + self::DecodeInput_UInt_Internal($encoded, $index) * 2; 
				} 

                $res = self::DecodeInput_Array($clean_output, $encoded, $element_start);   
            }
            else if($varType == VariableType::Tuple) 
			{
				//tuple with all static parameters have no initial array offset  
				if($isStaticType) { 
					$element_start = $index; 
				}
				else {
					$element_start = $first_index + self::DecodeInput_UInt_Internal($encoded, $index) * 2;  
				} 

                $res = self::DecodeGroup($clean_output->components, $encoded, $element_start);  
            }
            else if($varType == VariableType::String) 
			{ 
                $element_start = $first_index + self::DecodeInput_UInt_Internal($encoded, $index) * 2;
                $res = self::DecodeInput_String($encoded, $element_start);   
            }
			else if($varType == VariableType::Bytes) 
			{ 
                $element_start = $first_index + self::DecodeInput_UInt_Internal($encoded, $index) * 2;
                $res = self::DecodeInput_Bytes($encoded, $element_start);   
            }
            else 
			{
                $res = self::DecodeInput_Generic($varType, $encoded, $index);  
            } 

            $array []= $res;  
			$index += self::NUM_ZEROS * $element_offset;  
        } 

        return $array;
    }

 
    private static function DecodeInput_Generic($variableType, $encoded, $start)
    {
        if($variableType == VariableType::String) {
            return self::DecodeInput_String($encoded, $start);
        }
        else if($variableType == VariableType::UInt) {
            return self::DecodeInput_UInt($encoded, $start);
        }
		else if($variableType == VariableType::Int) {
            return self::DecodeInput_Int($encoded, $start);
        }
        else if($variableType == VariableType::Bool) {
            return self::DecodeInput_Bool($encoded, $start);
        }
        else if($variableType == VariableType::Address) {
            return self::DecodeInput_Address($encoded, $start);
        }
		else if($variableType == VariableType::BytesFixed) {
            return self::DecodeInput_BytesFixed($encoded, $start);
        }
		else if($variableType == VariableType::Bytes) {
            return self::DecodeInput_Bytes($encoded, $start);
        }
    }

 
	private static function DecodeInput_UInt_Internal($encoded, $start)
    {
        $partial = substr($encoded, $start, 64);    
        $partial = self::RemoveZeros($partial, true); 

        return hexdec($partial);
    }


    private static function DecodeInput_UInt($encoded, $start)
    { 
        $partial = substr($encoded, $start, 64);   
        $partial = self::RemoveZeros($partial, true);  
    
		$partial_big = new BigNumber($partial, 16);

		return $partial_big;
    }


	private static function DecodeInput_Int($encoded, $start)
    {
        $partial = substr($encoded, $start, 64);     
		$partial_big = new BigNumber($partial, -16);

        return $partial_big;
    }


    private static function DecodeInput_Bool($encoded, $start)
    {
        $partial = substr($encoded, $start, 64);
        $partial = self::RemoveZeros($partial, true); 
        return $partial == '1';
    }

	
    private static function DecodeInput_Address($encoded, $start)
    {
        $partial = self::RemoveZeros(substr($encoded, $start, 64), true);

		//add zero padding from left for 20 bytes
        $partial = str_pad($partial, 40, '0', STR_PAD_LEFT);

        return '0x'.$partial;
    }


    private static function DecodeInput_String($encoded, $start)
    {  
        $length = self::DecodeInput_UInt_Internal($encoded, $start); 
        $start += self::NUM_ZEROS; 

        $partial = substr($encoded, $start, $length * 2); 
        return hex2bin($partial);
    }


	private static function DecodeInput_BytesFixed($encoded, $start)
    { 
        $partial = self::RemoveZeros(substr($encoded, $start, 64), false);   
        return hex2bin($partial);
    }


	private static function DecodeInput_Bytes($encoded, $start)
    { 
        $length = self::DecodeInput_UInt_Internal($encoded, $start); 
        $start += self::NUM_ZEROS;

        $partial = substr($encoded, $start, $length * 2); 
        return hex2bin($partial);
    }
 

    private static function RemoveZeros($data, $remove_left)
    {
        $index = $remove_left ? 0 : strlen($data) - 1;
        $current = substr($data, $index, 1); 
        while ($current == '0')
        { 
            if ($remove_left) {
                $data = substr($data, 1, strlen($data) - 1);
            }
            else {
                $data = substr($data, 0, -1);
                $index--;
            }
            $current = substr($data, $index, 1);
        }
        
        return $data;
    }


	private static function GetOutputOffset ($output) : int
	{
		$output_type 	= is_string($output) ? $output : $output->type;
		$varType 		= self::GetParameterType($output_type); 

		if (Utils::string_contains($output_type, '[')) 
		{     
			$last_array_marker 		= strrpos($output->type, '[');  
			$last_array_marker_end 	= strrpos($output->type, ']');  
			$length = (int) substr($output->type, $last_array_marker + 1, $last_array_marker_end - $last_array_marker - 1); 

			if ($length > 0) 
			{  
				if ($varType == VariableType::Tuple) 
				{
					if (!self::ExistsDynamicParameter($output->components)) { 
						return $length * self::GetOutputOffset_StaticComponents($output->components);
					}
				}
				else if (self::IsStaticParameter($varType))
				{ 
					return $length;
				} 
			}
		}
		else if ($varType == VariableType::Tuple) 
		{ 
			if (!self::ExistsDynamicParameter($output->components)) {  
				return self::GetOutputOffset_StaticComponents($output->components);
			}
		} 

		return 1;
	}


	private static function GetOutputOffset_StaticComponents($components) : int
	{
		$offset = 0;

		foreach ($components as $comp)
		{
			$output_type 	= is_string($comp) ? $comp : $comp->type;
			$varType 		= self::GetParameterType($output_type);
	
			if (Utils::string_contains($output_type, '[') || $varType == VariableType::Tuple) {     
				$offset += self::GetOutputOffset($comp);
			}
			else { 
				$offset++;
			} 
		}
		 
		return $offset;
	}



	//EVENTS

	//parses event parameters
	//event inputs are splitted between indexed topics and encoded data string
	public function DecodeEvent($event_object, $log) : stdClass
    {
        $res = new stdClass();
		$res->indexed = array();
		$res->indexed []= $event_object->name;

		$res->data = array();

		//split inputs between indexed and raw data
		$indexed_index = 1;
		$data_inputs = array();
 
		foreach ($event_object->inputs as $input)
		{
			if ($input->indexed)
			{
				$input_type = is_string($input) ? $input : $input->type;
				$varType = self::GetParameterType($input_type);
				$res->indexed[$input->name] = $this->DecodeInput_Generic($varType, $log->topics[$indexed_index], 2);

				$indexed_index++;
			}
			else
			{
				$data_inputs []= $input;
			}
		}

		//parse raw data
		$encoded = substr($log->data, 2); 
		$res->data = $this->DecodeGroup($data_inputs, $encoded, 0);
 
		//Return
		return $res;
    }
}
