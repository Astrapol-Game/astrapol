// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract HashTester5 {
    event DebugHashEncode(bytes32 hash);
    event DebugHashEncodePacked(bytes32 hash);

    function generateHash(
        string memory uri
    ) public {
        // Hash'i oluştur
        bytes memory trimmedUri = bytes(uri);
        bytes32 hash = keccak256(abi.encode(trimmedUri));
        bytes32 hash2 = keccak256(abi.encodePacked(uri));


        // Hash'i logla
        emit DebugHashEncode(hash);
        emit DebugHashEncodePacked(hash2);
    }
}