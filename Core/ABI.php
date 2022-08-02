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
}


use stdClass; 
use kornrunner\Keccak;
use phpseclib\Math\BigInteger as BigNumber;

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
    

    public function Init($baseJSON)
    {
        $this->functions = [];
        $this->events = [];
        $this->other_objects = [];
        $this->events_encoded = [];
        $parsedJSON = json_decode($baseJSON);

        foreach($parsedJSON as $func)
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


    public function GetFunction($function_name)
    {
        if($function_name == '') return $this->constructor;
        return $this->functions[$function_name];
    }


    public function GetEvent($event_name)
    { 
        return $this->events[$event_name];
    }


    public function GetEventFromHash($event_hash)
    { 
        return $this->events_encoded[$event_hash];
    }
 

    private static function GetParameterType($abi_string)
    { 
        if (str_contains($abi_string, 'tuple'))         return VariableType::Tuple;
        else if (str_contains($abi_string, 'string'))   return VariableType::String;
        else if (str_contains($abi_string, 'bytes'))    return VariableType::String;
        else if (str_contains($abi_string, 'byte[]'))   return VariableType::String;
        //static
		
		else if (str_contains($abi_string, 'uint') )     return VariableType::UInt;
        else if (str_contains($abi_string, 'int') )     return VariableType::Int;
        else if (str_contains($abi_string, 'fixed') )   return VariableType::Int;
        else if (str_contains($abi_string, 'bool'))     return VariableType::Bool;
        else if (str_contains($abi_string, 'address'))  return VariableType::Address;
        else
        {
            var_dump("parameter error: " . $abi_string);
        }
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
         
        $hashData .= self::EncodeGroup($function->inputs, $data);
        //var_dump($hashData);
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
            else if ($type == 'uint' || $type == 'int') $type .= '256';
            else if ($type == 'uint[]') $type = 'uint256[]';
            else if ($type == 'int[]') $type = 'int256[]';

            $signature .= $type . ',';
        }

        if(count($function_inputs) > 0)  $signature = substr($signature, 0, -1); 
        $signature .= ')';

        return $signature;
    }


    private function forceWrapperArray($function, $data)
    {  
        if ($function === null || count($function->inputs) === 0)  {   
            $data = [];
        } 
        else if ($data == null)  {
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

     
    public static function EncodeGroup($inputs, $data)
    { 
        $hashData = "";
        $currentDynamicIndex = count($inputs) * self::NUM_ZEROS / 2; 
        
        //parameters
        $i = 0; 
        foreach ($inputs as $pos => $input) 
        {     
            $var_name = $input->name;
            $inputData = is_object($data) ? $data->$var_name : $data[$pos];  
            if (is_array($data) && $inputData == null) $inputData = $data[$var_name];

            $hashData .= self::EncodeInput($input, $inputData, 1, $currentDynamicIndex); 
  
            if(isset($input->hash)) $currentDynamicIndex += strlen($input->hash) / 2;
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


    private static function EncodeInput_Array($input_type, $inputData)
    { 
        $inputs = [];
        $currentDynamicIndex = count($inputData) * self::NUM_ZEROS / 2;
        
        //array lenght
        $hashData = self::EncodeInput_UInt(count($inputData));
          
        foreach($inputData as $pos => $element) 
        {      
            $input = new stdClass(); 
            $input->type = $input_type;
            $inputs []= $input; 
            $hashData .= self::EncodeInput($input, $element, 1, $currentDynamicIndex);  
            $currentDynamicIndex += strlen($input->hash) / 2; 
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
            //change byte[] or byte[xx] to string as they are encoded the same way
            if(str_contains($input->type, 'byte[')) {
                $input->type = 'string' .  substr($input->type, strpos($input->type, ']'));
            } 

	    	$input_type = is_string($input) ? $input : $input->type;
            $varType = self::GetParameterType($input_type);

            //dynamic
            if(str_contains($input->type, '['))
            {
                $last_array_marker = strrpos($input->type, '[');  
                $clean_type = substr($input->type, 0, $last_array_marker); 
 
                $input->hash =  self::EncodeInput_Array($clean_type, $inputData);
                $res = self::EncodeInput_UInt($currentDynamicIndex); 
                return $res;
                
            }
            else if  ($varType == VariableType::Tuple)
            {
                $input->hash =  self::EncodeGroup($input->components, $inputData);
                $res = self::EncodeInput_UInt($currentDynamicIndex); 
                return $res;
            }
            else if ($varType == VariableType::String) {
                $input->hash = self::EncodeInput_String($inputData);
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
		if($data instanceof BigNumber) { 
			$hash = self::AddZeros($data->toHex(true), true); 
		} 
		else {
			$hash = self::AddZeros(dechex($data), true); 
		} 
       
        return  $hash;
    }

	private static function EncodeInput_Int($data)
    {   
		if($data instanceof BigNumber) { 
			if($data->toString()[0] == '-')
				$hash = self::AddNegativeF($data->toHex(true), true); 
			else
				$hash = self::AddZeros($data->toHex(true), true); 
		} 
		else {
			$hash = self::AddZerosOrF(dechex($data), true); 
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


    private static function AddZeros($data, $add_left)
    { 
        $total = self::NUM_ZEROS - (strlen($data) % self::NUM_ZEROS);
        $res = $data;

        if($total > 0) {
            for($i=0; $i < $total; $i++) {
                if($add_left)   $res = '0'.$res;
                else            $res .= '0';
            }
        }
         
        return $res;
    }

	private static function AddNegativeF($data, $add_left)
    { 
        $total = self::NUM_ZEROS - (strlen($data) % self::NUM_ZEROS);
        $res = $data;

        if($total > 0) {
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

        $total = self::NUM_ZEROS - (strlen($data) % self::NUM_ZEROS);
        $res = $data;

        if($total > 0) {
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

        $decoded = $this->DecodeGroup($function->outputs, $encoded, 0);
        //var_dump($encoded);
        return $decoded;
    }


    public function DecodeGroup($outputs, $encoded, $index)
    { 
        $group = new stdClass();
        $first_index = $index;
        $elem_index = 1;
        $tuple_count = 1;
        $array_count = 1; 
		$output_count = count($outputs);

        foreach ($outputs as $output)
        {
            if(str_contains($output->type, 'byte[')) {
                $output->type = 'string' .  substr($output->type, strpos($output->type, ']'));
            } 

            //var_dump($output->type." ".$output->name." ".$index);
			$output_type = is_string($output) ? $output : $output->type;
            $varType = self::GetParameterType($output_type);
             
            //dynamic
            if(str_contains($output->type, '['))
            {  
                $last_array_marker = strrpos($output->type, '[');  
                $clean_type = substr($output->type, 0, $last_array_marker);

                $var_name = $output->name != '' ? $output->name : 'array_'.$array_count; 
                $dynamic_data_start = $first_index + $this->DecodeInput_UInt_Internal($encoded, $index) * 2; 
                $group->$var_name = $this->DecodeInput_Array($output, $clean_type, $encoded, $dynamic_data_start); 
                $array_count++;
            }
            else if ($varType == VariableType::Tuple) { 
                $var_name = $output->name != '' ? $output->name : 'tuple_'.$tuple_count;
                $dynamic_data_start = $first_index + $this->DecodeInput_UInt_Internal($encoded, $index) * 2;
                $group->$var_name = $this->DecodeGroup($output->components, $encoded, $dynamic_data_start);
                $tuple_count++;
            }
            else if ($varType == VariableType::String) { 
                $var_name = $output->name != '' ? $output->name : 'elem_'.$elem_index;
                $dynamic_data_start = $first_index + $this->DecodeInput_UInt_Internal($encoded, $index) * 2;
                $group->$var_name = $this->DecodeInput_String($encoded, $dynamic_data_start);  
            }
            //static
            else
            {
				$var_name = 'result';
				if($output->name != '')  $var_name = $output->name;
				else if($output_count > 1) 'elem_'.$elem_index; 

				$output_type = is_string($output) ? $output : $output->type;
                $group->$var_name = $this->DecodeInput_Generic(self::GetParameterType($output_type), $encoded, $index);  
            }  

            $elem_index++;
            $index += self::NUM_ZEROS;
        } 

        return $group; 
    } 

	
    private function DecodeInput_Array($output, $array_inner_type, $encoded, $index)
    {
        $array = [];
        $first_index = $index;  
        $varType = self::GetParameterType($array_inner_type);

        $length = $this->DecodeInput_UInt_Internal($encoded, $first_index); 
        $first_index += self::NUM_ZEROS;
        $index += self::NUM_ZEROS;
  
        for($i = 0; $i < $length; $i++)
        {  
            $res = "error"; 
            if (str_contains($array_inner_type, '[')) {   
                $last_array_marker = strrpos($array_inner_type, '[');  
                $clean_type = substr($array_inner_type, 0, $last_array_marker);
 
                $element_start = $first_index + $this->DecodeInput_UInt_Internal($encoded, $index) * 2;
                $res = $this->DecodeInput_Array($output, $clean_type, $encoded, $element_start); 
            }
            else if($varType == VariableType::Tuple) {
                $element_start = $first_index + $this->DecodeInput_UInt_Internal($encoded, $index) * 2; 
                $res = $this->DecodeGroup($output->components, $encoded, $element_start); 
            }
            else if($varType == VariableType::String) { 
                $element_start = $first_index + $this->DecodeInput_UInt_Internal($encoded, $index) * 2;
                $res = $this->DecodeInput_String($encoded, $element_start);  
            }
            else {
                $res = $this->DecodeInput_Generic($varType, $encoded, $index); 
            }
            
            $array []= $res;
            $index += self::NUM_ZEROS; 
        }

        return $array;
    }


 
    private function DecodeInput_Generic($variableType, $encoded, $start)
    {
        if($variableType == VariableType::String) {
            return $this->DecodeInput_String($encoded, $start);
        }
        else if($variableType == VariableType::UInt) {
            return $this->DecodeInput_UInt($encoded, $start);
        }
		else if($variableType == VariableType::Int) {
            return $this->DecodeInput_Int($encoded, $start);
        }
        else if($variableType == VariableType::Bool) {
            return $this->DecodeInput_Bool($encoded, $start);
        }
        else if($variableType == VariableType::Address) {
            return $this->DecodeInput_Address($encoded, $start);
        }
    }
 
	private function DecodeInput_UInt_Internal($encoded, $start)
    {
        $partial = substr($encoded, $start, 64);   
        $partial = $this->RemoveZeros($partial, true); 
        return hexdec($partial);
    }

    private function DecodeInput_UInt($encoded, $start)
    {
        $partial = substr($encoded, $start, 64);   
        $partial = $this->RemoveZeros($partial, true); 
        //return hexdec($partial);
		return new BigNumber($partial, 16);
    }

	private function DecodeInput_Int($encoded, $start)
    {
        $partial = substr($encoded, $start, 64);   
		$first_byte = hexdec($partial[0]);

		$partial = $this->RemoveZeros($partial, true); 
		$partial_big = new BigNumber($partial, -16);
 
        return $partial_big;
    }


    private function DecodeInput_Bool($encoded, $start)
    {
        $partial = substr($encoded, $start, 64);
        $partial = $this->RemoveZeros($partial, true); 
        return $partial == '1';
    }

    private function DecodeInput_Address($encoded, $start)
    {
        $partial = $this->RemoveZeros(substr($encoded, $start, 64), true);  
        return '0x'.$partial;
    }


    private function DecodeInput_String($encoded, $start)
    { 
        $length = $this->DecodeInput_UInt_Internal($encoded, $start); 
        $start += self::NUM_ZEROS;

        $partial = substr($encoded, $start, $length * 2); 
        return hex2bin($partial);
    }
 

    private function RemoveZeros($data, $remove_left)
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
				$varType = self::GetParameterType($input->type);
				$res->indexed[$input->name] = $this->DecodeInput_Generic($varType, $log->topics[$indexed_index], 0);

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