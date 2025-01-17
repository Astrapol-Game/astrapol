// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract GameNftV3{
    address public contractOwner;
    string public name = "GameNftV3";
    string public symbol = "GNV3";

    mapping(uint256 => uint256) public _levels;
    mapping(uint256 => uint256) public _shareWeight;
    mapping(uint256 => uint256) public _shareNextWeight;
    mapping(uint256 => address) public _nftOwners;
    mapping(uint256 => string) public _tokenURIs;

    event yeniNftTransferEt(address indexed gonderen, address indexed alici, uint256 eventNftId);
    event kontratSahibiDegisikliginiBildir(address indexed oncekiSahibi, address indexed yeniSahibi);

    constructor(){
        contractOwner = msg.sender;
    }

    modifier onlyOwner(){
        require(msg.sender == contractOwner, "Only the contract owner can call this");
        _;
    }

    function kontratSahibiniDegistir(address yeniKontratSahibiAdresi) public onlyOwner{
        require(yeniKontratSahibiAdresi != address(0), "New owner cannot be zero address");
        emit kontratSahibiDegisikliginiBildir(contractOwner, yeniKontratSahibiAdresi);
        contractOwner = yeniKontratSahibiAdresi;
    }

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
        require(msg.value >= (miktar - indirim), "Insufficient payment");

        // NFT bilgilerini kaydet
        _nftOwners[yeniNftId] = msg.sender;
        _levels[yeniNftId] = level;
        _shareWeight[yeniNftId] = shareWeight;
        _shareNextWeight[yeniNftId] = shareNextWeight;
        _tokenURIs[yeniNftId] = parMetadataUri;

        emit yeniNftTransferEt(contractOwner, msg.sender, yeniNftId);
    }
}