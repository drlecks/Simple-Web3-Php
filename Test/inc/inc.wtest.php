<?php
/**
 * This file is part of simple-web3-php package.
 * 
 * (c) Alex Cabrera  
 * 
 * @author Alex Cabrera
 * @license MIT 
 */

class WTest
{

	const color_ok = "#00ff00";
	const color_fail = "#ff0000";



	public static function printTitle(string $name)
	{ 
		echo '<h3>' . $name . '</3>';
	}


	private static function printResult(string $name, bool $res)
	{
		$res_html =  '<span style="font-style:bold;color:' . ($res ? self::color_ok: self::color_fail) . ';">' . ($res ? 'OK': 'FAIL') . '</span>';
		echo '<p>' . $name . ': ' . $res_html . '</p>';
	}


	public static function check(string $name, bool $comp)
	{
		self::printResult( $name,  $comp);
	}


}