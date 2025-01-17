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

        $jwtToken = $_COOKIE['erisimJetonu'] ?? '';
        $userWallet = $data['seciliCuzdanAdresi'] ?? '';
        $zincirKodu = $data['chainId'] ?? '';
        $userAgent = $data['userAgent'] ?? '';
        
        $ipAdresi = '';
        $zincirAdi = $ayarlar['tokenZincirDetaylari'][$zincirKodu]['adi'];
        $maxSure = $ayarlar['baglantiAyarlari']['ilkBaglantiKacSaniyeGecerliOlsun'];
        $cuzdanOnaylamaSuresi = $ayarlar['baglantiAyarlari']['baglantiOnayindaJwtKeyGecerlilikSuresiKacSaniye'];

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

        // Bağlantı oluşturma sürecinin ilk kısmı olduğundan JWT token değeri boş gelebilir
        // bu nedenle bunu kontrollere eklemedik
        if (empty($userWallet) || empty($zincirKodu) || empty($userAgent) || empty($ipAdresi)) 
        {
            // değişkenlerdeki değerlerin mevcut olup olmadığını kontrol et
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') 
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Satır = " . __LINE__ . PHP_EOL
            . "Hata Mesajı = Parametrelerde hata var" . PHP_EOL
            . "Cüzdan Adresi = " . $userWallet . PHP_EOL
            . "Ağ Adı = " . $zincirKodu . PHP_EOL
            . "IP Adresi = " . $ipAdresi . PHP_EOL
            . "User Agent = " . $userAgent . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);

            echo json_encode([
                "mesajKodu" => "3",
                "mesajAciklamasi" => "Eksik veya geçersiz parametreler"
            ]);

            exit;
        }

        // kullanıcının bağlantı kurduğu cüzdan adresini orjinal (checkSum formatı) haline getir
        $userWallet = checkSumFormatinaDonustur($userWallet);

        // redis yöntemi ile kullanıcının bu işlem için kaç saniye ayarlıysa, o süre içinde birden fazla istek yapıp yapmadığını kontrol et
        // if(istekTekrarLimitiAsildiMi($zincirKodu, $userWallet))
        // {
        //     http_response_code(429); // 429 Too Many Requests
        //     exit; // İstek sonlandırılır ve hiçbir şey döndürülmez
        // }

        // yeni bir bağlantı oluşturmadan önce aynı zincir kodu ve cüzdan adresi ile geçerli bir bağlantısı olup olmadığına bak
        // kullanıcı tarafında bu php iki şekilde tetiklenir, eğer kendisi menüden bağla derse ve herhangi bir özellik kullanılırken
        // o özellik için sunucuda çalışan php dosyası bağlantı kontrolü sırasında bir yanlış veya eksik görürse
        // burada bu ayrıma göre tasarım yapılmadı, bu php her tetiklendiğinde önce mevcut geçerli bir bağlantı olup olmadığına bakılır
        // yoksa yeniden oluşturulması için gerekli adımlar gerçekleştirilir
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
            . "Hata Mesajı = Birden fazla aktif bağlantınız var. Lütfen teknik ekibi haberdar ediniz" . PHP_EOL
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
            if(!$jwtToken)
            {
                // şuanda bağlanmaya çalıştığı chain id ve cüzdan adresi ile veritabanında bir bağlantı olmasına rağmen
                // cookie de token değeri bulunamadı, sebebi hakkında detaylı kodlar sonra yazılabilir ancak
                // şuan da basitçe var olan bağlantısı silinsin ve yerine yeni bir token ve bağlantı oluşturulsun

                $gizliAnahtar = anahtarUret();
                if($gizliAnahtar['sonuc'] === false)
                {
                    echo json_encode([
                        "mesajKodu" => $gizliAnahtar['mesajKodu'],
                        "mesajAciklamasi" => $gizliAnahtar['mesajAciklamasi']
                    ]);

                    exit;
                }

                // Header (Base64 ile kodlanır)
                $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));

                // Payload (Base64 ile kodlanır)
                $payload = base64_encode(json_encode([
                    'sub' => $userWallet, // Kullanıcı kimliği veya cüzdan adresi
                    'iat' => time(), // Token oluşturulma zamanı
                    'exp' => time() + $maxSure, // Token geçerlilik süresi
                    'short_exp1' => time() + $cuzdanOnaylamaSuresi, // Cüzdan onaylama süresi
                    'jti' => uniqid() // Benzersiz token kimliği
                ]));

                // Signature (Header ve Payload birleştirilir ve imzalanır)
                $signature = base64_encode(hash_hmac('sha256', "$header.$payload", $gizliAnahtar['anahtar'], true));

                // JWT Token oluşturulur
                $yeniToken = "$header.$payload.$signature";

                // JWT token oluşturuldu
                $kmt->beginTransaction();

                try 
                {
                    // bağlantıyı sil
                    $delete = $kmt->prepare("
                                        DELETE FROM connections
                                        WHERE
                                            chain_id = :chain_id
                                            AND wallet_address = :wallet_address");

                    $delete->execute([
                        ':chain_id' => $zincirKodu,
                        ':wallet_address' => $userWallet
                    ]);

                    $insert = $kmt->prepare("INSERT INTO connections (chain_id, chain_name, wallet_address, ip_address, user_agent, jwt_key, created_at, is_signed) 
                        VALUES (:chain_id, :chain_name, :wallet_address, :ip_address, :user_agent, :jwt_key, :created_at, :is_signed)");

                    $insert->execute([
                            ':chain_id' => $zincirKodu,
                            ':chain_name' => $zincirAdi,
                            ':wallet_address' => $userWallet,
                            ':ip_address' => $ipAdresi,
                            ':user_agent' => $userAgent,
                            ':jwt_key' => $gizliAnahtar['anahtar'],
                            ':created_at' => $kayitZamani,
                            ':is_signed' => 0
                    ]);

                    $kmt->commit();
                    
                    setcookie('erisimJetonu', $yeniToken, [
                        'expires' => time() + $maxSure,
                        'path' => '/',
                        'httponly' => true,
                        'secure' => true,
                        'samesite' => 'Strict'
                    ]);

                    echo json_encode([
                        "mesajKodu" => "6",
                        "mesajAciklamasi" => "Yeni bağlantı oluşturma başarılı, imza bekleniyor",
                    ]);

                    exit;
                } 
                catch (PDOException $pdoHataMesaji) 
                {
                    // süresi dolan bağlantı silme ve yenisini oluşturma işleminde hata oluştu,
                    // bütün işlemleri geri al ve çık
                    $kmt->rollBack();

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

                    exit;
                }
            }
            else
            {
                // cookie de token değeri bulundu, kontrolleri yap
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
                    . "JWT Key = " . $gizliAnahtar . PHP_EOL
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
                    . "JWT Key = " . $gizliAnahtar . PHP_EOL
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
                    . "JWT Key = " . $gizliAnahtar . PHP_EOL
                    . PHP_EOL
                    , FILE_APPEND);

                    echo json_encode([
                        "mesajKodu" => "11",
                        "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibi haberdar edin"
                    ]);

                    exit;
                }

                // Bağlantı süresi kontrolü, baglantiOlustur.php de 'short_exp1' kontrolü yapılmaz
                // daha önce bir bağlantısı varsa kontolü yapıldığından 'exp' değeri dikkate alınır
                if ($decoded_payload['exp'] < time()) 
                {
                    // bağlantı süresi dolmuş, yeni bir token oluşmalı, var olan bağlantısı silinmeli,
                    // yeni bağlantı bilgileri insert edilmeli ve kullanıcının onayına sunulmalı

                    $gizliAnahtar = anahtarUret();
                    if($gizliAnahtar['sonuc'] === false)
                    {
                        echo json_encode([
                            "mesajKodu" => $gizliAnahtar['mesajKodu'],
                            "mesajAciklamasi" => $gizliAnahtar['mesajAciklamasi']
                        ]);

                        exit;
                    }

                    // Header (Base64 ile kodlanır)
                    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));

                    // Payload (Base64 ile kodlanır)
                    $payload = base64_encode(json_encode([
                        'sub' => $userWallet, // Kullanıcı kimliği veya cüzdan adresi
                        'iat' => time(), // Token oluşturulma zamanı
                        'exp' => time() + $maxSure, // Token geçerlilik süresi
                        'short_exp1' => time() + $cuzdanOnaylamaSuresi, // Cüzdan onaylama süresi
                        'jti' => uniqid() // Benzersiz token kimliği
                    ]));

                    // Signature (Header ve Payload birleştirilir ve imzalanır)
                    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", $gizliAnahtar['anahtar'], true));

                    // JWT Token oluşturulur
                    $yeniToken = "$header.$payload.$signature";

                    // JWT token oluşturuldu
                    $kmt->beginTransaction();

                    try 
                    {
                        // bağlantıyı sil
                        $delete = $kmt->prepare("
                                            DELETE FROM connections
                                            WHERE
                                                chain_id = :chain_id
                                                AND wallet_address = :wallet_address");

                        $delete->execute([
                            ':chain_id' => $zincirKodu,
                            ':wallet_address' => $userWallet
                        ]);

                        // yeni bağlantıyı kaydet
                        $insert = $kmt->prepare("INSERT INTO connections (chain_id, chain_name, wallet_address, ip_address, user_agent, jwt_key, created_at, is_signed) 
                            VALUES (:chain_id, :chain_name, :wallet_address, :ip_address, :user_agent, :jwt_key, :created_at, :is_signed)");

                        $insert->execute([
                                ':chain_id' => $zincirKodu,
                                ':chain_name' => $zincirAdi,
                                ':wallet_address' => $userWallet,
                                ':ip_address' => $ipAdresi,
                                ':user_agent' => $userAgent,
                                ':jwt_key' => $gizliAnahtar['anahtar'],
                                ':created_at' => $kayitZamani,
                                ':is_signed' => 0
                        ]);

                        $kmt->commit();
                        
                        setcookie('erisimJetonu', $yeniToken, [
                            'expires' => time() + $maxSure,
                            'path' => '/',
                            'httponly' => true,
                            'secure' => true,
                            'samesite' => 'Strict'
                        ]);

                        echo json_encode([
                            "mesajKodu" => "6",
                            "mesajAciklamasi" => "Yeni bağlantı oluşturma başarılı, imza bekleniyor",
                        ]);

                        exit;
                    }
                    catch (PDOException $pdoHataMesaji) 
                    {
                        // süresi dolan bağlantı silme ve yenisini oluşturma işleminde hata oluştu,
                        // bütün işlemleri geri al ve çık
                        $kmt->rollBack();

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

                        exit;
                    }
                }
                else
                {
                    // bağlantı süresi bitmemiş, mevcut token bilgileri ile kullanmaya devam edebilir
                    echo json_encode([
                        "mesajKodu" => "14",
                        "mesajAciklamasi" => "Bağlantı hala geçerli",
                        // bu noktada kontrol yapmadan zincir adı ayarlardan getirilebilir ama eğer yeni bir bağlantı oluşturmaya karar verilseydi
                        // yani if($baglantiSayisi == 0) olsaydı, o zaman önce seçtiği ağın ayarlarda kayıtlı olup olmadığına bakılacaktı
                        "chain" => $zincirAdi
                    ]);
        
                    exit;
                }
            }
        }
        else
        {
            // aynı zincir kodu ve cüzdan adresi ile veritabanında kayıtlı bir bağlantı yok, yeni bir bağlantı oluştur
            // önce yeni bir bağlantı oluşturmak için jwt token ayarla bunun için kullanıcıya özel bir gizli anahtar üretilsin
            $gizliAnahtar = anahtarUret();
            if($gizliAnahtar['sonuc'] === false)
            {
                echo json_encode([
                    "mesajKodu" => $gizliAnahtar['mesajKodu'],
                    "mesajAciklamasi" => $gizliAnahtar['mesajAciklamasi']
                ]);

                exit;
            }

            // Header (Base64 ile kodlanır)
            $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));

            // Payload (Base64 ile kodlanır)
            $payload = base64_encode(json_encode([
                'sub' => $userWallet, // Kullanıcı kimliği veya cüzdan adresi
                'iat' => time(), // Token oluşturulma zamanı
                'exp' => time() + $maxSure, // Token geçerlilik süresi
                'short_exp1' => time() + $cuzdanOnaylamaSuresi, // Cüzdan onaylama süresi
                'jti' => uniqid() // Benzersiz token kimliği
            ]));

            // Signature (Header ve Payload birleştirilir ve imzalanır)
            $signature = base64_encode(hash_hmac('sha256', "$header.$payload", $gizliAnahtar['anahtar'], true));

            // JWT Token oluşturulur
            $yeniToken = "$header.$payload.$signature";

            // JWT token oluşturuldu, tek bir insert işlemi olduğundan transaction, dolayısıylada ikinci bir try catch e gerek yok
            $insert = $kmt->prepare("INSERT INTO connections (chain_id, chain_name, wallet_address, ip_address, user_agent, jwt_key, created_at, is_signed) 
                VALUES (:chain_id, :chain_name, :wallet_address, :ip_address, :user_agent, :jwt_key, :created_at, :is_signed)");

            $insert->execute([
                    ':chain_id' => $zincirKodu,
                    ':chain_name' => $zincirAdi,
                    ':wallet_address' => $userWallet,
                    ':ip_address' => $ipAdresi,
                    ':user_agent' => $userAgent,
                    ':jwt_key' => $gizliAnahtar['anahtar'],
                    ':created_at' => $kayitZamani,
                    ':is_signed' => 0
            ]);
            
            setcookie('erisimJetonu', $yeniToken, [
                'expires' => time() + $maxSure, // Cookie süresi
                'path' => '/', // Tüm yollar için geçerli
                'httponly' => true, // JavaScript erişimi yasak
                'secure' => true, // HTTPS zorunlu
                'samesite' => 'Strict' // CSRF koruması
            ]);

            echo json_encode([
                "mesajKodu" => "6",
                "mesajAciklamasi" => "Yeni bağlantı oluşturma başarılı, imza bekleniyor",
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

    