// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

/// @title NFTMint: Mint ve Update Ücretli NFT Yönetimi
/// @notice Hem mint hem de güncelleme işlemleri sunucu tarafından doğrulama ve ücret kontrolü ile yapılır.
contract NFTMint {
    string public name;
    string public symbol;
    address public trustedServer;
    mapping(uint256 => address) public tokenOwners;
    mapping(uint256 => string) public tokenURIs;

    uint256 public totalSupply;

    event NFTMinted(address indexed user, uint256 tokenId, uint256 price, string tokenURI);
    event NFTUpdated(address indexed user, uint256 tokenId, string newTokenURI);

    constructor(address _trustedServer, string memory _name, string memory _symbol) {
        trustedServer = _trustedServer;
        name = _name;
        symbol = _symbol;
    }

    /// @notice NFT Mint İşlemi
    /// @param user Kullanıcının cüzdan adresi
    /// @param tokenId Mint edilecek NFT'nin kimliği
    /// @param price NFT mint ücreti
    /// @param chainId Blok zincir kimliği
    /// @param signature Sunucu imzası
    /// @param message Hashlenmiş mesaj
    /// @param memTokenURI NFT'nin metadata URI'si
    function mintNFT(
        address user,
        uint256 tokenId,
        uint256 price,
        uint256 chainId,
        bytes memory signature,
        bytes32 message,
        string memory memTokenURI
    ) external payable {
        require(msg.value >= price, "Insufficient mint fee");
        require(chainId == 43113, "Invalid chain ID");

        bytes32 calculatedHash = keccak256(abi.encodePacked(user, tokenId, price, chainId, memTokenURI));
        require(calculatedHash == message, "Message hash mismatch");
        require(recoverSigner(message, signature) == trustedServer, "Invalid server signature");
        require(tokenOwners[tokenId] == address(0), "Token already minted");

        tokenOwners[tokenId] = user;
        tokenURIs[tokenId] = memTokenURI;
        totalSupply += 1;

        emit NFTMinted(user, tokenId, price, memTokenURI);
    }

    /// @notice NFT Güncelleme İşlemi
    /// @param user Kullanıcının cüzdan adresi
    /// @param tokenId Güncellenecek NFT'nin kimliği
    /// @param price NFT güncelleme ücreti
    /// @param signature Sunucu imzası
    /// @param message Hashlenmiş mesaj
    /// @param newTokenURI Yeni metadata URI'si
    function updateNFT(
        address user,
        uint256 tokenId,
        uint256 price,
        bytes memory signature,
        bytes32 message,
        string memory newTokenURI
    ) external payable {
        require(msg.value >= price, "Insufficient update fee");
        require(tokenOwners[tokenId] == user, "Only the owner can update NFT");

        bytes32 calculatedHash = keccak256(abi.encodePacked(user, tokenId, price, newTokenURI));
        require(calculatedHash == message, "Message hash mismatch");
        require(recoverSigner(message, signature) == trustedServer, "Invalid server signature");

        tokenURIs[tokenId] = newTokenURI;

        emit NFTUpdated(user, tokenId, newTokenURI);
    }

    /// @notice Bir Token'ın URI'sini Döner
    function tokenURI(uint256 tokenId) public view returns (string memory) {
        require(tokenOwners[tokenId] != address(0), "Token does not exist");
        return tokenURIs[tokenId];
    }

    /// @notice İmza Doğrulama
    function recoverSigner(bytes32 message, bytes memory signature) public pure returns (address) {
        require(signature.length == 65, "Invalid signature length");
        bytes32 r;
        bytes32 s;
        uint8 v;

        assembly {
            r := mload(add(signature, 0x20))
            s := mload(add(signature, 0x40))
            v := byte(0, mload(add(signature, 0x60)))
        }

        require(v == 27 || v == 28, "Invalid v value");
        return ecrecover(toEthSignedMessageHash(message), v, r, s);
    }

    function toEthSignedMessageHash(bytes32 hash) internal pure returns (bytes32) {
        return keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", hash));
    }
}