# simple-web3-php

[![PHP](https://github.com/drlecks/Simple-Web3-Php/actions/workflows/php.yml/badge.svg)](https://github.com/drlecks/Simple-Web3-Php/actions/workflows/php.yml)
[![Build Status](https://travis-ci.org/drlecks/Simple-Web3-Php.svg?branch=master)](https://travis-ci.org/drlecks/simple-web3-php)
[![codecov](https://codecov.io/gh/drlecks/Simple-Web3-Php/branch/master/graph/badge.svg)](https://codecov.io/gh/drlecks/Simple-Web3-Php)
[![Join the chat at https://gitter.im/drlecks/Simple-Web3-Php](https://img.shields.io/badge/gitter-join%20chat-brightgreen.svg)](https://gitter.im/drlecks/Simple-Web3-Php)
[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/drlecks/Simple-Web3-Php/blob/master/LICENSE)


A php interface for interacting with the Ethereum blockchain and ecosystem.

**UNDER CONSTRUCTION**


# Features

- Customizable curl calls
- Call: get net state
- Send signed transactions
- Full ABIv2 encode/decode 
- Contract interaction (call/send)
- Examples provided interacting with simple types, strings, tuples, arrays, arrays of tuples with arrays, multi-dimension arrays... 



# Install

 
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
//initialize SWeb3 main object
$sweb3 = new SWeb3('http://ethereum.node.provider');
```

 
### general ethereum block information call:
```php
// 
$res = $sweb3->call('eth_blockNumber', []);
```

 
### refresh gas price 
```php
// 
$gasPrice = $sweb3->refreshGasPrice();
```


### estimate  gas price (from send params)
```php
$sweb3->chainId = '0x3';//ropsten
$sendParams = [ 
    'from' => SWP_ADDRESS,
    'to' => '0x3Fc47d792BD1B0f423B0e850F4E2AD172d408447', 
    'gasPrice' => $sweb3->gasPrice,
    'gasLimit' => 210000,
    'value' => $sweb3->utils->toWei('0.001', 'ether'),
    'nonce' => $sweb3->getNonce(SWP_ADDRESS)
]; 
$gasEstimateResult = $sweb3->call('eth_estimateGas', [$sendParams]);
```


 
### general ethereum block information call:
```php
$sweb3->chainId = '0x3';//ropsten
$sendParams = [ 
    'from' => SWP_ADDRESS,
    'to' => '0x3Fc47d792BD1B0f423B0e850F4E2AD172d408447', 
    'gasPrice' => $sweb3->gasPrice,
    'gasLimit' => 210000,
    'value' => $sweb3->utils->toWei('0.001', 'ether'),
    'nonce' => $sweb3->getNonce(SWP_ADDRESS)
];    
$result = $sweb3->send($sendParams); 
```


   

### Contract

```php
use SWeb3\SWeb3_Contract;

$contract = new SWeb3_contract($sweb3, SWP_Contract_Address, SWP_Contract_ABI);
  
// call contract function
$res = $contract->call('autoinc_tuple_a');

// change function state
$extra_data = ['nonce' => $sweb3->getNonce(SWP_ADDRESS)];
$result = $contract->send('Set_public_uint', 123,  $extra_data);
```

 

# Examples

 

# Modules

Kudos to the people from web3p & kornrunner. Never could have understood anything from web3 if it wasn't for those sources.

- Utils library forked from web3p/web3.php
- Transaction signing from kornrunner/ethereum-offline-raw-tx
- sha3 encoding from kornrunner/keccak


# TODO

- Contract creation

 
# Contribution
 
<a href="https://github.com/drlecks/Simple-Web3-Php/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=drlecks/Simple-Web3-Php" />
</a>

# License
MIT



# DONATIONS (ETH)
 
``` 
0x4a890A7AFB7B1a4d49550FA81D5cdca09DC8606b
```