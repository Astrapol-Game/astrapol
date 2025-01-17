<?php

// Ethereum için özel anahtar (Örnek bir anahtar, gerçek projede ENV dosyasında tutun)
$privateKey = '8e37f833f31ca31743fadee7a340c15a09613823a3fab61b586921c4daebb356';

// Kullanıcıya özel mint verilerini imzala
function signMintData($user, $nftId, $price, $discountAmount, $privateKey) 
{
    // Ethereum özel anahtarı 64 karakter olmalı ve başında '0x' olmamalı
    $privateKey = ltrim($privateKey, '0x');

    // Mesajı oluştur
    $message = "0x" . hash('sha3-256', 
        pack('H*', 
            str_pad(substr($user, 2), 64, '0', STR_PAD_LEFT) .
            str_pad(dechex($nftId), 64, '0', STR_PAD_LEFT) .
            str_pad(dechex($price), 64, '0', STR_PAD_LEFT) .
            str_pad(dechex($discountAmount), 64, '0', STR_PAD_LEFT)
        )
    );

    // OpenSSL ile imzalama
    $signature = '';
    $binaryPrivateKey = hex2bin($privateKey);
    openssl_sign(hex2bin(substr($message, 2)), $signature, $binaryPrivateKey, OPENSSL_ALGO_SHA256);

    $r = bin2hex(substr($signature, 0, 32));
    $s = bin2hex(substr($signature, 32, 32));
    $v = '1b'; // Ethereum v değeri: 27 (0x1b)

    $fullSignature = "0x{$r}{$s}{$v}";

    return [
        'message' => $message,
        'signature' => $fullSignature
    ];
}

// Örnek kullanım
$user = '0xUserAddress';
$nftId = 1;
$price = '0x' . dechex(100000000000000000); // 0.1 ETH (18 ondalık basamak)
$discountAmount = '0x' . dechex(10000000000000000); // 0.01 ETH (18 ondalık basamak)

$result = signMintData($user, $nftId, $price, $discountAmount, $privateKey);

echo "Message Hash: " . $result['message'] . PHP_EOL;
echo "Signature: " . $result['signature'] . PHP_EOL;
?>
