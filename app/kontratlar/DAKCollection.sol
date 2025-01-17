// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract DAKCollectionV5
{
    string public name = "DAK v5";
    string public symbol = "DAK";
    address public owner;

    mapping(uint256 => address) public tokenOwners;
    mapping(uint256 => string) public tokenURIs;
    mapping(uint256 => bool) public transferAllowed;

    struct NFTAttributes 
    {
        uint256 level;
        uint256 shareWeight;
        uint256 shareNextWeight;
    }

    mapping(uint256 => NFTAttributes) public nftAttributes;

    event NFTMinted(uint256 tokenId, address owner, string tokenURI, uint256 level, uint256 shareWeight, uint256 shareNextWeight);
    event NFTTransferred(uint256 tokenId, address from, address to);
    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);

    modifier onlyOwner() 
    {
        require(msg.sender == owner, "Only the contract owner can perform this action");
        _;
    }

    constructor() 
    {
        owner = msg.sender;
    }

    function mint(uint256 tokenId, string memory _tokenURI, uint256 _level, uint256 _shareWeight, uint256 _shareNextWeight) public onlyOwner 
    {
        require(tokenOwners[tokenId] == address(0), "Token ID already exists");
        require(msg.sender != address(0), "Invalid sender address");
        tokenOwners[tokenId] = msg.sender;
        tokenURIs[tokenId] = _tokenURI;
        nftAttributes[tokenId] = NFTAttributes(_level, _shareWeight, _shareNextWeight);
        emit NFTMinted(tokenId, msg.sender, _tokenURI, _level, _shareWeight, _shareNextWeight);
    }

    function updateAttributes(uint256 tokenId, uint256 _level, uint256 _shareWeight, uint256 _shareNextWeight) public onlyOwner 
    {
        require(tokenOwners[tokenId] != address(0), "Token does not exist");
        nftAttributes[tokenId] = NFTAttributes(_level, _shareWeight, _shareNextWeight);
    }

    function setTransferPermission(uint256 tokenId, bool isAllowed) public onlyOwner 
    {
        require(tokenOwners[tokenId] != address(0), "Token does not exist");
        transferAllowed[tokenId] = isAllowed;
    }

    function transferNFT(address to, uint256 tokenId) public 
    {
        require(tokenOwners[tokenId] != address(0), "Token does not exist");
        require(to != address(0), "Receiver address cannot be zero");

        if (!transferAllowed[tokenId]) {
            require(msg.sender == owner, "Only the contract owner can transfer this NFT");
        } else {
            require(msg.sender == tokenOwners[tokenId], "Only the token owner can transfer this NFT");
        }

        tokenOwners[tokenId] = to;
        emit NFTTransferred(tokenId, msg.sender, to);
    }
}
