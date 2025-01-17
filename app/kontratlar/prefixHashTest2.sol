// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract PrefixedMessageTester2 {
    event Debug(string message);

    function debugPrefixedMessage(bytes32 message) public {
        bytes memory prefixedMessage = abi.encode("\x19Ethereum Signed Message:\n32", message);
        emit Debug(string(abi.encodePacked("Prefixed Message: ", prefixedMessage)));
    }
}
