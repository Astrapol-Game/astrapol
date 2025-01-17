// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract GameNftV5{
    address public contractOwner;
    string public name = "GameNftV5";
    string public symbol = "GNV5";

    mapping(uint256 => uint256) public _levels;
    mapping(uint256 => uint256) public _shareWeight;
    mapping(uint256 => uint256) public _shareNextWeight;
    mapping(uint256 => address) public _nftSahipleri;
    mapping(uint256 => string) public _nftURIs;
    mapping(address => uint256) private _toplamNftSayisi;

    event Transfer(address indexed gonderen, address indexed alici, uint256 eventNftId);
    event OwnershipTransferred(address indexed oncekiSahibi, address indexed yeniSahibi);

    constructor(){
        contractOwner = msg.sender;
    }

    modifier onlyOwner(){
        require(msg.sender == contractOwner, "Only the contract owner can call this");
        _;
    }

    function ownerOf(uint256 nftId) public view returns (address) {
        require(_nftSahipleri[nftId] != address(0), "NFT does not exist");
        return _nftSahipleri[nftId];
    }

    function balanceOf(address nftSahibi) public view returns (uint256) {
        require(nftSahibi != address(0), "Invalid address");
        return _toplamNftSayisi[nftSahibi];
    }

    function tokenURI(uint256 nftId) public view returns (string memory) {
        require(_nftSahipleri[nftId] != address(0), "NFT does not exist");
        return _nftURIs[nftId];
    }

    function transferFrom(address gonderen, address alici, uint256 nftId) public {
        require(gonderen == msg.sender, "You are not the owner");
        require(alici != address(0), "Cannot transfer to zero address");
        require(_nftSahipleri[nftId] == gonderen, "NFT not owned by sender");

        _nftSahipleri[nftId] = alici;
        _toplamNftSayisi[gonderen]--;
        _toplamNftSayisi[alici]++;

        emit Transfer(gonderen, alici, nftId);
    }

    function transferOwnership(address yeniKontratSahibiAdresi) public onlyOwner{
        require(yeniKontratSahibiAdresi != address(0), "New owner cannot be zero address");
        emit OwnershipTransferred(contractOwner, yeniKontratSahibiAdresi);
        contractOwner = yeniKontratSahibiAdresi;
    }

    function mint(
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
        _nftSahipleri[yeniNftId] = msg.sender;
        _levels[yeniNftId] = level;
        _shareWeight[yeniNftId] = shareWeight;
        _shareNextWeight[yeniNftId] = shareNextWeight;
        _nftURIs[yeniNftId] = parMetadataUri;

        emit Transfer(contractOwner, msg.sender, yeniNftId);
    }
}