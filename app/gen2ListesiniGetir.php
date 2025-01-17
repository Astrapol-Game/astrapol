<?php

    try 
    {
        // Veritabanı bağlantı ayarlarını yükle
        // $config = require_once('/var/www/beta.astrapol.com/app/Config/beta.astrapol.com/yeniEkipDb.php');
        // $config = require_once __DIR__ . '/../Config/beta.astrapol.com/yeniEkipDb.php';
        $config = require_once('/var/www/test.astrapol.com/app/config/yeniEkipDb.php');

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

        // kullanıcıdan gelen POST isteğini oku
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) 
        {
            // kullanıcıdan gelen veri JSON formatında olmayabilir, bunu kontrol et
            echo json_encode([
                "mesajKodu" => "3",
                "mesajAciklamasi" => "Geçersiz JSON formatı"
            ]);

            exit;
        }

        $seciliNesneler = $data['seciliNesne'] ?? [];
        $seciliNadirlikler = $data['seciliNadirlik'] ?? [];
        $cuzdanAdresi = $data['seciliCuzdanAdresi'] ?? '';

        if (empty($seciliNesneler) || empty($seciliNadirlikler) || empty($cuzdanAdresi)) 
        {
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt', 
                date('Y-m-d H:i:s') . ", gen2ListesiniGetir.php satir = " . __LINE__ 
                . ", Cüzdan Adresi = " . $cuzdanAdresi
                . ", Nadirlik = " . json_encode($seciliNadirlikler) // Diziyi JSON formatında logla
                . ", Nesne = " . json_encode($seciliNesneler) // Diziyi JSON formatında logla
                . PHP_EOL, 
                FILE_APPEND
            );
        
            // Hata mesajını JSON formatında dön
            echo json_encode([
                "mesajKodu" => "4",
                "mesajAciklamasi" => "Eksik veya geçersiz parametreler"
            ]);
        
            exit; // Kodun devam etmesini engelle
        }

        // Parametre dizilerini SQL'e uyumlu hale getir
        // js tarafında seçilmeyen her checkbox için 'default' değeri atamıştık, bütün 'default' ları kaldır çünkü
        // birden fazla aynı değerle in sorgusu yapmak mantıksız ve sql bunu yanlış anlayabilir
        $seciliNadirlikler = array_filter($seciliNadirlikler, function($value) 
        {
            return $value !== 'default';
        });
        if (empty($seciliNadirlikler)) 
        {
            // Eğer tüm değerler "default" ise, en az bir tane "default" ekle
            $seciliNadirlikler = ['default'];
        }
        $seciliNadirlikler = array_values($seciliNadirlikler); // İndeksleri sıfırla
        $nadirlikPlaceholders = implode(',', array_fill(0, count($seciliNadirlikler), '?'));

        $seciliNesneler = array_filter($seciliNesneler, function($value)
        {
            return $value !== 'default';
        });
        if(empty($seciliNesneler))
        {
            // Eğer tüm değerler "default" ise, en az bir tane "default" ekle
            $seciliNesneler = ['default'];
        }
        $seciliNesneler = array_values($seciliNesneler); // indeksleri sıfırla
        $nesnePlaceholders = implode(',', array_fill(0, count($seciliNesneler), '?'));

        // Hazırlanmış bir SQL sorgusu oluştur
        $sorgu = "
            SELECT 
                o.item_id, 
                o.name, 
                o.rarity, 
                o.image, 
                o.level,
                n.token1_degeri
            FROM 
                other_avaxtars o
            INNER JOIN 
                nesne_hisse_degerleri n
            ON 
                o.rarity = n.nadirlik AND o.level = n.seviye
            WHERE 
                o.rarity IN ($nadirlikPlaceholders) 
                AND o.owner = ?
        ";

        // Parametreleri birleştir ve sıraya dikkat et
        $parameters = array_merge($seciliNadirlikler, [$cuzdanAdresi]);

        // Sorguyu çalıştır
        $sdr = $kmt->prepare($sorgu);
        $sdr->execute($parameters);

        // Sonuçları al
        $veriler = $sdr->fetchAll(PDO::FETCH_ASSOC);

        // aşağıdaki 1 ve 2 kısımları bu php nin avaxtars.com içindeki gen2 resim dosyalarına ulaşmasını sağlar
        // sorgu ile getirilen veriler tekrar dolaşılır ve image sütunundaki değerin başına çalışan bir adres linki eklenir
        // bu yöntemin doğru olduğundan emin değilim, çalışmasını sağlamak için acele ile bunu bulabildim
        // aynı dosyaları kendi klasörümüze kopyalayarak yapmak istemedim çok yer kaplar diye yöntemleri internetten buldum
        // v3DosyaAdresi çalışmadı bende v3DosyaLinki ni kullandım

        // 1-Base URL tanımla
        $v3DosyaLinki = "https://avaxtars.com/static/content/";
        $v3DosyaAdresi = "/var/www/avaxtars.com/static/content/";

        // 2-Her bir sonucu tam URL ile güncelle
        foreach ($veriler as &$satir) 
        {
            if (!empty($satir['image'])) 
            {
                $satir['image'] = $v3DosyaLinki . $satir['image'];
            }
        }

        echo json_encode([
            "mesajKodu" => "15",
            "mesajAciklamasi" => "Sorgu başarılı",
            "veriler" => $veriler
        ]);
    }
    catch (PDOException $pdoHataMesaji) 
    {
        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt', date('Y-m-d H:i:s') . ", gen2ListesiniGetir.php içinde PDO hatası, " . $pdoHataMesaji->getMessage() . PHP_EOL, FILE_APPEND);

        echo json_encode([
            "mesajKodu" => "1",
            "mesajAciklamasi" => "Veritabanı bağlantı hatası, lütfen teknik ekibi haberdar edin"
        ]);

        exit;
    }
    catch (Exception $hataMesaji) 
    {
        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt', date('Y-m-d H:i:s') . ", gen2ListesiniGetir.php içinde Genel hata, " . $hataMesaji->getMessage() . PHP_EOL, FILE_APPEND);

        echo json_encode([
            "mesajKodu" => "x",
            "mesajAciklamasi" => "Genel hata, lütfen teknik ekibi haberdar edin"
        ]);
    }
