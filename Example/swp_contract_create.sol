// SPDX-License-Identifier: GPL-3.0

pragma solidity >=0.7.0 <0.9.0;

/**
 * @title Owner
 * @dev Set & change owner
 */
contract SWP_contract 
{
    uint256 public example_int;

    constructor(uint256 val) 
    {
         example_int = val;
    }
}