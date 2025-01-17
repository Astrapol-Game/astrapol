<?php

date_default_timezone_set('Europe/Istanbul');

$iz = debug_backtrace();
$calisanDosya = isset($iz[1]['file']) ? $iz[1]['file'] : 'Bilinmeyen dosya';
$calisanSatir = isset($iz[1]['line']) ? $iz[1]['line'] : 'Bilinmeyen satır';

function anahtarUret()
{
    try 
    {
        $yeniAnahtar = bin2hex(random_bytes(32));

        return[
            "mesajKodu" => "23",
            "mesajAciklamasi" => "JWT token için yeni anahtar üretimi başarılı",
            "sonuc" => true,
            "anahtar" => $yeniAnahtar
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
            "mesajKodu" => "24",
            "mesajAciklamasi" => "JWT token için yeni anahtar üretimi başarısız",
            "sonuc" => false
        ];
    }
}

function baglantiGecerliMi($kmt, $zincirKodu, $userWallet, $token)
{
    // baglantiOlustur.php ve baglantiOnayla.php dosyaları bu php dosyasına ihtiyaç duymadan kontrollerini kendi içlerinde yaparlar
    // bağlantı ilk defa kuruluyorsa veya yenilenmesi gerekiyorsa diye bu şekilde yapıldı, bu php ise mekanikleri kullanmak isteyen
    // işlemler yapılırken kullanılır, örneğin kayıtlı dak listesini getirirken, böylece eğer bağlantıda sorun olursa
    // herşeyi en temel düzeyde baştan başlatan bağlantı php lerine yönlendirme yapabildik, daha gelişmiş bir kod olana kadar
    // bu tasarıma güvenildi, bu tasarım sayesinde sunucuda ki bağlantı oluşturma ve onaylama dosyalarının aşırı kullanımına engel olmak istedim

    // x = işlemi devam ettirme, hatalara bakılmalı
    // 1 = işlemi devam ettirme, hatalara bakılmalı
    // 9 = kullanıcıya sormadan yeniden cüzdan bağlama işlemi başlatılabilir
    // 10 = kullanıcıya sormadan yeniden cüzdan bağlama işlemi başlatılabilir
    // 11 = işlemi devam ettirme, hatalara bakılmalı
    // 12 = işlemi devam ettirme, hatalara bakılmalı
    // 14 = bağlantı hala geçerli, işlemlere devam edilebilir

    try 
    {
        // kullanıcı bir işlem yapmak istedi, veritabanında bağlantısı olup olmadığına bakalım
        $select = $kmt->prepare("
            SELECT 
                chain_name,
                ip_address,
                user_agent,
                jwt_key
            FROM connections
            WHERE
                chain_id = :chain_id
                AND wallet_address = :wallet_address");
        $select->execute([
            ':chain_id' => $zincirKodu,
            ':wallet_address' => $userWallet
        ]);

        $dbConnections = $select->fetchAll(PDO::FETCH_ASSOC);
        $baglantiSayisi = count($dbConnections);

        if($baglantiSayisi > 1)
        {
            // aynı zincir kodu ve cüzdan adresi ile veritabanında birden fazla kayıt var, 
            // algoritmalarda buna izin veren bir yapı olmadığından tüm bağlantılar iptal edilmeli
            // bu durum kayıt edilmeli ve kullanıcı uyarılmalı

            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') . PHP_EOL
            . "Dosya = " . $calisanDosya . PHP_EOL
            . "Satır = " . $calisanSatir . PHP_EOL
            . "Hata Mesajı = Birden fazla bağlantı var" . PHP_EOL
            . "Chain Id = " . $zincirKodu . PHP_EOL
            . "Cüzdan Adresi = " . $userWallet . PHP_EOL
            . "JWT Token = " . $token . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);

            return[
                "mesajKodu" => "12",
                "mesajAciklamasi" => "Birden fazla aktif bağlantınız var. Lütfen teknik ekibi haberdar ediniz",
                "sonuc" => false
            ];
        }
        else if($baglantiSayisi == 1)
        {
            // kullanıcıya özel anahtarı getir, sorgu fetchall ile yapılmış olsa bile satır sayısı 1 olduğundan 0. index okunabilir
            $gizliAnahtar = $dbConnections[0]['jwt_key'];
            if(empty($gizliAnahtar))
            {
                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "Dosya = " . __FILE__ . PHP_EOL
                . "Hata Mesajı = if(empty(gizliAnahtar)) true, veritabanında key değeri boş" . PHP_EOL
                . "Chain Id = " . $zincirKodu . PHP_EOL
                . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                . "JWT Token = " . $token . PHP_EOL
                . "JWT Key = " . $gizliAnahtar . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                return[
                    "mesajKodu" => "11",
                    "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin",
                    "sonuc" => false
                ];
            }

            // Token'ı parçala
            $token_parts = explode('.', $token);
            if (count($token_parts) !== 3)
            {
                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "Dosya = " . $calisanDosya . PHP_EOL
                . "Satır = " . $calisanSatir . PHP_EOL
                . "Hata Mesajı = if (count(token_parts) !== 3) true, geçersiz token formatı" . PHP_EOL
                . "Chain Id = " . $zincirKodu . PHP_EOL
                . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                . "JWT Token = " . $token . PHP_EOL
                . "JWT Key = " . $gizliAnahtar . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                return[
                    "mesajKodu" => "11",
                    "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin",
                    "sonuc" => false
                ];
            }

            // Parçaları ayır
            list($header, $payload, $signature) = $token_parts;

            // Signature doğrulaması
            $valid_signature = base64_encode(hash_hmac('sha256', "$header.$payload", $gizliAnahtar, true));
            if ($signature !== $valid_signature) 
            {
                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "Dosya = " . $calisanDosya . PHP_EOL
                . "Satır = " . $calisanSatir . PHP_EOL
                . "Hata Mesajı = if (signature !== valid_signature) true, token doğrulama başarısız" . PHP_EOL
                . "Chain Id = " . $zincirKodu . PHP_EOL
                . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                . "JWT Token = " . $token . PHP_EOL
                . "JWT Key = " . $gizliAnahtar . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                return[
                    "mesajKodu" => "11",
                    "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin",
                    "sonuc" => false
                ];
            }

            // Payload'u çöz
            $decoded_payload = json_decode(base64_decode($payload), true);
            if (!$decoded_payload) 
            {
                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "Dosya = " . $calisanDosya . PHP_EOL
                . "Satır = " . $calisanSatir . PHP_EOL
                . "Hata Mesajı = if (!decoded_payload) true, payload çözülemedi" . PHP_EOL
                . "Chain Id = " . $zincirKodu . PHP_EOL
                . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                . "JWT Token = " . $token . PHP_EOL
                . "JWT Key = " . $gizliAnahtar . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                return[
                    "mesajKodu" => "11",
                    "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin",
                    "sonuc" => false
                ];
            }

            // bağlantı süresini kontrol et
            if ($decoded_payload['exp'] < time())
            {
                return[
                    "mesajKodu" => "9",
                    "mesajAciklamasi" => "Token geçerlilik süresi doldu",
                    "sonuc" => false
                ];
            }
            else
            {
                return[
                    "mesajKodu" => "14",
                    "mesajAciklamasi" => "Bağlantı hala geçerli",
                    "sonuc" => true
                ];
            }
        }
        else
        {
            return[
                "mesajKodu" => "10",
                "mesajAciklamasi" => "Geçerli bir bağlantı yok",
                "sonuc" => false
            ];
        }
    } 
    catch (PDOException $pdoHataMesaji) 
    {
        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
        , date('Y-m-d H:i:s') . PHP_EOL
        . "Dosya = " . $calisanDosya . PHP_EOL
        . "Satır = " . $calisanSatir . PHP_EOL
        . "Hata Mesajı = " . $pdoHataMesaji->getMessage() . PHP_EOL
        . "Satır = " . $pdoHataMesaji->getLine() . PHP_EOL
        . "Hata Kodu = " . $pdoHataMesaji->getCode() . PHP_EOL
        . "Chain Id = " . $zincirKodu . PHP_EOL
        . "Cüzdan Adresi = " . $userWallet . PHP_EOL
        . PHP_EOL
        , FILE_APPEND);

        return[
            "mesajKodu" => "1",
            "mesajAciklamasi" => "Veritabanı bağlantı hatası, lütfen teknik ekibi haberdar edin",
            "sonuc" => false
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
            "mesajKodu" => "x",
            "mesajAciklamasi" => "Genel hata, lütfen teknik ekibi haberdar edin",
            "sonuc" => false
        ];
    }
}