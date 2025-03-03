// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract GameNftV32 {
    string public name = "GameNftV32";
    string public symbol = "GNV";

    address public contractOwner;
    address public paymentAddress;
    address public authorizedSigner;

    mapping(uint256 => address) private _owners;
    mapping(address => uint256) private _balances;
    mapping(uint256 => string) private _tokenURIs;
    mapping (uint256 => uint256) private _levels;
    mapping(uint256 => bool) private _transferControl;
    mapping(uint256 => address) private _tokenApprovals;

    event Transfer(address indexed from, address indexed to, uint256 indexed tokenId);
    event OwnershipTransferred(address indexed previousOwner, address indexed newOwner);
    event PaymentAddressUpdated(address indexed previousAddress, address indexed newAddress);
    event LevelUpdated(uint256 indexed tokenId, uint256 previousLevel, uint256 newLevel);
    event TransferControlUpdated(uint256 indexed tokenId, bool enabled);
    event Approval(address indexed owner, address indexed approved, uint256 indexed tokenId);
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

    function setTransferControl(uint256 tokenId, bool status) public {
        require(_owners[tokenId] != address(0), "Token does not exist");

        if (!_transferControl[tokenId]) 
        {
            // Transfer yetkisi hala kontrat sahibindeyse, sadece kontrat sahibi yetkiyi verebilir
            require(msg.sender == contractOwner, "Only contract owner can enable transfer control");
        } 
        else 
        {
            // Transfer yetkisi sahibine geçtiyse, sadece NFT sahibi yetkiyi geri verebilir
            require(msg.sender == _owners[tokenId], "Only token owner can disable transfer control");
        }

        _transferControl[tokenId] = status;
        emit TransferControlUpdated(tokenId, status);
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

    function approve(address to, uint256 tokenId) public {
        require(_owners[tokenId] != address(0), "Token does not exist");
        require(_transferControl[tokenId], "Transfer control is still with contract owner"); // Transfer yetkisi sahibine geçti mi?
        require(_owners[tokenId] == msg.sender, "Caller is not the owner");

        _tokenApprovals[tokenId] = to;
        emit Approval(msg.sender, to, tokenId);
    }

    function getApproved(uint256 tokenId) public view returns (address) {
        require(_owners[tokenId] != address(0), "Token does not exist");
        require(_transferControl[tokenId], "Transfer control is still with contract owner"); // Transfer yetkisi sahibine geçti mi?

        return _tokenApprovals[tokenId];
    }

    // NFT'yi başka bir adrese transfer et
    function transferFrom(
        address from, 
        address to, 
        uint256 tokenId, 
        uint256 price, 
        uint256 commission, 
        bytes memory signature
    ) public payable {
        emit Debug("fonksiyon basladi, devam ediyor"); // hata takibi için, sonra kaldır
        require(to != address(0), "Cannot transfer to zero address");
        emit Debug("adres verisi gecerli, devam ediyor"); // hata takibi için, sonra kaldır
        require(_owners[tokenId] == from, "Token not owned by sender");
        emit Debug("token sahibi from ile ayni"); // hata takibi için, sonra kaldır

        if (!_transferControl[tokenId]) 
        {
            // Eğer transfer yetkisi sahibindeyse, ERC-721 standardına göre çalıştır
            require(from == msg.sender || msg.sender == getApproved(tokenId), "Caller is not owner nor approved");
            _executeTransfer(from, to, tokenId);
        } 
        else 
        {
            // Eğer transfer yetkisi kontrat sahibindeyse, işlemi ya NFT sahibi ya da satın alan kişi başlatabilir
            require(msg.sender == from || msg.sender == to, "Only owner or buyer can execute this transaction");
            
            bytes32 message = keccak256(abi.encode(from, to, tokenId, price, commission));
            emit DebugByte(message); // hata takibi için, sonra kaldır

            bytes32 prefixedHash = keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", message));
            emit DebugByte(prefixedHash); // hata takibi için, sonra kaldır

            address signer = recoverSigner(prefixedHash, signature);
            emit DebugByte(bytes32(uint256(uint160(signer)))); // hata takibi için, sonra kaldır

            require(signer == authorizedSigner, "Invalid signature");

            (bool paymentSuccess, ) = from.call{value: price}("");
            require(paymentSuccess, "Payment to seller failed");

            if (commission > 0) 
            {
                (bool commissionSuccess, ) = paymentAddress.call{value: commission}("");
                require(commissionSuccess, "Commission payment failed");
            }

            _executeTransfer(from, to, tokenId);
        }
    }

    function _executeTransfer(address from, address to, uint256 tokenId) internal {
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