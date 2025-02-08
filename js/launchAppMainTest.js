

document.addEventListener('DOMContentLoaded', async () =>
{
    document.getElementById('btnMesajKutusunuKapat').addEventListener('click', () =>
    {
        document.getElementById("divMesajKutusu").style.display = "none";
    });

    document.getElementById('menuButton').addEventListener('click', () => 
    {
        // Menü görünürlüğünü kontrol et
        if(window.innerWidth <= 480)
        {
            if (sidebar.style.left === '0px') 
            {
                sidebar.style.left = '-400px'; // Menü kapatılır
            } 
            else 
            {
                sidebar.style.left = '0px'; // Menü açılır
            }
    
            sidebar.style.right = '';
        }
        else if(window.innerWidth >= 1281)
        {
            if (sidebar.style.right === '0px') 
            {
                sidebar.style.right = '-650px'; // Menü kapatılır
            } 
            else 
            {
                sidebar.style.right = '0px'; // Menü açılır
            }
    
            sidebar.style.left = '';
        }
    });

    document.getElementById('btnDakListeMenusunuKapat').addEventListener('click', () =>
    {
        document.getElementById("divDakListesiMenusu").style.display = "none";
    });

    document.getElementById('btn_cuzdanBagliMiBagla').addEventListener('click', () =>
    {
        connectWallet();
    });

    document.getElementById('btnDakMenusu').addEventListener('click', function() 
    {
        dakMenusunuOlustur();
    });

    document.getElementById('btnGen2Olustur').addEventListener('click', function() 
    {
        gen2UretmeMenusunuOlustur();
    });

    document.getElementById('btnItemOlustur').addEventListener('click', function() 
    {
        itemUretmeMenusunuOlustur();
    });

    document.getElementById('btnArena').addEventListener('click', function() 
    {
        arenaMenusunuOlustur();
    });

    document.getElementById('btnAdventure').addEventListener('click', function() 
    {
        adventureMenusunuOlustur();
    });

    document.getElementById('btnMarket').addEventListener('click', function() 
    {
        marketMenusunuOlustur();
    });

    document.getElementById('btnKullaniciHesabi').addEventListener('click', function() 
    {
        inventoryMenusunuOlustur();
    });

    document.getElementById('btnDonustur').addEventListener('click', function() 
    {
        transformationMergeMenusunuOlustur();
    });

    document.getElementById('btnListele').addEventListener('click', function()
    {
        listeyiDoldurTest();
    });

    if (window.ethereum && window.ethereum.isMetaMask) 
    {
        window.ethereum.on("accountsChanged", (accounts) => {
            if (accounts.length === 0) 
            {
                window.location.reload();
            } 
            else 
            {
                connectWallet();
            }
        });
    } 
    else 
    {
        console.warn("MetaMask yüklü değil!");
    }

    document.getElementById('dakNftResmi').addEventListener('click', function()
    {
        dakListesiMenusunuGoster();
    });
});

function dakSecimKutusunuSec() 
{
    console.log("resim tıklandı");
}

function dakSecimKutusunuSec2() 
{
    console.log("div tıklandı");
}

function sayiFormatlama(sayi, islem) 
{
    if (islem === "gorunumeCevir") 
    {
        // Sayıyı görüntüleme formatına çevir: 2563.25 => 2.563,25
        return sayi.toFixed(4).replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    } 
    else if (islem === "islemeCevir") 
    {
        // Metni sayıya çevir: 2.563,25 => 2563.25
        return parseFloat(sayi.replace(/\./g, "").replace(",", "."));
    } 
    else 
    {
        throw new Error("Geçersiz mod. 'gorunumeCevir' veya 'islemeCevir' kullanmalısınız.");
    }
}

function mesajKutusunuGoster(mesaj)
{
    document.getElementById("divMesajKutusuIcerigi").textContent = mesaj;
    document.getElementById("divMesajKutusu").style.display = "flex";
}

function onayKutusunuGoster(mesaj)
{
    return new Promise((cevap, reject) => 
    {
        document.getElementById("divOnayKutusuIcerigi").textContent = mesaj;
        document.getElementById("divOnayKutusu").style.display = "flex";

        document.getElementById("btnOnayKutusuEvet").onclick = null;
        document.getElementById("btnOnayKutusuHayir").onclick = null;

        document.getElementById("btnOnayKutusuEvet").onclick = () =>
        {
            document.getElementById("divOnayKutusu").style.display = "none";
            cevap("olustur");
        };

        document.getElementById("btnOnayKutusuHayir").onclick = () =>
        {
            document.getElementById("divOnayKutusu").style.display = "none";
            cevap("iptal");
        };
    });
}

function listeyiDoldurTest()
{
    document.getElementById("hisseMenusu_2_liste").innerHTML = "";
    const seciliNesne = "gen2";
    const seciliNadirlik = "rare";
    var level = 35;
    for(say = 1; say <= 39; say++)
    {
        document.getElementById("hisseMenusu_2_liste").innerHTML += 
        `
            <div class="divNesneSecim">
                <div class="divNesneSecimResim">
                    <img src="images/1.png" id="profilResmi" onclick="ilgiliSecimKutusunuSec(${say + 10})">
                    <input type="checkbox" class="chbListedekiNesne" id="${say + 10}" data-value="${level}">
                </div>
                <div class="divNesneSecimSatirlar">
                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                    <p>ID</p>
                    <p id="nesne_id">${say + 10}</p>
                </div>
                <div class="divNesneSecimSatirlar">
                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                    <p>Level</p>
                    <p id="nesne_levelDegeri">${level}</p>
                </div>
                <div class="divNesneSecimSatirlar">
                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                    <div class="divNesneSecimNadirlikResmi">
                        <img src="images/${seciliNadirlik}.png">
                    </div>
                    <div class="divNesneSecimNadirlikAdi">
                        <p id="nesne_nadirlik">${seciliNadirlik.toUpperCase()}</p>
                    </div>
                </div>
                <div class="divNesneSecimSatirlar">
                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                    <p>AVAX value =</p>
                    <p id="nesne_avaxDegeri">0,025</p>
                </div>
            </div>
        `;
    }
}

async function connectWallet()
{
    if (window.ethereum && window.ethereum.isMetaMask)
    {
        try
        {
            let cuzdanAdresleri = await ethereum.request({ method:'eth_accounts'});
            if(cuzdanAdresleri.length === 0)
            {
                cuzdanAdresleri = await ethereum.request({method: 'eth_requestAccounts'});
            }

            const seciliCuzdanAdresi = cuzdanAdresleri[0];
            const chainId = await ethereum.request({ method: 'eth_chainId' });
            const userAgent = navigator.userAgent;

            if (!sessionStorage.getItem("session_id")) 
            {
                sessionStorage.setItem("session_id", Math.random().toString(36).substring(2, 11));
            }            

            const sessionId = sessionStorage.getItem("session_id");

            const baglantiDurumu = await fetch('/app/baglantiOlustur.php', 
            {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(
                {
                    seciliCuzdanAdresi, 
                    chainId,
                    userAgent,
                    sessionId
                })
            });

            if(baglantiDurumu.ok === false)
            {
                const errorData = await baglantiDurumu.json().catch(() => ({ message: "Bilinmeyen hata" }));
                mesajKutusunuGoster("Sunucu hatası: " + (errorData.message || "Bilinmeyen hata"));
                cuzdanBilgileriTemizlensinMi(true);
                return false;
            }

            const baglantiDurumuSonucu = await baglantiDurumu.json();
            if (baglantiDurumuSonucu.mesajKodu === "6")
            {
                const onayliChainId = baglantiDurumuSonucu.chain;
                const onayliCuzdanAdresi = baglantiDurumuSonucu.userWallet;
                const onayliImzaMesaji = baglantiDurumuSonucu.signMessage;

                const imza = await ethereum.request(
                {
                    method: 'personal_sign',
                    params: [onayliImzaMesaji, onayliCuzdanAdresi],
                });

                const baglantiOnayla = await fetch('/app/baglantiOnayla.php', 
                {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(
                    {
                        onayliCuzdanAdresi, 
                        onayliChainId,
                        userAgent,
                        onayliImzaMesaji,
                        imza
                    })
                });

                if(baglantiOnayla.ok === false)
                {
                    const errorData = await baglantiOnayla.json().catch(() => ({ message: "Bilinmeyen hata" }));
                    mesajKutusunuGoster("Sunucu hatası: " + (errorData.message || "Bilinmeyen hata"));
                    cuzdanBilgileriTemizlensinMi(true);
                    return false;
                }

                const baglantiOnaylaSonucu = await baglantiOnayla.json();
                if(baglantiOnaylaSonucu.mesajKodu === "5" || baglantiOnaylaSonucu.mesajKodu === "32")
                {
                    document.getElementById("p_cuzdanAdresi").innerText = baglantiOnaylaSonucu.userWallet;
                    document.getElementById("p_ag").innerText = baglantiOnaylaSonucu.chain;
                    return true;
                }
                else
                {
                    mesajKutusunuGoster(baglantiOnaylaSonucu.mesajAciklamasi);
                    cuzdanBilgileriTemizlensinMi(true);
                    return false;
                }
            }
            else if(baglantiDurumuSonucu.mesajKodu === "14")
            {
                document.getElementById("p_cuzdanAdresi").innerText = baglantiDurumuSonucu.userWallet;
                document.getElementById("p_ag").innerText = baglantiDurumuSonucu.chain;
                return true;
            }
            else
            {
                mesajKutusunuGoster(baglantiDurumuSonucu.mesajAciklamasi);
                cuzdanBilgileriTemizlensinMi(true);
                return false;
            }
        }
        catch(hataMesaji)
        {
            mesajKutusunuGoster("Cüzdan bağlantısı reddedildi veya bir hata oluştu.");
            cuzdanBilgileriTemizlensinMi(true);
            return false;
        }
    }
    else
    {
        mesajKutusunuGoster("Metamask yüklü değil! Lütfen Metamask cüzdanını yükleyin.");
        cuzdanBilgileriTemizlensinMi(true);
        return false;
    }
}

function seciliAgAdiniGetir(chainId) 
{
    const networks = {
        '0x1': 'Ethereum Mainnet',
        '0x38': 'BNB Smart Chain Mainnet',
        '0xa86a': 'Avalanche C-Chain',
        '0x89': 'Polygon Mainnet'
    };

    return networks[chainId] || `Unknown Chain ID: ${chainId}`;
}

function cuzdanBilgileriTemizlensinMi(karar)
{
    if(karar === true)
    {
        document.getElementById("p_cuzdanAdresi").innerText = "";
        document.getElementById("p_ag").innerText = "";
        document.getElementById("btn_cuzdanBagliMiBagla").innerText = "Connect";
    }
}

async function dakMenusunuOlustur()
{
    try 
    {
        document.getElementById('btnDakMenusu').disabled = true;

        const cuzdanBagliMi = await connectWallet();
        if(cuzdanBagliMi == true)
        {
            const seciliCuzdanAdresi = document.getElementById("p_cuzdanAdresi").innerText;
            const zincirKodu = await ethereum.request({ method: 'eth_chainId' });
            const userAgent = navigator.userAgent;

            const sonDakBilgisi = await fetch('/app/kayitliSonDakBilgisiniGetir.php', 
            {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(
                {
                    seciliCuzdanAdresi,
                    zincirKodu,
                    userAgent
                })
            });

            if(sonDakBilgisi.ok === false)
            {
                const sonDakBilgisiJsonMesaji = await sonDakBilgisi.json().catch(() => ({ message: "Bilinmeyen hata" }));
                mesajKutusunuGoster("Json hatası: " + (sonDakBilgisiJsonMesaji.message || "Bilinmeyen hata"));
                return false;
            }

            const sonDakBilgisiYaniti = await sonDakBilgisi.json();
            switch (sonDakBilgisiYaniti.mesajKodu) 
            {
                case "x": mesajKutusunuGoster(sonDakBilgisiYaniti.mesajAciklamasi); return;
                case "1": mesajKutusunuGoster(sonDakBilgisiYaniti.mesajAciklamasi); return;
                case "3": mesajKutusunuGoster(sonDakBilgisiYaniti.mesajAciklamasi); return;
                case "4": mesajKutusunuGoster(sonDakBilgisiYaniti.mesajAciklamasi); return;
                case "7": mesajKutusunuGoster(sonDakBilgisiYaniti.mesajAciklamasi); return;
                case "9": if(await connectWallet()){break;}else{return;}
                case "10": if(await connectWallet()){break;}else{return;}
                case "11": mesajKutusunuGoster(sonDakBilgisiYaniti.mesajAciklamasi); return;
                case "12": mesajKutusunuGoster(sonDakBilgisiYaniti.mesajAciklamasi); return;
                case "15": break;
                case "17": break;
                case "18": mesajKutusunuGoster(sonDakBilgisiYaniti.mesajAciklamasi); return;
                default: return;
            }

            if(sonDakBilgisiYaniti.mesajKodu === "15")
            {
                document.getElementById("panel_3_2").innerHTML = 
                `
                    <div class="hisseMenusu_1">
                        <div class="dakDivi">
                            <div class="dakResmi">
                                <img id="dakNftResmi" src="${sonDakBilgisiYaniti.dbDakListesi[0].image_url}">
                            </div>
                            <div class="dakDetaylari">
                                <div class="dakDetaylariSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>ID =</p>
                                    <p id="dak_idDegeri">${sonDakBilgisiYaniti.dbDakListesi[0].id}</p>
                                </div>

                                <div class="dakDetaylariSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Level =</p>
                                    <p id="dak_levelDegeri">${sonDakBilgisiYaniti.dbDakListesi[0].dak_level}</p>
                                </div>

                                <div class="dakDetaylariSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Share Weight =</p>
                                    <p id="dak_hisseAgirlikDegeri">${sonDakBilgisiYaniti.dbDakListesi[0].share_weight}</p>
                                </div>

                                <div class="dakDetaylariSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Share Weight Next Level =</p>
                                    <p id="dak_sonrakiLevelHisseAgirlikDegeri">${sonDakBilgisiYaniti.dbDakListesi[0].share_weight_next}</p>
                                </div>
                            </div>

                            <div class="finansDivi">
                                <div class="finansDiviSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Processing Fee (AVXT) =</p>
                                    <p id="dak_islemUcreti">100</p>
                                </div>
                                <div class="finansDiviSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Level Up Cost (AVAX) =</p>
                                    <p id="dak_sonrakiLevelAvaxUcreti">2,3842</p>
                                </div>
                                <div class="finansDiviSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Total Discount (AVAX) =</p>
                                    <p id="dak_toplamAvaxIndirimi">0,0000</p>
                                </div>
                                <div class="finansDiviSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Total Cost (AVAX) =</p>
                                    <p id="dak_toplamAvaxUcreti">2,3842</p>
                                </div>
                            </div>

                            <div class="hisseMenusu_1_islemlerDivi">
                                <div class="hisseMenusu_1_islemlerDiviSecim">
                                    <input type="checkbox" id="chbHepsiniSec1">
                                    <label for="chbHepsiniSec1">Select All</label>
                                </div>
                                
                                <button id="btnLevelUp">
                                    <img src="images/butonArkaPlanResmi.png" class="butonArkaPlanResmi">
                                    <p>Level Up</p>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="hisseMenusu_2">
                        <div class="hisseMenusu_2_filtre">
                            <div class="hisseMenusu_2_filtreNadirlik">
                                <div class="nadirlikEtiketi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Rarity</p>
                                </div>
                                <div class="nadirlikCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nadirlikCesidiEtiketi">
                                        <input type="checkbox" id="chbNadirlikSecimiRare" name="seciliNadirlikler[]" value="rare" class="filtreCheckboxlari">
                                        <label for="chbNadirlikSecimiRare">Rare</label>
                                    </div>
                                </div>
                                <div class="nadirlikCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nadirlikCesidiEtiketi">
                                        <input type="checkbox" id="chbNadirlikSecimiEpic" name="seciliNadirlikler[]" value="epic" class="filtreCheckboxlari">
                                        <label for="chbNadirlikSecimiEpic">Epic</label>
                                    </div>
                                </div>
                                <div class="nadirlikCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nadirlikCesidiEtiketi">
                                        <input type="checkbox" id="chbNadirlikSecimiLegend" name="seciliNadirlikler[]" value="legend" class="filtreCheckboxlari">
                                        <label for="chbNadirlikSecimiLegend">Legend</label>
                                    </div>
                                </div>
                            </div>

                            <div class="hisseMenusu_2_filtreNesne">
                                <div class="nesneEtiketi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Object</p>
                                </div>
                                <div class="nesneCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nesneCesidiEtiketi">
                                        <input type="checkbox" id="chbNesneSecimiGen2" name="seciliNesneler[]" value="gen2" class="filtreCheckboxlari">
                                        <label for="chbNesneSecimiGen2">Gen2</label>
                                    </div>
                                </div>
                                <div class="nesneCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nesneCesidiEtiketi">
                                        <input type="checkbox" id="chbNesneSecimiArmor" name="seciliNesneler[]" value="armor" class="filtreCheckboxlari">
                                        <label for="chbNesneSecimiArmor">Armor</label>
                                    </div>
                                </div>
                                <div class="nesneCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nesneCesidiEtiketi">
                                        <input type="checkbox" id="chbNesneSecimiVehicles" name="seciliNesneler[]" value="vehicles" class="filtreCheckboxlari">
                                        <label for="chbNesneSecimiVehicles">Vehicles</label>
                                    </div>
                                </div>
                                <div class="nesneCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nesneCesidiEtiketi">
                                        <input type="checkbox" id="chbNesneSecimiRune" name="seciliNesneler[]" value="rune" class="filtreCheckboxlari">
                                        <label for="chbNesneSecimiRune">Rune</label>
                                    </div>
                                </div>
                                <div class="nesneCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nesneCesidiEtiketi">
                                        <input type="checkbox" id="chbNesneSecimiPagm" name="seciliNesneler[]" value="pagm" class="filtreCheckboxlari">
                                        <label for="chbNesneSecimiPagm">PAGM</label>
                                    </div>
                                </div>
                                <div class="nesneCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nesneCesidiEtiketi">
                                        <input type="checkbox" id="chbNesneSecimiDgcg" name="seciliNesneler[]" value="dgcg" class="filtreCheckboxlari">
                                        <label for="chbNesneSecimiDgcg">DGCG</label>
                                    </div>
                                </div>
                                <div class="nesneCesidi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="nesneCesidiEtiketi">
                                        <input type="checkbox" id="chbNesneSecimiCfg" name="seciliNesneler[]" value="cfg" class="filtreCheckboxlari">
                                        <label for="chbNesneSecimiCfg">CFG</label>
                                    </div>
                                </div>
                            </div>

                            <div class="hisseMenusu_2_filtreLevel">
                                <div class="levelEtiketi">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Level</p>
                                </div>
                                <div class="levelDegeri">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="levelDegeriDivi">
                                        <p>Min Level</p>
                                        <input type="text" id="txtMinLevel">
                                    </div>
                                </div>
                                <div class="levelDegeri">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <div class="levelDegeriDivi">
                                        <p>Max Level</p>
                                        <input type="text" id="txtMaxLevel">
                                    </div>
                                </div>
                            </div>

                            <div class="hisseMenusu_2_filtreButonlar">
                                <button id="btnFiyatListesi">
                                    <img src="images/butonArkaPlanResmi.png" class="butonArkaPlanResmi">
                                    <p>Price List</p>
                                </button>
                                <button id="btnListele">
                                    <img src="images/butonArkaPlanResmi.png" class="butonArkaPlanResmi">
                                    <p>Show</p>
                                </button>
                            </div>
                        </div>

                        <div class="hisseMenusu_2_liste" id="hisseMenusu_2_liste">
                            
                        </div>
                    </div>
                `;

                // aşağıdaki bu üç kısım belki son if bloğundan sonra yapılabilir
                // şuanda buna emin değilim
                document.getElementById("dakNftResmi").addEventListener('click', function()
                {
                    dakListesiMenusunuGoster();
                });

                document.getElementById("btnListele").addEventListener('click', function() 
                {
                    secenekleriYukle();
                });

                document.getElementById("chbHepsiniSec1").addEventListener('click', function () 
                {
                    // burayı metodla yapmamın sebebi, farklı ekran boyutları için aynı işi yapan farklı id ye sahip elementler kullanmak zorunda kalırsam
                    // listeninHepsiniSec(this); metodunda belirttiğim elemente göre işlemi yaptırabilmek
                    listeninHepsiniSec(this);
                });

                // DAK bilgisi yerleştirildi, sonraki satırlar çalışmasın
                return;
            }

            if(sonDakBilgisiYaniti.mesajKodu === "17")
            {
                const secim = await onayKutusunuGoster(sonDakBilgisiYaniti.mesajAciklamasi);
                if(secim === "olustur")
                {
                    const yeniMintBaslat = await fetch('/app/yeniDakMintHazirlaBaslat.php',
                    {
                        method: 'POST',
                        credentials: 'include',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(
                        {
                            from: ethereum.selectedAddress,
                            chainId: zincirKodu,
                            nonceNewPurchase: sonDakBilgisiYaniti.nonceNewPurchase
                        })
                    });

                    if(yeniMintBaslat.ok === false)
                    {
                        const sorguJsonMesaji = await yeniMintBaslat.json().catch(() => ({ message: "Bilinmeyen hata" }));
                        mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                        return false;
                    }
        
                    const yeniMintBaslatSonucu = await yeniMintBaslat.json();
                    switch (yeniMintBaslatSonucu.mesajKodu)
                    {
                        case "x": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        case "1": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        case "3": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        case "4": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        case "7": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        case "21": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        case "25": break;
                        case "27": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        case "29": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        case "30": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        case "31": mesajKutusunuGoster(yeniMintBaslatSonucu.mesajAciklamasi); return;
                        default: return;
                    }

                    if(yeniMintBaslatSonucu.mesajKodu === "25")
                    {
                        // İşlem Verilerini Hazırla
                        const transactionParameters = {
                            from: yeniMintBaslatSonucu.userWallet,
                            to: yeniMintBaslatSonucu.contractAddress,
                            value: yeniMintBaslatSonucu.priceHex,
                            data: yeniMintBaslatSonucu.data,
                        };

                        console.log(transactionParameters);
                        console.log(yeniMintBaslatSonucu.signature);

                        const txHashMint = await ethereum.request({
                            method: 'eth_sendTransaction',
                            params: [transactionParameters],
                        });

                        console.log('Mint İşlemi Tamamlandı:', txHashMint);

                        const yeniMintHashDogrula = await fetch('/app/yeniDakMintTxHashDogrula.php',
                        {
                            method: 'POST',
                            credentials: 'include',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(
                            {
                                from: ethereum.selectedAddress,
                                chainId: zincirKodu,
                                nonceNewPurchase: sonDakBilgisiYaniti.nonceNewPurchase,
                                txHash: txHashMint
                            })
                        });
    
                        if(yeniMintHashDogrula.ok === false)
                        {
                            const sorguJsonMesaji = await yeniMintHashDogrula.json().catch(() => ({ message: "Bilinmeyen hata" }));
                            mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                            return false;
                        }

                        const yeniMintHashDogrulaSonucu = await yeniMintHashDogrula.json();
                        switch (yeniMintHashDogrulaSonucu.mesajKodu)
                        {
                            case "x": mesajKutusunuGoster(yeniMintHashDogrulaSonucu.mesajAciklamasi); return;
                            case "1": mesajKutusunuGoster(yeniMintHashDogrulaSonucu.mesajAciklamasi); return;
                            case "3": mesajKutusunuGoster(yeniMintHashDogrulaSonucu.mesajAciklamasi); return;
                            case "4": mesajKutusunuGoster(yeniMintHashDogrulaSonucu.mesajAciklamasi); return;
                            case "7": mesajKutusunuGoster(yeniMintHashDogrulaSonucu.mesajAciklamasi); return;
                            case "20": break;
                            default: return;
                        }
                    }
                }
                else
                {
                    return;
                }
            }
        }
        else
        {
            mesajKutusunuGoster("Connect Wallet");
        }
    } 
    catch (hataMesaji) 
    {
        mesajKutusunuGoster(hataMesaji.message);
    }
    finally
    {
        document.getElementById('btnDakMenusu').disabled = false;
    }
}

async function dakListesiMenusunuGoster()
{
    try 
    {
        document.getElementById('btnDakMenusu').disabled = true;

        document.querySelectorAll(".dakAnaCerceveResmi img").forEach(img => {
            // img'nin ID'sini al (veya varsayılan bir değer ver)
            let imgId = img.id || "defaultId"; 
            
            // Tıklanınca çalışacak fonksiyonu ekle
            img.onclick = function() {
                dakSecimKutusunuSec(imgId);
            };
        });

        document.getElementById("divDakListesiMenusu").style.display = "flex";
    } 
    catch (hataMesaji) 
    {
        mesajKutusunuGoster(hataMesaji.message);
    }
    finally
    {
        document.getElementById('btnDakMenusu').disabled = false;
    }
}

function gen2UretmeMenusunuOlustur()
{
    
}

function itemUretmeMenusunuOlustur()
{
    
}

function arenaMenusunuOlustur()
{
    
}

function adventureMenusunuOlustur()
{
    
}

function marketMenusunuOlustur()
{
    
}

function inventoryMenusunuOlustur()
{
    
}

function transformationMergeMenusunuOlustur()
{
    
}

async function secenekleriYukle() 
{
    try 
    {
        const cuzdanBagliMi = await connectWallet();

        if(cuzdanBagliMi === true)
        {
            document.getElementById("hisseMenusu_2_liste").innerHTML = "";
            document.getElementById('chbHepsiniSec1').checked = false;
            // document.getElementById('chbHepsiniSec_2').checked = false;

            const seciliNadirlikler = [];
            document.querySelectorAll('input[name="seciliNadirlikler[]"]').forEach(checkbox => 
            {
                if(checkbox.checked)
                {
                    seciliNadirlikler.push(checkbox.value);
                }
                else
                {
                    seciliNadirlikler.push('default');
                }
            });

            const seciliNesneler = [];
            document.querySelectorAll('input[name="seciliNesneler[]"]').forEach(checkbox =>
            {
                if(checkbox.checked)
                {
                    seciliNesneler.push(checkbox.value);
                }
                else
                {
                    seciliNesneler.push('default');
                }
            });

            const seciliCuzdanAdresi = document.getElementById("p_cuzdanAdresi").innerText;

            const sorgu = await fetch('/app/gen2ListesiniGetir.php', 
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(
                {
                    seciliNesne: seciliNesneler,
                    seciliNadirlik: seciliNadirlikler,
                    seciliCuzdanAdresi: seciliCuzdanAdresi
                })
            });

            if(sorgu.ok === false)
            {
                const sorguJsonMesaji = await sorgu.json().catch(() => ({ message: "Bilinmeyen hata" }));
                mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                return false;
            }

            const sonuc = await sorgu.json();
            if(sonuc.mesajKodu !== "15")
            {
                mesajKutusunuGoster(sonuc.mesajAciklamasi);
                return;
            }

            if(sonuc.veriler.length === 0)
            {
                mesajKutusunuGoster("No assets found for this wallet");
                return;
            }

            sonuc.veriler.forEach(satir => 
            {
                const tokenDegeri = parseFloat(satir.token1_degeri);
                document.getElementById("hisseMenusu_2_liste").innerHTML += 
                `
                    <div class="divNesneSecim">
                        <div class="divNesneSecimResim">
                            <img src="${satir.image}" onclick="ilgiliSecimKutusunuSec(${satir.item_id})">
                            <input type="checkbox" class="chbListedekiNesne" id="${satir.item_id}" data-value="0,0250">
                        </div>
                        <div class="divNesneSecimSatirlar">
                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                            <p>ID</p>
                            <p id="nesne_id">${satir.item_id}</p>
                        </div>
                        <div class="divNesneSecimSatirlar">
                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                            <p>Level</p>
                            <p id="nesne_levelDegeri">${satir.level}</p>
                        </div>
                        <div class="divNesneSecimSatirlar">
                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                            <div class="divNesneSecimNadirlikResmi">
                                <img src="images/${satir.rarity}.png">
                            </div>
                            <div class="divNesneSecimNadirlikAdi">
                                <p id="nesne_nadirlik">${satir.rarity.toUpperCase()}</p>
                            </div>
                        </div>
                        <div class="divNesneSecimSatirlar">
                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                            <p>AVAX value =</p>
                            <p id="nesne_avaxDegeri">${tokenDegeri.toFixed(5)}</p>
                        </div>
                    </div>
                `;
            });

            var secilenler = document.querySelectorAll('#hisseMenusu_2_liste .chbListedekiNesne');
            secilenler.forEach(function(checkbox) 
            {
                // hisseMenusu_2_liste divi içindeki tüm .chbListedekiNesne için tıklama olayını takip etmesini sağla
                // .chbListedekiNesne değişirse yeniListeyiGuncelle metodunu çalıştır, bu metod bütün .chbListedekiNesne 
                // leri dolaşır
                checkbox.addEventListener('change', yeniListeyiGuncelle);
            });
        }
        else
        {
            mesajKutusunuGoster("Connect Wallet");
        }
    } 
    catch (hataMesaji) 
    {
        mesajKutusunuGoster(hataMesaji.message);
        console.log(hataMesaji.stack);
    }
}

function ilgiliSecimKutusunuSec(id) 
{
    // Checkbox'ların durumunu güncelleyen fonksiyon
    const checkbox = document.getElementById(`${id}`);
    checkbox.checked = !checkbox.checked; // Checkbox'ın checked durumunu değiştir
    document.getElementById('chbHepsiniSec1').checked = false;
    // document.getElementById('chbHepsiniSec2').checked = false;
    yeniListeyiGuncelle(); // Seçimleri güncelle
}



function yeniListeyiGuncelle()
{
    // hisseMenusu_2_liste divi içindeki .chbListedekiNesne leri dolaş, eğer seçili ise
    // dataset.value değerini topla
    const secimKutulari = document.querySelectorAll('#hisseMenusu_2_liste .chbListedekiNesne');

    var secilenSayisi = 0;
    let toplamNesneAvaxDegeri = 0;

    secimKutulari.forEach(function(checkbox)
    {
        if(checkbox.checked)
        {
            secilenSayisi++;
            toplamNesneAvaxDegeri += parseFloat((checkbox.dataset.value || "0").replace(",", "."));
        }
    });

    document.getElementById('dak_toplamAvaxIndirimi').innerHTML = toplamNesneAvaxDegeri.toFixed(4).replace(".", ",");
    // const dakLevelYukseltmeAvaxDegeri = parseFloat(document.getElementById("dak_sonrakiLevelAvaxUcreti").textContent);
    const dakLevelYukseltmeAvaxDegeri = sayiFormatlama(document.getElementById("dak_sonrakiLevelAvaxUcreti").textContent, "islemeCevir");
    if (dakLevelYukseltmeAvaxDegeri >= toplamNesneAvaxDegeri)
    {
        document.getElementById('dak_toplamAvaxUcreti').innerHTML = (dakLevelYukseltmeAvaxDegeri - toplamNesneAvaxDegeri).toFixed(4).replace(".", ",");
    } 
    else 
    {
        mesajKutusunuGoster("The total AVAX value of the selected objects is more than the DAK leveling cost. The list will be reset, please select one by one.");
        document.getElementById("hisseMenusu_2_liste").innerHTML = "";
        document.getElementById('chbHepsiniSec1').checked = false;
        // document.getElementById('dak_toplamAvaxUcreti').innerHTML = dakLevelYukseltmeAvaxDegeri;
        document.getElementById('dak_toplamAvaxUcreti').innerHTML = sayiFormatlama(dakLevelYukseltmeAvaxDegeri, "gorunumeCevir");
        document.getElementById('dak_toplamAvaxIndirimi').innerHTML = "0,0000";
    }
}

function listeninHepsiniSec(chbKaynak)
{
    const secimKutulari = document.querySelectorAll('#hisseMenusu_2_liste .chbListedekiNesne');
    secimKutulari.forEach(checkbox => checkbox.checked = chbKaynak.checked);
    if(chbKaynak.id == "chbHepsiniSec1")
    {
        // document.getElementById("chbHepsiniSec_2").checked = chbKaynak.checked;
        yeniListeyiGuncelle();
    }
    else
    {
        // document.getElementById("chbHepsiniSec_1").checked = chbKaynak.checked;
        // yeniListeyiGuncelle();
    }
}