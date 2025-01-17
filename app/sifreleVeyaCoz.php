<?php

date_default_timezone_set('Europe/Istanbul');

$iz = debug_backtrace();
$calisanDosya = isset($iz[1]['file']) ? $iz[1]['file'] : 'Bilinmeyen dosya';
$calisanSatir = isset($iz[1]['line']) ? $iz[1]['line'] : 'Bilinmeyen satır';

function chainIdVeUserAdresiSifrele($zincirKodu, $userWallet, $exNonce, $key)
{
    try 
    {
        // Rastgele bir nonce (IV) oluştur
        $nonce = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // Şifrelenecek veriyi birleştir
        $plaintext = json_encode(['data1' => $zincirKodu, 'data2' => $userWallet, 'data3' => $exNonce]);

        // Veriyi şifrele
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $nonce);

        $sifreliVeri = base64_encode($nonce . $ciphertext);

        return[
            "mesajKodu" => "26",
            "mesajAciklamasi" => "Veri şifreleme başarılı",
            "sonuc" => true,
            "sifreliVeri" => $sifreliVeri
        ];
    } 
    catch (Exception $hataMesaji) 
    {
        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
        , date('Y-m-d H:i:s') . PHP_EOL
        . "Dosya = " . $calisanDosya . PHP_EOL
        . "Satır = " . $calisanSatir . PHP_EOL
        . "Hata Mesajı = " . $hataMesaji->getMessage() . PHP_EOL
        . "Satır = " . $hataMesaji->getLine() . PHP_EOL
        . "Hata Kodu = " . $hataMesaji->getCode() . PHP_EOL
        . "Chain Id = " . $zincirKodu . PHP_EOL
        . "Cüzdan Adresi = " . $userWallet . PHP_EOL
        . PHP_EOL
        , FILE_APPEND);

        return[
            "mesajKodu" => "27",
            "mesajAciklamasi" => "Veri şifreleme başarısız",
            "sonuc" => false
        ];
    }
}

function chainIdVeUserAdresiSifreCoz($sifreliVeri, $key)
{
    try 
    {
        // Şifreli veriyi çöz
        $data = base64_decode($sifreliVeri);

        // Nonce uzunluğunu belirle
        $nonceLength = openssl_cipher_iv_length('aes-256-cbc');
        $nonce = substr($data, 0, $nonceLength);
        $ciphertext = substr($data, $nonceLength);

        // Veriyi çöz
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $nonce);

        $sifresizVeri = json_decode($plaintext, true); // Veriyi asıl formata geri döndür

        return[
            "mesajKodu" => "28",
            "mesajAciklamasi" => "Veri çözme başarılı",
            "sonuc" => true,
            "sifresizVeri" => $sifresizVeri
        ];
    } 
    catch (Exception $hataMesaji) 
    {
        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
        , date('Y-m-d H:i:s') . PHP_EOL
        . "Dosya = " . $calisanDosya . PHP_EOL
        . "Satır = " . $calisanSatir . PHP_EOL
        . "Hata Mesajı = " . $hataMesaji->getMessage() . PHP_EOL
        . "Satır = " . $hataMesaji->getLine() . PHP_EOL
        . "Hata Kodu = " . $hataMesaji->getCode() . PHP_EOL
        . "Chain Id = " . $zincirKodu . PHP_EOL
        . "Cüzdan Adresi = " . $userWallet . PHP_EOL
        . PHP_EOL
        , FILE_APPEND);

        return[
            "mesajKodu" => "29",
            "mesajAciklamasi" => "Veri çözme başarısız",
            "sonuc" => false
        ];
    }
}