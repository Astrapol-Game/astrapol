<?php
    require_once __DIR__ . '/paketler/vendor/autoload.php';
    use kornrunner\Keccak;

    // Prefixed Hash Hesaplama
    function calculatePrefixedHash($message) {
        // Ethereum imza prefiksi
        $prefix = "\x19Ethereum Signed Message:\n32";

        // Mesajın hex formatını binary formatına çevir (0x önekini kaldır)
        $binaryMessage = hex2bin(substr($message, 2));

        // Prefiks ve binary mesajı birleştir
        $prefixedMessage = $prefix . $binaryMessage;

        // Prefikslı mesajın Keccak256 hash'ini oluştur
        $prefixedHash = "0x" . Keccak::hash($prefixedMessage, 256);

        return $prefixedHash;
    }

    // Terminalden Mesaj Girişi
    echo "Lütfen mesaj hash'ini (0x ile başlayan) girin: ";
    $inputMessage = trim(fgets(STDIN));

    // Giriş Doğrulama
    if (substr($inputMessage, 0, 2) !== "0x" || strlen($inputMessage) !== 66) {
        echo "Hata: Geçerli bir mesaj hash'i giriniz (0x ile başlamalı ve 32 byte uzunluğunda olmalı).\n";
        exit(1);
    }

    // Prefixed Hash Hesaplama
    $prefixedHash = calculatePrefixedHash($inputMessage);

    // Sonucu Yazdır
    echo "Message: " . $inputMessage . PHP_EOL;
    echo "Prefixed Hash: " . $prefixedHash . PHP_EOL;