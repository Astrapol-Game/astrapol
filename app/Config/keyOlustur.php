<?php
    // require_once __DIR__ . '/../../paketler/vendor/autoload.php';
    require_once '/var/www/test.astrapol.com/app/paketler/vendor/autoload.php';

    use phpseclib3\Crypt\EC;

    // 🚀 Anahtar Çifti Oluştur
    $key = EC::createKey('secp256k1');

    // 🔑 Özel ve Açık Anahtarları Al
    $privateKeyPem = $key->toString('PKCS8'); // Özel Anahtar
    $publicKeyPem = $key->getPublicKey()->toString('PKCS8'); // Açık Anahtar

    // 📄 Anahtarları Dosyaya Yazdır
    $file = fopen('keys.txt', 'w');
    if ($file) 
    {
        fwrite($file, "===== PRIVATE KEY =====\n");
        fwrite($file, $privateKeyPem . "\n\n");
        fwrite($file, "===== PUBLIC KEY =====\n");
        fwrite($file, $publicKeyPem . "\n\n");
        fclose($file);
        echo "✅ Anahtarlar 'keys.txt' dosyasına başarıyla yazdırıldı.\n";
    } 
    else 
    {
        echo "❌ Dosya oluşturulamadı veya yazılamadı.\n";
    }