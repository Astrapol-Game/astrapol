<?php
    require_once __DIR__ . '/paketler/vendor/autoload.php';
    require_once __DIR__ . '/sifreleVeyaCoz.php';

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

        // kontrattaki string parametreler için offset denilen bir ayarlama yapılması gerekmektedir
        function calculateOffsets($staticCount, $dynamicParams) {
            $currentOffset = $staticCount * 32; // Tüm statik verilerin toplam uzunluğu
            $offsets = [];
        
            foreach ($dynamicParams as $param) {
                $offsets[] = $currentOffset; // Mevcut offset'i kaydet
                $currentOffset += ceil(strlen(bin2hex($param)) / 64) * 32; // Verinin uzunluğu kadar ilerlet
            }
        
            return $offsets;
        }

        // metamask data bilgisi için bu fonksiyon çok önemli, kontratın düzgün çalışabilmesi için 
        // sorunsuz bir data bilgisi oluşturuyor, parametrelerde string bir değerde olduğundan
        // function calculateOffsets($staticCount, $dynamicParams) de kullanıldı
        // buradaki kodların ram olarak nasıl çalıştığını anladım ancak sistemin ezberlenmesi gerekiyor 
        function encodeABI($method_id, $address, $erc20Amount, $string, $premium, $signature) {
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

            $signatureHex = substr($signature, 2); // "0x" ön ekini kaldır
            $encodedSignatureOffset = str_pad(dechex($offsets[1]), 64, '0', STR_PAD_LEFT);
            $encodedSignatureLength = str_pad(dechex(strlen($signatureHex) / 2), 64, '0', STR_PAD_LEFT); // Bayt cinsinden uzunluk
            $encodedSignature = str_pad($signatureHex, ceil(strlen($signatureHex) / 64) * 64, '0', STR_PAD_RIGHT);
        
            // Final `data`
            $data = $method_id . // Method ID
                    $encodedAddress .
                    $encodedErc20Amount .
                    $encodedStringOffset .
                    $encodedPremium .
                    $encodedStringLength .
                    $encodedString .
                    $encodedSignatureOffset .
                    $encodedSignatureLength .
                    $encodedSignature;
        
            return $data;
        }

        // createSignature2 sadece kontrat tarafında uri bilgisi ile hash edilen bir mesaj ve
        // bu mesajla oluşturulan imza uyumu nasıl oluru test etmek için yapılmıştı şaun da 
        // şifreleme işlemlerinde uri dahil değil ama ileride dahil edilecekse bu kısım yardımcı olacaktır
        function createSignature2($privateKey, $uri) {
            $utf8Uri = utf8_encode($uri);
            $message = "0x" . Keccak::hash($utf8Uri, 256);

            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "uri = " . $uri . PHP_EOL
                . "message = " . $message . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

            // Node.js komutunu hazırla ve çalıştır
            $command = escapeshellcmd("node /var/www/test.astrapol.com/app/imzaDogrulama/yeniDakMintSignature.js '{$message}' '{$privateKey}'");
            $signature = shell_exec($command);
            return trim($signature);
        }

        // kontrat içinde şifrelenen mesaj çözülürken bytes32 prefixedHash = keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", message));
        // satırının ürettiği değer ile aynı işi php yaparsa sonuç ne çıkarı test edebileceğimiz bir fonksiyon gerekiyordu
        // gerekli öğrenim sağlandı ve sorun çözüldü, ileride tekrar lazım olursa bu fonksiyon kullanılabilir
        function calculatePrefixedHash($to, $tokenId, $level) {
            // Solidity'deki message hash'i üret
            $message = "0x" . Keccak::hash(
                hex2bin(
                    implode('', [
                        str_pad(substr($to, 2), 64, "0", STR_PAD_LEFT),    // Address
                        str_pad(dechex($tokenId), 64, "0", STR_PAD_LEFT), // Token ID
                        str_pad(dechex($level), 64, "0", STR_PAD_LEFT)    // Level
                    ])
                ),
                256
            );
        
            // Prefiks ekle ve prefixed hash üret
            $prefix = "\x19Ethereum Signed Message:\n32";
            $encodedMessage = $prefix . hex2bin(substr($message, 2));
            $prefixedHash = "0x" . Keccak::hash($encodedMessage, 256);

            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "to = " . $to . PHP_EOL
                . "tokenId = " . $tokenId . PHP_EOL
                . "level = " . $level . PHP_EOL
                . "Message: " . $message . PHP_EOL
                . "Prefixed Message (binary): " . bin2hex($encodedMessage) . PHP_EOL
                . "Prefixed Hash: " . $prefixedHash . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);
        
            return $prefixedHash;
        }

        // kontratın public key ile çözeceği veriyi şifreleyen fonksiyondur, daha önce burada uri değişkeni de vardı, çok sorun çıkarınca kaldırıdım
        // aslında sorunu daha sonra çözdük ama eklemeyi gerekli görmedim
        function createSignature($privateKey, $to, $tokenId, $level) {
            $message = "0x" . Keccak::hash(
                hex2bin(
                    implode('', [
                        str_pad(substr($to, 2), 64, "0", STR_PAD_LEFT),
                        str_pad(dechex($tokenId), 64, "0", STR_PAD_LEFT),
                        str_pad(dechex($level), 64, "0", STR_PAD_LEFT)
                    ])
                ),
                256
            );

            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "to = " . $to . PHP_EOL
                . "tokenId = " . $tokenId . PHP_EOL
                . "level = " . $level . PHP_EOL
                . "message = " . $message . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

            // Node.js komutunu hazırla ve çalıştır
            $command = escapeshellcmd("node /var/www/test.astrapol.com/app/imzaDogrulama/yeniDakMintSignature.js '{$message}' '{$privateKey}'");
            $signature = shell_exec($command);
            return trim($signature);
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

        $userWallet = $data['from'] ?? '';
        $chainId = $data['chainId'] ?? '';
        $userNonceNewPurchase = $data['nonceNewPurchase'] ?? '';
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

        if (empty($userWallet) || empty($chainId) || empty($userNonceNewPurchase))
        {
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') . PHP_EOL
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Satır = " . __LINE__ . PHP_EOL
            . "Hata Mesajı = Parametrelerde hata var" . PHP_EOL
            . "userWallet = " . $userWallet . PHP_EOL
            . "chainId = " . $chainId . PHP_EOL
            . "userNonceNewPurchase = " . $userNonceNewPurchase . PHP_EOL
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
        $userWalletTest = checkSumFormatinaDonustur($userWallet);

        // zincir kodu hala listede var mı
        $zincirKoduBulunduMu = isset($ayarlar['ucretler']['yeniDak'][$chainId]);
        if($zincirKoduBulunduMu === false)
        {
            // DAK için insert kaydı yapıldı ama İşlem onayı için kullanıcıdan gelen zincir kodu ayarlar.php de bulunamadı
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') . PHP_EOL
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Satır = " . __LINE__ . PHP_EOL
            . "Hata Mesajı = DAK bulunamadı, yeni birtane oluşsun dendi (bu noktada purchase tablosuna insert edilmiş olmalı), zincir kodu ücretler listesinde bulunamadı" . PHP_EOL
            . "userWallet = " . $userWallet . PHP_EOL
            . "chainId = " . $chainId . PHP_EOL
            . "zincir adı = " . $ayarlar['tokenZincirDetaylari'][$chainId]['adi'] . PHP_EOL
            . "userNonceNewPurchase = ". $userNonceNewPurchase . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);
            
            echo json_encode([
                "mesajKodu" => "21",
                "mesajAciklamasi" => "İşlem doğrulanamadı, lütfen teknik ekibi haberdar edin"
            ]);

            exit;
        }
        else
        {
            // yeni bir dak üretme isteğinde hazırlıklar tamamlandıktan sonra kullanıcıdan onay almadan önce zincir kodu, cüzdan adresi, rastgele bir nonce
            // özel bir anahtarla şifrlenmişti, şifre kullanıcı diğer işlemleri yaparken js tarafında duruyordu, işlemler bittiğinde ve kullanıcı onay verdiğinde
            // bu şifre buraya post edildi, bu noktada çözülsün ve kullanıcıdan tekrar gelen veriler ile aynı olup olmadığı kontrol edilsin
            $sonuc = chainIdVeUserAdresiSifreCoz($userNonceNewPurchase, $ayarlar['sifrelemeAnahtarlari']['key1']);
            if($sonuc['sonuc'] === false)
            {
                // şifreleme başarısız
                echo json_encode([
                    "mesajKodu" => $sonuc['mesajKodu'],
                    "mesajAciklamasi" => $sonuc['mesajAciklamasi']
                ]);

                exit;
            }

            $sifresizVeri = $sonuc['sifresizVeri'];
            $sifresizChainId = $sifresizVeri['data1'] ?? null;
            $sifresizUserWallet = $sifresizVeri['data2'] ?? null;
            // eğer cüzdan adresi ve chain id doğruysa aşağıdaki select sorgusunda şifresi çözülmüş nonce ile tek bir satır gelmesi
            // nonce kontrolünü ede sağlamış olacak, hatırlayalım purchase tablosu aynı nonce değerinden iki tane olamayacak şekilde tasarlandı
            $sifresizNonceNewPurchase = $sifresizVeri['data3'] ?? null;
            if($sifresizChainId != $chainId)
            {
                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "Dosya = " . __FILE__ . PHP_EOL
                . "Satır = " . __LINE__ . PHP_EOL
                . "Hata Mesajı = DAK bulunamadı, yeni birtane oluşsun dendi (bu noktada purchase tablosuna insert edilmiş olmalı), yeni dak üretimi sırasında kayitliSonDakBilgisiniGetir.php ile yeniDakMintHazirlaBaslat.php işlemleri arasında chain id aynı değil" . PHP_EOL
                . "userWallet = " . $userWallet . PHP_EOL
                . "chainId = " . $chainId . PHP_EOL
                . "zincir adı = " . $ayarlar['tokenZincirDetaylari'][$chainId]['adi'] . PHP_EOL
                . "userNonceNewPurchase = ". $userNonceNewPurchase . PHP_EOL
                . "sifresizVeri = ". $sifresizVeri . PHP_EOL
                . "sifresizChainId = ". $sifresizChainId . PHP_EOL
                . "sifresizUserWallet = ". $sifresizUserWallet . PHP_EOL
                . "sifresizNonceNewPurchase = ". $sifresizNonceNewPurchase . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                echo json_encode([
                    "mesajKodu" => "30",
                    "mesajAciklamasi" => "Chain ID aynı değil"
                ]);

                exit;
            }
            
            if($sifresizUserWallet != $userWalletTest)
            {
                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "Dosya = " . __FILE__ . PHP_EOL
                . "Satır = " . __LINE__ . PHP_EOL
                . "Hata Mesajı = DAK bulunamadı, yeni birtane oluşsun dendi (bu noktada purchase tablosuna insert edilmiş olmalı), yeni dak üretimi sırasında kayitliSonDakBilgisiniGetir.php ile yeniDakMintHazirlaBaslat.php işlemleri arasında cüzdan adresi aynı değil" . PHP_EOL
                . "userWallet = " . $userWallet . PHP_EOL
                . "chainId = " . $chainId . PHP_EOL
                . "zincir adı = " . $ayarlar['tokenZincirDetaylari'][$chainId]['adi'] . PHP_EOL
                . "userNonceNewPurchase = ". $userNonceNewPurchase . PHP_EOL
                . "sifresizVeri = ". $sifresizVeri . PHP_EOL
                . "sifresizChainId = ". $sifresizChainId . PHP_EOL
                . "sifresizUserWallet = ". $sifresizUserWallet . PHP_EOL
                . "sifresizNonceNewPurchase = ". $sifresizNonceNewPurchase . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                echo json_encode([
                    "mesajKodu" => "31",
                    "mesajAciklamasi" => "Cüzdan adresi aynı değil"
                ]);

                exit;
            }

            // bu noktada yeni bir NFT alışverişi için purchase tablosunda benzersiz bir kayıt oluşmuş olmalıydı
            // bu kaydı kontrol mekanizması olarak kullan
            $dtPurchase = '';
            try 
            {
                $select = $kmt->prepare
                    ("SELECT 
                        id, chain_id, from_address, to_address, token_amount, token_symbol, nonce_token
                    FROM purchase
                    WHERE from_address = :from_address
                        AND nonce_token = :nonce_token
                        AND chain_id = :chain_id
                    ORDER BY id DESC");
                $select->execute([
                    ':from_address' => $userWallet,
                    ':chain_id' => $chainId,
                    ':nonce_token' => $sifresizNonceNewPurchase
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

            // bu php dosyası DAK oluştur dendikten hemen sonra çalıştı, bu noktaya gelmeden öncekileri hatırlayalım, sunucuda bu istek
            // purchase tablosuna nonce bilgisi ile kayıt edidi, bu nonce bilgisi + adres + chain id + özel anahtar ile şifrelendi ve kullanıcıya döndürüldü
            // DAK üretilsin mi diye soruldu, tekrar buraya gelirken kullanıcıdan zaten gerekli bilgileri POST ile aldık, şifreli veriyi çözdük ve karşılaştırdık
            // ancak herşeye rağmen bu noktaya kullanıcı tarafından gelen verilerde
            // pekçok istenmeyen durum olabilme ihtimalin bilinemeyeceğinden bu bilgileri, veritabanında ilk oluşturulan kayıttan okumayı tercih ettim
            // yani burada pekçok gereksiz kod oluştu, ileride daha derli toparlı bir sistem kurulabilir, istedim ki kullanıcı js kodlarını incelerse
            // ödeme onayı için gerekli verilerin js tarafından geldiğini düşünsün, şimdi bunun için ilk ödeme isteğinde bulunduğu sırada kayıt edilen
            // orjinal verieri veritabanından getirelim
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
                . "userNonceNewPurchase = " . $userNonceNewPurchase . PHP_EOL
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
                . "userNonceNewPurchase = " . $userNonceNewPurchase . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                echo json_encode([
                    "mesajKodu" => "7",
                    "mesajAciklamasi" => "Bağlantı hatası, lütfen teknik ekibe haber verin"
                ]);

                exit;
            }

            // tek bir php de hem kayıtlı dak listesini getirme hem de eğer hiç dak yoksa yeni bir tane üretebilmenin
            // hazırlığı yapıldı, bu satırdan itibaren demektir ki hiç dak yok ve yeni bir tane üretilsin mi sorusu sorulacak

            $dbAmount = $dtPurchase[0]['token_amount'];
            $dbTokenSymbol = $dtPurchase[0]['token_symbol'];
            $dakLevel = 1;
            $dakShareWeight = 1;
            $dakShareNextWeight = 3;

            $kmt->beginTransaction();

            try 
            {
                // kontrat doğru çalışırsa tx hash bilgisi sonra kontrol edilcek, kontrat bizden NFT nin parametrelerini beklediği için
                // ve bütün işlemlerin tek bir seferde olması ve gas ücretlerinin kullanıcı tarafından ödenebilmesi için
                // bütün değerleri sunucu tarafında hazırlayacağız, şifreleyeceğiz ve kullanıcı bunu Metamask ile imzalayarak 
                // akıllı kontrat ile iletişime geçecek, DAK NFT nin id bilgisi oluşabilmesi için veritabanında insert olmalı
                // txHash onay alına kadar available = false olacak böylece NFT nin sistemde hareketi kısıtlanacak
                $insert = $kmt->prepare("
                    INSERT INTO dak (owner_address, chain_id, chain_symbol, image_url, dak_level, share_weight, share_weight_next, created_at, created_address, created_chain_id, created_chain_symbol, purchase_nonce_token, mint_status, mint_txhash, last_updated_at, last_updated_address, last_updated_chain_id, last_updated_chain_symbol, available)
                        VALUES (:owner_address, :chain_id, :chain_symbol, :image_url, :dak_level, :share_weight, :share_weight_next, :created_at, :created_address, :created_chain_id, :created_chain_symbol, :purchase_nonce_token, :mint_status, :mint_txhash, :last_updated_at, :last_updated_address, :last_updated_chain_id, :last_updated_chain_symbol, :available)");

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
                    ':purchase_nonce_token' => $sifresizNonceNewPurchase,
                    ':mint_status' => 'pending',
                    ':mint_txhash' => '',
                    ':last_updated_at' => $kayitZamani,
                    ':last_updated_address' => $userWallet,
                    ':last_updated_chain_id' => $chainId,
                    ':last_updated_chain_symbol' => $dbTokenSymbol,
                    ':available' => 'false'
                ]);

                $kmt->commit();

                // üretilen yeni DAK ın id bilgisi getirilsin
                $select = $kmt->prepare("
                    SELECT 
                        id
                    FROM dak
                    WHERE 
                        owner_address = :owner_address
                        AND chain_id = :chain_id
                        AND purchase_nonce_token = :purchase_nonce_token");
                
                $select->execute([
                    ':owner_address' => $userWallet,
                    ':chain_id' => $chainId,
                    ':purchase_nonce_token' => $sifresizNonceNewPurchase
                ]);

                $yeniDakSorguSonucu = $select->fetch(PDO::FETCH_ASSOC);
                $yeniDakId = $yeniDakSorguSonucu['id'] ?? null;

                // hiç dak yoktu, yenisi oluşsun mu dendi, NFT mint başlasın

                // Sunucu Özel Anahtarı (Yeni oluşturulan özel anahtarı kullanın)
                $privateKey = '0x8e37f833f31ca31743fadee7a340c15a09613823a3fab61b586921c4daebb356';

                // Parametreler
                $tokenId = $yeniDakId; // Rastgele bir token ID
                $dakLevel = 1; // Başlangıç seviyesi
                $dakShareWeight = 1;
                $dakShareNextWeight = 3;
                $discount = 0; // İndirim
                // $priceDecimal, $priceWei, $priceHex birbirine olan dönüşümler birçok deneme sonrası en son bu halini aldı
                // veri tabanında decimal 10,8 ayarında bir sütunda Metamask öncesi bu dönüşüm olmazsa Metamask doğru çalışmıyor
                $priceDecimal = (string)$dbAmount; // kayitliSonDakBilgisiniGetir.php içerisinde tespit edilerek purchase tablosuna kayıt edilen token değeri
                $priceWei = bcmul($priceDecimal, bcpow('10', '18', 0), 0); // wei
                $priceHex = '0x' . dechex((int)$priceWei); // hex
                $feeRecipient = '0x32EEC9F20F75442B3dC91279fF307E17c7A6D7E8'; // Ücretin gideceği adres
                $contractAddress = '0x800c21c3a8c1510be404e6ee8574c5906c0e6772'; // Akıllı kontrat adresi
                $functionSelector = '0x6a627842'; // kontratta çalışacak fonkisyonun id değeri
                $metadataURI = "https://test.astrapol.com/public/metadata/{$tokenId}.json"; // Metadata URI

                // Metadata Dosyasını Oluştur
                $metadata = [
                    "name" => "Secure NFT #$tokenId",
                    "description" => "An exclusive Secure NFT with level $dakLevel",
                    "image" => $ayarlar['dosyaKonumAyarlari']['dakNftResimlerLinki'] . "gen1Key1.png",
                    "attributes" => [
                        ["trait_type" => "Level", "value" => $dakLevel],
                        ["trait_type" => "Share Weight", "value" => $dakShareWeight],
                        ["trait_type" => "Sahre Next Weight", "value" => $dakShareNextWeight]
                    ]
                ];

                // Metadata dosyasını kaydet
                $metadataFile = "/var/www/test.astrapol.com/public/metadata/{$tokenId}.json";
                file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                // function calculatePrefixedHash($to, $tokenId, $level)
                $testPrefixedHash = calculatePrefixedHash($userWalletTest, $tokenId, $dakLevel);
                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "testPrefixedHash = " . $testPrefixedHash . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                // function createSignature($privateKey, $to, $tokenId, $uri, $level)
                $signature = createSignature($privateKey, $userWalletTest, $tokenId, $dakLevel);
                file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "signature = " . $signature . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                // data hazırla, metamask data: değerinde ilk başa fonksiyon id si gelmeli, sonrada parametreler
                // function encodeABI($method_id, $address, $erc20Amount, $string, $premium)
                $data = encodeABI($functionSelector, $userWallet, $tokenId, $metadataURI, $dakLevel, $signature);

                // JSON Yanıt Oluşturma
                echo json_encode([
                    "mesajKodu" => "25",
                    "mesajAciklamasi" => "işlem başarılı",
                    "contractAddress" => $contractAddress,
                    "userWallet" => $userWallet,
                    "signature" => $signature,
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
