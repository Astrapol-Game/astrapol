<?php

    require_once __DIR__ . '/paketler/vendor/autoload.php';
    require_once __DIR__ . '/baglantiKontrol.php';
    use kornrunner\Keccak;

    try 
    {
        date_default_timezone_set('Europe/Istanbul');
        $config = require_once('/var/www/test.astrapol.com/app/config/yeniEkipDb.php');
        $ayarlar = require_once('/var/www/test.astrapol.com/app/config/ayarlar.php');
        $kayitZamani = date('Y-m-d H:i:s');

        // PDO bağlantısı oluşturma
        $dsn = "mysql:
        host={$config['default']['server']};
        dbname={$config['default']['name']};
        charset=utf8mb4";

        $kmt = new PDO
        (
            $dsn,
            $config['default']['user'],
            $config['default']['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
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
                "mesajAciklamasi" => "Geçersiz JSON formatı"
            ]);

            exit;
        }

        $userWallet = $data['seciliCuzdanAdresi'] ?? '';
        $zincirKodu = $data['chainId'] ?? '';
        $userAgent = $data['userAgent'] ?? '';
        $jwtToken = $_COOKIE['erisimJetonu'] ?? '';        
        $ipAdresi = '';
        $imzaMesaji = $data['imzaMesaji'] ?? '';
        $imza = $data['imza'] ?? '';

        $zincirAdi = $ayarlar['tokenZincirDetaylari'][$zincirKodu]['adi'];

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

        // Bağlantı oluşturma sürecinden sonraki onaylama kısmı olduğundan JWT token değeri boş gelmemeli
        if (empty($userWallet) || empty($zincirKodu) || empty($imzaMesaji) || empty($imza) || empty($jwtToken) || empty($ipAdresi)) 
        {
            // değişkenlerdeki değerlerin mevcut olup olmadığını kontrol et, bunu 
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s')
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Satır = " . __LINE__ . PHP_EOL
            . "Hata Mesajı = Parametrelerde hata var" . PHP_EOL
            . "Cüzdan Adresi = " . $userWallet . PHP_EOL
            . "Chain Id = " . $zincirKodu . PHP_EOL
            . "İmzalanan Mesaj = " . $imzaMesaji . PHP_EOL
            . "İmza = " . $imza . PHP_EOL
            . "JWT Token = " . $jwtToken . PHP_EOL
            . "IP Adresi = " . $ipAdresi . PHP_EOL
            . PHP_EOL
            . FILE_APPEND);

            echo json_encode([
                "mesajKodu" => "3",
                "mesajAciklamasi" => "Eksik veya geçersiz parametreler"
            ]);

            exit;
        }

        // kullanıcının bağlantı kurduğu cüzdan adresini orjinal (checkSum formatı) haline getir
        $userWallet = checkSumFormatinaDonustur($userWallet);

        // rare limit kontrolüne burada gerek yok, bu php baglantiOlustur.php den sonra hemen çalıştırılır

        // Node.js scriptini çalıştır, sunucuda bir komut çalıştırırken her zaman escapeshellcmd kullanılmalıdır, bu güvenlik sağlar detaylar için GPT ye danış
        // bu komut node.js ve ethers.js kütüphanesini kullanarak cüzdandan POST edilen imza bilgilerin doğruluğunu kontrol eder
        $komutCalistir = escapeshellcmd("node /var/www/test.astrapol.com/app/imzaDogrulama/verifySignature.js '$imzaMesaji' '$imza' '$userWallet'");
        $komutSonucu = shell_exec($komutCalistir);

        if (trim($komutSonucu) == "false") 
        {
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') 
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Satır = " . __LINE__ . PHP_EOL
            . "Hata Mesajı = Node.js ile çalıştırılan komut sonucu = false" . PHP_EOL
            . "Cüzdan Adresi = " . $userWallet . PHP_EOL
            . "Chain Id = " . $zincirKodu . PHP_EOL
            . "shell_exec sonucu = " . $komutSonucu . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);

            echo json_encode([
                "mesajKodu" => "2",
                "mesajAciklamasi" => "İmza doğrulama başarısız"
            ]);
    
            exit;
        }

        // bağlantı isteği imzalandı, imza geçerli, veritabanından jwt key değeri alınarak jwt token kontrolü yapılsın
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
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Hata Mesajı = Bağlantı onaylama işlemindeyiz, bağlantı oluştur kısmında veritabanı kontrol edilmişti, kullanıcının Metamask ile imzalanması sağlandı, JWT token bağlantı süresi hala geçerli, arada geçen bu kısa sürede aynı bağlantıdan birden fazla olmaması gerekiyordu" . PHP_EOL
            . "Chain Id = " . $zincirKodu . PHP_EOL
            . "Cüzdan Adresi = " . $userWallet . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);

            echo json_encode([
                "mesajKodu" => "12",
                "mesajAciklamasi" => "Birden fazla aktif bağlantınız var. Lütfen teknik ekibi haberdar ediniz"
            ]);

            exit;
        }
        else if($baglantiSayisi == 1)
        {
            if ($jwtToken)
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
                    . "JWT Token = " . $jwtToken . PHP_EOL
                    . "JWT Key = " . $gizliAnahtar . PHP_EOL
                    . PHP_EOL
                    , FILE_APPEND);

                    echo json_encode([
                        "mesajKodu" => "11",
                        "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
                    ]);

                    exit;
                }

                // Token'ı parçala
                $token_parts = explode('.', $jwtToken);
                if (count($token_parts) !== 3)
                {
                    file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                    , date('Y-m-d H:i:s') . PHP_EOL
                    . "Dosya = " . __FILE__ . PHP_EOL
                    . "Hata Mesajı = if (count(token_parts) !== 3) true, geçersiz token formatı" . PHP_EOL
                    . "Chain Id = " . $zincirKodu . PHP_EOL
                    . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                    . "JWT Token = " . $jwtToken . PHP_EOL
                    . PHP_EOL
                    , FILE_APPEND);

                    echo json_encode([
                        "mesajKodu" => "11",
                        "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
                    ]);

                    exit;
                }

                // Parçaları ayır
                list($header, $payload, $signature) = $token_parts;

                // Signature doğrulaması
                $valid_signature = base64_encode(hash_hmac('sha256', "$header.$payload", $gizliAnahtar, true));
                if ($signature !== $valid_signature) 
                {
                    file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                    , date('Y-m-d H:i:s') . PHP_EOL
                    . "Dosya = " . __FILE__ . PHP_EOL
                    . "Hata Mesajı = if (signature !== valid_signature) true, token doğrulama başarısız" . PHP_EOL
                    . "Chain Id = " . $zincirKodu . PHP_EOL
                    . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                    . "JWT Token = " . $jwtToken . PHP_EOL
                    . PHP_EOL
                    , FILE_APPEND);

                    echo json_encode([
                        "mesajKodu" => "11",
                        "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
                    ]);

                    exit;
                }

                // Payload'u çöz
                $decoded_payload = json_decode(base64_decode($payload), true);
                if (!$decoded_payload) 
                {
                    file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                    , date('Y-m-d H:i:s') . PHP_EOL
                    . "Dosya = " . __FILE__ . PHP_EOL
                    . "Hata Mesajı = if (!decoded_payload) true, payload çözülemedi" . PHP_EOL
                    . "Chain Id = " . $zincirKodu . PHP_EOL
                    . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                    . "JWT Token = " . $jwtToken . PHP_EOL
                    . PHP_EOL
                    , FILE_APPEND);

                    echo json_encode([
                        "mesajKodu" => "11",
                        "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
                    ]);

                    exit;
                }

                // Bağlantı onay süresi kontrolü, baglantiOnayla.php de 'exp' değil kontrolü yapılmaz 'short_exp1' kontrolü yapılır
                // bu php sadece baglantiOlustur.php den hemen sonra çalıştığından 'short_exp1' kontrolü yeterlidir
                if ($decoded_payload['short_exp1'] < time())
                {
                    file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                    , date('Y-m-d H:i:s') . PHP_EOL
                    . "Dosya = " . __FILE__ . PHP_EOL
                    . "Hata Mesajı = Okunan jwt token formatımıza uygun değil veya bir süre belirtilmemiş, bağlantı oluştururken jwt token üretim sırasında ayarlardan getirilen jwt key değerinde sorun olmadığına göre ve şuanda hemen sonrasında çalışan bağlantı onaylama süresince olduğumuza göre kullanıcı tarafından manipüle edilmeye çalışılıyor olabilir" . PHP_EOL
                    . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                    . "Chain Id = " . $zincirKodu . PHP_EOL
                    . "IP Adresi = " . $ipAdresi . PHP_EOL
                    . "User Agent = " . $userAgent . PHP_EOL
                    . "JWT Token = " . $jwtToken . PHP_EOL
                    . PHP_EOL
                    , FILE_APPEND);

                    echo json_encode([
                        "mesajKodu" => "11",
                        "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
                    ]);
        
                    exit;
                }
                else
                {
                    // bağlantı oluştur dendi, JWT token bağlantı süresi hala geçerli, veritabanı tutarlı, bağlantıyı onayla
                    $update = $kmt->prepare("
                        UPDATE connections
                        SET
                            is_signed = :is_signed
                        WHERE
                            chain_id = :chain_id
                            AND wallet_address = :wallet_address");

                    $update->execute([
                        ':is_signed' => 1,
                        ':chain_id' => $zincirKodu,
                        ':wallet_address' => $userWallet
                    ]);

                    echo json_encode([
                        "mesajKodu" => "5",
                        "mesajAciklamasi" => "Bağlantı onaylandı",
                        "chain" => $zincirAdi
                    ]);

                    exit;
                }
            }
            else
            {
                // aslında bu kontrol yukarıda parametreler kontrol edilirken yapılmıştı, yinede yapıyı bozmak istedim
                // benzer bir uyarıyı burada da vermeliyiz

                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "Dosya = " . __FILE__ . PHP_EOL
                . "Hata Mesajı = Bağlantı onaylama işlemindeyiz, bağlantı oluştur kısmında veritabanı kontrol edilmişti, kullanıcının Metamask ile imzalanması sağlandı, JWT token bağlantı süresi hala geçerli, arada geçen bu kısa sürede oluşturulan bağlantı silindi mi, neden veritabanında tekrar bulunamadı" . PHP_EOL
                . "Chain Id = " . $zincirKodu . PHP_EOL
                . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                echo json_encode([
                    "mesajKodu" => "11",
                    "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
                ]);

                exit;
            }
        }
        else
        {
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') . PHP_EOL
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Hata Mesajı = Bağlantı onaylama işlemindeyiz, bağlantı oluştur kısmında veritabanı kontrol edilmişti, kullanıcının Metamask ile imzalanması sağlandı, JWT token bağlantı süresi hala geçerli, arada geçen bu kısa sürede oluşturulan bağlantı silindi mi, neden veritabanında tekrar bulunamadı" . PHP_EOL
            . "Chain Id = " . $zincirKodu . PHP_EOL
            . "Cüzdan Adresi = " . $userWallet . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);

            echo json_encode([
                "mesajKodu" => "11",
                "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
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