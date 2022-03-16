# simple-web3-php
 
[![Join the chat at https://gitter.im/drlecks/Simple-Web3-Php](https://img.shields.io/badge/gitter-join%20chat-brightgreen.svg)](https://gitter.im/Simple-Web3-Php/community)
[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/drlecks/Simple-Web3-Php/blob/master/LICENSE)


A php interface for interacting with the Ethereum blockchain and ecosystem.


# Features

- PHP >= 7.4
- Customizable curl calls
- Call: get net state
- Send signed transactions
- Batch call requests and signed transactions
- Full ABIv2 encode/decode 
- Contract creation
- Contract interaction (call/send)
- Examples provided interacting with simple types, strings, tuples, arrays, arrays of tuples with arrays, multi-dimension arrays... 


# Install

```
composer require drlecks/simple-web3-php "^0.7.0"
```

Or you can add this line in composer.json

```
"drlecks/simple-web3-php": "^0.7.0"
```


# Usage

### New instance
```php
use SWeb3\SWeb3;
//initialize SWeb3 main object
$sweb3 = new SWeb3('http://ethereum.node.provider');

//optional if not sending transactions
$from_address = '0x0000000000000000000000000000000000000000';
$from_address_private_key = '345346245645435....';
$sweb3->setPersonalData($from_address, $from_address_private_key);
```
 
### general ethereum block information call:
```php 
$res = $sweb3->call('eth_blockNumber', []);
```
 
### refresh gas price 
```php 
$gasPrice = $sweb3->getGasPrice();
``` 

### estimate  gas price (from send params)
```php
use SWeb3\Utils;

$sweb3->chainId = '0x3';//ropsten 
$sendParams = [ 
    'from' =>   $sweb3->personal->address,  
    'to' =>     '0x1111111111111111111111111111111111111111', 
    'value' => $sweb3->utils->toWei('0.001', 'ether'),
    'nonce' => $sweb3->personal->getNonce()  
]; 
$gasEstimateResult = $sweb3->call('eth_estimateGas', [$sendParams]);
```
 
### send 0.001 ether to address
```php
use SWeb3\Utils;

//remember to set personal data first with a valid pair of address & private key
$sendParams = [ 
    'from' =>   $sweb3->personal->address,  
    'to' =>     '0x1111111111111111111111111111111111111111', 
    'gasLimit' => 210000,
    'value' => $sweb3->utils->toWei('0.001', 'ether'),
    'nonce' => $sweb3->personal->getNonce()
];    
$result = $sweb3->send($sendParams); 
```
 
### batch calls & transactions
```php 
//enable batching
$sweb3->batch(true);

$sweb3->call('eth_blockNumber', []); 
$sweb3->call('eth_getBalance', [$sweb3->personal->address]);

//execute all batched calls in one request
$res = $sweb3->executeBatch();

//batching has to be manually disabled
$sweb3->batch(false); 
```
 
### Contract interaction

```php
use SWeb3\SWeb3_Contract;

$contract = new SWeb3_contract($sweb3, '0x2222222222222222222222222222222222222222', '[ABI...]'); //'0x2222...' is contract address
  
// call contract function
$res = $contract->call('autoinc_tuple_a');

// change function state
//remember to set the sign values and chain id first: $sweb3->setPersonalData() & $sweb3->chainId
$extra_data = ['nonce' => $sweb3->personal->getNonce()]; //'0x0000...' is sender (from) address
$result = $contract->send('Set_public_uint', 123,  $extra_data);
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
$extra_params = [  
    'nonce' => $sweb3->personal->getNonce()
];  
$result = $contract->deployContract( [123123],  $extra_params); 
```
 

# Provided Examples

In the folder Examples/ there are some extended examples with call & send examples:

- example.call.php
- example.send.php
- example.batch.php
- example.contract_creation.php

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

Kudos to the people from web3p & kornrunner. Never could have understood anything from web3 if it wasn't for those sources.

- Utils library forked from web3p/web3.php
- Transaction signing from kornrunner/ethereum-offline-raw-tx
- sha3 encoding from kornrunner/keccak
- phpseclib\Math for BigNumber interaction


# TODO

- Events
- Node accounts creation / interaction


# License
MIT
 

# DONATIONS (ETH)
 
``` 
0x4a890A7AFB7B1a4d49550FA81D5cdca09DC8606b
```