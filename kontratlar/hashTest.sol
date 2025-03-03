// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract HashTester {
    event DebugHash(bytes32 hash);

    function generateHash(
        address to,
        uint256 tokenId,
        string memory uri,
        uint256 level
    ) public {
        // Hash'i oluştur
        bytes32 hash = keccak256(abi.encode(to, tokenId, bytes(uri), level));
        // Hash'i logla
        emit DebugHash(hash);
    }
}