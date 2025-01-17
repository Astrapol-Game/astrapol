<?php
    require_once __DIR__ . '/paketler/vendor/autoload.php';
    use kornrunner\Ethereum\Transaction;
    use kornrunner\Keccak;
    use phpseclib3\Crypt\EC;
    use phpseclib3\Crypt\Hash;
    
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

        function rpcRequest($rpcUrl, $method, $params) 
        {
            // cURL oturumu başlat
            $curl = curl_init($rpcUrl);

            // JSON verisini hazırlama
            $payload = json_encode([
                "jsonrpc" => "2.0",
                "method" => $method,
                "params" => $params,
                "id" => 1
            ]);

            // cURL ayarlarını yapılandır
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);

            // İsteği gönder ve yanıtı al
            $response = curl_exec($curl);

            // Hata kontrolü
            if (curl_errno($curl))
            {
                $curlHataMesaji = curl_error($curl); // Hata mesajını al
                curl_close($curl); // cURL oturumunu kapat
                throw new Exception("cURL Hatası: " . $curlHataMesaji); // Hata fırlat
            }

            // cURL oturumunu kapat
            curl_close($curl);

            // bu noktada metod hata vermeden işlemi bitirdi, yanıtı JSON dan diziye çevir ve fonksiyonu çağıran yapıya geri gönder
            return json_decode($response, true);
        }

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

        function calculateOffsets($staticCount, $dynamicParams) {
            $currentOffset = $staticCount * 32; // Tüm statik verilerin toplam uzunluğu
            $offsets = [];
        
            foreach ($dynamicParams as $param) {
                $offsets[] = $currentOffset; // Mevcut offset'i kaydet
                $currentOffset += ceil(strlen(bin2hex($param)) / 64) * 32; // Verinin uzunluğu kadar ilerlet
            }
        
            return $offsets;
        }

        function encodeABI($method_id, $address, $erc20Amount, $string, $premium) {
            // Statik Veriler
            $encodedAddress = str_pad(substr($address, 2), 64, '0', STR_PAD_LEFT);
            $encodedErc20Amount = str_pad(dechex($erc20Amount), 64, '0', STR_PAD_LEFT);
            $encodedPremium = str_pad(dechex($premium), 64, '0', STR_PAD_LEFT);
        
            // Dinamik Veri ve Offset
            $staticCount = 3; // 3 statik parametre
            $dynamicParams = [$string];
            $offsets = calculateOffsets($staticCount, $dynamicParams);
        
            $encodedString = str_pad(bin2hex($string), ceil(strlen($string) / 32) * 64, '0', STR_PAD_RIGHT);
            $encodedStringOffset = str_pad(dechex($offsets[0]), 64, '0', STR_PAD_LEFT);
            $encodedStringLength = str_pad(dechex(strlen($string)), 64, '0', STR_PAD_LEFT);
        
            // Final `data`
            $data = $method_id . // Method ID
                    $encodedAddress .
                    $encodedErc20Amount .
                    $encodedStringOffset .
                    $encodedPremium .
                    $encodedStringLength .
                    $encodedString;
        
            return $data;
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

        $txHash = $data['txHash'] ?? '';
        $userWallet = $data['from'] ?? '';
        $chainId = $data['chainId'] ?? '';
        $userNonceHex = $data['nonceHex'] ?? '';
        $userAgent = $data['userAgent'] ?? '';
        $ipAdresi = '';

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

        if (empty($txHash) || empty($userWallet) || empty($chainId) || empty($userNonceHex))
        {
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') . PHP_EOL
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Satır = " . __LINE__ . PHP_EOL
            . "Hata Mesajı = Parametrelerde hata var" . PHP_EOL
            . "TxHash = " . $txHash . PHP_EOL
            . "From = " . $userWallet . PHP_EOL
            . "To = " . $to . PHP_EOL
            . "Amount = " . $amount . PHP_EOL
            . "Chain Id = " . $chainId . PHP_EOL
            . "Data (nonce hex) = " . $userNonceHex . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);
        
            // Hata mesajını JSON formatında dön
            echo json_encode([
                "mesajKodu" => "3",
                "mesajAciklamasi" => "Eksik veya geçersiz parametreler"
            ]);
        
            exit; // Kodun devam etmesini engelle
        }

        $txHash = isset($data['txHash']) ? trim($data['txHash']) : '';
        $txHash = filter_var($txHash, FILTER_SANITIZE_STRING);

        // işlem şuanda gerçekleşiyor ama birazdan txHash değeri ağda bulunamayabilir ve işlem gerçekleşmiş olsa bile ağ onayı almadan
        // işlemlere devam edemeyiz ve bu nedenle txHash kontrolü sonraya kalabilir, sonra yapacağımız kontrollerde kolaylık olması için
        // en azından mümknüse txHash değeri önceden veritabanında güncellensin, güncellemede hata olsa bile devam etsin
        try 
        {
            $update = $kmt->prepare("UPDATE purchase 
                SET
                    tx_hash = :tx_hash
                WHERE 
                    from_address = :from_address
                    AND nonce_token = :nonce_token
                    AND chain_id = :chain_id");

            $update->execute([
                ':tx_hash' => $txHash,
                ':from_address' => $userWallet,
                ':nonce_token' => $userNonceHex,
                ':chain_id' => $chainId
            ]);
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
        }

        // kontrolleri yap
        $zincirKoduBulunduMu = false;
        $kacSaniyeBeklenecek = $ayarlar['rpcOkumaAyarlari'][$chainId]['rpcSorgusuHerDefasindaKacSaniyeBekleyecek'];
        $kacDefaDenenecek = $ayarlar['rpcOkumaAyarlari'][$chainId]['rpcSorgusuKacDefaYapilacak'];

        foreach ($ayarlar['rpcOkumaAdresleri'][$chainId] as $siradakiRpcLinki) 
        {
            $zincirKoduBulunduMu = true;
            $rpcOkumaBasariliMi = false;
            for ($kacDefaBakildi=1; $kacDefaBakildi <= $kacDefaDenenecek; $kacDefaBakildi++) 
            {
                $transaction = rpcRequest($siradakiRpcLinki, 'eth_getTransactionByHash', [$txHash]);
            
                if ($transaction && isset($transaction['result']))
                {
                    // $transaction değişkeni tanımlı ve NULL değil se
                    // isset($transaction['result']) 'result' anahtarı tanımlı ve NULL değil se
                    $valueWei = hexdec($transaction['result']['value']);
                    
                    // $valueWei büyük bir sayı olabilir. bcdiv ile 1e18 bölünerek hassasiyet korunur.
                    $txHashAmount = bcdiv($valueWei, bcpow('10', '18', 8), 8); // Wei → Token dönüşümü

                    // txhash ten okunan değer number_format kullanarak birazdan veritabanından getirilecek değer ile aynı hassasiyete getirilir.
                    $txHashAmountFormatted = number_format((float)$txHashAmount, 8, '.', '');

                    $agdaOkunanAdres = checkSumFormatinaDonustur($transaction['result']['to']);
                    $dtPurchase = '';

                    try 
                    {
                        $select = $kmt->prepare
                            ("SELECT 
                                id, chain_id, from_address, to_address, token_amount, token_symbol, nonce_token, tx_hash
                            FROM purchase
                            WHERE from_address = :from_address
                                AND nonce_token = :nonce_token
                                AND chain_id = :chain_id
                            ORDER BY id DESC");
                        $select->execute([
                            ':from_address' => $userWallet,
                            ':nonce_token' => $userNonceHex,
                            ':chain_id' => $chainId
                        ]);
                        
                        $dtPurchase = $select->fetchAll(PDO::FETCH_ASSOC);
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
                            "mesajKodu" => "4",
                            "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibe haber verin"
                        ]);

                        exit;
                    }

                    // try catch hata döndürmezse kodlar buradan devam eder, catch olursa hata raporlandıktan sonra exit ile çıkış yapılır
                    // bu php dosyası DAK oluştur dendikten hemen sonra çalıştı, bu noktaya gelmeden öncekileri hatırlayalım, sunucuda bu istek
                    // purchase tablosuna hex formatında nonce bilgisi ile kayıt edidi, bu nonce bilgisi kullanıcıya döndürüldü ve DAK üretilsin mi diye soruldu,
                    // kullanıcı tarafında en ufak bir maniplasyona yer bırakmamak için Metamask ta alınan onay içine bu nonce bilgisi yerleştirildi
                    // tekrar buraya gelirken kullanıcıdan zaten gerekli bilgileri POST ile aldık ancak herşeye rağmen bu noktaya kullanıcı tarafından gelen verilerde
                    // pekçok istenmeyen durum olabilme ihtimalin bilinemeyeceğinden bu bilgileri, veritabanında ilk oluşturulan kayıttan okumayı tercih ettim
                    // yani burada pekçok gereksiz kod oluştu, ileride daha derli toparlı bir sistem kurulabilir, istedim ki kullanıcı js kodlarını incelerse
                    // ödeme onayı için gerekli verilerin js tarafından geldiğini düşünsün
                    $islemSayisi = count($dtPurchase);
                    if($islemSayisi > 1)
                    {
                        // aynı nonce hex ile birden fazla alışveriş olması mümkün olmamalıydı
                        // bu önlem veritabanı sütun özelliğinde alınmıştı, kritik bir hata
                        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                        , date('Y-m-d H:i:s') . PHP_EOL
                        . "Dosya = " . __FILE__ . PHP_EOL
                        . "Satır = " . __LINE__ . PHP_EOL
                        . "Hata Mesajı = Aynı nonce hex ile birden fazla kayıt var, program algoritmalarında böyle bir izin yok, çok kritik bir hata" . PHP_EOL
                        . "TxHash = " . $txHash . PHP_EOL
                        . "From = " . $userWallet . PHP_EOL
                        . "To = " . $to . PHP_EOL
                        . "dbAmount = " . $amount . PHP_EOL
                        . "Chain Id = " . $chainId . PHP_EOL
                        . "Data (nonce hex) = " . $userNonceHex . PHP_EOL
                        . PHP_EOL
                        , FILE_APPEND);

                        echo json_encode([
                            "mesajKodu" => "7",
                            "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibe haber verin"
                        ]);

                        exit;
                    }
                    else if($islemSayisi == 0)
                    {
                        // zincir kodu + cüzdan adresi + imzalanan nonce hex değerine göre veritabanında işlem bulunamadı
                        // oysa ki bu php bir öncekinde insert olmadan çalışmaz, kritik hata
                        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                        , date('Y-m-d H:i:s') . PHP_EOL
                        . "Dosya = " . __FILE__ . PHP_EOL
                        . "Satır = " . __LINE__ . PHP_EOL
                        . "Hata Mesajı = Zincir kodu + cüzdan adresi + imzalanan nonce hex değerine göre veritabanında işlem bulunamadı, oysa ki bu php bir öncekinde insert olmadan çalışmaz" . PHP_EOL
                        . "TxHash = " . $txHash . PHP_EOL
                        . "From = " . $userWallet . PHP_EOL
                        . "To = " . $to . PHP_EOL
                        . "dbAmount = " . $amount . PHP_EOL
                        . "Chain Id = " . $chainId . PHP_EOL
                        . "Data (nonce hex) = " . $userNonceHex . PHP_EOL
                        . PHP_EOL
                        , FILE_APPEND);

                        echo json_encode([
                            "mesajKodu" => "7",
                            "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibe haber verin"
                        ]);

                        exit;
                    }

                    $dbToAddress = $dtPurchase[0]['to_address'];
                    $dbAmount = $dtPurchase[0]['token_amount'];
                    $dbNonceHex = $dtPurchase[0]['nonce_token'];
                    $dbTokenSymbol = $dtPurchase[0]['token_symbol'];

                    // veritabanından okunan değer number_format kullanarak txHash ten okunan değer ile aynı hassasiyete getirilir.
                    $dbAmountFormatted = number_format((float)$dbAmount, 8, '.', '');

                    // nonce hex bilgisini data kısmından oku
                    $txHashDataBilgisi = $transaction['result']['input'] ?? '';

                    // bu noktada zincir kodu + cüzdan adresi + nonce hex ile veritabanında sadece 1 kayıt var, tutarlı
                    // tx hash ağda bulundu, önce içindeki nonce hex kontrolü yapılsın
                    if($txHashDataBilgisi !== $dbNonceHex)
                    {
                        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                        , date('Y-m-d H:i:s') . PHP_EOL
                        . "Dosya = " . __FILE__ . PHP_EOL
                        . "Satır = " . __LINE__ . PHP_EOL
                        . "Hata Mesajı = Zincir kodu + cüzdan adresi + imzalanan nonce hex değerine göre veritabanında 1 kayıt var tutarlı ancak kullanıcıdan gelen txHash bilgisine göre ağdaki nonce hex ile veritabanındaki nonce hex uyumlu değil" . PHP_EOL
                        . "adres = " . $userWallet . PHP_EOL
                        . "txHash = " . $txHash . PHP_EOL
                        . "data (nonce hex) = ". $userNonceHex . PHP_EOL
                        . "txHash içindeki miktar = " . $txHashAmountFormatted . PHP_EOL
                        . "veri tabanındaki miktar = " . $dbAmountFormatted . PHP_EOL
                        . "txhash kontrolünde okunan transfer adresi = " . $agdaOkunanAdres . PHP_EOL
                        . "ayarlarda okunan hedef adres = " . $dbToAddress . PHP_EOL
                        . "txHash içindeki data (nonce hex) = " . $txHashDataBilgisi . PHP_EOL
                        . PHP_EOL
                        , FILE_APPEND);

                        echo json_encode([
                            "mesajKodu" => "21",
                            "mesajAciklamasi" => "İşlem doğrulanamadı, lütfen teknik ekibi haberdar edin"
                        ]);

                        exit;
                    }

                    if($agdaOkunanAdres === $dbToAddress)
                    {
                        // transfer adresi doğru
                        // bccomp: İki değeri belirli bir hassasiyetle karşılaştırır, 0 dönerse, iki değer eşittir.
                        if (bccomp($txHashAmountFormatted, $dbAmountFormatted, 8) === 0)
                        {
                            // transfer adresi doğru, miktar doğru, kullanıcıya imzalatmadan önce bir nonceHex üretilmişti ve tabloya kayıt edilmişti
                            // kullanıcı bu nonceHex bilgisini Metamask ta ödemeyi gerçekleştirirken data olarak eklemiştik, bu data bilgisi de doğru
                            // bu noktada zincir kodu + cüzdan adresi + nonce hex 1 tane var ve RPC okuma ile ağda onaylandığı doğrulandı

                            $dakLevel = 1;
                            $dakShareWeight = 1;
                            $dakShareNextWeight = 3;

                            $kmt->beginTransaction();

                            try 
                            {
                                // ödeme onayı ağda okundu, önce yeni bir DAK üretelim
                                $insert = $kmt->prepare("
                                    INSERT INTO dak (owner_address, chain_id, chain_symbol, image_url, dak_level, share_weight, share_weight_next, created_at, created_address, created_chain_id, created_chain_symbol, mintpay_txhash, mint_status, mint_txhash, last_updated_at, last_updated_address, last_updated_chain_id, last_updated_chain_symbol, available)
                                        VALUES (:owner_address, :chain_id, :chain_symbol, :image_url, :dak_level, :share_weight, :share_weight_next, :created_at, :created_address, :created_chain_id, :created_chain_symbol, :mintpay_txhash, :mint_status, :mint_txhash, :last_updated_at, :last_updated_address, :last_updated_chain_id, :last_updated_chain_symbol, :available)");

                                $insert->execute([
                                    ':owner_address' => $userWallet,
                                    ':chain_id' => $chainId,
                                    ':chain_symbol' => $dbTokenSymbol,
                                    ':image_url' => 'gen1Key1.png',
                                    ':dak_level' => $dakLevel,
                                    ':share_weight' => $dakShareWeight,
                                    ':share_weight_next' => $dakShareNextWeight,
                                    ':created_at' => $kayitZamani,
                                    ':created_address' => $userWallet,
                                    ':created_chain_id' => $chainId,
                                    ':created_chain_symbol' => $dbTokenSymbol,
                                    ':mintpay_txhash' => $txHash,
                                    ':mint_status' => 'pending',
                                    ':mint_txhash' => '',
                                    ':last_updated_at' => $kayitZamani,
                                    ':last_updated_address' => $userWallet,
                                    ':last_updated_chain_id' => $chainId,
                                    ':last_updated_chain_symbol' => $dbTokenSymbol,
                                    ':available' => 'false'
                                ]);

                                // üretilen yeni DAK ın id bilgisi getirilsin
                                $select = $kmt->prepare("
                                    SELECT 
                                        id
                                    FROM dak
                                    WHERE owner_address = :owner_address
                                        AND chain_id = :chain_id
                                        AND mintpay_txhash = :mintpay_txhash");
                                
                                $select->execute([
                                    ':owner_address' => $userWallet,
                                    ':chain_id' => $chainId,
                                    ':mintpay_txhash' => $txHash
                                ]);

                                $yeniDakSorguSonucu = $select->fetch(PDO::FETCH_ASSOC);
                                $yeniDakId = $yeniDakSorguSonucu['id'] ?? null;

                                // id bilgisi purchase tablosunda da güncellensin
                                $update = $kmt->prepare("
                                    UPDATE purchase 
                                    SET
                                        stock_id = :stock_id,
                                        tx_hash = :tx_hash,
                                        network_status = :network_status
                                    WHERE 
                                        from_address = :from_address
                                        AND nonce_token = :nonce_token
                                        AND chain_id = :chain_id");

                                $update->execute([
                                    ':stock_id' => $yeniDakId,
                                    ':tx_hash' => $txHash,
                                    ':network_status' => 'success',
                                    ':from_address' => $userWallet,
                                    ':nonce_token' => $userNonceHex,
                                    ':chain_id' => $chainId
                                ]);

                                $kmt->commit();

                                // hiç dak yoktu, yenisi oluşsun mu dendi, onaylandı, ödemesi yapıldı ve doğrulandı, NFT mint başlasın

                                // Sunucu Özel Anahtarı (Yeni oluşturulan özel anahtarı kullanın)
                                // $privateKeyPem = file_get_contents('/var/www/test.astrapol.com/app/config/private_key.pem');

                                // 📊 **Parametreler**
                                $tokenId = $yeniDakId; // Rastgele bir token ID
                                $level = 1; // Başlangıç seviyesi
                                $shareWeight = 1;
                                $shareNextWeight = 3;
                                $discount = 0; // İndirim
                                $priceDecimal = 0.1; // decimal
                                $priceWei = bcmul($priceDecimal, bcpow('10', '18')); // wei
                                $priceHex = '0x' . dechex($priceWei); // hex
                                $feeRecipient = '0x32EEC9F20F75442B3dC91279fF307E17c7A6D7E8'; // Ücretin gideceği adres
                                $contractAddress = '0x44d114f3c017d767c4fb22f983d42e443615f750'; // Akıllı kontrat adresi
                                $functionSelector = '0x08a8d7aa'; // kontratta çalışacak fonkisyonun id değeri
                                $metadataURI = "https://test.astrapol.com/public/metadata/{$tokenId}.json"; // Metadata URI

                                // 🚀 Metadata Dosyasını Oluştur
                                $metadata = [
                                    "name" => "Secure NFT #$tokenId",
                                    "description" => "An exclusive Secure NFT with level $level",
                                    "image" => "https://test.astrapol.com/public/images/dak/gen1Key1.png",
                                    "attributes" => [
                                        ["trait_type" => "Level", "value" => $level],
                                        ["trait_type" => "Share Weight", "value" => $shareWeight],
                                        ["trait_type" => "Sahre Next Weight", "value" => $shareNextWeight]
                                    ]
                                ];

                                // Metadata dosyasını kaydet
                                $metadataFile = "/var/www/test.astrapol.com/public/metadata/{$tokenId}.json";
                                file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
                                
                                // $encodedSignature = str_pad(bin2hex(base64_decode($encodedSignature)), 130, '0', STR_PAD_LEFT);

                                // data hazırla, metamask data: değerinde ilk başa fonksiyon id si gelmeli, sonrada parametreler
                                // function encodeABI($method_id, $address, $erc20Amount, $string, $premium)
                                $data = encodeABI($functionSelector, $userWallet, $tokenId, $metadataURI, $level);

                                // kullanıcıya verileri göndermeden önce hepsini dosyaya yazdır test amaçlı
                                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                                , date('Y-m-d H:i:s') . PHP_EOL
                                . "Dosya = " . __FILE__ . PHP_EOL
                                . "Satır = " . __LINE__ . PHP_EOL
                                . "data = " . $data . PHP_EOL
                                . PHP_EOL
                                , FILE_APPEND);

                                // parametreleri hash le
                                // $message = hash('sha256', $data . $contractAddress);

                                // hash özel anahtarla imzalansın
                                // $signature = '';
                                // if (!openssl_sign($message, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256))
                                // {
                                //     // transfer adresi doğru, miktar yanlış
                                //     file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                                //     , date('Y-m-d H:i:s') . PHP_EOL
                                //     . "Dosya = " . __FILE__ . PHP_EOL
                                //     . "Satır = " . __LINE__ . PHP_EOL
                                //     . "Hata Mesajı = Open SSL çalışmadı" . PHP_EOL
                                //     . "tokenId = " . $tokenId . PHP_EOL
                                //     . "level = " . $level . PHP_EOL
                                //     . "price = ". $priceWei . PHP_EOL
                                //     . "feeRecipient = " . $feeRecipient . PHP_EOL
                                //     . "contractAddress = " . $contractAddress . PHP_EOL
                                //     . "message = " . $message . PHP_EOL
                                //     . "signature = " . $signature . PHP_EOL
                                //     . PHP_EOL
                                //     , FILE_APPEND);
                                    
                                //     echo json_encode([
                                //         "mesajKodu" => "88",
                                //         "mesajAciklamasi" => "İşlem doğrulanamadı, lütfen teknik ekibi haberdar edin"
                                //     ]);

                                //     exit;
                                // }

                                // imzayı base64 formatına çevir
                                // $encodedSignature = base64_encode($signature);

                                // 📦 **JSON Yanıt Oluşturma**
                                echo json_encode([
                                    "mesajKodu" => "25",
                                    "mesajAciklamasi" => "işlem başarılı",
                                    "contractAddress" => $contractAddress,
                                    "userWallet" => $userWallet,
                                    // "signature" => $encodedSignature,
                                    "priceHex" => $priceHex,
                                    "data" => $data
                                ]);

                                exit;
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
                                . "Cüzdan Adresi = " . $userWallet . PHP_EOL
                                . PHP_EOL
                                , FILE_APPEND);
                                
                                echo json_encode([
                                    "mesajKodu" => "4",
                                    "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibe haber verin"
                                ]);

                                exit;
                            }
                        }
                        else
                        {
                            // transfer adresi doğru, miktar yanlış
                            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                            , date('Y-m-d H:i:s') . PHP_EOL
                            . "Dosya = " . __FILE__ . PHP_EOL
                            . "Satır = " . __LINE__ . PHP_EOL
                            . "Hata Mesajı = DAK bulunamadı, yeni birtane oluşsun dendi (bu noktada stock_movements tablosuna insert edilmiş olmalı), transfer adresi doğru, mikar yanlış" . PHP_EOL
                            . "adres = " . $userWallet . PHP_EOL
                            . "txHash = " . $txHash . PHP_EOL
                            . "data (nonce hex) = ". $userNonceHex . PHP_EOL
                            . "txHash içindeki miktar = " . $txHashAmountFormatted . PHP_EOL
                            . "Veri tabanındaki miktar = " . $dbAmountFormatted . PHP_EOL
                            . "txhash kontrolünde okunan transfer adresi = " . $agdaOkunanAdres . PHP_EOL
                            . "ayarlarda okunan hedef adres = " . $dbToAddress . PHP_EOL
                            . "txHash içindeki data (nonce hex) = " . $txHashDataBilgisi . PHP_EOL
                            . PHP_EOL
                            , FILE_APPEND);
                            
                            echo json_encode([
                                "mesajKodu" => "21",
                                "mesajAciklamasi" => "İşlem doğrulanamadı, lütfen teknik ekibi haberdar edin"
                            ]);

                            exit;
                        }
                    }
                    else
                    {
                        // transfer adresi yanlış
                        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                        , date('Y-m-d H:i:s') . PHP_EOL
                        . "Dosya = " . __FILE__ . PHP_EOL
                        . "Satır = " . __LINE__ . PHP_EOL
                        . "Hata Mesajı = DAK bulunamadı, yeni birtane oluşsun dendi (bu noktada stock_movements tablosuna insert edilmiş olmalı), transfer adresi yanlış" . PHP_EOL
                        . "adres = " . $userWallet . PHP_EOL
                        . "txHash = " . $txHash . PHP_EOL
                        . "data (nonce hex) = ". $userNonceHex . PHP_EOL
                        . "txHash içindeki miktar = " . $txHashAmountFormatted . PHP_EOL
                        . "veri tabanındaki miktar = " . $dbAmountFormatted . PHP_EOL
                        . "txhash kontrolünde okunan transfer adresi = " . $agdaOkunanAdres . PHP_EOL
                        . "ayarlarda okunan hedef adres = " . $dbToAddress . PHP_EOL
                        . "txHash içindeki data (nonce hex) = " . $txHashDataBilgisi . PHP_EOL
                        . PHP_EOL
                        , FILE_APPEND);
                        
                        echo json_encode([
                            "mesajKodu" => "21",
                            "mesajAciklamasi" => "İşlem doğrulanamadı, lütfen teknik ekibi haberdar edin"
                        ]);

                        exit;
                    }
                }
                else 
                {
                    // işlem for döngüsünde denendi ve bu turda bulunamadı
                    // $transaction değişkeni tanımlı değil veya NULL
                    // isset($transaction['result']) 'result' anahtarı tanımlı değil veya NULL
                    // yani $rpcOkumaBasariliMi hala false
                    // tekrar denemek için bekle ve dene
                    sleep($kacSaniyeBeklenecek);
                    continue;
                }
            }

            // for bitti ve exit olmadıysa tüm denemelere rağmen belirtilen ayarlarda rpc okuma yapılamadı
            // bu noktada kullanılan yöntem hata vermedğine göre yüksek ihtimalle ağ veya rpc listesindeki node yoğunluğu var
            // rpc ayarları değiştirilerek sorun çözülebilir
            if($rpcOkumaBasariliMi === false)
            {
                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "Dosya = " . __FILE__ . PHP_EOL
                . "Satır = " . __LINE__ . PHP_EOL
                . "Hata Mesajı = RPC kontrolünde if (transaction && isset(transaction['result'])) koşulu, " . $siradakiRpcLinki . " linki ile "
                . $kacDefaDenenecek . " defa denendi ve her defasında false döndü
                , blockcahin ağında bir yoğunluk veya listedeki node larda bir yoğunluk yada sorun olabilir
                , rpc ayarlarını değiştirerek sorunu çözmeye çalışabilirsiniz" . PHP_EOL
                . "TxHash = " . $txHash . PHP_EOL
                . "From = " . $userWallet . PHP_EOL
                . "To = " . $to . PHP_EOL
                . "Amount = " . $amount . PHP_EOL
                . "Chain Id = " . $chainId . PHP_EOL
                . "Data (nonce hex) = " . $userNonceHex . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                echo json_encode([
                    "mesajKodu" => "21",
                    "mesajAciklamasi" => "İşlem doğrulanamadı, lütfen teknik ekibi haberdar edin"
                ]);

                exit;
            }
        }

        if($zincirKoduBulunduMu === false)
        {
            // DAK için insert kaydı yapıldı ama İşlem onayı için kullanıcıdan gelen zincir kodu ayarlar.php de bulunamadı
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') . PHP_EOL
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Satır = " . __LINE__ . PHP_EOL
            . "Hata Mesajı = DAK bulunamadı, yeni birtane oluşsun dendi (bu noktada stock_movements tablosuna insert edilmiş olmalı), zincir kodu RPC link listesinde bulunamadı" . PHP_EOL
            . "adres = " . $userWallet . PHP_EOL
            . "txHash = " . $txHash . PHP_EOL
            . "zincir kodu = " . $chainId . PHP_EOL
            . "zincir adı = " . $ayarlar['tokenZincirDetaylari'][$chainId]['adi'] . PHP_EOL
            . "data (nonce hex) = ". $userNonceHex . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);
            
            echo json_encode([
                "mesajKodu" => "21",
                "mesajAciklamasi" => "İşlem doğrulanamadı, lütfen teknik ekibi haberdar edin"
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
