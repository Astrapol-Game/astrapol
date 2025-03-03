// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract HashTester4 {
    event DebugHash(bytes32 hash);

    function generateHash(
        string memory uri
    ) public {
        // Hash'i oluştur
        bytes32 hash = keccak256(abi.encode(bytes(uri)));
        // Hash'i logla
        emit DebugHash(hash);
    }
}