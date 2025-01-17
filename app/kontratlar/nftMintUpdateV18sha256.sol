// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract GameNftV18 {
    string public name = "GameNftV18";
    string public symbol = "GNV";

    uint256 private _tokenIdCounter;
    address public contractOwner;
    address public trustedSigner;
    mapping(uint256 => address) private _owners;
    mapping(address => uint256) private _balances;
    mapping(uint256 => string) private _tokenURIs;
    mapping (uint256 => uint256) private _levels;

    event Transfer(address indexed from, address indexed to, uint256 indexed tokenId);
    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);

    modifier onlyOwner() {
        require(msg.sender == contractOwner, "Caller is not the contract owner");
        _;
    }

    constructor(address signer) {
        contractOwner = msg.sender;
        trustedSigner = signer;
    }

    function transferOwnership(address newOwner) public onlyOwner {
        require(newOwner != address(0), "New owner cannot be the zero address");
        emit OwnershipTransferred(contractOwner, newOwner);
        contractOwner = newOwner;
    }

    function setTrustedSigner(address signer) public onlyOwner {
        require(signer != address(0), "Trusted signer cannot be the zero address");
        trustedSigner = signer;
    }

    // NFT'nin sahibini döndür
    function ownerOf(uint256 tokenId) public view returns (address) {
        require(_owners[tokenId] != address(0), "Token does not exist");
        return _owners[tokenId];
    }

    // Bir adresin sahip olduğu NFT sayısını döndür
    function balanceOf(address owner) public view returns (uint256) {
        require(owner != address(0), "Address zero is not a valid owner");
        return _balances[owner];
    }

    // Yeni bir NFT mintle
    function mint(
        address to,
        uint256 tokenId,
        string memory tokenURI,
        uint256 level,
        bytes memory signature
    ) public payable {
        require(to != address(0), "Cannot mint to zero address");
        require(msg.value > 0, "Minting fee must be greater than zero");

        // Hash doğrulama için oluştur
        bytes32 messageHash = keccak256(abi.encodePacked(to, tokenId, tokenURI, level, address(this)));
        bytes32 ethSignedMessageHash = keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", messageHash));

        // İmzayı doğrula
        require(recoverSigner(ethSignedMessageHash, signature) == trustedSigner, "Invalid signature");

        // Token sahibini belirle
        require(_owners[tokenId] == address(0), "Token ID already exists");
        _owners[tokenId] = to;
        _balances[to]++;
        _tokenURIs[tokenId] = tokenURI;
        _levels[tokenId] = level;

        emit Transfer(address(0), to, tokenId);
    }

    // NFT Metadata URI'sini al
    function tokenURI(uint256 tokenId) public view returns (string memory) {
        require(_owners[tokenId] != address(0), "Token does not exist");
        return _tokenURIs[tokenId];
    }

    // Tokenın level değerini al
    function getLevel(uint256 tokenId) public view returns (uint256) {
        require(_owners[tokenId] != address(0), "Token does not exist");
        return _levels[tokenId];
    }

    // NFT'yi başka bir adrese transfer et
    function transferFrom(address from, address to, uint256 tokenId) public {
        require(from == msg.sender, "You are not the owner");
        require(to != address(0), "Cannot transfer to zero address");
        require(_owners[tokenId] == from, "Token not owned by sender");

        _owners[tokenId] = to;
        _balances[from]--;
        _balances[to]++;

        emit Transfer(from, to, tokenId);
    }

    function recoverSigner(bytes32 ethSignedMessageHash, bytes memory signature) internal pure returns (address) {
        require(signature.length == 65, "Invalid signature length");

        bytes32 r;
        bytes32 s;
        uint8 v;

        assembly {
            r := mload(add(signature, 0x20))
            s := mload(add(signature, 0x40))
            v := byte(0, mload(add(signature, 0x60)))
        }

        if (v < 27) {
            v += 27;
        }

        require(v == 27 || v == 28, "Invalid v value");

        return ecrecover(ethSignedMessageHash, v, r, s);
    }
}
