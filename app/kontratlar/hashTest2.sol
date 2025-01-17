// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract HashTester2 {
    event DebugHash(bytes32 hash);

    function generateHash(
        address to
    ) public {
        // Hash'i oluştur
        bytes32 hash = keccak256(abi.encode(to));
        // Hash'i logla
        emit DebugHash(hash);
    }
}