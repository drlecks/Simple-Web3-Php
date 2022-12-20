# simple-web3-php

[![Latest Version on Packagist](https://img.shields.io/packagist/v/drlecks/simple-web3-php.svg?style=flat-square)](https://packagist.org/packages/drlecks/simple-web3-php)
[![Join the chat at https://gitter.im/drlecks/Simple-Web3-Php](https://img.shields.io/badge/gitter-join%20chat-brightgreen.svg)](https://gitter.im/Simple-Web3-Php/community)
[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/drlecks/Simple-Web3-Php/blob/master/LICENSE)


A php interface for interacting with the Ethereum blockchain and ecosystem.


# Features

- PHP >= 7.4
- Customizable curl calls
- Call: get net state
- Send signed transactions
- Batch call requests and signed transactions 
- Address & private key creation
- Message signing
- Full ABIv2 encode/decode 
- Contract creation
- Contract interaction (call/send)
- Contract Events/logs with filters
- Support for ERC20 contracts with non-nominative decimal values
- Examples provided interacting with simple types, strings, tuples, arrays, arrays of tuples with arrays, multi-dimension arrays... 


# Install

### Latest stable release
```
composer require drlecks/simple-web3-php "^0.10.0"
```

Or you can add this line in composer.json

```
"drlecks/simple-web3-php": "^0.10.0"
```


### Development (main branch)
```
composer require drlecks/simple-web3-php dev-master
```

Or you can add this line in composer.json

```
"drlecks/simple-web3-php": "dev-master"
```


# Usage

### New instance
```php
use SWeb3\SWeb3;
//initialize SWeb3 main object
$sweb3 = new SWeb3('http://ethereum.node.provider:optional.node.port');

//optional if not sending transactions
$from_address = '0x0000000000000000000000000000000000000000';
$from_address_private_key = '345346245645435....';
$sweb3->setPersonalData($from_address, $from_address_private_key);
```

### Convert values 
Most calls return Hex encoded strings to represent numbers. 

Hex to Big Number: 
```php 
use SWeb3\Utils;

$res = $sweb3->call('eth_blockNumber', []);
$bigNum = Utils::hexToBn($res->result);
``` 

Number to BigNumber:
```php 
$bigNum = Utils::ToBn(123);
``` 

Get average-human readable string representation from Big Number:
```php 
$s_number = $bigNum->toString();
``` 

Format 1 ether to wei (unit required for ether values in transactions):
```php 
Utils::toWei('0.001', 'ether');
``` 

Get average-human readable string representation from a value conversion:
```php  
$s_val = Utils::fromWeiToString('1001', 'kwei'); // "1.001"

$s_val = Utils::toWeiString('1.001', 'kwei'); // "1001"
```  


### General ethereum block information call:
```php 
$res = $sweb3->call('eth_blockNumber', []);
```
 
### Refresh gas price 
```php 
$gasPrice = $sweb3->getGasPrice();
``` 

### Estimate  gas price (from send params)
```php 
$sweb3->chainId = '0x3';//ropsten 
$sendParams = [ 
    'from' =>	$sweb3->personal->address,  
    'to' =>     '0x1111111111111111111111111111111111111111', 
    'value' => 	Utils::toWei('0.001', 'ether'),
    'nonce' => 	$sweb3->personal->getNonce()  
]; 
$gasEstimateResult = $sweb3->call('eth_estimateGas', [$sendParams]);
```
 
### Send 0.001 ether to address
```php
//remember to set personal data first with a valid pair of address & private key
$sendParams = [ 
    'from' =>   	$sweb3->personal->address,  
    'to' =>     	'0x1111111111111111111111111111111111111111', 
    'gasLimit' => 	210000,
    'value' => 		Utils::toWei('0.001', 'ether'),
    'nonce' => 		$sweb3->personal->getNonce()
];    
$result = $sweb3->send($sendParams); 
```
 
### Batch calls & transactions
```php 
//enable batching
$sweb3->batch(true);

$sweb3->call('eth_blockNumber', []); 
$sweb3->call('eth_getBalance', [$sweb3->personal->address, 'latest']);

//execute all batched calls in one request
$res = $sweb3->executeBatch();

//batching has to be manually disabled
$sweb3->batch(false); 
```

### Account
```php 
use SWeb3\Accounts; 
use SWeb3\Account;

//create new account privateKey/address (returns Account)
$account = Accounts::create();

//retrieve account (address) from private key 
$account2 = Accounts::privateKeyToAccount('...private_key...');

//sign message with account
$res = $account2->sign('Some data'); 

```

 
### Contract interaction

```php
use SWeb3\SWeb3_Contract;

$contract = new SWeb3_contract($sweb3, '0x2222222222222222222222222222222222222222', '[ABI...]'); //'0x2222...' is contract address
  
// call contract function
$res = $contract->call('autoinc_tuple_a');

// change function state
//remember to set the sign values and chain id first: $sweb3->setPersonalData() & $sweb3->chainId
$extra_data = ['nonce' => $sweb3->personal->getNonce()]; 
$result = $contract->send('Set_public_uint', 123,  $extra_data);
```

### Contract events (logs)

```php
//optional parameters fromBlock, toBlock, topics
//default values -> '0x0', 'latest', null (all events/logs from this contract) 
$res = $contract->getLogs();
``` 

### Contract creation (deployment)

```php
use SWeb3\SWeb3_Contract;
 
$creation_abi = '[abi...]';
$contract = new SWeb3_contract($sweb3, '', $creation_abi);

//set contract bytecode data
$contract_bytecode = '123456789....';
$contract->setBytecode($contract_bytecode);

//remember to set the sign values and chain id first: $sweb3->setPersonalData() & $sweb3->chainId
$extra_params = ['nonce' => $sweb3->personal->getNonce()];  
$result = $contract->deployContract( [123123],  $extra_params); 
```


### Usual required includes

```php 
use SWeb3\SWeb3;                            //always needed, to create the Web3 object
use SWeb3\Utils;                            //sweb3 helper classes (for example, hex conversion operations)
use SWeb3\SWeb3_Contract;                   //contract creation and interaction
use SWeb3\Accounts;                   		//account creation
use SWeb3\Account;                   		//single account management (signing)
use phpseclib\Math\BigInteger as BigNumber; //BigInt handling
use stdClass;                               //for object interaction 
```

# Provided Examples

In the folder Examples/ there are some extended examples with call & send examples:

- example.call.php
- example.send.php
- example.batch.php
- example.account.php
- example.contract_creation.php
- example.erc20.php

 ### Example configuration

 To execute the examples you will need to add some data to the configuration file (example.config.php).

The example is pre-configured to work with an infura endpoint:

```php
define('INFURA_PROJECT_ID', 'XXXXXXXXX');
define('INFURA_PROJECT_SECRET', 'XXXXXXXXX');
define('ETHEREUM_NET_NAME', 'ropsten'); //ropsten , mainnet

define('ETHEREUM_NET_ENDPOINT', 'https://'.ETHEREUM_NET_NAME.'.infura.io/v3/'.INFURA_PROJECT_ID); 
```
Just add your infura project keys. If you have not configured the api secret key requisite, just ignore it.

If you are using a private endpoint, just ignore all the infura definitions:

```php 
define('ETHEREUM_NET_ENDPOINT', 'https://123.123.40.123:1234'); 
```

To enable contract interaction, set the contract data (address & ABI). The file is preconfigured to work with our example contract, already deployed on ropsten.
```php
//swp_contract.sol is available on ropsten test net, address: 0x706986eEe8da42d9d85997b343834B38e34dc000
define('SWP_Contract_Address','0x706986eEe8da42d9d85997b343834B38e34dc000'); 
$SWP_Contract_ABI = '[...]';
define('SWP_Contract_ABI', $SWP_Contract_ABI);
```

To enable transaction sending & signing, enter a valid pair of address and private key. Please take this advises before continuing:
- The address must be active on the ropsten network and have some ether available to send the transactions.
- Double check that you are using a test endpoint, otherwise you will be spending real eth to send the transactions
- Be sure to keep your private key secret! 

```php
//SIGNING
define('SWP_ADDRESS', 'XXXXXXXXXX');
define('SWP_PRIVATE_KEY', 'XXXXXXXXXX');
```

### Example contract

The solidity contract used in this example is available too in the same folder: swp_contract.sol

### Example disclaimer

Don't base your code structure on this example. This example does not represent clean / efficient / performant aproach to implement them in a production environment. It's only aim is to show some of the features of Simple Web3 Php.


# Modules

- Utils library forked & extended from web3p/web3.php
- Transaction signing: kornrunner/ethereum-offline-raw-tx
- sha3 encoding: from kornrunner/keccak
- BigNumber interaction: phpseclib\Math  
- Asymetric key handling: simplito/elliptic-php


# TODO
 
- Node accounts creation / interaction


# License
MIT
 

# DONATIONS (ETH)
 
``` 
0x4a890A7AFB7B1a4d49550FA81D5cdca09DC8606b
```
