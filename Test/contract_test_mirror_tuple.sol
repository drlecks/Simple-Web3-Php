// SPDX-License-Identifier: GPL-3.0

pragma solidity >=0.7.0 <0.9.0;
 
contract contract_test_mirror_tuple 
{  
    struct Tuple_A {
        uint uint_a;  
        bool boolean_a;   
    }

    struct Tuple_B { 
        string string_b1;   
        string string_b2;   
    } 

    struct Tuple_C {
        uint uint_c;  
        string string_c;    
    } 
 
    function Mirror_TupleA(Tuple_A memory t) public pure returns (Tuple_A memory)
    {
        return t;
    }
 
    function Mirror_TupleB(Tuple_B memory t) public pure returns (Tuple_B memory)
    {
        return t;
    }

    function Mirror_TupleC(Tuple_C memory t) public pure returns (Tuple_C memory)
    {
        return t;
    }


    function Mirror_TupleArray(Tuple_C[] memory t) public pure returns (Tuple_C[] memory)
    {
        return t;
    }

}