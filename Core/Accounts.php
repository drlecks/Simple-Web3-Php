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
use Elliptic\EC;
use kornrunner\Keccak;
use stdClass;
use Exception;
 
class Account
{
	public string $privateKey;
	public string $publicKey;
}

class Accounts
{
 
	public static function create()
	{
		//Generates an account object with private key and public key.

		// Create the keypair
		$privateKey = Keccak::hash(Utils::GetRandomHex(128), 256);  
		
		return self::privateKeyToAccount($privateKey);
	} 


	public static function privateKeyToAccount(string $privateKey, bool $ignoreLength = false)
	{
		//Generates an account object with private key and public key.

		if (substr($privateKey, 0, 2) == '0x') {
			$privateKey = substr($privateKey, 2, strlen($privateKey) - 2);
		}
	 
		// 64 hex characters + hex-prefix
		if (!$ignoreLength && strlen($privateKey) !== 64) {
			throw new Exception("Private key must be 32 bytes long (" . strlen($privateKey) . " provided)");
		}
		
		//get public key
		$ec = new EC('secp256k1');
		$ec_priv = $ec->keyFromPrivate($privateKey);
		$publicKey = $ec_priv->getPublic(true, "hex");
  
		// Returns a Web3 Account from a given privateKey 
		$account = new Account();
		$account->privateKey = '0x' . $privateKey;
		$account->publicKey = '0x' . $publicKey;
		$account->address = self::ecKeyToAddress($ec_priv->pub);

		return $account;
	}
  

	public static function hashMessage(string $message)
	{
		//"\x19Ethereum Signed Message:\n" + message.length + message and hashed using keccak256.

		$hash = Keccak::hash($signature, "\x19Ethereum Signed Message:\n" . strlen($message) . $message);

		return $hash;

		//web3.eth.accounts.hashMessage("Hello World")
 		//"0xa1de988600a42c4b4ab089b619297c17d53cffae5d5120d82d8a92d0bb3b78f2"
		// the below results in the same hash
		//web3.eth.accounts.hashMessage(web3.utils.utf8ToHex("Hello World"))
		//> "0xa1de988600a42c4b4ab089b619297c17d53cffae5d5120d82d8a92d0bb3b78f2"
	}
 

	//https://github.com/ethereum/wiki/wiki/Web3-Secret-Storage-Definition
	public static function encrypt(string $privateKey, string $password)
	{
		//web3.eth.accounts.encrypt(privateKey, password); 
		//Encrypts a private key to the web3 keystore v3 standard.
		/*
		cipher: 'aes-128-ctr',
        kdf: 'scrypt',
        kdfparams: {
            dklen: 32,
            salt: '4531b3c174cc3ff32a6a7a85d6761b410db674807b2d216d022318ceee50be10',
            n: 262144,
            r: 8,
            p: 1
		*/
 

		//returns ciphertext
	}


	public static function decrypt(string $ciphertext, string $password)
	{
		//web3.eth.accounts.encrypt(privateKey, password); 
		//Encrypts a private key to the web3 keystore v3 standard.
		//'aes-128-ctr'
	}


	public static function ecKeyToAddress($pubkey) 
	{
		return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
	}
	
}