<?php

    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);

    // bu php js tarafında dakMenusunuOlustur() kısmında çalışır, bağlantı kontrolleri o anda yapıldığından bu php içinde
    // bağlantı kontrolü gerek görülmemiştir
    require_once __DIR__ . '/paketler/vendor/autoload.php';
    require_once __DIR__ . '/baglantiKontrol.php';
    require_once __DIR__ . '/sifreleVeyaCoz.php';
    use kornrunner\Keccak;

    try 
    {
        date_default_timezone_set('Europe/Istanbul');
        $config = require_once('/var/www/test.astrapol.com/app/config/yeniEkipDb.php');
        $ayarlar = require_once('/var/www/test.astrapol.com/app/config/ayarlar.php');
        $kayitZamani = date('Y-m-d H:i:s');

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

        function checkSumFormatinaDonustur(string $address): string
        {
            // Adresi temizle (0x ile başlıyorsa kontrol et ve küçült)
            $address = strtolower(str_replace('0x', '', $address));

            // Keccak256 hash hesapla
            $hash = Keccak::hash($address, 256);

            $checksumAddress = '0x';

            // Her karakteri kontrol ederek checksum adres oluştur
            for ($i = 0; $i < strlen($address); $i++) 
            {
                if (intval($hash[$i], 16) > 7) 
                {
                    $checksumAddress .= strtoupper($address[$i]);
                } 
                else 
                {
                    $checksumAddress .= $address[$i];
                }
            }

            return $checksumAddress;
        }

        // kullanıcıdan gelen POST isteğini oku
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) 
        {
            // kullanıcıdan gelen veri JSON formatında olmayabilir, bunu kontrol et
            echo json_encode([
                "mesajKodu" => "3",
                "mesajAciklamasi" => "Geçersiz JSON formatı = " . json_last_error_msg()
            ]);

            exit;
        }

        $userWallet = $data['seciliCuzdanAdresi'] ?? '';
        $zincirKodu = $data['zincirKodu'] ?? '';
        $userAgent = $data['userAgent'] ?? '';
        $jwtToken = $_COOKIE['erisimJetonu'] ?? '';
        $ipAdresi = '';

        // sunucunun bağlantı isteğinde bulunan kullanıcının ip adresini öğrenmesini sağla
        if (empty($_SERVER['HTTP_CLIENT_IP']) == false) 
        {
            // $_SERVER['HTTP_CLIENT_IP'], PHP'de istemcinin IP adresini belirtmek için kullanılan özel bir başlıktır
            // Genellikle özel proxy sunucuları veya bazı ağ yapılandırmaları tarafından ayarlanır
            // Ancak bu başlık standart bir HTTP başlığı DEĞİLDİR ve çoğu sunucu yapılandırmasında mevcut olmayabilir
            // $_SERVER['REMOTE_ADDR'] değeri boş değil bu değeri al
            $ipAdresi = $_SERVER['HTTP_CLIENT_IP'];
        } 
        elseif (empty($_SERVER['HTTP_X_FORWARDED_FOR']) == false) 
        {
            // Eğer bir kullanıcı bir proxy sunucu veya CDN (ör. Cloudflare, AWS ELB) üzerinden sunucuya bağlanıyorsa, doğrudan istemcinin IP adresi $_SERVER['REMOTE_ADDR'] ile alınamaz
            // HTTP_X_FORWARDED_FOR değeri bir IP adresi listesi olarak döner, okunan en baştaki değer gerçek IP adresi kabul edilir
            // okunan ilk ip adresini al
            $ipAdresi = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } 
        else 
        {
            // $_SERVER['REMOTE_ADDR'], PHP'de istemcinin (kullanıcının) sunucuya yaptığı HTTP isteğini gerçekleştirdiği IP adresini döndürür
            // Bu IP adresi genellikle istemcinin gerçek IP adresi olur. Ancak bazı durumlarda proxy veya yük dengeleyici (Load Balancer) arkasından gelen IP adresi olabilir
            // $_SERVER['REMOTE_ADDR'] çıktı üretmezse son koşul olarak 0.0.0.0 değerini al
            $ipAdresi = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        if (empty($userWallet) || empty($zincirKodu) || empty($userAgent) || empty($jwtToken)) 
        {
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') . PHP_EOL
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Satır = " . __LINE__ . PHP_EOL
            . "Hata Mesajı = Parametrelerde hata var" . PHP_EOL
            . "Cüzdan Adresi = " . $userWallet . PHP_EOL
            . "Zincir Kodu = " . $zincirKodu . PHP_EOL
            . "User Agent = " . $userAgent . PHP_EOL
            . "JWT Token = " . $jwtToken . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);
        
            // Hata mesajını JSON formatında dön
            echo json_encode([
                "mesajKodu" => "3",
                "mesajAciklamasi" => "Eksik veya geçersiz parametreler"
            ]);
        
            exit; // Kodun devam etmesini engelle
        }

        // kullanıcının bağlantı kurduğu cüzdan adresini orjinal (checkSum formatı) haline getir
        $userWallet = checkSumFormatinaDonustur($userWallet);

        // redis yöntemi ile kullanıcının bu işlem için kaç saniye ayarlıysa, o süre içinde birden fazla istek yapıp yapmadığını kontrol et
        // if(istekTekrarLimitiAsildiMi($zincirKodu, $userWallet))
        // {
        //     http_response_code(429); // 429 Too Many Requests
        //     exit; // İstek sonlandırılır ve hiçbir şey döndürülmez
        // }

        // function baglantiGecerliMi($kmt, $zincirKodu, $userWallet, $token)
        $sonuc = baglantiGecerliMi($kmt, $zincirKodu, $userWallet, $jwtToken);
        if($sonuc['sonuc'] === false)
        {
            // bağlantı geçerli değil
            echo json_encode([
                "mesajKodu" => $sonuc['mesajKodu'],
                "mesajAciklamasi" => $sonuc['mesajAciklamasi']
            ]);

            exit;
        }

        // geçerli bir bağlantı var, süresi devam ediyor
        $select = $kmt->prepare("
            SELECT 
                image_url, dak_level, share_weight, share_weight_next
            FROM dak
            WHERE owner_address = :owner_address
            AND chain_id = :chain_id
            AND available = :available
            ORDER BY
                dak_level DESC
            LIMIT 1");

        $select->execute([
            ':owner_address' => $userWallet,
            ':chain_id' => $zincirKodu,
            ':available' => 'true'
        ]);
        
        $dbDakListesi = $select->fetchAll(PDO::FETCH_ASSOC);
        $dakSayisi = count($dbDakListesi);

        // aşağıdaki 1 ve 2 kısımları bu php nin resim dosyalarına ulaşmasını sağlar
        // sorgu ile getirilen dbDakListesi tekrar dolaşılır ve image_url sütunundaki değerin başına çalışan bir adres linki eklenir
        // bu yöntemin doğru olduğundan emin değilim, çalışmasını sağlamak için acele ile bunu bulabildim

        // 1-Base URL tanımla 'dakNftResimlerLinki' => 'https://test.astrapol.com/public/image/dak/'
        $resimDosyaLinki = $ayarlar['dosyaKonumAyarlari']['dakNftResimlerLinki'];

        // 2-Her bir sonucu tam URL ile güncelle, okunacak satır yoksa bu kısım çalışmayacaktır
        // $satir['image_url'] değeri nesnenin dosyaismi örneğin 5789.png (5789 nesne id si)
        foreach ($dbDakListesi as &$satir) 
        {
            if (!empty($satir['image_url'])) 
            {
                $satir['image_url'] = $resimDosyaLinki . $satir['image_url'];
            }
        }

        if($dakSayisi > 0)
        {
            // kullanıcıya ait en az bir adet DAK var, sorguda level değeri en yüksek üretilmiş en son DAK getirildi, js tarafında DAK bilgileri okunabilir
            echo json_encode([
                "mesajKodu" => "15",
                "mesajAciklamasi" => "Kullanıcıya ait en az bir tane DAK var, bilgileri yerleştirilebilir",
                "dbDakListesi" => $dbDakListesi
            ]);
        }
        else
        {
            // kullanıcıya ait DAK yok, yeni bir tane oluşturabilmesi için seçtiği ağa göre fiyatını görmesini sağla
            // seçtiği ağ bilgilerini ayarlar.php de ara ve ücret bilgilerini getir
            $oyunCuzdanAdresi = '';
            $tokenZincirAdi = '';
            $tokenSymbol = '';
            $tokenDegeri = 0.0;
            $tokenDegeriBulunduMu = false;
            $sifreliVeri = '';
            foreach ($ayarlar['ucretler']['yeniDak'] as $chainList => $siradakiChainId)
            {
                if($chainList !== $zincirKodu)
                {
                    continue;
                }
                else
                {
                    $tokenDegeriBulunduMu = true;
                    $tokenSymbol = $siradakiChainId['sembol'];
                    $tokenDegeri = $siradakiChainId['miktar'];
                    $oyunCuzdanAdresi = $siradakiChainId['adres'];
                    $tokenZincirAdi = $ayarlar['tokenZincirDetaylari'][$chainList]['adi'];
                    break;
                }
            }

            if($tokenDegeriBulunduMu === true)
            {
                $hataVarMi = true; // insert başarılı olursa false olacak
                $kacDefaDenensin = $ayarlar['baglantiAyarlari']['sqlBenzersizKodDenemesiKacDefaOlsun'];

                if($kmt->inTransaction() == false)
                {
                    $kmt->beginTransaction();
                }
                
                // DAK üretmeyi kabul ederse, bu işlemin önceden benzersiz bir kimliği olmalı
                // benzersiz (nonce) kimlik ile daha önce tabloda kayıt varsa tekrar denenmesi için döngü oluşturuldu
                for ($kacinci=1; $kacinci <= $kacDefaDenensin; $kacinci++) 
                { 
                    try
                    {
                        // kullanıcının kullandığı ağa göre ayarlar.php de ücret bulundu, istek şiflenmeli
                        $nonceNewPurchase = bin2hex(random_bytes(16));
                        $sonuc = chainIdVeUserAdresiSifrele($zincirKodu, $userWallet, $nonceNewPurchase, $ayarlar['sifrelemeAnahtarlari']['key1']);
                        if($sonuc['sonuc'] === false)
                        {
                            // şifreleme başarısız
                            echo json_encode([
                                "mesajKodu" => $sonuc['mesajKodu'],
                                "mesajAciklamasi" => $sonuc['mesajAciklamasi']
                            ]);

                            exit;
                        }

                        $sifreliVeri = $sonuc['sifreliVeri'];
                        
                        $insert = $kmt->prepare("INSERT INTO purchase (stock_id, stock_name, chain_id, chain_name, from_address, to_address, token_amount, token_symbol, ip_address, user_agent, nonce_token, tx_hash, network_status, created_at) 
                                        VALUES (:stock_id, :stock_name, :chain_id, :chain_name, :from_address, :to_address, :token_amount, :token_symbol, :ip_address, :user_agent, :nonce_token, :tx_hash, :network_status, :created_at)");

                        $insert->execute([
                            ':stock_id' => 0,
                            ':stock_name' => 'dak',
                            ':chain_id' => $zincirKodu,
                            ':chain_name' => $tokenZincirAdi,
                            ':from_address' => $userWallet,
                            ':to_address' => $oyunCuzdanAdresi,
                            ':token_amount' => $tokenDegeri,
                            ':token_symbol' => $tokenSymbol,
                            ':ip_address' => $ipAdresi,
                            ':user_agent' => $userAgent,
                            ':nonce_token' => $nonceNewPurchase,
                            ':tx_hash' => '',
                            ':network_status' => 'pending',
                            ':created_at' => $kayitZamani
                        ]);

                        $kmt->commit();
                        $hataVarMi = false;
                        break;
                    }
                    catch (PDOException $pdoHataMesaji) 
                    {
                        if($kmt->inTransaction() == false)
                        {
                            $kmt->rollBack();
                        }

                        if($kacinci < $kacDefaDenensin)
                        {
                            // deneme sayısı ayarlanan sayıya gelene kadar devam et
                            continue;
                        }

                        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                        , date('Y-m-d H:i:s') . PHP_EOL
                        . "Dosya = " . __FILE__ . PHP_EOL
                        . "Hata Mesajı = " . $pdoHataMesaji->getMessage() . PHP_EOL
                        . "Satır = " . $pdoHataMesaji->getLine() . PHP_EOL
                        . "Hata Kodu = " . $pdoHataMesaji->getCode() . PHP_EOL
                        . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                        . PHP_EOL
                        , FILE_APPEND);
                        
                        echo json_encode([
                            "mesajKodu" => "7",
                            "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
                        ]);
                    }
                }

                if($hataVarMi === true)
                {
                    // seçtiği ağa göre token değeri bulundu, işlem öncesi stok hareketi insert edilemedi
                    echo json_encode([
                        "mesajKodu" => "4",
                        "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
                    ]);

                    exit;
                }
                else
                {
                    // seçtiği ağa göre token değeri bulundu, işlem öncesi stok hareketi insert edildi, kullanıcıdan onay istenecek
                    echo json_encode([
                        "mesajKodu" => "17",
                        "mesajAciklamasi" => "DAK sahibi değilsiniz."
                        . " "
                        . $tokenDegeri
                        . " "
                        . $tokenSymbol
                        . " harcayarak bir tane oluşturabilirsiniz.",
                        "oyunCuzdanAdresi" => $oyunCuzdanAdresi,
                        "tokenDegeri" => $tokenDegeri,
                        "nonceNewPurchase" => $sifreliVeri
                    ]);

                    exit;
                }
            }
            else
            {
                // kullanıcının kullandığı ağa göre ayarlar.php de ücret yok
                echo json_encode([
                    "mesajKodu" => "18",
                    "mesajAciklamasi" => "DAK sahibi değilsiniz. Şuan da seçtiğiniz blockchain ağına uygun NFT kontratı yoktur. Lütfen başka bir ağ deneyin"
                ]);
            }
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
        . "Cüzdan Adresi = " . $userWallet . PHP_EOL
        . PHP_EOL
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
        . "Cüzdan Adresi = " . $userWallet . PHP_EOL
        . PHP_EOL
        , FILE_APPEND);

        echo json_encode([
            "mesajKodu" => "x",
            "mesajAciklamasi" => "Genel hata, lütfen teknik ekibi haberdar edin"
        ]);
    }
