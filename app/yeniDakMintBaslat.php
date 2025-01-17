<?php

    try 
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $signedTransaction = $data['signedTransaction'] ?? '';
        $userWallet = $data['userWallet'] ?? '';
        $tokenId = $data['nftId'] ?? '';

        if (empty($signedTransaction) || empty($userWallet) || empty($tokenId))
        {
            file_put_contents('/var/www/test.astrapol.com/app/error_log.txt'
            , date('Y-m-d H:i:s') . PHP_EOL
            . "Dosya = " . __FILE__ . PHP_EOL
            . "Satır = " . __LINE__ . PHP_EOL
            . "Hata Mesajı = Parametrelerde hata var" . PHP_EOL
            . "signedTransaction = " . $signedTransaction . PHP_EOL
            . "userWallet = " . $userWallet . PHP_EOL
            . "tokenId = " . $tokenId . PHP_EOL
            . PHP_EOL
            , FILE_APPEND);
        
            // Hata mesajını JSON formatında dön
            echo json_encode([
                "mesajKodu" => "3",
                "mesajAciklamasi" => "Eksik veya geçersiz parametreler"
            ]);
        
            exit; // Kodun devam etmesini engelle
        }

        // Blockchain'e Mint İşlemini Gönder
        $rpcUrl = "https://api.avax-test.network/ext/bc/C/rpc";

        $transaction = [
            "jsonrpc" => "2.0",
            "method" => "eth_sendRawTransaction",
            "params" => [$signedTransaction],
            "id" => 1
        ];

        $ch = curl_init($rpcUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction));
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        $txHash = $result['result'] ?? null;

        // NFT Kullanıcıya Aktar
        if ($txHash) 
        {
            $transferData = "0xa9059cbb" . // transfer(address,uint256) Method ID
                str_pad(substr($userWallet, 2), 64, "0", STR_PAD_LEFT) .
                str_pad(dechex($tokenId), 64, "0", STR_PAD_LEFT);

            $transaction['params'] = [[
                "from" => $serverWalletAddress,
                "to" => $contractAddress,
                "data" => $transferData
            ]];

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction));
            $response = curl_exec($ch);
            curl_close($ch);
        }

        echo json_encode([
            "mesajKodu" => "88",
            "mesajAciklamasi" => "kodlar başarılı",
            "success" => true, 
            "txHash" => $txHash
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