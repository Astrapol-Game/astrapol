// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract PrefixedMessageTester3 {
    event DebugMessage(bytes message);       // Prefikslı mesajın binary formatını loglar
    event DebugHash(bytes32 hash);          // Prefikslı hash'i loglar

    function debugPrefixedMessage(bytes32 message) public {
        // Prefiks ve mesajı Solidity'deki gibi birleştir
        bytes memory prefixedMessage = abi.encodePacked("\x19Ethereum Signed Message:\n32", message);

        // Prefikslı mesajın hash'ini oluştur
        bytes32 prefixedHash = keccak256(prefixedMessage);

        // Loglama
        emit DebugMessage(prefixedMessage); // Prefikslı mesajın binary hali
        emit DebugHash(prefixedHash);      // Prefikslı hash
    }
}
