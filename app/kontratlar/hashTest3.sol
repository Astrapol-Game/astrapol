// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract HashTester3 {
    event DebugHash(bytes32 hash);

    function generateHash(
        address to,
        uint256 tokenId
    ) public {
        // Hash'i oluştur
        bytes32 hash = keccak256(abi.encode(to, tokenId));
        // Hash'i logla
        emit DebugHash(hash);
    }
}