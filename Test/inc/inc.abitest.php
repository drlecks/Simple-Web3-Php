<?php

namespace SWeb3;
use \stdClass;
use SWeb3\ABI;

class abitest {
	static function abiForDecodingInput($abi, $filter_function = false) {
		
		if (!is_array($abi))
			$abi = json_decode($abi, true);
		$tmp = [];
		foreach ($abi as $k=>$v) {
			if ($v['type'] != 'function')
				continue;
			if ($filter_function !== false && strcasecmp($filter_function, $v['name']) != 0)
				continue;
			
			$v['outputs'] = $v['inputs'];
			$tmp[$v['name']] = $v;
		}
		return json_encode(array_values($tmp));
	}
	
	static function showMessageByBlocks($msg, $title = "")
	{
		$title = "--{$title}";
		if (strlen($title) < 30) {
			$title .= str_repeat('-', 30-strlen($title));
		}
		
		$d = $msg;
		echo "<pre>";
		echo "\n{$title}\n";
		$d = substr($d, 10);
		$d = str_split($d, 64);
		foreach ($d as $k => $v) {
			
			$addr = hexdec("0x{$v}");
			$addr /= 32;
			
			if ($k < 10)
				$k = "0{$k}";
			echo "{$k}. {$v} {$addr}\n";
		}
		echo "\n</pre>";
	}
	
	static function trimFunctionName($msg) {
		if (stripos($msg, '0x') === 0)
			$msg = substr($msg, 2);
		return '0x'.substr($msg, 8);
	}
	
	static function stripTypeInfo($data) {
		foreach ($data as $k => $v) {
			if ($v instanceof stdClass) {
				$v = get_object_vars($v);
				$_t = [];
				foreach ($v as $kk=>$vv)
					$_t[] = "$vv";
				$tmp[] = $_t;
				continue;
			}
			
			if (!is_array($v)) {
				$tmp[] = "{$v}";
				continue;
			}
			
			foreach ($v as $kk=>$vv)
				$v[$kk] = "$vv";
			$tmp[] = $v;
		}
		return $tmp;
	}
	
	private $data = [];
	function __construct($abi, $function_name, $sample_raw_data) {
		$this->data = [$abi, $function_name, $sample_raw_data];
	}
	
	function getABIInputsCount($abi_fn = false) {
		[$abi_swapv3, $abi_fn_] = $this->data;
		$abi_swapv3 = json_decode($abi_swapv3, true);
		if ($abi_fn === false)
			$abi_fn = $abi_fn_;
		
		foreach ($abi_swapv3 as $v) {
			if (strcasecmp($v['type'], 'function') !== 0)
				continue;
			if (strcasecmp($v['name'], $abi_fn) !== 0)
				continue;
			return count($v['inputs'] ?? []);
		}
		return false;
	}
	
	function runTest($limitParams = false) {
		[$abi_swapv3, $abi_fn, $raw_data] = $this->data;
		$abi_swapv3 = self::abiForDecodingInput($abi_swapv3, $abi_fn);
		
		$aa = new ABI();
		$aa->Init($abi_swapv3);
		$data = $aa->DecodeData($abi_fn, self::trimFunctionName($raw_data));
		
		// re-encode, strip type info from data
		$data = self::stripTypeInfo($data);
		$param_count = $this->getABIInputsCount();
		
		$aa = new ABI();
		if ($limitParams !== false) {	// maybe we need to test only some parameters, helps with debugging
			$abi_swapv3 = json_decode($abi_swapv3, true)[0];
			$tmp = [];
			$inputs = $abi_swapv3['inputs'];
			foreach ($inputs as $k=>$v) {
				if ($k>=$limitParams)
					break;
				$tmp[] = $v;
			}
			$abi_swapv3['inputs'] = $tmp;
			$abi_swapv3 = json_encode([$abi_swapv3]);
		}
		$aa->Init($abi_swapv3);
		
		$reencoded = $aa->EncodeData('swapExactInput', $data);
		
		echo "<pre>";
		$check = [];
		$check[] = substr(sha1($raw_data), 0, 15);
		$check[] = substr(sha1($reencoded), 0, 15);
		$chk = " <b>{$abi_fn}</b>";
		if (strlen($raw_data) != strlen($reencoded) && $limitParams < $param_count) {
			$chk .= " ({$limitParams} / {$param_count}) ";
			$chk .= "? Check manually";
		} else {
			$chk .= $check[0] == $check[1] ? " ✓ Valid " : " x Invalid";
		}
		
		if ($limitParams === false) {
			echo "Check: " . implode(" vs ", $check) . ' :' . $chk . "\n";
			echo "</pre>";
			self::showMessageByBlocks($raw_data, 'orin');
			echo "<br>";
			self::showMessageByBlocks($reencoded, 'new');
		} else {
			self::showMessageByBlocks($reencoded, "new ({$limitParams}/{$param_count})");
		}
		
	}
	
}
