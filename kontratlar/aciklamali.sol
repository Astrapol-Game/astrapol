// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

/// @title NFT Mint ve Yönetim Sistemi
/// @dev Sunucu tarafından belirlenen verilerle mint edilir. Transfer izinleri kontrol edilebilir.

contract DAKCollectionV6 
{
    address public owner; // Kontrat sahibi (Sunucu tarafından kontrol edilecek)
    
    // NFT bilgileri
    struct NFT {
        string tokenURI; // NFT resmi ve metadata bilgisi
        uint256 level; // Level bilgisi
        uint256 shareWeight; // Ağırlık
        uint256 shareNextWeight; // Gelecek Ağırlık
        address transferAuthority; // Transfer yetkisi (owner veya kullanıcı)
    }

    mapping(uint256 => NFT) public nfts; // NFT ID -> NFT Bilgisi
    mapping(uint256 => address) public tokenOwners; // NFT ID -> Sahip Adresi
    mapping(bytes32 => bool) public usedSignatures; // Kullanılmış imzalar (tekrar saldırılarına karşı koruma)

    /// @notice NFT Mint İsteği Oluşturulduğunda Tetiklenir
    event NFTMintRequested(uint256 tokenId, address indexed user, string tokenURI);
    /// @notice NFT Mint İşlemi Başarıyla Tamamlandığında Tetiklenir
    event NFTMinted(uint256 tokenId, address indexed user, string tokenURI);
    /// @notice NFT Transfer Yetkisi Güncellendiğinde Tetiklenir
    event TransferAuthorityUpdated(uint256 tokenId, address newAuthority);
    /// @notice NFT Özellikleri Güncellendiğinde Tetiklenir
    event NFTAttributesUpdated(uint256 tokenId, uint256 level, uint256 shareWeight, uint256 shareNextWeight);

    constructor() {
        owner = msg.sender; // Kontrat deploy edildiğinde sahibi atanır
    }

    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can perform this action");
        _;
    }

    modifier onlyTransferAuthority(uint256 tokenId) {
        require(
            msg.sender == nfts[tokenId].transferAuthority || msg.sender == owner,
            "Not authorized to transfer"
        );
        _;
    }

    /// @notice Mint Talebi (Sunucu tarafından başlatılır)
    function requestMint(
        uint256 tokenId,
        string memory _tokenURI,
        address user,
        uint256 _level,
        uint256 _shareWeight,
        uint256 _shareNextWeight
    ) public onlyOwner {
        require(tokenOwners[tokenId] == address(0), "Token already minted");

        nfts[tokenId] = NFT({
            tokenURI: _tokenURI,
            level: _level,
            shareWeight: _shareWeight,
            shareNextWeight: _shareNextWeight,
            transferAuthority: owner
        });

        emit NFTMintRequested(tokenId, user, _tokenURI);
    }

    /// @notice Kullanıcı gas ücretini ödeyerek mint işlemini tamamlar
    function confirmMint(
        uint256 tokenId,
        address user,
        uint256 gasFee,
        bytes memory signature
    ) public payable {
        require(msg.value == gasFee, "Incorrect gas fee");
        require(tokenOwners[tokenId] == address(0), "Token already minted");

        bytes32 messageHash = keccak256(abi.encodePacked(tokenId, user, gasFee));
        require(!usedSignatures[messageHash], "Signature already used");

        usedSignatures[messageHash] = true;

        tokenOwners[tokenId] = user;

        emit NFTMinted(tokenId, user, nfts[tokenId].tokenURI);
    }

    /// @notice NFT Transfer Yetkisini Güncelle
    function updateTransferAuthority(uint256 tokenId, address newAuthority) public onlyOwner {
        require(tokenOwners[tokenId] != address(0), "Token not minted");

        nfts[tokenId].transferAuthority = newAuthority;

        emit TransferAuthorityUpdated(tokenId, newAuthority);
    }

    /// @notice NFT Özelliklerini Güncelle
    function updateNFTAttributes(
        uint256 tokenId,
        uint256 _level,
        uint256 _shareWeight,
        uint256 _shareNextWeight
    ) public onlyOwner {
        require(tokenOwners[tokenId] != address(0), "Token not minted");

        nfts[tokenId].level = _level;
        nfts[tokenId].shareWeight = _shareWeight;
        nfts[tokenId].shareNextWeight = _shareNextWeight;

        emit NFTAttributesUpdated(tokenId, _level, _shareWeight, _shareNextWeight);
    }
}
