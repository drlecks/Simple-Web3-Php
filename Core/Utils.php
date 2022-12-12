<?php

/**
 * This file is part of simple-web3-php package.
 * 
 * (c) Alex Cabrera  
 * 
 * @author Alex Cabrera
 * @license MIT
 * 
 * This file is a modified part based on original code: web3.php package.
 * 
 * (c) Kuan-Cheng,Lai <alk03073135@gmail.com>
 * 
 * @author Peter Lai <alk03073135@gmail.com>
 * @license MIT
 */

namespace SWeb3;


use stdClass;
use InvalidArgumentException;  
use kornrunner\Keccak;
use phpseclib\Math\BigInteger as BigNumber;

class Utils
{
    /**
     * SHA3_NULL_HASH
     * 
     * @const string
     */
    const SHA3_NULL_HASH = 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470';

    /**
     * UNITS
     * from ethjs-unit
     * 
     * @const array
     */
    const UNITS = [
        'noether' => '0',
        'wei' => '1',
        'kwei' => '1000',
        'Kwei' => '1000',
        'babbage' => '1000',
        'femtoether' => '1000',
        'mwei' => '1000000',
        'Mwei' => '1000000',
        'lovelace' => '1000000',
        'picoether' => '1000000',
        'gwei' => '1000000000',
        'Gwei' => '1000000000',
        'shannon' => '1000000000',
        'nanoether' => '1000000000',
        'nano' => '1000000000',
        'szabo' => '1000000000000',
        'microether' => '1000000000000',
        'micro' => '1000000000000',
        'finney' => '1000000000000000',
        'milliether' => '1000000000000000',
        'milli' => '1000000000000000',
        'ether' => '1000000000000000000',
        'kether' => '1000000000000000000000',
        'grand' => '1000000000000000000000',
        'mether' => '1000000000000000000000000',
        'gether' => '1000000000000000000000000000',
        'tether' => '1000000000000000000000000000000'
    ];



    /**
     * hexToBn
     * decoding hex number into decimal 
     * 
     * @param string  $value 
     * @return  int
     */
    public static function hexToBn($value)
    {
        $value = self::stripZero($value);
        return (new BigNumber($value, 16));
    }

    /**
     * toHex
     * Encoding string or integer or numeric string(is not zero prefixed) or big number to hex.
     * 
     * @param string|int|BigNumber $value
     * @param bool $isPrefix
     * @return string
     */
    public static function toHex($value, $isPrefix=false)
    {
        if (is_numeric($value) && !is_float($value) && !is_double($value)) {
            // turn to hex number
            $bn = self::toBn($value);
            $hex = $bn->toHex(true);
            $hex = preg_replace('/^0+(?!$)/', '', $hex);
        } elseif (is_string($value)) {
            $value = self::stripZero($value);
            $hex = implode('', unpack('H*', $value));
        } elseif ($value instanceof BigNumber) {
            $hex = $value->toHex(true);
            $hex = preg_replace('/^0+(?!$)/', '', $hex);
        } else {
			$type_error = gettype($value);
            throw new InvalidArgumentException("The value to Utils::toHex() function is not supported: value=$value type=$type_error. Only int, hex string, BigNumber or int string representation are allowed.");
        }
        
        if ($isPrefix) {
            return self::addZeroPrefix($hex);
        }
        return $hex;
    }

    /**
     * hexToBin
     * 
     * @param string
     * @return string
     */
    public static function hexToBin($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to hexToBin function must be string.');
        }
        if (self::isZeroPrefixed($value)) {
            $count = 1;
            $value = str_replace('0x', '', $value, $count);
        }
        return pack('H*', $value);
    }

    /**
     * isZeroPrefixed
     * 
     * @param string
     * @return bool
     */
    public static function isZeroPrefixed($value)
    {
        if (!is_string($value)) {
            //throw new InvalidArgumentException('The value to isZeroPrefixed function must be string.');
        }
        return (strpos($value, '0x') === 0);
    }

    /**
     * stripZero
     * 
     * @param string $value
     * @return string
     */
    public static function stripZero($value)
    {
        if (self::isZeroPrefixed($value)) {
            $count = 1;
            return str_replace('0x', '', $value, $count);
        }
        return $value;
    }

    /**
     * addZeroPrefix
     * 
     * @param string
     * @return string
     */
    public static function addZeroPrefix($value)
    {
        $value = '' . $value;

        if (self::isZeroPrefixed($value)) return $value;

        //remove leading 0s
        $value = ltrim($value, "0"); 

        return '0x' . $value;
    }

    /**
     * forceAllNumbersHex
     * 
     * @param object[]
     * @return object[]
     */
    public static function forceAllNumbersHex($params)
    { 
        foreach($params as $key => $param) 
        {  
            if ($key !== 'chainId')
            { 
                if(is_numeric($param) || $param instanceof BigNumber)
                {  
                    $params[$key] = self::toHex($param, true);
                }
                else if(is_array($param))
                { 
                    foreach($param as $sub_key => $sub_param)  
                    {  
                        if ($sub_key !== 'chainId')
                        { 
                            if(is_numeric($sub_param) || $sub_param instanceof BigNumber) {   
                                $param[$sub_key] = self::toHex($sub_param, true);
                            }  
                        }
                    } 

                    $params[$key] = $param;
                }
            }
        }

        return $params;
    }



    /**
     * isNegative
     * 
     * @param string
     * @return bool
     */
    public static function isNegative($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to isNegative function must be string.');
        }
        return (strpos($value, '-') === 0);
    }

    /**
     * isAddress
     * 
     * @param string $value
     * @return bool
     */
    public static function isAddress($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to isAddress function must be string.');
        }
        if (preg_match('/^(0x|0X)?[a-f0-9A-F]{40}$/', $value) !== 1) {
            return false;
        } elseif (preg_match('/^(0x|0X)?[a-f0-9]{40}$/', $value) === 1 || preg_match('/^(0x|0X)?[A-F0-9]{40}$/', $value) === 1) {
            return true;
        }
        return self::isAddressChecksum($value);
    }

    /**
     * isAddressChecksum
     *
     * @param string $value
     * @return bool
     */
    public static function isAddressChecksum($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to isAddressChecksum function must be string.');
        }
        $value = self::stripZero($value);
        $hash = self::stripZero(self::sha3(mb_strtolower($value)));

        for ($i = 0; $i < 40; $i++) {
            if (
                (intval($hash[$i], 16) > 7 && mb_strtoupper($value[$i]) !== $value[$i]) ||
                (intval($hash[$i], 16) <= 7 && mb_strtolower($value[$i]) !== $value[$i])
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * toChecksumAddress
     *
     * @param string $value
     * @return string
     */
    public static function toChecksumAddress($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to toChecksumAddress function must be string.');
        }
        $value = self::stripZero(strtolower($value));
        $hash = self::stripZero(self::sha3($value));
        $ret = '0x';

        for ($i = 0; $i < 40; $i++) {
            if (intval($hash[$i], 16) >= 8) {
                $ret .= strtoupper($value[$i]);
            } else {
                $ret .= $value[$i];
            }
        }
        return $ret;
    }

    /**
     * isHex
     * 
     * @param string $value
     * @return bool
     */
    public static function isHex($value)
    {
        return (is_string($value) && preg_match('/^(0x)?[a-f0-9]*$/', $value) === 1);
    }

    /**
     * sha3
     * keccak256
     * 
     * @param string $value
     * @return string
     */
    public static function sha3($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to sha3 function must be string.');
        }
        if (strpos($value, '0x') === 0) {
            $value = self::hexToBin($value);
        }
        $hash = Keccak::hash($value, 256);

        if ($hash === self::SHA3_NULL_HASH) {
            return null;
        }
        return '0x' . $hash;
    }

    /**
     * toString
     * 
     * @param mixed $value
     * @return string
     */
    public static function toString($value)
    {
        $value = (string) $value;

        return $value;
    }

    /**
     * toWei
     * Change number from unit to wei.
     * For example:
     * $wei = Utils::toWei('1', 'kwei'); 
     * $wei->toString(); // 1000
     * 
     * @param BigNumber|string $number
     * @param string $unit
     * @return \phpseclib\Math\BigInteger
     */
    public static function toWei($number, string $unit)
    { 
        if (!isset(self::UNITS[$unit])) {
            throw new InvalidArgumentException('toWei doesn\'t support ' . $unit . ' unit.');
        } 
		
		return self::toWei_Internal($number, self::UNITS[$unit]);
    }


	/**
     * toWeiFromDecimals
     * Change number from unit that has decimals to wei.
     * For example:
     * $wei = Utils::toWeiFromDecimals('0.01', 8);  //1000000
     * $wei->toString(); // 1000
     * 
     * @param BigNumber|string $number
     * @param string $unit
     * @return \phpseclib\Math\BigInteger
     */
    public static function toWeiFromDecimals($number, int $numberOfDecimals)
    {  
		$exponent = str_pad('1', $numberOfDecimals + 1, '0', STR_PAD_RIGHT);
		return self::toWei_Internal($number, $exponent);
    }


	 /**
     * toWei_Internal
     * Internal private fucntion to convert a number in "unti" to string. 
	 * The unit string is 1000...000 having # decimal zero positions 
     * 
     * @param BigNumber|string $number
     * @param string $unit_value
     * @return \phpseclib\Math\BigInteger
     */
	private static function toWei_Internal($number, string $unit_value)
    {
        if (!is_string($number) && !($number instanceof BigNumber)) {
            throw new InvalidArgumentException('toWei number must be string or bignumber.');
        }
        $bn = self::toBn($number);
  
        $bnt = new BigNumber($unit_value);

        if (is_array($bn)) {
            // fraction number
            list($whole, $fraction, $fractionLength, $negative1) = $bn;

            if ($fractionLength > strlen($unit_value)) {
                throw new InvalidArgumentException('toWei fraction part is out of limit.');
            }
            $whole = $whole->multiply($bnt);

            // There is no pow function in phpseclib 2.0, only can see in dev-master
            // Maybe implement own biginteger in the future
            // See 2.0 BigInteger: https://github.com/phpseclib/phpseclib/blob/2.0/phpseclib/Math/BigInteger.php
            // See dev-master BigInteger: https://github.com/phpseclib/phpseclib/blob/master/phpseclib/Math/BigInteger.php#L700
            // $base = (new BigNumber(10))->pow(new BigNumber($fractionLength));

            // So we switch phpseclib special global param, change in the future
            switch (MATH_BIGINTEGER_MODE) {
                case $whole::MODE_GMP:
                    static $two;
                    $powerBase = gmp_pow(gmp_init(10), (int) $fractionLength);
                    break;
                case $whole::MODE_BCMATH:
                    $powerBase = bcpow('10', (string) $fractionLength, 0);
                    break;
                default:
                    $powerBase = pow(10, (int) $fractionLength);
                    break;
            }
            $base = new BigNumber($powerBase);
            $fraction = $fraction->multiply($bnt)->divide($base)[0];

            if ($negative1 !== false) {
                return $whole->add($fraction)->multiply($negative1);
            }
            return $whole->add($fraction);
        }

        return $bn->multiply($bnt);
    }


    /**
     * toEther
     * Change number from unit to ether.
     * For example:
     * list($bnq, $bnr) = Utils::toEther('1', 'kether'); 
     * $bnq->toString(); // 1000
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * @return array
     */
    public static function toEther($number, $unit)
    {
        // if ($unit === 'ether') {
        //     throw new InvalidArgumentException('Please use another unit.');
        // }
        $wei = self::toWei($number, $unit);
        $bnt = new BigNumber(self::UNITS['ether']); 

        return $wei->divide($bnt);
    }


    /**
     * fromWei
     * Change number from wei to unit.
     * For example:
     * list($bnq, $bnr) = Utils::fromWei('1000', 'kwei'); 
     * $bnq->toString(); // 1
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * @return \phpseclib\Math\BigInteger
     */
    public static function fromWei($number, $unit)
    {
        $bn = self::toBn($number);

        if (!is_string($unit)) {
            throw new InvalidArgumentException('fromWei unit must be string.');
        }
        if (!isset(self::UNITS[$unit])) {
            throw new InvalidArgumentException('fromWei doesn\'t support ' . $unit . ' unit.');
        }
        $bnt = new BigNumber(self::UNITS[$unit]);

        return $bn->divide($bnt);
    }


	 
	 /**
     * toWeiString
     * Change number from unit to wei. and show a string representation
     * For example:
     * $wei = Utils::toWeiString('1', 'kwei');  // 1000
     * 
     * @param BigNumber|string $number
     * @param string $unit
     * @return string
     */
    public static function toWeiString($number, $unit) : string
    {
		$conv = self::toWei($number, $unit);
		return $conv->toString();
	}

	/**
     * toWeiStringFromDecimals
     * Change number from decimals to wei. and show a string representation
     * For example:
     * $wei = Utils::toWeiStringFromDecimals('1', 'kwei');  // 1000
     * 
     * @param BigNumber|string $number
     * @param int $numberOfDecimals
     * @return string
     */
    public static function toWeiStringFromDecimals($number, int $numberOfDecimals) : string
    {
		$conv = self::toWeiFromDecimals($number, $numberOfDecimals);
		return $conv->toString();
	}


	/**
     * toEtherString
     * Change number from unit to ether. and show a string representation
     * For example:
     * $ether = Utils::toEtherString('1', 'kether');  // 1000
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * @return string
     */
    public static function toEtherString($number, $unit) : string
    {
        $conversion = self::toEther($number, $unit);   
		return self::transformDivisionToString($conversion, self::UNITS[$unit], self::UNITS['ether']);
    }


	/**
     * fromWeiToString
     * Change number from wei to unit. and show a string representation
     * For example:
     * $kwei = Utils::fromWei('1001', 'kwei'); // 1.001 
     * 
     * @param BigNumber|string|int $number
     * @param string $unit
     * @return \phpseclib\Math\BigInteger
     */
	public static function fromWeiToString($number, $unit) : string
    {
		$conversion = self::fromWei($number, $unit);   
		return self::transformDivisionToString($conversion, self::UNITS['wei'], self::UNITS[$unit]);
	}
 

	/**
     * fromWeiToDecimalsString
     * Change number from wei to number of decimals.
     * For example:
     * $stringNumber = Utils::fromWeiToDecimalsString('1000000', 8); //0.01 
     * 
     * @param BigNumber|string|int $number
     * @param int $numberOfDecimals
     * @return string
     */
    public static function fromWeiToDecimalsString($number, int $numberOfDecimals) : string
    {
        $bn = self::toBn($number);

        $exponent = str_pad('1', $numberOfDecimals + 1, '0', STR_PAD_RIGHT);

        $bnt = new BigNumber($exponent);

		$conversion = $bn->divide($bnt);

        return self::transformDivisionToString($conversion, self::UNITS['wei'], $exponent);
    }


	/**
     * transformDivisionToString
     * Internal private fucntion to convert a [quotient, remainder] BigNumber division result, 
	 * to a human readable unit.decimals (12.3920012000)
	 * The unit string is 1000...000 having # decimal zero positions 
     * 
     * @param (\phpseclib\Math\BigInteg, \phpseclib\Math\BigInteg) $divisionArray
     * @param string $unitZerosOrigin string representing the origin unit's number of zeros 
	 * @param string $unitZerosOrigin string representing the origin unit's number of zeros 
     * @return string
     */
	private static function transformDivisionToString($divisionArray, $unitZerosOrigin, $unitZerosDestiny) : string
	{
		$left = $divisionArray[0]->toString();
		$right = $divisionArray[1]->toString();
 
		if ($right != "0")
		{
			$bnt_wei = new BigNumber($unitZerosOrigin);
			$bnt_unit = new BigNumber($unitZerosDestiny);
 
			$right_lead_zeros = strlen($bnt_unit->toString()) - strlen($bnt_wei->toString()) - strlen($right);  
			
			for ($i = 0; $i < $right_lead_zeros; $i++) $right = '0' . $right;
			$right = rtrim($right, "0");
			
			return $left . '.' . $right; 
		}
		else
		{
			return $left;
		} 
	}

    /**
     * jsonMethodToString
     * 
     * @param stdClass|array $json
     * @return string
     */
    public static function jsonMethodToString($json) : string
    {
        if ($json instanceof stdClass) {
            // one way to change whole json stdClass to array type
            // $jsonString = json_encode($json);

            // if (JSON_ERROR_NONE !== json_last_error()) {
            //     throw new InvalidArgumentException('json_decode error: ' . json_last_error_msg());
            // }
            // $json = json_decode($jsonString, true);

            // another way to change whole json to array type but need the depth
            // $json = self::jsonToArray($json, $depth)

            // another way to change json to array type but not whole json stdClass
            $json = (array) $json;
            $typeName = [];

            foreach ($json['inputs'] as $param) {
                if (isset($param->type)) {
                    $typeName[] = $param->type;
                }
            }
            return $json['name'] . '(' . implode(',', $typeName) . ')';
        } elseif (!is_array($json)) {
            throw new InvalidArgumentException('jsonMethodToString json must be array or stdClass.');
        }
        if (isset($json['name']) && strpos($json['name'], '(') > 0) {
            return $json['name'];
        }
        $typeName = [];

        foreach ($json['inputs'] as $param) {
            if (isset($param['type'])) {
                $typeName[] = $param['type'];
            }
        }
        return $json['name'] . '(' . implode(',', $typeName) . ')';
    }

    /**
     * jsonToArray
     * 
     * @param stdClass|array $json
     * @return array
     */
    public static function jsonToArray($json)
    {
        if ($json instanceof stdClass) {
            $json = (array) $json;
            $typeName = [];

            foreach ($json as $key => $param) {
                if (is_array($param)) {
                    foreach ($param as $subKey => $subParam) {
                        $json[$key][$subKey] = self::jsonToArray($subParam);
                    }
                } elseif ($param instanceof stdClass) {
                    $json[$key] = self::jsonToArray($param);
                }
            }
        } elseif (is_array($json)) {
            foreach ($json as $key => $param) {
                if (is_array($param)) {
                    foreach ($param as $subKey => $subParam) {
                        $json[$key][$subKey] = self::jsonToArray($subParam);
                    }
                } elseif ($param instanceof stdClass) {
                    $json[$key] = self::jsonToArray($param);
                }
            }
        }
        return $json;
    }

    /**
     * toBn
     * Change number or number string to bignumber.
     * 
     * @param BigNumber|string|int $number
     * @return array|\phpseclib\Math\BigInteger
     */
    public static function toBn($number)
    {
        if ($number instanceof BigNumber){
            $bn = $number;
        } 
		elseif (is_int($number)) {
            $bn = new BigNumber($number);
        } 
		elseif (is_numeric($number)) {
            $number = (string) $number;

            if (self::isNegative($number)) {
                $count = 1;
                $number = str_replace('-', '', $number, $count);
                $negative1 = new BigNumber(-1);
            }
            if (strpos($number, '.') > 0) {
                $comps = explode('.', $number);

                if (count($comps) > 2) {
                    throw new InvalidArgumentException('toBn number must be a valid number.');
                }
                $whole = $comps[0];
                $fraction = $comps[1];

                return [
                    new BigNumber($whole),
                    new BigNumber($fraction),
                    strlen($comps[1]),
                    isset($negative1) ? $negative1 : false
                ];
            } else {
                $bn = new BigNumber($number);
            }
            if (isset($negative1)) {
                $bn = $bn->multiply($negative1);
            }
        } 
		elseif (is_string($number)) {
            $number = mb_strtolower($number);

            if (self::isNegative($number)) {
                $count = 1;
                $number = str_replace('-', '', $number, $count);
                $negative1 = new BigNumber(-1);
            }
            if (self::isZeroPrefixed($number) || preg_match('/^[0-9a-f]+$/i', $number) === 1) {
                $number = self::stripZero($number);
                $bn = new BigNumber($number, 16);
            } elseif (empty($number)) {
                $bn = new BigNumber(0);
            } else {
                throw new InvalidArgumentException('toBn number must be valid hex string.');
            }
            if (isset($negative1)) {
                $bn = $bn->multiply($negative1);
            }
        } 
		else {
            throw new InvalidArgumentException('toBn number must be BigNumber, string or int.');
        }
        return $bn;
    }


	public static function GetRandomHex(int $length)
	{
		return bin2hex(openssl_random_pseudo_bytes($length / 2));   
	}


	public static function string_contains(string $haystack, string $needle)
	{
		return empty($needle) || strpos($haystack, $needle) !== false;
	}
 
}