<?php

    // OpenSSL ile özel anahtar oluştur
    exec('openssl ecparam -name secp256k1 -genkey -noout -out private_key.pem');
    exec('openssl ec -in private_key.pem -pubout -out public_key.pem');

    // Özel anahtar ve public key'i oku
    $privateKey = file_get_contents('private_key.pem');
    $publicKeyPem = file_get_contents('public_key.pem');

    // Public key'i temizle (header ve footer'ları kaldır)
    $publicKey = str_replace(
        ["-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----", "\n", "\r"],
        '',
        $publicKeyPem
    );

    // Public key'i Base64'ten binary'e çevir
    $binaryPublicKey = base64_decode($publicKey);

    // X ve Y koordinatlarını birleştirerek Ethereum formatında public key oluştur
    $ethereumPublicKey = substr($binaryPublicKey, -64); // X ve Y koordinatları

    // SHA3 (keccak256) hash'i hesapla
    $hash = hash('sha3-256', $ethereumPublicKey, true);

    // Hash'in son 20 byte'ını alarak Ethereum adresini oluştur
    $ethereumAddress = "0x" . substr(bin2hex($hash), -40);

    // 📄 Anahtarları Dosyaya Yazdır
    $file = fopen('keys.txt', 'w');
    if ($file) 
    {
        fwrite($file, "===== PRIVATE KEY =====\n");
        fwrite($file, $privateKey . "\n\n");
        fwrite($file, "===== PUBLIC KEY =====\n");
        fwrite($file, $publicKey . "\n\n");
        fwrite($file, "===== Ethereum Address =====\n");
        fwrite($file, $ethereumAddress . "\n\n");
        fclose($file);
        echo "✅ Anahtarlar 'keys.txt' dosyasına başarıyla yazdırıldı.\n";
    } 
    else 
    {
        echo "❌ Dosya oluşturulamadı veya yazılamadı.\n";
    }