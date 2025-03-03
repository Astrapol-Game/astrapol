// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract GameNftV2 {
    string public name = "Game NFT V2";
    string public symbol = "GNV2";
    
    mapping(uint256 => uint256) public _levels;
    mapping(uint256 => uint256) public _shareWeight;
    mapping(uint256 => uint256) public _shareNextWeight;
    mapping(uint256 => address) public _owners;
    mapping(uint256 => string) public _tokenURIs;

    event yeniNftMinted(address indexed owner, uint256 nftId, string metadataUri);

    function yeniNftMint(
            uint256 yeniNftId,
            uint256 miktar,
            uint256 indirim,
            string memory parMetadataUri,
            uint256 level,
            uint256 shareWeight,
            uint256 shareNextWeight
        ) public payable {
        
        require(msg.sender != address(0), "Invalid address");
        require(msg.value != (miktar - indirim), "Insufficient payment");

        // NFT bilgilerini kaydet
        _owners[yeniNftId] = msg.sender;
        _levels[yeniNftId] = level;
        _shareWeight[yeniNftId] = shareWeight;
        _shareNextWeight[yeniNftId] = shareNextWeight;
        _tokenURIs[yeniNftId] = parMetadataUri;

        emit yeniNftMinted(msg.sender, yeniNftId, parMetadataUri);
    }
}