<?php
    return
    [
        'baglantiAyarlari' =>
        [
            'ilkBaglantiKacSaniyeGecerliOlsun' => 180, // exp
            'sqlBenzersizKodDenemesiKacDefaOlsun' => 3,
            'redisleRateLimitKontroluKacSaniye' => 3,
            'baglantiOnayindaJwtKeyGecerlilikSuresiKacSaniye' => 30 // short_exp1
        ],

        'sifrelemeAnahtarlari' =>
        [
            'key1' => 'Co5g87vratoExXM8jeme7KjYXxJTH6qWYyQcKoAJqLo=' // 32 bayt uzunluğunda, yeni dak üretiminde doğrulama sırasında kullanılıyor teminalde openssl rand -base64 32 ile elde edildi
        ],

        'kontratAyarlari' =>
        [
            'key2' => '0x8e37f833f31ca31743fadee7a340c15a09613823a3fab61b586921c4daebb356', // mint kontratını imzalayan cüzdanın (wallet2) özel anahtarı
            'wallet1' => '0xeA38107B1748E28ce23367D4A43fC8D00b3CaAe8', // kontratları deploy eden, kontratların sahibi olan cüzdan
            'wallet2' => '0xB555E7d0A762aC70723a0Ddd2b7fD24Fb3Cdf516', // kontratlardaki işlemler için imza yetkisi olan cüzdan (private keyi sunucuda saklı olan cüzdan)
            'kontrat1' => '0xf894c1be0ebb42a02e8c4593e09636e16c40d19e' // mint NFT, update NFT kontrat adresleri
        ],

        'dosyaKonumAyarlari' =>
        [
            'dakNftResimlerLinki' => 'https://test.astrapol.com/public/images/dak/'
        ],

        'nesneHisseDegerlemeAyarlari' =>
        [
            // her nesne için değerler farklıdır, 
            // levelBaslangicDegeri = -1 ise level e göre ayarlama yapılmayacak ve tokenBaslangicDegeri tek bir kayıt olarak yapılacak demektir

            'token1DegerineGore' =>
            [
                'zincirKodu' => '0xa86a',
                'tokenSymbol' => 'AVAX',

                'rareGen2' =>
                [
                    'katSayiOrani' => 5,
                    'tokenBaslangicDegeri' => 0.01,
                    'levelBaslangicDegeri' => 20
                ],

                'epicGen2' =>
                [
                    'katSayiOrani' => 5,
                    'tokenBaslangicDegeri' => 0.03,
                    'levelBaslangicDegeri' => 20
                ],

                'legendGen2' =>
                [
                    'katSayiOrani' => 5,
                    'tokenBaslangicDegeri' => 0.05,
                    'levelBaslangicDegeri' => 20
                ],

                'rareVehicles' =>
                [
                    'katSayiOrani' => 1,
                    'tokenBaslangicDegeri' => 1,
                    'levelBaslangicDegeri' => -1
                ],

                'epicVehicles' =>
                [
                    'katSayiOrani' => 1,
                    'tokenBaslangicDegeri' => 3,
                    'levelBaslangicDegeri' => -1
                ],

                'legendVehicles' =>
                [
                    'katSayiOrani' => 1,
                    'tokenBaslangicDegeri' => 5,
                    'levelBaslangicDegeri' => -1
                ],

                'pagm' =>
                [
                    'katSayiOrani' => 1,
                    'tokenBaslangicDegeri' => 0.2,
                    'levelBaslangicDegeri' => -1
                ],

                'uc' =>
                [
                    'katSayiOrani' => 10,
                    'tokenBaslangicDegeri' => 0.2,
                    'levelBaslangicDegeri' => 1
                ],

                'dgcg' =>
                [
                    'katSayiOrani' => 10,
                    'tokenBaslangicDegeri' => 0.2,
                    'levelBaslangicDegeri' => 1
                ],

                'cfg' =>
                [
                    'katSayiOrani' => 10,
                    'tokenBaslangicDegeri' => 0.2,
                    'levelBaslangicDegeri' => 1
                ],

                'rune' =>
                [
                    'katSayiOrani' => 30,
                    'tokenBaslangicDegeri' => 0.2,
                    'levelBaslangicDegeri' => 1
                ],
            ],
        ],

        'tokenZincirDetaylari' =>
        [
            '0xa86a' =>
            [
                'sembol' => 'AVAX',
                'adi' => 'Avalanche C-Chain'
            ],

            '0xa869' =>
            [
                'sembol' => 'AVAX',
                'adi' => 'Avalanche Fuji Testnet'
            ],

            '0x1' =>
            [
                'sembol' => 'ETH',
                'adi' => 'Ethereum Mainnet'
            ],

            '0x38' =>
            [
                'sembol' => 'BNB',
                'adi' => 'BNB Smart Chain Mainnet'
            ],
        ],

        'ucretler' =>
        [
            'yeniDak' =>
            [
                '0xa86a' => // avalacnhe c
                [
                    'sembol' => 'AVAX',
                    'miktar' => 0.1,
                    'adres' => '0x32EEC9F20F75442B3dC91279fF307E17c7A6D7E8'
                ],

                '0xa869' => //avalanche fuji testnet
                [
                    'sembol' => 'AVAX',
                    'miktar' => 0.1,
                    'adres' => '0x32EEC9F20F75442B3dC91279fF307E17c7A6D7E8'
                ],
            ],
        ],

        'rpcOkumaAdresleri' =>
        [
            '0xa86a' => //avalanche c 
            [
                'https://api.avax.network/ext/bc/C/rpc',
                'https://rpc.ankr.com/avalanche'
            ],

            '0xa869' => //avalanche fuji testnet
            [
                'https://api.avax-test.network/ext/bc/C/rpc'
            ],
        ],

        'rpcOkumaAyarlari' =>
        [
            '0xa86a' => //avalanche c 
            [
                'rpcSorgusuKacDefaYapilacak' => 3,
                'rpcSorgusuHerDefasindaKacSaniyeBekleyecek' => 3
            ],

            '0xa869' => //avalanche fuji testnet
            [
                'rpcSorgusuKacDefaYapilacak' => 3,
                'rpcSorgusuHerDefasindaKacSaniyeBekleyecek' => 3
            ],
        ],
    ];