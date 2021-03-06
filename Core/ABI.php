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
    const Bool = 6;
}


use stdClass; 
use kornrunner\Keccak;

class ABI
{
    private $baseJSON;
    public $constructor;
    public $functions;
    public $events; 
    public $other_objects; 

    //dictionary of encoded signature => function
    public $events_encoded;

    private $num_zeros = 64;
    

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
 

    private function GetParameterType($abi_string)
    { 
        if (str_contains($abi_string, 'tuple'))         return VariableType::Tuple;
        else if (str_contains($abi_string, 'string'))   return VariableType::String;
        else if (str_contains($abi_string, 'bytes'))    return VariableType::String;
        else if (str_contains($abi_string, 'byte[]'))   return VariableType::String;
        //static
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
         
        $hashData .= $this->EncodeGroup($function->inputs, $data);
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
        $signature = $function->name . $this->GetSignatureFromFunction_Inptuts($function->inputs); 
        return $signature;
    }

 
    private function GetSignatureFromFunction_Inptuts($function_inputs)
    {
        $signature = "(";
        foreach($function_inputs as $input)
        {
            $type = $input->type;
            if ($type == 'tuple') $type = $this->GetSignatureFromFunction_Inptuts($input->components);
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

     
    public function EncodeGroup($inputs, $data)
    { 
        $hashData = "";
        $currentDynamicIndex = count($inputs) * $this->num_zeros / 2; 
        
        //parameters
        $i = 0; 
        foreach ($inputs as $pos => $input) 
        {     
            $var_name = $input->name;
            $inputData = is_object($data) ? $data->$var_name : $data[$pos];  
            if (is_array($data) && $inputData == null) $inputData = $data[$var_name];

            $hashData .= $this->EncodeInput($input, $inputData, 1, $currentDynamicIndex); 
             
            if(isset($input->hash)) $currentDynamicIndex += strlen($input->hash) / 2;
            $i++;
        }

        foreach($inputs as $pos => $input) { 
            $hashData .= $this->EncodeInput($input, null, 2, $currentDynamicIndex); 
        }

        if (count($inputs) == 0) {
            $hashData .= $this->num_zeros / 2;
        } 

        return $hashData;
    }


    private function EncodeInput_Array($input_type, $inputData)
    { 
        $inputs = [];
        $currentDynamicIndex = count($inputData) * $this->num_zeros / 2;
        
        //array lenght
        $hashData = $this->EncodeInput_Int(count($inputData));;
          
        foreach($inputData as $pos => $element) 
        {      
            $input = new stdClass(); 
            $input->type = $input_type;
            $inputs []= $input; 
            $hashData .= $this->EncodeInput($input, $element, 1, $currentDynamicIndex);  
            $currentDynamicIndex += strlen($input->hash) / 2; 
        }

        foreach($inputs as $pos => $input) 
        {
            $data = $inputData[$pos];
            $hashData .= $this->EncodeInput($input, $data, 2, $currentDynamicIndex);  
        }

        if (count($inputs) == 0) {
            $hashData .= $this->num_zeros / 2;
        } 

        return $hashData;
    }

 
    private function EncodeInput($input, $inputData, $round, &$currentDynamicIndex)
    { 
        $hash = "";

        if($round == 1)
        {  
            //change byte[] or byte[xx] to string as they are encoded the same way
            if(str_contains($input->type, 'byte[')) {
                $input->type = 'string' .  substr($input->type, strpos($input->type, ']'));
            } 

            $varType = $this->GetParameterType($input->type);

            //dynamic
            if(str_contains($input->type, '['))
            {
                $last_array_marker = strrpos($input->type, '[');  
                $clean_type = substr($input->type, 0, $last_array_marker); 
 
                $input->hash =  $this->EncodeInput_Array($clean_type, $inputData);
                $res = $this->EncodeInput_Int($currentDynamicIndex); 
                return $res;
                
            }
            else if  ($varType == VariableType::Tuple)
            {
                $input->hash =  $this->EncodeGroup($input->components, $inputData);
                $res = $this->EncodeInput_Int($currentDynamicIndex); 
                return $res;
            }
            else if ($varType == VariableType::String) {
                $input->hash = $this->EncodeInput_String($inputData);
                $res = $this->EncodeInput_Int($currentDynamicIndex); 
                return $res;
            }
            //static
            else if ($varType == VariableType::Int) { 
                return $this->EncodeInput_Int($inputData);
            }
            else if ($varType == VariableType::Bool) { 
                return $this->EncodeInput_Bool($inputData);
            }
            else if ($varType == VariableType::Address) { 
                return $this->EncodeInput_Address($inputData);
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


    private function EncodeInput_Int($data)
    {  
        $hash = $this->AddZeros(dechex($data), true); 
        return  $hash;
    }

    private function EncodeInput_Bool($data)
    { 
        $hash = $data ? '1' : '0';
        $hash = $this->AddZeros($hash, true);  
        return  $hash;
    }

    private function EncodeInput_Address($data)
    { 
        $hash = $this->AddZeros(substr($data, 2), true); 
        return  $hash;
    }

    private function EncodeInput_String($data)
    { 
        //length + hexa string
        $hash = $this->EncodeInput_Int(strlen($data)).$this->AddZeros(bin2hex($data), false);  

        return  $hash;
    }


    private function AddZeros($data, $add_left)
    {
        $total = $this->num_zeros - strlen($data);
        $res = $data;

        if($total > 0) {
            for($i=0; $i < $total; $i++) {
                if($add_left)   $res = '0'.$res;
                else            $res .= '0';
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

        foreach ($outputs as $output)
        {
            if(str_contains($output->type, 'byte[')) {
                $output->type = 'string' .  substr($output->type, strpos($output->type, ']'));
            } 

            //var_dump($output->type." ".$output->name." ".$index);
            $varType = $this->GetParameterType($output->type);
             
            //dynamic
            if(str_contains($output->type, '['))
            {  
                $last_array_marker = strrpos($output->type, '[');  
                $clean_type = substr($output->type, 0, $last_array_marker);

                $var_name = $output->name != '' ? $output->name : 'array_'.$array_count; 
                $dynamic_data_start = $first_index + $this->DecodeInput_Int($encoded, $index) * 2; 
                $group->$var_name = $this->DecodeInput_Array($output, $clean_type, $encoded, $dynamic_data_start); 
                $array_count++;
            }
            else if ($varType == VariableType::Tuple) { 
                $var_name = $output->name != '' ? $output->name : 'tuple_'.$tuple_count;
                $dynamic_data_start = $first_index + $this->DecodeInput_Int($encoded, $index) * 2;
                $group->$var_name = $this->DecodeGroup($output->components, $encoded, $dynamic_data_start);
                $tuple_count++;
            }
            else if ($varType == VariableType::String) { 
                $var_name = $output->name != '' ? $output->name : 'elem_'.$elem_index;
                $dynamic_data_start = $first_index + $this->DecodeInput_Int($encoded, $index) * 2;
                $group->$var_name = $this->DecodeInput_String($encoded, $dynamic_data_start);  
            }
            //static
            else
            {
                $var_name = $output->name != '' ? $output->name : 'elem_'.$elem_index;
                $group->$var_name = $this->DecodeInput_Generic($this->GetParameterType($output->type), $encoded, $index); 
            }  

            $elem_index++;
            $index += $this->num_zeros;
        }
        

        return $group; 
    } 

    private function DecodeInput_Array($output, $array_inner_type, $encoded, $index)
    {
        $array = [];
        $first_index = $index;  
        $varType = $this->GetParameterType($array_inner_type);

        $length = $this->DecodeInput_Int($encoded, $first_index); 
        $first_index += $this->num_zeros;
        $index += $this->num_zeros;
  
        for($i = 0; $i < $length; $i++)
        {  
            $res = "error"; 
            if (str_contains($array_inner_type, '[')) {   
                $last_array_marker = strrpos($array_inner_type, '[');  
                $clean_type = substr($array_inner_type, 0, $last_array_marker);
 
                $element_start = $first_index + $this->DecodeInput_Int($encoded, $index) * 2;
                $res = $this->DecodeInput_Array($output, $clean_type, $encoded, $element_start); 
            }
            else if($varType == VariableType::Tuple) {
                $element_start = $first_index + $this->DecodeInput_Int($encoded, $index) * 2; 
                $res = $this->DecodeGroup($output->components, $encoded, $element_start); 
            }
            else if($varType == VariableType::String) { 
                $element_start = $first_index + $this->DecodeInput_Int($encoded, $index) * 2;
                $res = $this->DecodeInput_String($encoded, $element_start);  
            }
            else {
                $this->DecodeInput_Generic($varType, $encoded, $index); 
            }
            
            $array []= $res;
            $index += $this->num_zeros; 
        }

        return $array;
    }


 
    private function DecodeInput_Generic($variableType, $encoded, $start)
    {
        if($variableType == VariableType::String) {
            return $this->DecodeInput_String($encoded, $start);
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
 

    private function DecodeInput_Int($encoded, $start)
    {
        $partial = substr($encoded, $start, 64);   
        $partial = $this->RemoveZeros($partial, true); 
        return hexdec($partial);
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
        $length = $this->DecodeInput_Int($encoded, $start); 
        $start += $this->num_zeros;

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
}