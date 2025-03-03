// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract GameNftV27 {
    string public name = "GameNftV27";
    string public symbol = "GNV";

    uint256 public mintPrice = 0.1 ether;
    uint256 public levelUpdatePrice = 0.1 ether;
    address public contractOwner;
    address public paymentAddress;
    address public authorizedSigner;

    mapping(uint256 => address) private _owners;
    mapping(address => uint256) private _balances;
    mapping(uint256 => string) private _tokenURIs;
    mapping (uint256 => uint256) private _levels;

    event Transfer(address indexed from, address indexed to, uint256 indexed tokenId);
    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);
    event PaymentAddressUpdated(address indexed previousAddress, address indexed newAddress);
    event LevelUpdated(uint256 indexed tokenId, uint256 previousLevel, uint256 newLevel);
    event MintWithSignature(address indexed to, uint256 indexed tokenId);
    event Debug(string message);
    event DebugByte(bytes32 data);

    modifier onlyOwner() {
        require(msg.sender == contractOwner, "Caller is not the contract owner");
        _;
    }

    constructor() {
        contractOwner = msg.sender;
        paymentAddress = msg.sender;
        authorizedSigner = msg.sender;
    }

    function transferOwnership(address newOwner) public onlyOwner {
        require(newOwner != address(0), "New owner cannot be the zero address");
        emit OwnershipTransferred(contractOwner, newOwner);
        contractOwner = newOwner;
    }

    function updatePaymentAddress(address newPaymentAddress) public onlyOwner {
        require(newPaymentAddress != address(0), "Payment address cannot be the zero address");
        emit PaymentAddressUpdated(paymentAddress, newPaymentAddress);
        paymentAddress = newPaymentAddress;
    }

    function setAuthorizedSigner(address signer) public onlyOwner {
        require(signer != address(0), "Signer cannot be the zero address");
        authorizedSigner = signer;
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

    function setMintFee(uint256 _mintFee) public onlyOwner {
        mintPrice = _mintFee;
    }

    function setLevelUpdateFee(uint256 _levelUpdateFee) public onlyOwner {
        levelUpdatePrice = _levelUpdateFee;
    }

    // Yeni bir NFT mintle
    function mint(
        address to,
        uint256 tokenId,
        string memory uri,
        uint256 level,
        bytes memory signature
    ) public payable {
        emit Debug("Mint function called");
        require(to != address(0), "Cannot mint to zero address");
        emit Debug("Address is valid");
        require(msg.value >= mintPrice, "Minting fee must be greater than zero");
        emit Debug("ETH value is sufficient");
        require(_owners[tokenId] == address(0), "Token ID already exists");
        emit Debug("Token ID is unique");

        // İmzayı doğrula
        bytes32 message = keccak256(abi.encode(to, tokenId, level));
        emit DebugByte(message);

        bytes32 prefixedHash = keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", message));
        emit DebugByte(prefixedHash);

        address signer = recoverSigner(prefixedHash, signature);
        emit DebugByte(bytes32(uint256(uint160(signer))));

        require(signer == authorizedSigner, "Invalid signature");

        // Token sahibini belirle
        _owners[tokenId] = to;
        _balances[to]++;
        _tokenURIs[tokenId] = uri;
        _levels[tokenId] = level;

        emit Transfer(address(0), to, tokenId);

        payable(paymentAddress).transfer(mintPrice);

        // fazla gönderilen miktar iade et
        if(msg.value > mintPrice)
        {
            payable (msg.sender).transfer(msg.value - mintPrice);
        }

        emit MintWithSignature(to, tokenId);
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

    function updateLevel(uint256 tokenId, uint256 newLevel) public payable {
        require(_owners[tokenId] != address(0), "Token does not exist");
        require(msg.sender == _owners[tokenId] || msg.sender == contractOwner, "Caller is not authorized");
        require(msg.value >= levelUpdatePrice, "Insufficient fee for level update");

        uint256 previousLevel = _levels[tokenId];
        _levels[tokenId] = newLevel;
        emit LevelUpdated(tokenId, previousLevel, newLevel);

        // Ödeme adresine levelUpdatePrice aktar
        payable(paymentAddress).transfer(levelUpdatePrice);

        // Fazla gönderilen miktarı iade et
        if (msg.value > levelUpdatePrice) {
            payable(msg.sender).transfer(msg.value - levelUpdatePrice);
        }
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

    function recoverSigner(bytes32 hash, bytes memory signature) internal pure returns (address) {
        require(signature.length == 65, "Invalid signature length");

        bytes32 r;
        bytes32 s;
        uint8 v;

        assembly {
            r := mload(add(signature, 32))
            s := mload(add(signature, 64))
            v := byte(0, mload(add(signature, 96)))
        }

        return ecrecover(hash, v, r, s);
    }
}