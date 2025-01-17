<?php

    try 
    {
        // Veritabanı bağlantı ayarlarını yükle
        // $config = require_once('/var/www/beta.astrapol.com/app/Config/beta.astrapol.com/yeniEkipDb.php');
        // $config = require_once __DIR__ . '/../Config/beta.astrapol.com/yeniEkipDb.php';
        date_default_timezone_set('Europe/Istanbul');
        $config = require_once('/var/www/test.astrapol.com/app/config/yeniEkipDb.php');
        $ayarlar = require_once('/var/www/test.astrapol.com/app/config/ayarlar.php');

        // PDO ile veritabanı bağlantısını oluştur
        $dsn = "mysql:
                    host={$config['default']['server']};
                    dbname={$config['default']['name']};
                    charset=utf8mb4";
        $kmt = new PDO(
                    $dsn, 
                    $config['default']['user'], 
                    $config['default']['password'], 
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Hata ayarları
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Sonuçları associative array olarak al
                    ]
        );

        $kmt->beginTransaction();

        try 
        {
            $kmt->prepare("DELETE FROM nesne_hisse_degerleri")->execute();

            foreach($ayarlar['nesneHisseDegerlemeAyarlari'] as $jetonlar)
            {
                // blockchain bilgilerini al
                $zincirKodu = $jetonlar['zincirKodu'];
                $tokenSymbol = $jetonlar['tokenSymbol'];

                // gen2 rare ayarları
                $katSayiOrani = $jetonlar['rareGen2']['katSayiOrani'];
                $tokenBaslangicDegeri = $jetonlar['rareGen2']['tokenBaslangicDegeri'];
                $levelBaslangicDegeri = $jetonlar['rareGen2']['levelBaslangicDegeri'];
                $tokenDegeri = $tokenBaslangicDegeri;

                $kmtSon = $kmt->prepare("INSERT INTO nesne_hisse_degerleri (nesne, nadirlik, seviye, token1_zincirkodu, token1_symbol, token1_degeri) 
                                VALUES (:nesne, :nadirlik, :seviye, :token1_zincirkodu, :token1_symbol, :token1_degeri)");

                for ($siradakiLevel = $levelBaslangicDegeri; $siradakiLevel <= 100; $siradakiLevel++) 
                { 
                    $kmtSon->execute([
                            ':nesne' => 'gen2',
                            ':nadirlik' => 'rare',
                            ':seviye' => $siradakiLevel,
                            ':token1_zincirkodu' => $zincirKodu,
                            ':token1_symbol' => $tokenSymbol,
                            ':token1_degeri' => $tokenDegeri
                    ]);

                    $tokenDegeri += ($tokenDegeri * $katSayiOrani / 100);
                }

                // gen2 epic ayarları
                $katSayiOrani = $jetonlar['epicGen2']['katSayiOrani'];
                $tokenBaslangicDegeri = $jetonlar['epicGen2']['tokenBaslangicDegeri'];
                $levelBaslangicDegeri = $jetonlar['epicGen2']['levelBaslangicDegeri'];
                $tokenDegeri = $tokenBaslangicDegeri;

                $kmtSon = $kmt->prepare("INSERT INTO nesne_hisse_degerleri (nesne, nadirlik, seviye, token1_zincirkodu, token1_symbol, token1_degeri) 
                                VALUES (:nesne, :nadirlik, :seviye, :token1_zincirkodu, :token1_symbol, :token1_degeri)");

                for ($siradakiLevel = $levelBaslangicDegeri; $siradakiLevel <= 100; $siradakiLevel++) 
                { 
                    $kmtSon->execute([
                            ':nesne' => 'gen2',
                            ':nadirlik' => 'epic',
                            ':seviye' => $siradakiLevel,
                            ':token1_zincirkodu' => $zincirKodu,
                            ':token1_symbol' => $tokenSymbol,
                            ':token1_degeri' => $tokenDegeri
                    ]);

                    $tokenDegeri += ($tokenDegeri * $katSayiOrani / 100);
                }

                // gen2 legend ayarları
                $katSayiOrani = $jetonlar['legendGen2']['katSayiOrani'];
                $tokenBaslangicDegeri = $jetonlar['legendGen2']['tokenBaslangicDegeri'];
                $levelBaslangicDegeri = $jetonlar['legendGen2']['levelBaslangicDegeri'];
                $tokenDegeri = $tokenBaslangicDegeri;

                $kmtSon = $kmt->prepare("INSERT INTO nesne_hisse_degerleri (nesne, nadirlik, seviye, token1_zincirkodu, token1_symbol, token1_degeri) 
                                VALUES (:nesne, :nadirlik, :seviye, :token1_zincirkodu, :token1_symbol, :token1_degeri)");

                for ($siradakiLevel = $levelBaslangicDegeri; $siradakiLevel <= 100; $siradakiLevel++) 
                { 
                    $kmtSon->execute([
                            ':nesne' => 'gen2',
                            ':nadirlik' => 'legend',
                            ':seviye' => $siradakiLevel,
                            ':token1_zincirkodu' => $zincirKodu,
                            ':token1_symbol' => $tokenSymbol,
                            ':token1_degeri' => $tokenDegeri
                    ]);

                    $tokenDegeri += ($tokenDegeri * $katSayiOrani / 100);
                }
            }

            $kmt->commit();

            echo json_encode([
                "mesajKodu" => "15",
                "mesajAciklamasi" => "Sorgu başarılı"
            ]);
        }
        catch (PDOException $pdoHataMesaji)
        {
            $kmt->rollBack();
            
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') . PHP_EOL
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Hata Mesajı = " . $pdoHataMesaji->getMessage() . PHP_EOL
            . "Satır = " . $pdoHataMesaji->getLine() . PHP_EOL
            . "Hata Kodu = " . $pdoHataMesaji->getCode() . PHP_EOL
            , FILE_APPEND);
            
            echo json_encode([
                "mesajKodu" => "16",
                "mesajAciklamasi" => "nesneHisseDegerAyar.php içinde transaction hatası, error_log.txt yi kontrol edin"
            ]);

            exit;
        }
    }
    catch (PDOException $pdoHataMesaji) 
    {
        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
        , date('Y-m-d H:i:s') . PHP_EOL
        . "Dosya = " . __FILE__ . PHP_EOL
        . "Hata Mesajı = " . $pdoHataMesaji->getMessage() . PHP_EOL
        . "Satır = " . $pdoHataMesaji->getLine() . PHP_EOL
        . "Hata Kodu = " . $pdoHataMesaji->getCode() . PHP_EOL
        , FILE_APPEND);

        echo json_encode([
            "mesajKodu" => "1",
            "mesajAciklamasi" => "Veritabanı bağlantı hatası, lütfen teknik ekibi haberdar edin"
        ]);
    }
    catch (Exception $hataMesaji) 
    {
        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
        , date('Y-m-d H:i:s') . PHP_EOL
        . "Dosya = " . __FILE__ . PHP_EOL
        . "Hata Mesajı = " . $hataMesaji->getMessage() . PHP_EOL
        . "Satır = " . $hataMesaji->getLine() . PHP_EOL
        . "Hata Kodu = " . $hataMesaji->getCode() . PHP_EOL
        , FILE_APPEND);

        echo json_encode([
            "mesajKodu" => "x",
            "mesajAciklamasi" => "Genel hata, lütfen teknik ekibi haberdar edin"
        ]);
    }
