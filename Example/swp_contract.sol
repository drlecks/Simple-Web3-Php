// SPDX-License-Identifier: GPL-3.0

pragma solidity >=0.7.0 <0.9.0;

/**
 * @title Owner
 * @dev Set & change owner
 */
contract SWP_contract 
{
    uint256 public autoinc_tuple_a;
    uint256 public public_uint;
    int256 public public_int;
    string public public_string;

 
    struct Tuple_A {
        uint uint_a;  
        bool boolean_a;   
        address address_a; 
        bytes bytes_a;    
    }

    struct Tuple_B {
        uint uint_b;  
        string string_b;   
        string[] string_array_b; 
    }

    mapping(uint => Tuple_A) public map_tuples_a;
    Tuple_B[] private array_tuples_b;


    event Event_Set_public_uint(uint newValue);
    event Event_AddTuple_A(Tuple_A newObject);
    event Event_AddTuple_B(Tuple_B newObject);


    //CONSTRUCTOR
    constructor() 
    {
         Set_public_uint(12342535644675);
         Set_public_int(-12342535644675);
         Set_public_string("Welcome to Simple Web3 PHP!");


        AddTupleA_Params(111, true, 0x0729377BeCbd653b9F14fBf2956CfD317Bf2afAa, "hello my apple");  
        AddTupleA_Params(222, false, 0x0729377becbd653b9F14fBf2956cfd317bf2Afbb, "hello my banana");
        AddTupleA_Params(333, true, 0x0729377becBD653b9f14fBf2956cFD317BF2aFcc, "hello my coconut");
        AddTupleA_Params(444, false, 0x0729377becBd653b9f14fBF2956cfD317bF2aFDd, "hello my durian");

        Tuple_B memory tb = Tuple_B(1, "text1", new string[](0));
        AddTuple_B(tb);
        tb = Tuple_B(2, "text2", new string[](0));
        AddTuple_B(tb);
        tb = Tuple_B(3, "text3", new string[](0));
        AddTuple_B(tb);
        tb = Tuple_B(4, "text4", new string[](0));
        AddTuple_B(tb);

        AddTuple_B_Params(5, "text1", new string[](0));
        AddTuple_B_Params(6, "text1", new string[](0));
    }



    //CALL  (GETTERS)
    function GetAllTuples_B() public view returns (Tuple_B[] memory)
    {
        return array_tuples_b;
    } 

    function GetTuple_A(uint id) public view returns (Tuple_A memory)
    {
        return map_tuples_a[id];
    }

    function ExistsTuple_B(Tuple_B memory t1) public view returns (bool)
    {
        for(uint i = 0; i < array_tuples_b.length; i++)
        {
            Tuple_B memory t2 = array_tuples_b[i];
            if (t1.uint_b == t2.uint_b && Compare(t1.string_b, t2.string_b)) {
                return true;
            }
        } 
        return false;
    }

    function GetTuples_B(string[] calldata search) public view returns (Tuple_B[] memory)
    {
        //no dynamic memory arrays... so max 10 results..
        uint8 maxResults = 10;
        uint8 currentResults = 0;

        Tuple_B[] memory hits = new Tuple_B[](maxResults);
        uint256 len = search.length;

        for (uint i = 0; i < array_tuples_b.length; i++)
        {
            Tuple_B memory t2 = array_tuples_b[i]; 
            for (uint ii = 0; ii < len; ii++)
            {
                if (Compare(search[ii], t2.string_b)) {
                    hits[currentResults] = t2; 
                    currentResults++;
                    break;
                }
                
            }
            if(currentResults >= maxResults) break;
        } 

        return hits;
    }


    //SEND TRANSACTION (SETTERS)
    function Set_public_uint(uint new_uint) public
    {
        public_uint = new_uint;
        emit Event_Set_public_uint(public_uint);
    }

    function Set_public_int(int new_int) public
    {
        public_int = new_int; 
    }

    function Set_public_string(string memory new_string) public
    {
        public_string = new_string; 
    }


    function AddTupleA(Tuple_A memory new_tuple_a) public
    {
        autoinc_tuple_a++;
        map_tuples_a[autoinc_tuple_a] = new_tuple_a;

        emit Event_AddTuple_A(new_tuple_a);
    }

    function AddTupleA_Params(uint uint_a, bool boolean_a, address address_a, bytes memory bytes_a) public 
    {
        autoinc_tuple_a++;
        Tuple_A storage tuple = map_tuples_a[autoinc_tuple_a];
        tuple.uint_a = uint_a;
        tuple.boolean_a = boolean_a;
        tuple.address_a = address_a;
        tuple.bytes_a = bytes_a; 

        emit Event_AddTuple_A(tuple);
    }


    function AddTuple_B(Tuple_B memory new_tuple_b) public
    {
        array_tuples_b.push(new_tuple_b);

        array_tuples_b[array_tuples_b.length-1].string_array_b.push(new_tuple_b.string_b);
        array_tuples_b[array_tuples_b.length-1].string_array_b.push(new_tuple_b.string_b);

        emit Event_AddTuple_B(new_tuple_b);
    }

    function AddTuple_B_Params(uint uint_b, string memory string_b, string[] memory string_array_b) public 
    { 
        Tuple_B memory tuple;
        tuple.uint_b = uint_b;
        tuple.string_b = string_b;
        tuple.string_array_b = string_array_b; 

        array_tuples_b.push(tuple);

        array_tuples_b[array_tuples_b.length-1].string_array_b.push(tuple.string_b);
        array_tuples_b[array_tuples_b.length-1].string_array_b.push(tuple.string_b);
        array_tuples_b[array_tuples_b.length-1].string_array_b.push(tuple.string_b);

        emit Event_AddTuple_B(tuple);
    }


    function Mirror_StringArray(string[][] memory sa) public pure returns (string[][] memory)
    {
        return sa;
    }


    //HELPERS
    function Compare(string memory a, string memory b) public pure returns (bool)
    {
        return keccak256(abi.encodePacked(a)) == keccak256(abi.encodePacked(b));
    }     


}