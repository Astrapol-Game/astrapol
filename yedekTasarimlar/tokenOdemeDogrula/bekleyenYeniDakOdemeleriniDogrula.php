<?php
    require_once __DIR__ . '/paketler/vendor/autoload.php';
    use kornrunner\Keccak;
    
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

        $select = $kmt->prepare
                    ("SELECT 
                        id, chain_id, from_address, to_address, token_amount, token_symbol, nonce_token, tx_hash
                    FROM purchase
                    WHERE network_status = :network_status
                    ORDER BY id ASC");
        $select->execute([
                ':network_status' => 'pending'
            ]);
        
        $dtPurchase1 = $select->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dtPurchase1 as $bekleyenTxHash) 
        {
            $bekleyenZincirKodu = $bekleyenTxHash['chain_id'];
            $from = $bekleyenTxHash['from_address'];
            $to = $bekleyenTxHash['to_address'];
            $dbAmount = $bekleyenTxHash['token_amount'];
            $txHash = $bekleyenTxHash['tx_hash'];
            $txData = $bekleyenTxHash['nonce_token'];

            $kacSaniyeBeklenecek = $ayarlar['rpcOkumaAyarlari'][$bekleyenZincirKodu]['rpcSorgusuHerDefasindaKacSaniyeBekleyecek'];
            $kacDefaDenenecek = $ayarlar['rpcOkumaAyarlari'][$bekleyenZincirKodu]['rpcSorgusuKacDefaYapilacak'];

            $zincirKoduBulunduMu = false;
            foreach ($ayarlar['rpcOkumaAdresleri'][$bekleyenZincirKodu] as $siradakiRpcLinki) 
            {
                $zincirKoduBulunduMu = true;
                $rpcOkumaBasariliMi = false;
                $txHashGuncellendiMi = false;
                
                for ($kacDefaBakildi=1; $kacDefaBakildi <= $kacDefaDenenecek; $kacDefaBakildi++) 
                {
                    if(!empty($txHash))
                    {
                        // işlem sırasında txHash değeri alınabilmiş, aramaları buna göre yap
                        $transaction = rpcRequest($siradakiRpcLinki, 'eth_getTransactionByHash', [$txHash]);

                        if ($transaction && isset($transaction['result']))
                        {
                            // $transaction değişkeni tanımlı ve NULL değil se
                            // isset($transaction['result']) 'result' anahtarı tanımlı ve NULL değil se
                            $valueWei = hexdec($transaction['result']['value']);

                            // $valueWei büyük bir sayı olabilir. bcdiv ile 1e18 bölünerek hassasiyet korunur.
                            $txHashAmount = bcdiv($valueWei, bcpow('10', '18', 8), 8); // Wei → Token dönüşümü

                            // İki değer de number_format ile aynı hassasiyete getirilir.
                            $dbAmountFormatted = number_format((float)$dbAmount, 8, '.', '');
                            $txHashAmountFormatted = number_format((float)$txHashAmount, 8, '.', '');

                            // nonce hex bilgisini data kısmından oku
                            $txHashDataBilgisi = $transaction['result']['input'] ?? '';

                            $agdaOkunanAdres = checkSumFormatinaDonustur($transaction['result']['to']);
                            if($agdaOkunanAdres === $to)
                            {
                                // transfer adresi doğru
                                // bccomp: İki değeri belirli bir hassasiyetle karşılaştırır, 0 dönerse, iki değer eşittir.
                                if (bccomp($txHashAmountFormatted, $dbAmountFormatted, 8) === 0)
                                {
                                    // transfer adresi doğru, miktar doğru, nonceHex (txData) değerine göre beklenen ödeme bilgisini bul
                                    // kullanıcıya imzalatmadan önce bir nonceHex (txData) üretilmişti ve tabloya kayıt edilmişti
                                    // kullanıcı bu nonceHex (txData) bilgisini Metamask ta ödemeyi gerçekleştirirken data olarak eklemiştik
                                    // bu noktada bu data bilgisi txHash ten okunarak tablodan transfer işlemi çağırılmalı
                                    $kmt->beginTransaction();
                                    try 
                                    {
                                        // aslında bu kontrolde belki txHash değeri de koşula eklenebilirdi ama birbirine çok benzeyen birden fazla kayıt varsa
                                        // bunun raporlanması daha doğru olur, zaten bu çok nadir çalışabileceğinden ve hata kayıt edileceğinden
                                        // gerekirse onaylanmayan bu nadir işlemler için insan kontrolü ile işlem yapılabilir
                                        // bunun dışında bu noktaya zaten bekleyen işlemler tablosunu dolaşırken sıradaki satır ile geldik ancak
                                        // yine de ne olur ne olmaz diye ikinci bir select ile sıradaki bekleyen işlemin bir kopyası var mı diye bakılmalı
                                        $select = $kmt->prepare
                                                    ("SELECT 
                                                        COUNT(*) AS 'satirSayisi' 
                                                    FROM purchase
                                                    WHERE from_address = :from_address
                                                    AND nonce_token = :nonce_token
                                                    AND chain_id = :chain_id
                                                    ORDER BY id DESC");
                                        $select->execute([
                                                ':from_address' => $from,
                                                ':nonce_token' => $txHashDataBilgisi,
                                                ':chain_id' => $bekleyenZincirKodu
                                            ]);
                                        
                                        $dtPurchase2 = $select->fetch(PDO::FETCH_ASSOC);

                                        if($dtPurchase2['satirSayisi'] > 1)
                                        {
                                            file_put_contents('/var/www/test.astrapol.com/app/komutlu_rpc_kontrol_error_log.txt'
                                            , date('Y-m-d H:i:s') . PHP_EOL
                                            . "Dosya = " . __FILE__ . PHP_EOL
                                            . "Satır = " . __LINE__ . PHP_EOL
                                            . "Hata Mesajı = Aynı işlemden birden fazla kayıt var" . PHP_EOL
                                            . "TxHash = " . $txHash . PHP_EOL
                                            . "From = " . $from . PHP_EOL
                                            . "To = " . $to . PHP_EOL
                                            . "dbAmount = " . $dbAmount . PHP_EOL
                                            . "Chain Id = " . $bekleyenZincirKodu . PHP_EOL
                                            . "Data (nonce hex) = " . $txData . PHP_EOL
                                            . PHP_EOL
                                            , FILE_APPEND);

                                            // bu noktada $txHashGuncellendiMi hala false ama bu sonraki bekleyen işleme geçmeye engel değil
                                            // bu işlem için daha fazla deneme yapmamak için for döngüsünü bitir
                                            // if($txHashGuncellendiMi == false) sonraki işleme geçmek için foreach için contiune çalıştıracak
                                            break;
                                        }
                                        else
                                        {
                                            // herşey doğru, bekleyen işlemin devam etmesi gereken aşamaları gerçekleştirilebilir
                                            $update = $kmt->prepare("UPDATE purchase 
                                                    SET
                                                        network_status = :network_status
                                                    WHERE 
                                                        from_address = :from_address
                                                        AND nonce_token = :nonce_token
                                                        AND chain_id = :chain_id");

                                            $update->execute([
                                                    ':network_status' => 'success',
                                                    ':from_address' => $from,
                                                    ':nonce_token' => $txData,
                                                    ':chain_id' => $bekleyenZincirKodu
                                                ]);

                                            // nft üretme işlemleri yapılmalı ve $txHashGuncellendiMi true olmalı

                                            $kmt->commit();
                                        }
                                    }
                                    catch (PDOException $pdoHataMesaji) 
                                    {
                                        // transfer adresi doğru, miktar doğru sql işlemlerinde hata var, işlemler geri alınsın
                                        // sonraki bekleyen işleme geçilebilir
                                        $kmt->rollBack();
                                        
                                        file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
                                        , date('Y-m-d H:i:s') . PHP_EOL
                                        . "Dosya = " . __FILE__ . PHP_EOL
                                        . "Hata Mesajı = " . $pdoHataMesaji->getMessage() . PHP_EOL
                                        . "Satır = " . $pdoHataMesaji->getLine() . PHP_EOL
                                        . "Hata Kodu = " . $pdoHataMesaji->getCode() . PHP_EOL
                                        . "Cüzdan Adresi = " . $from . PHP_EOL
                                        . PHP_EOL
                                        , FILE_APPEND);

                                        // bu noktada $txHashGuncellendiMi hala false ama bu sonraki bekleyen işleme geçmeye engel değil
                                        // bu işlem için daha fazla deneme yapmamak için for döngüsünü bitir
                                        // if($txHashGuncellendiMi == false) sonraki işleme geçmek için foreach için contiune çalıştıracak
                                        break;
                                    }
                                }
                                else
                                {
                                    // transfer adresi doğru, miktar yanlış
                                    file_put_contents('/var/www/test.astrapol.com/app/komutlu_rpc_kontrol_error_log.txt'
                                    , date('Y-m-d H:i:s') . PHP_EOL
                                    . "Dosya = " . __FILE__ . PHP_EOL
                                    . "Satır = " . __LINE__ . PHP_EOL
                                    . "Hata Mesajı = Txhash bulundu ve okundu, transfer adresi doğru, miktar yanlış" . PHP_EOL
                                    . "Kullanıcı adresi = " . $from . PHP_EOL
                                    . "işlem anında ayarlardan insert edilen hedef adres = " . $to . PHP_EOL
                                    . "txHash = " . $txHash . PHP_EOL
                                    . "data (nonce hex) = ". $txHashDataBilgisi . PHP_EOL
                                    . "Doğru miktar = " . $dbAmount . PHP_EOL
                                    . "txHash içindeki miktar = " . $txHashAmount . PHP_EOL
                                    . PHP_EOL
                                    , FILE_APPEND);

                                    // bu noktada $txHashGuncellendiMi hala false ama bu sonraki bekleyen işleme geçmeye engel değil
                                    // bu işlem için daha fazla deneme yapmamak için for döngüsünü bitir
                                    // if($txHashGuncellendiMi == false) sonraki işleme geçmek için foreach için contiune çalıştıracak
                                    break;
                                }
                            }
                            else
                            {
                                // transfer adresi yanlış
                                file_put_contents('/var/www/test.astrapol.com/app/komutlu_rpc_kontrol_error_log.txt'
                                , date('Y-m-d H:i:s') . PHP_EOL
                                . "Dosya = " . __FILE__ . PHP_EOL
                                . "Satır = " . __LINE__ . PHP_EOL
                                . "Hata Mesajı = Txhash bulundu ve okundu, transfer adresi yanlış" . PHP_EOL
                                . "Kullanıcı adresi = " . $from . PHP_EOL
                                . "txHash = " . $txHash . PHP_EOL
                                . "data (nonce hex) = ". $txHashDataBilgisi . PHP_EOL
                                . "txHash içindeki miktar = " . $txHashAmount . PHP_EOL
                                . "txhash kontrolünde okunan transfer adresi = " . $agdaOkunanAdres . PHP_EOL
                                . "işlem anında ayarlardan insert edilen hedef adres = " . $to . PHP_EOL
                                . PHP_EOL
                                , FILE_APPEND);

                                // bu noktada $txHashGuncellendiMi hala false ama bu sonraki bekleyen işleme geçmeye engel değil
                                // bu işlem için daha fazla deneme yapmamak için for döngüsünü bitir
                                // if($txHashGuncellendiMi == false) sonraki işleme geçmek için foreach için contiune çalıştıracak
                                break;
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
                    else
                    {
                        // işlem sırasında txHash değeri alnamamış, aramaları buna göre yap
                    }
                }

                // for bitti, tüm denemelere rağmen belirtilen ayarlarda rpc okuma yapılamadıysa
                // bu noktada kullanılan yöntem hata vermedğine göre yüksek ihtimalle ağ veya rpc listesindeki node yoğunluğu var
                // rpc ayarları değiştirilerek sorun çözülebilir
                if($rpcOkumaBasariliMi === false)
                {
                    file_put_contents('/var/www/test.astrapol.com/app/komutlu_rpc_kontrol_error_log.txt'
                    , date('Y-m-d H:i:s') . PHP_EOL
                    . "Dosya = " . __FILE__ . PHP_EOL
                    . "Satır = " . __LINE__ . PHP_EOL
                    . "Hata Mesajı = RPC kontrolünde if (transaction && isset(transaction['result'])) koşulu, " . $siradakiRpcLinki . " linki ile "
                    . $kacDefaDenenecek . " defa denendi ve her defasında false döndü
                    , blockcahin ağında bir yoğunluk veya listedeki node larda bir yoğunluk yada sorun olabilir
                    , rpc ayarlarını değiştirerek sorunu çözmeye çalışabilirsiniz" . PHP_EOL
                    . "TxHash = " . $txHash . PHP_EOL
                    . "From = " . $from . PHP_EOL
                    . "To = " . $to . PHP_EOL
                    . "dbAmount = " . $dbAmount . PHP_EOL
                    . "Chain Id = " . $bekleyenZincirKodu . PHP_EOL
                    . "Data (nonce hex) = " . $txData . PHP_EOL
                    . PHP_EOL
                    , FILE_APPEND);

                    // foreach ($ayarlar['rpcOkumaAdresleri'][$bekleyenZincirKodu] as $siradakiRpcLinki) değerine göre RPC okuma metodu hata vermedi
                    // gerekli kayıtlar tutuldu sonraki RPC linkine geçilsin
                    continue;
                }

                // rpc okuma başarılı ama başka kontrollerde sorun bulundu yine de sıradaki işleme geçmekte sorun görülmedi
                // başka rpc linkine bakma ve sonraki işleme geç
                if($txHashGuncellendiMi === false)
                {
                    break;
                }
            }

            // rpc okuma hiç başlayamadı çünkü sıradaki işlemin zincir kodu bilgisi için gerekli rpc ayarları ayarlar.php de bulunamadı
            // hata kayıt edildikten sonra sıradaki işleme geçilebilir
            if($zincirKoduBulunduMu === false)
            {
                // DAK için insert kaydı yapıldı ama İşlem onayı için kullanıcıdan gelen zincir kodu ayarlar.php de bulunamadı
                file_put_contents('/var/www/test.astrapol.com/app/komutlu_rpc_kontrol_error_log.txt'
                , date('Y-m-d H:i:s') . PHP_EOL
                . "Dosya = " . __FILE__ . PHP_EOL
                . "Satır = " . __LINE__ . PHP_EOL
                . "Hata Mesajı = Zincir kodu RPC link listesinde bulunamadı" . PHP_EOL
                . "Kullanıcı adresi = " . $from . PHP_EOL
                . "txHash = " . $txHash . PHP_EOL
                . "zincir kodu = " . $bekleyenZincirKodu . PHP_EOL
                . "zincir adı = " . $ayarlar['tokenZincirDetaylari'][$bekleyenZincirKodu]['adi'] . PHP_EOL
                . "data (nonce hex) = ". $txData . PHP_EOL
                . PHP_EOL
                , FILE_APPEND);

                continue;
            }

            // bu noktada hiçbir sorun olmadan işlem bulunmuş ve güncellenmiş olmalı, sıradaki işleme geçilebilir
        }

        echo json_encode([
            "mesajKodu" => "22",
            "mesajAciklamasi" => "Bekleyen yeni DAK ödemelerinin ağ kontrolleri tamamlandı, hatalar olduysa kayıt edildi"
        ]);

        exit;
    }
    catch (PDOException $pdoHataMesaji) 
    {
        file_put_contents('/var/www/test.astrapol.com/app/komutlu_rpc_kontrol_error_log.txt'
        , date('Y-m-d H:i:s') . PHP_EOL
        . "Dosya = " . __FILE__ . PHP_EOL
        . "Hata Mesajı = " . $pdoHataMesaji->getMessage() . PHP_EOL
        . "Satır = " . $pdoHataMesaji->getLine() . PHP_EOL
        . "Hata Kodu = " . $pdoHataMesaji->getCode() . PHP_EOL
        . PHP_EOL
        , FILE_APPEND);
    }
    catch (Exception $hataMesaji) 
    {
        file_put_contents('/var/www/test.astrapol.com/app/komutlu_rpc_kontrol_error_log.txt'
        , date('Y-m-d H:i:s') . PHP_EOL
        . "Dosya = " . __FILE__ . PHP_EOL
        . "Hata Mesajı = " . $hataMesaji->getMessage() . PHP_EOL
        . "Satır = " . $hataMesaji->getLine() . PHP_EOL
        . "Hata Kodu = " . $hataMesaji->getCode() . PHP_EOL
        . PHP_EOL
        , FILE_APPEND);

        echo json_encode([
            "mesajKodu" => "x",
            "mesajAciklamasi" => "Genel hata, lütfen teknik ekibi haberdar edin"
        ]);
    }
