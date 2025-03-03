// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract GameNftV33 {
    string public name = "GameNftV33";
    string public symbol = "GNV";

    address public contractOwner;
    address public paymentAddress;
    address public authorizedSigner;

    // Royalty bilgilerini saklamak için değişkenler
    address private _royaltyReceiver;
    uint256 private _royaltyPercentage; // Örneğin 500 = %5 (10000 üzerinden hesaplanır)

    mapping(uint256 => address) private _owners;
    mapping(address => uint256) private _balances;
    mapping(uint256 => string) private _tokenURIs;
    mapping (uint256 => uint256) private _levels;
    mapping(uint256 => address) private _tokenApprovals;
    mapping(address => mapping(address => bool)) private _operatorApprovals;

    event Transfer(address indexed from, address indexed to, uint256 indexed tokenId);
    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);
    event PaymentAddressUpdated(address indexed previousAddress, address indexed newAddress);
    event LevelUpdated(uint256 indexed tokenId, uint256 previousLevel, uint256 newLevel);
    event Approval(address indexed owner, address indexed approved, uint256 indexed tokenId);
    event ApprovalForAll(address indexed owner, address indexed operator, bool approved);

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

    // Tek bir NFT'yi belirli bir adrese transfer etme yetkisi verme
    function approve(address to, uint256 tokenId) public {
        require(_owners[tokenId] == msg.sender, "Caller is not the owner");
        _tokenApprovals[tokenId] = to;
        emit Approval(msg.sender, to, tokenId);
    }

    // Belirli bir operatöre tüm NFT'ler için yetki verme
    function setApprovalForAll(address operator, bool approved) public {
        _operatorApprovals[msg.sender][operator] = approved;
        emit ApprovalForAll(msg.sender, operator, approved);
    }

    // Belirli bir NFT için verilen yetkiyi kontrol etme
    function getApproved(uint256 tokenId) public view returns (address) {
        return _tokenApprovals[tokenId];
    }

    // Bir adresin başka bir adres için NFT transfer yetkisi olup olmadığını kontrol etme
    function isApprovedForAll(address owner, address operator) public view returns (bool) {
        return _operatorApprovals[owner][operator];
    }

    // Safe transfer işlemi
    function safeTransferFrom(address from, address to, uint256 tokenId) public {
        transferFrom(from, to, tokenId);
        require(_checkOnERC721Received(from, to, tokenId, ""), "Transfer to non ERC721Receiver implementer");
    }

    // Standart ERC-721 arayüz desteğini bildirir
    function supportsInterface(bytes4 interfaceId) public pure returns (bool) {
        return interfaceId == 0x80ac58cd || interfaceId == 0x5b5e139f; // ERC-721 ve Metadata standardı
    }

    // Telif hakkı alacak adresi ve yüzdesini ayarla
    function setRoyaltyInfo(address receiver, uint256 percentage) public onlyOwner {
        require(percentage <= 10000, "Percentage too high"); // %100 üzeri engelleniyor
        _royaltyReceiver = receiver;
        _royaltyPercentage = percentage;
    }

    // NFT satışı olduğunda telif hakkı olarak ödenecek miktarı hesaplar
    function royaltyInfo(uint256 /*tokenId*/, uint256 salePrice) public view returns (address receiver, uint256 royaltyAmount) {
        return (_royaltyReceiver, (salePrice * _royaltyPercentage) / 10000);
    }

    // ERC-721 alıcı olup olmadığını kontrol eden iç fonksiyon
    function _checkOnERC721Received(address from, address to, uint256 tokenId, bytes memory data) private returns (bool) {
        if (isContract(to)) 
        {
            (bool success, bytes memory returnData) = to.call(
                abi.encodeWithSignature("onERC721Received(address,address,uint256,bytes)", msg.sender, from, tokenId, data)
            );
            if (!success) {
                return false;
            }
            bytes4 returnedValue = abi.decode(returnData, (bytes4));
            return returnedValue == 0x150b7a02; // ERC-721 onERC721Received selector
        }
        return true;
    }

    // Adresin akıllı kontrat olup olmadığını kontrol eden yardımcı fonksiyon
    function isContract(address account) private view returns (bool) {
        uint256 size;
        assembly {
            size := extcodesize(account)
        }
        return size > 0;
    }

    // NFT'yi başka bir adrese transfer et
    function transferFrom(address from, address to, uint256 tokenId) public 
    {
        require(from == msg.sender, "You are not the owner");
        require(to != address(0), "Cannot transfer to zero address");
        require(_owners[tokenId] == from, "Token not owned by sender");

        _owners[tokenId] = to;
        _balances[from]--;
        _balances[to]++;

        emit Transfer(from, to, tokenId);
    }

    // Yeni bir NFT mintle
    function mint(
        address to,
        uint256 tokenId,
        uint256 level,
        uint256 price,
        string memory uri,
        bytes memory signature
    ) public payable {
        emit Debug("fonksiyon basladi, devam ediyor"); // hata takibi için, sonra kaldır
        require(to != address(0), "Cannot mint to zero address");
        emit Debug("adres verisi gecerli, devam ediyor"); // hata takibi için, sonra kaldır
        require(msg.sender == to, "Sender must match the on server set address");
        emit Debug("islemi baslatan adres ile sunucuda ayarlanan kullanici adresi ayni, devam ediyor"); // hata takibi için, sonra kaldır
        require(_owners[tokenId] == address(0), "Token ID already exists");
        emit Debug("token id kullanilabilir, devam ediyor"); // hata takibi için, sonra kaldır
        

        // İmzayı doğrula
        bytes32 message = keccak256(abi.encode(to, tokenId, level, price));
        emit DebugByte(message); // hata takibi için, sonra kaldır

        bytes32 prefixedHash = keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", message));
        emit DebugByte(prefixedHash); // hata takibi için, sonra kaldır

        address signer = recoverSigner(prefixedHash, signature);
        emit DebugByte(bytes32(uint256(uint160(signer)))); // hata takibi için, sonra kaldır

        require(signer == authorizedSigner, "Invalid signature");

        // Token sahibini belirle
        _owners[tokenId] = to;
        _balances[to]++;
        _tokenURIs[tokenId] = uri;
        _levels[tokenId] = level;

        emit Transfer(address(0), to, tokenId); // ERC-721 standardıdır ve belli bir adrese transfer edildiğini blockchaine haber verir
        
        (bool success, ) = paymentAddress.call{value: price}("");
        require(success, "Payment failed");

        // eğer fazla gönderilen miktar varsa iade et
        if(msg.value > price) 
        {
            (bool refundSuccess, ) = payable(msg.sender).call{value: msg.value - price}("");
            require(refundSuccess, "Refund failed");
        }
    }

    // NFT Metadata URI'sini al
    function tokenURI(uint256 tokenId) public view returns (string memory) 
    {
        require(_owners[tokenId] != address(0), "Token does not exist");
        return _tokenURIs[tokenId];
    }

    // Tokenın level değerini al
    function getLevel(uint256 tokenId) public view returns (uint256) 
    {
        require(_owners[tokenId] != address(0), "Token does not exist");
        return _levels[tokenId];
    }

    function updateLevel(
        address to,
        uint256 tokenId, 
        uint256 newLevel, 
        uint256 price, 
        bytes memory signature
    ) public payable {
        emit Debug("fonksiyon basladi, devam ediyor"); // hata takibi için, sonra kaldır
        require(_owners[tokenId] != address(0), "Token does not exist");
        emit Debug("token id kullanilabilir, devam ediyor"); // hata takibi için, sonra kaldır
        require(msg.sender == to, "Sender must match the on server set address");
        emit Debug("islemi baslatan adres ile sunucuda ayarlanan kullanici adresi ayni, devam ediyor");
        require(msg.sender == _owners[tokenId], "Sender must match the nft owner address");
        emit Debug("token id sahibi update islemini yapan ile ayni, devam ediyor"); // hata takibi için, sonra kaldır

        // İmzayı doğrula
        bytes32 message = keccak256(abi.encode(to, tokenId, newLevel, price));
        emit DebugByte(message); // hata takibi için, sonra kaldır

        bytes32 prefixedHash = keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", message));
        emit DebugByte(prefixedHash); // hata takibi için, sonra kaldır

        address signer = recoverSigner(prefixedHash, signature);
        emit DebugByte(bytes32(uint256(uint160(signer)))); // hata takibi için, sonra kaldır

        require(signer == authorizedSigner, "Invalid signature");
        emit Debug("sunucu imza adresi kontrattaki ile ayni, devam ediyor"); // hata takibi için, sonra kaldır

        uint256 previousLevel = _levels[tokenId];
        _levels[tokenId] = newLevel;
        emit LevelUpdated(tokenId, previousLevel, newLevel);

        (bool success, ) = paymentAddress.call{value: price}("");
        require(success, "Payment failed");

        // Fazla gönderilen miktarı iade et
        if(msg.value > price) 
        {
            (bool refundSuccess, ) = payable(msg.sender).call{value: msg.value - price}("");
            require(refundSuccess, "Refund failed");
        }
    }

    function recoverSigner(bytes32 hash, bytes memory signature) internal pure returns (address) 
    {
        require(signature.length == 65, "Invalid signature length");

        bytes32 r;
        bytes32 s;
        uint8 v;

        assembly {
            r := mload(add(signature, 32))
            s := mload(add(signature, 64))
            v := byte(0, mload(add(signature, 96)))
        }

        require(uint256(s) > 0 && uint256(s) <= 0x7fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff, "Invalid S value");
        require(v == 27 || v == 28, "Invalid V value");

        return ecrecover(hash, v, r, s);
    }

    // kontrat adresine token göndermeyi imkansız hale getirir
    receive() external payable {
        revert("Direct payments not allowed");
    }
}