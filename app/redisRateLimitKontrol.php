<?php

$ayarlar = require_once('/var/www/test.astrapol.com/app/config/ayarlar.php');

function istekTekrarLimitiAsildiMi($chainId, $walletAddress)
{
    global $redis;

    $redisRateLimitSuresi = $ayarlar['baglantiAyarlari']['redisleRateLimitKontroluKacSaniye'];

    // Benzersiz Redis anahtarı oluştur
    $redisKey = "rate_limit:$chainId:$walletAddress";
    $currentTime = time();

    // Redis anahtarını kontrol et
    if ($redis->exists($redisKey)) 
    {
        return true; // Rate limit aşıldı
    }

    // Anahtarı oluştur ve süre sonunda otomatik sil
    $redis->setex($redisKey, $redisRateLimitSuresi, $currentTime);
    return false;
}

try 
{
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379); // Redis sunucu bağlantısı
} 
catch (Exception $hataMesaji) 
{
    http_response_code(500);

    file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
    , date('Y-m-d H:i:s') . PHP_EOL
    . "Dosya = " . __FILE__ . PHP_EOL
    . "Hata Mesajı = " . $hataMesaji->getMessage() . PHP_EOL
    . "Satır = " . $hataMesaji->getLine() . PHP_EOL
    . "Hata Kodu = " . $hataMesaji->getCode() . PHP_EOL
    . "Cüzdan Adresi = " . $userWallet . PHP_EOL
    . PHP_EOL
    , FILE_APPEND);

    echo json_encode([
        "mesajKodu" => "x",
        "mesajAciklamasi" => "Genel hata, lütfen teknik ekibi haberdar edin"
    ]);

    exit;
}