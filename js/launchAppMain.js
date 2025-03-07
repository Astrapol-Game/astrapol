// v1

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

    if (window.ethereum && window.ethereum.isMetaMask) 
    {
        window.ethereum.on("accountsChanged", () => window.location.reload());
        setInterval(() => 
        {
            if (!window.ethereum.listenerCount || !window.ethereum._events || !window.ethereum._events.accountsChanged) 
            {
                console.warn("Event listener kaldırılmış, tekrar ekleniyor...");
                window.ethereum.on("accountsChanged", () => window.location.reload());
            }
        }, 3000);
    } 
    else 
    {
        console.warn("MetaMask yüklü değil!");
    }
});

function sayiFormatlama2(sayi, islem) 
{
    if (islem === "gorunumeCevir") 
    {
        // bazen değer sayısal gibi görünse de aslında string olabilir ve toFixed string ile çalışamaz
        sayi = Number(sayi);

        // Sayıyı 5 basamaklı ondalık hale getir
        let formatted = sayi.toFixed(5);

        // Binlik ayracı ekle
        let parts = formatted.split(".");

        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");

        return parts.join(",");
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
            const zincirKodu = await ethereum.request({ method: 'eth_chainId' });
            const userAgent = navigator.userAgent;

            const baglantiOlustur = await fetch('/app/baglantiOlustur.php', 
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

            if(baglantiOlustur.ok === false)
            {
                const errorData = await baglantiOlustur.json().catch(() => ({ message: "Bilinmeyen hata" }));
                mesajKutusunuGoster("Sunucu hatası: " + (errorData.message || "Bilinmeyen hata"));
                cuzdanBilgileriTemizlensinMi(true);
                return false;
            }

            const baglantiOlusturSonucu = await baglantiOlustur.json();
            if (baglantiOlusturSonucu.mesajKodu === "6")
            {
                const onayliChainId = baglantiOlusturSonucu.chain;
                const onayliCuzdanAdresi = baglantiOlusturSonucu.userWallet;
                const onayliImzaMesaji = baglantiOlusturSonucu.signMessage;
                const sessionId = baglantiOlusturSonucu.session_id;

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
                        sessionId,
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
                    sessionStorage.setItem("session_id", sessionId);
                    return true;
                }
                else
                {
                    mesajKutusunuGoster(baglantiOnaylaSonucu.mesajAciklamasi);
                    cuzdanBilgileriTemizlensinMi(true);
                    return false;
                }
            }
            else if(baglantiOlusturSonucu.mesajKodu === "14")
            {
                document.getElementById("p_cuzdanAdresi").innerText = baglantiOlusturSonucu.userWallet;
                document.getElementById("p_ag").innerText = baglantiOlusturSonucu.chain;
                sessionStorage.setItem("session_id", sessionId);
                return true;
            }
            else
            {
                mesajKutusunuGoster(baglantiOlusturSonucu.mesajAciklamasi);
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
        document.getElementById("panel_3_2").innerHTML = "";

        if (window.ethereum && window.ethereum.isMetaMask)
        {
            let cuzdanAdresleri = await ethereum.request({ method:'eth_accounts'});
            if(cuzdanAdresleri.length === 0)
            {
                cuzdanAdresleri = await ethereum.request({method: 'eth_requestAccounts'});
            }

            const seciliCuzdanAdresi = cuzdanAdresleri[0];
            const zincirKodu = await ethereum.request({ method: 'eth_chainId' });
            const userAgent = navigator.userAgent;
            const sessionId = sessionStorage.getItem("session_id");

            const dakListesiniGetir = await fetch('/app/dakListesiniGetir.php', 
            {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(
                {
                    seciliCuzdanAdresi,
                    zincirKodu,
                    userAgent,
                    sessionId
                })
            });

            if(dakListesiniGetir.ok === false)
            {
                const dakListesiniGetirJsonMesaji = await dakListesiniGetir.json().catch(() => ({ message: "Bilinmeyen hata" }));
                mesajKutusunuGoster("Json hatası: " + (dakListesiniGetirJsonMesaji.message || "Bilinmeyen hata"));
                return false;
            }

            const dakListesiniGetirSonucu= await dakListesiniGetir.json();
            switch (dakListesiniGetirSonucu.mesajKodu) 
            {
                case "8": if(await connectWallet()){break;}else{return;}
                case "9": if(await connectWallet()){break;}else{return;}
                case "10": if(await connectWallet()){break;}else{return;}
                case "15": break;
                case "17": break;
                case "34": if(await connectWallet()){break;}else{return;}
                default: mesajKutusunuGoster(dakListesiniGetirSonucu.mesajAciklamasi); return;
            }

            if(dakListesiniGetirSonucu.mesajKodu === "15")
            {
                const formattedPrice = sayiFormatlama2(dakListesiniGetirSonucu.dakOzellikleri[0].price, "gorunumeCevir");
                const formattedTotalCost = formattedPrice;

                document.getElementById("panel_3_2").innerHTML = 
                `
                    <div class="hisseMenusu_1">
                        <div class="dakDivi">
                            <div class="dakResmi">
                                <img id="dakNftResmi" src="${dakListesiniGetirSonucu.dakOzellikleri[0].imageUrl}">
                            </div>
                            <div class="dakDetaylari">
                                <div class="dakDetaylariSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>ID =</p>
                                    <p id="dak_idDegeri">${dakListesiniGetirSonucu.dakOzellikleri[0].id}</p>
                                </div>

                                <div class="dakDetaylariSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Level =</p>
                                    <p id="dak_levelDegeri">${dakListesiniGetirSonucu.dakOzellikleri[0].level}</p>
                                </div>

                                <div class="dakDetaylariSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Share Weight =</p>
                                    <p id="dak_hisseAgirlikDegeri">${dakListesiniGetirSonucu.dakOzellikleri[0].sw}</p>
                                </div>

                                <div class="dakDetaylariSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Share Weight Next Level =</p>
                                    <p id="dak_sonrakiLevelHisseAgirlikDegeri">${dakListesiniGetirSonucu.dakOzellikleri[0].swn}</p>
                                </div>
                            </div>

                            <div class="finansDivi">
                                <div class="finansDiviSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Processing Fee (AVXT) =</p>
                                    <p id="dak_islemUcreti">0</p>
                                </div>
                                <div class="finansDiviSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Level Up Cost (AVAX) =</p>
                                    <p id="dak_sonrakiLevelAvaxUcreti">${formattedPrice}</p>
                                </div>
                                <div class="finansDiviSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Total Discount (AVAX) =</p>
                                    <p id="dak_toplamAvaxIndirimi">0,0000</p>
                                </div>
                                <div class="finansDiviSatirlar">
                                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                                    <p>Total Cost (AVAX) =</p>
                                    <p id="dak_toplamAvaxUcreti">${formattedTotalCost}</p>
                                </div>
                            </div>

                            <div class="hisseMenusu_1_islemlerDivi">
                                <div class="hisseMenusu_1_islemlerDiviSecim">
                                    <input type="checkbox" id="chbHepsiniSec1">
                                    <label for="chbHepsiniSec1">Select All</label>
                                </div>
                                
                                <button id="btnLevelUp_${dakListesiniGetirSonucu.dakOzellikleri[0].id}">
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

                            <div class="hisseMenusu_2_filtreNesne" id="div_hisseMenusu_2_filtreNesne">
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
                // şuanda buna emin değilim, addEventListener a eklenen bu kodlar işleri tamamlandığında kaldırılmalıdır
                if(!document.getElementById("dakNftResmi").onclick)
                {
                    document.getElementById("dakNftResmi").onclick = function()
                    {
                        dakListesiMenusunuGoster();
                    };
                }

                if(!document.getElementById(`btnLevelUp_${dakListesiniGetirSonucu.dakOzellikleri[0].id}`).onclick)
                {
                    document.getElementById(`btnLevelUp_${dakListesiniGetirSonucu.dakOzellikleri[0].id}`).onclick = function()
                    {
                        dakGuncelle();
                    };
                }

                if(!document.getElementById("btnListele").onclick)
                {
                    document.getElementById("btnListele").onclick = function()
                    {
                        secenekleriYukle();
                    };
                }

                if(!document.getElementById("chbHepsiniSec1").onclick)
                {
                    document.getElementById("chbHepsiniSec1").onclick = function()
                    {
                        // burayı metodla yapmamın sebebi, farklı ekran boyutları için aynı işi yapan farklı id ye sahip elementler kullanmak zorunda kalırsam
                        // listeninHepsiniSec(this); metodunda belirttiğim elemente göre işlemi yaptırabilmek
                        listeninHepsiniSec(this);
                    };
                }

                if(document.getElementById("div_hisseMenusu_2_filtreNesne"))
                {
                    document.getElementById("div_hisseMenusu_2_filtreNesne").addEventListener("change", function(event){
                        if(event.target.type === "checkbox")
                        {
                            document.getElementById("div_hisseMenusu_2_filtreNesne").querySelectorAll("input[type='checkbox']").forEach(checkbox => {
                                if(checkbox !== event.target)
                                {
                                    checkbox.checked = false;
                                }
                            });
                        }
                    });
                }

                // DAK bilgisi yerleştirildi, sonraki satırlar çalışmasın
                return;
            }

            if(dakListesiniGetirSonucu.mesajKodu === "17")
            {
                const secim = await onayKutusunuGoster(dakListesiniGetirSonucu.mesajAciklamasi);
                if(secim === "olustur")
                {
                    const dakMintHazirla = await fetch('/app/dakMintHazirla.php', {
                        method: 'POST',
                        credentials: 'include',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(
                        {
                            seciliCuzdanAdresi,
                            zincirKodu,
                            userAgent,
                            sessionId,
                            nonceNewPurchase: dakListesiniGetirSonucu.nonceNewPurchase
                        })
                    });

                    if(dakMintHazirla.ok === false)
                    {
                        const dakMintHazirlaJsonMesaji = await dakMintHazirla.json().catch(() => ({ message: "Bilinmeyen hata" }));
                        mesajKutusunuGoster("Json hatası: " + (dakMintHazirlaJsonMesaji.message || "Bilinmeyen hata"));
                        return false;
                    }

                    const dakMintHazirlaSonucu = await dakMintHazirla.json();
                    switch (dakMintHazirlaSonucu.mesajKodu) 
                    {
                        case "49":break;                 
                        default: mesajKutusunuGoster(dakMintHazirlaSonucu.mesajAciklamasi); break;
                    }

                    if(dakMintHazirlaSonucu.mesajKodu === "49")
                    {
                        const dakMintBaslat = await fetch('/app/dakMintBaslat.php',
                        {
                            method: 'POST',
                            credentials: 'include',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(
                            {
                                seciliCuzdanAdresi: seciliCuzdanAdresi,
                                seciliZincir: zincirKodu,
                                userAgent: userAgent,
                                sessionId: sessionId,
                                nonceNewPurchase: dakMintHazirlaSonucu.nonceNewPurchase
                            })
                        });
    
                        if(dakMintBaslat.ok === false)
                        {
                            const dakMintBaslatJsonMesaji = await dakMintBaslat.json().catch(() => ({ message: "Bilinmeyen hata" }));
                            mesajKutusunuGoster("Json hatası: " + (dakMintBaslatJsonMesaji.message || "Bilinmeyen hata"));
                            return false;
                        }

                        // dak mint başlat ı bitiridim ama test etmedim, burada kaldım, devam edip tüm aşamaları uyumlu yap ve test et
            
                        const dakMintBaslatSonucu = await dakMintBaslat.json();
                        switch (dakMintBaslatSonucu.mesajKodu) 
                        {
                            case "25": break;
                            default: mesajKutusunuGoster(dakMintBaslatSonucu.mesajAciklamasi); break;
                        }
    
                        if(dakMintBaslatSonucu.mesajKodu === "25")
                        {
                            // İşlem Verilerini Hazırla
                            const transactionParameters = {
                                from: dakMintBaslatSonucu.userWallet,
                                to: dakMintBaslatSonucu.contractAddress,
                                value: dakMintBaslatSonucu.priceHex,
                                data: dakMintBaslatSonucu.data,
                            };

                            let txHashMint;
                            try 
                            {
                                txHashMint = await ethereum.request({
                                    method: 'eth_sendTransaction',
                                    params: [transactionParameters],
                                });
                            } 
                            catch (txHashMintHataMesaji) 
                            {
                                const dakMintDurdur = await fetch('/app/dakMintDurdur.php',
                                {
                                    method: 'POST',
                                    credentials: 'include',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify(
                                    {
                                        from: seciliCuzdanAdresi,
                                        chainId: zincirKodu,
                                        userAgent: userAgent,
                                        sessionId: sessionId,
                                        nonceNewPurchase: dakMintHazirlaSonucu.nonceNewPurchase
                                    })
                                });
            
                                if(dakMintDurdur.ok === false)
                                {
                                    const sorguJsonMesaji = await dakMintDurdur.json().catch(() => ({ message: "Bilinmeyen hata" }));
                                    mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                                    return false;
                                }
                    
                                const dakGuncelleDurdurSonucu = await dakMintDurdur.json();
                                switch (dakGuncelleDurdurSonucu.mesajKodu)
                                {
                                    case "21": mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); return;
                                    default: mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); return;
                                }
                            }

                            const dakMintTxDogrula = await fetch('/app/dakMintTxDogrula.php',
                            {
                                method: 'POST',
                                credentials: 'include',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(
                                {
                                    from: dakMintBaslatSonucu.userWallet,
                                    chainId: zincirKodu,
                                    userAgent: userAgent,
                                    sessionId: sessionId,
                                    nonceNewPurchase: dakMintHazirlaSonucu.nonceNewPurchase,
                                    txHash: txHashMint
                                })
                            });
        
                            if(dakMintTxDogrula.ok === false)
                            {
                                const dakMintTxDogrulasonMesaji = await dakMintTxDogrula.json().catch(() => ({ message: "Bilinmeyen hata" }));
                                mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                                return false;
                            }
    
                            const dakMintTxDogrulaSonucu = await dakMintTxDogrula.json();
                            switch (dakMintTxDogrulaSonucu.mesajKodu)
                            {
                                case "20": mesajKutusunuGoster(dakMintTxDogrulaSonucu.mesajAciklamasi); break;
                                default: mesajKutusunuGoster(dakMintTxDogrulaSonucu.mesajAciklamasi); return;
                            }
                        }
                        else
                        {
                            const dakGuncelleDurdur = await fetch('/app/dakMintDurdur.php',
                            {
                                method: 'POST',
                                credentials: 'include',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(
                                {
                                    from: seciliCuzdanAdresi,
                                    chainId: zincirKodu,
                                    userAgent: userAgent,
                                    sessionId: sessionId,
                                    nonceNewPurchase: dakMintHazirlaSonucu.nonceNewPurchase
                                })
                            });
        
                            if(dakGuncelleDurdur.ok === false)
                            {
                                const sorguJsonMesaji = await dakGuncelleDurdur.json().catch(() => ({ message: "Bilinmeyen hata" }));
                                mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                                return false;
                            }
                
                            const dakGuncelleDurdurSonucu = await dakGuncelleDurdur.json();
                            switch (dakGuncelleDurdurSonucu.mesajKodu)
                            {
                                case "21": mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); break;
                                default: mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); return;
                            }
                        }
                    }
                    else
                    {
                        const dakGuncelleDurdur = await fetch('/app/dakMintDurdur.php',
                        {
                            method: 'POST',
                            credentials: 'include',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(
                            {
                                from: seciliCuzdanAdresi,
                                chainId: zincirKodu,
                                userAgent: userAgent,
                                sessionId: sessionId,
                                nonceNewPurchase: dakMintHazirlaSonucu.nonceNewPurchase
                            })
                        });
    
                        if(dakGuncelleDurdur.ok === false)
                        {
                            const sorguJsonMesaji = await dakGuncelleDurdur.json().catch(() => ({ message: "Bilinmeyen hata" }));
                            mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                            return false;
                        }
            
                        const dakGuncelleDurdurSonucu = await dakGuncelleDurdur.json();
                        switch (dakGuncelleDurdurSonucu.mesajKodu)
                        {
                            case "21": mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); break;
                            default: mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); return;
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
            mesajKutusunuGoster("Metamask is not installed, please install Metamask.");
            cuzdanBilgileriTemizlensinMi(true);
            return false;
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

        const seciliCuzdanAdresi = document.getElementById("p_cuzdanAdresi").innerText;
        const zincirKodu = await ethereum.request({ method: 'eth_chainId' });
        const userAgent = navigator.userAgent;

        const dakListesiniGetir = await fetch('/app/dakListesiniGetir.php', 
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

        if(dakListesiniGetir.ok === false)
        {
            const dakListesiniGetirJsonMesaji = await dakListesiniGetir.json().catch(() => ({ message: "Bilinmeyen hata" }));
            mesajKutusunuGoster("Json hatası: " + (dakListesiniGetirJsonMesaji.message || "Bilinmeyen hata"));
            return false;
        }

        const dakListesiniGetirSonucu = await dakListesiniGetir.json();
        switch (dakListesiniGetirSonucu.mesajKodu) 
        {
            case "15": break;
            default: mesajKutusunuGoster(dakListesiniGetirSonucu.mesajAciklamasi); return;
        }

        if(dakListesiniGetirSonucu.dakListesi.length === 0)
        {
            mesajKutusunuGoster("No assets found for this wallet");
            return;
        }

        let menu = document.getElementById('divDakListesiMenusu');
        if(!menu)
        {
            menu = document.createElement("div");
            menu.id = "divDakListesiMenusu";
            menu.classList.add("dakListeMenusu");
            menu.innerHTML = 
            `
                <img src="images/list_left.png">
                <div class="dakListeMenusu_ustBaslik">
                    <button class="dakListeMenusu_ustBaslik_btnKapat" id="btnDakListeMenusunuKapat">
                        <img src="images/butonArkaPlanResmi.png" class="dakListesiButonArkaPlanResmi">
                        X
                    </button>
                </div>
                <div class="dakListeMenusu_liste" id="divDakListeMenusu_liste">

                </div>
                <div class="dakListeMenusu_butonlar">
                <button class="dakListeMenusu_butonlar_dakOlustur" id="btnDakOlustur">
                    <img src="images/butonArkaPlanResmi.png" class="dakListesiButonArkaPlanResmi">
                    New DAK Create
                </button>
                <button class="dakListeMenusu_butonlar_dakSec" id="btnDakSec">
                    <img src="images/butonArkaPlanResmi.png" class="dakListesiButonArkaPlanResmi">
                    Level Up
                </button>
            </div>
            `;

            document.body.appendChild(menu);
        }

        // NFT seçim butonu için olay ekle
        document.getElementById("btnDakSec").addEventListener("click", seciliDakBilgileriniGuncelle);

        // **Menü kapatma butonu**
        document.getElementById('btnDakListeMenusunuKapat').addEventListener("click", () => {
            menu.remove();
            document.removeEventListener("mousedown", menuDisaTiklaKapat); // Event Listener'ı kaldır
        });

        // **Menü dışına tıklayınca kapatma (sadece bir tane event listener ekleriz)**
        function menuDisaTiklaKapat(event) 
        {
            if (!menu.contains(event.target) && event.target.id !== "btnDakMenusu") 
            {
                menu.remove();
                document.removeEventListener("mousedown", menuDisaTiklaKapat); // Event Listener'ı kaldır
            }
        }

        // **Menü her açıldığında olay dinleyicisini ekleriz**
        document.addEventListener("mousedown", menuDisaTiklaKapat);

        const listeContainer = menu.querySelector("#divDakListeMenusu_liste");
        listeContainer.addEventListener("click", function(event)
        {
            const clickedImage = event.target.closest("img"); // En yakın img öğesini bul
            if (clickedImage && clickedImage.id.startsWith("dakNftResmi_")) 
            {
                const id = clickedImage.id.replace("dakNftResmi_", ""); // ID'yi al
                console.log(`Resim tıklandı! ID: ${id}`);
        
                // **İlgili checkbox'ı bul**
                const checkbox = document.getElementById(`checkbox_${id}`);
        
                if (checkbox) {
                    // **Eğer checkbox zaten seçiliyse, seçimini kaldır**
                    if (checkbox.checked) {
                        checkbox.checked = false;
                    } else {
                        // **Diğer tüm checkbox'ları sıfırla**
                        document.querySelectorAll(".chbDakListedekiNesne").forEach(chb => {
                            chb.checked = false;
                        });
        
                        // **Tıklanan checkbox'ı seçili yap**
                        checkbox.checked = true;
                    }
                }
            }
        });

        dakListesiniGetirSonucu.dakListesi.forEach(satir => 
        {
            const itemDiv = document.createElement("div");
            itemDiv.classList.add("dakAnaCerceve");

            itemDiv.innerHTML = `
                <div class="dakAnaCerceveResmi">
                    <img src="${satir.image_url}" id="dakNftResmi_${satir.id}">
                    <input type="checkbox" id="checkbox_${satir.id}" class="chbDakListedekiNesne">
                </div>
                <div class="dakAnaCerceveDetaySatirlari">
                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                    <p>ID = </p>
                    <p id="dak_AnaCerceveId">${satir.id}</p>
                </div>
                <div class="dakAnaCerceveDetaySatirlari">
                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                    <p>Level = </p>
                    <p id="dak_AnaCerceveLevel">${satir.dak_level}</p>
                </div>
                <div class="dakAnaCerceveDetaySatirlari">
                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                    <p>Share Weight = </p>
                    <p id="dak_AnaCerceveShareWeight">${satir.share_weight}</p>
                </div>
                <div class="dakAnaCerceveDetaySatirlari">
                    <img src="images/dakDetayArkaPlanResmi.png" class="dakDetayArkaPlanResmi">
                    <p>Share Next Weight = </p>
                    <p id="dak_AnaCerceveShareNextWeight">${satir.share_weight_next}</p>
                </div>
            `;

            // Oluşturulan satırı liste içine ekleyelim
            listeContainer.appendChild(itemDiv);
        });

        menu.style.display = "flex";
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

async function seciliDakBilgileriniGuncelle() 
{
    try 
    {
        const seciliDak = document.querySelector(".chbDakListedekiNesne:checked");

        if(!seciliDak)
        {
            mesajKutusunuGoster("Please select an item");
            return;
        }

        const seciliDakId = seciliDak.id.replace("checkbox_", "");
        const menu = document.getElementById("divDakListesiMenusu");
        if(menu)
        {
            menu.remove();
        }
        else
        {
            return;
        }

        const seciliCuzdanAdresi = document.getElementById("p_cuzdanAdresi").innerText;
        const zincirKodu = await ethereum.request({ method: 'eth_chainId' });
        const userAgent = navigator.userAgent;

        const seciliDakGetir = await fetch('/app/seciliDakGetir.php', 
        {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(
            {
                seciliCuzdanAdresi,
                zincirKodu,
                seciliDakId,
                userAgent
            })
        });

        if(seciliDakGetir.ok === false)
        {
            const seciliDakGetirJsonMesaji = await seciliDakGetir.json().catch(() => ({ message: "Bilinmeyen hata" }));
            mesajKutusunuGoster("Json hatası: " + (seciliDakGetirJsonMesaji.message || "Bilinmeyen hata"));
            return false;
        }

        const seciliDakGetirSonucu = await seciliDakGetir.json();
        switch (seciliDakGetirSonucu.mesajKodu) 
        {
            case "36": break;
            default: mesajKutusunuGoster(seciliDakGetirSonucu.mesajAciklamasi); return;
        }

        if(seciliDakGetirSonucu.dakOzellikleri.length === 0)
        {
            mesajKutusunuGoster("No assets found for this wallet");
            return;
        }

        document.getElementById("dakNftResmi").src = seciliDakGetirSonucu.dakOzellikleri[0].image_url;
        document.getElementById("dak_idDegeri").textContent  = seciliDakGetirSonucu.dakOzellikleri[0].id;
        document.getElementById("dak_levelDegeri").textContent  = seciliDakGetirSonucu.dakOzellikleri[0].dak_level;
        document.getElementById("dak_hisseAgirlikDegeri").textContent  = seciliDakGetirSonucu.dakOzellikleri[0].share_weight;
        document.getElementById("dak_sonrakiLevelHisseAgirlikDegeri").textContent  = seciliDakGetirSonucu.dakOzellikleri[0].share_weight_next;
        document.querySelector(".hisseMenusu_1_islemlerDivi").querySelector("button").id = `btnLevelUp_${seciliDakGetirSonucu.dakOzellikleri[0].id}`;
    } 
    catch (hataMesaji) 
    {
        mesajKutusunuGoster(hataMesaji.message);
    }
    finally
    {
        
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

async function transformationMergeMenusunuOlustur()
{
    try 
    {
        document.getElementById('btnDonustur').disabled = true;
        document.getElementById("panel_3_2").innerHTML = "";

        const cuzdanBagliMi = await connectWallet();
        if(cuzdanBagliMi == true)
        {

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

async function secenekleriYukle() 
{
    try 
    {
        const cuzdanBagliMi = await connectWallet();

        if(cuzdanBagliMi === true)
        {
            if (!document.getElementById("hisseMenusu_2_liste").dataset.eventAdded)
            {
                document.getElementById("hisseMenusu_2_liste").addEventListener("click", function(event) 
                {
                    const target = event.target;

                    // Eğer tıklanan eleman bir resimse (nesneResim sınıfına sahipse)
                    if (target.classList.contains("nesneResim")) 
                    {
                        const itemId = target.id;
                        const checkbox = document.getElementById(`gen2_${itemId}`);

                        if (checkbox) 
                        {
                            checkbox.checked = !checkbox.checked; // Seçiliyse kaldır, değilse seç
                            yeniListeyiGuncelle(); // Güncellenmiş listeyi tetikle
                        }
                    }
                });

                // **Event'in sadece bir kez eklenmesini sağlamak için işaret koyuyoruz**
                document.getElementById("hisseMenusu_2_liste").dataset.eventAdded = "true";
            }

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
            const zincirKodu = await ethereum.request({ method: 'eth_chainId' });
            const userAgent = navigator.userAgent;

            const sorgu = await fetch('/app/nesneListesiniGetir.php', 
            {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(
                {
                    seciliCuzdanAdresi,
                    zincirKodu,
                    userAgent,
                    seciliNesneler,
                    seciliNadirlikler
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

            if(sonuc.nesneListesi.length === 0)
            {
                mesajKutusunuGoster("No object suitable for discount was found according to the price list");
                return;
            }

            // **DOM manipülasyonunu optimize etmek için documentFragment kullanıyoruz!**
            const fragment = document.createDocumentFragment();

            sonuc.nesneListesi.forEach(satir => 
            {
                const tokenDegeri = parseFloat(satir.token1_degeri);
                const gen2Div = document.createElement("div");
                gen2Div.classList.add("divNesneSecim");
                gen2Div.innerHTML =
                `
                    <div class="divNesneSecimResim">
                        <img src="${satir.image}" class="nesneResim" id="${satir.item_id}">
                        <input type="checkbox" class="chbListedekiNesne" id="gen2_${satir.item_id}" data-value="${tokenDegeri.toFixed(5)}">
                    </div>
                    <div class="divNesneSecimSatirlar">
                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                        <p>ID</p>
                        <p id="gen2_id">${satir.item_id}</p>
                    </div>
                    <div class="divNesneSecimSatirlar">
                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                        <p>Level</p>
                        <p id="gen2_levelDegeri">${satir.level}</p>
                    </div>
                    <div class="divNesneSecimSatirlar">
                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                        <div class="divNesneSecimNadirlikResmi">
                            <img src="images/${satir.rarity}.png">
                        </div>
                        <div class="divNesneSecimNadirlikAdi">
                            <p id="gen2_nadirlik">${satir.rarity.toUpperCase()}</p>
                        </div>
                    </div>
                    <div class="divNesneSecimSatirlar">
                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="nesneSecimDivleriArkaPlanResmi">
                        <p>AVAX value =</p>
                        <p id="gen2_avaxDegeri">${tokenDegeri.toFixed(5)}</p>
                    </div>
                `;

                fragment.appendChild(gen2Div); // **Bütün öğeleri fragment içine ekledik**
            });

            // **Fragment içindeki tüm öğeleri DOM’a TEK SEFERDE ekle (büyük hız artışı sağlar!)**
            document.getElementById("hisseMenusu_2_liste").appendChild(fragment);
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

function yeniListeyiGuncelle()
{
    // hisseMenusu_2_liste divi içindeki .chbListedekiNesne leri dolaş, eğer seçili ise
    // dataset.value (data-value) değerlerini topla
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

    document.getElementById('dak_toplamAvaxIndirimi').innerHTML = toplamNesneAvaxDegeri.toFixed(5).replace(".", ",");
    const dakLevelYukseltmeAvaxDegeri = sayiFormatlama2(document.getElementById("dak_sonrakiLevelAvaxUcreti").textContent, "islemeCevir");
    if (dakLevelYukseltmeAvaxDegeri >= toplamNesneAvaxDegeri)
    {
        document.getElementById('dak_toplamAvaxUcreti').innerHTML = (dakLevelYukseltmeAvaxDegeri - toplamNesneAvaxDegeri).toFixed(5).replace(".", ",");
    } 
    else 
    {
        mesajKutusunuGoster("The total AVAX value of the selected objects is more than the DAK leveling cost. The list will be reset, please select one by one.");
        document.getElementById('chbHepsiniSec1').checked = false;
        secimKutulari.forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('dak_toplamAvaxUcreti').textContent = sayiFormatlama2(dakLevelYukseltmeAvaxDegeri, "gorunumeCevir");
        document.getElementById('dak_toplamAvaxIndirimi').textContent = "0,0000";
    }
}

async function dakGuncelle() 
{
    try 
    {
        if(window.ethereum && window.ethereum.isMetaMask)
        {
            let cuzdanAdresleri = await ethereum.request({ method:'eth_accounts'});
            if(cuzdanAdresleri.length === 0)
            {
                cuzdanAdresleri = await ethereum.request({method: 'eth_requestAccounts'});
            }

            const seciliCuzdanAdresi = cuzdanAdresleri[0];
            const seciliZincir = await ethereum.request({ method: 'eth_chainId' });
            const userAgent = navigator.userAgent;
            const sessionId = sessionStorage.getItem("session_id");
            const seciliDakId = document.querySelector(".hisseMenusu_1_islemlerDivi").querySelector("button").id;

            const seciliNesneler = [];
            document.getElementById("hisseMenusu_2_liste").querySelectorAll('input.chbListedekiNesne:checked').forEach(checkbox =>
            {
                seciliNesneler.push(checkbox.id);
            });

            const dakGuncelleHazirla = await fetch('/app/dakGuncelleHazirla.php', 
            {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(
                {
                    seciliCuzdanAdresi, 
                    seciliZincir,
                    userAgent,
                    sessionId,
                    seciliNesneler,
                    seciliDakId
                })
            });

            if(dakGuncelleHazirla.ok === false)
            {
                const errorData = await dakGuncelleHazirla.json().catch(() => ({ message: "Bilinmeyen hata" }));
                mesajKutusunuGoster("Sunucu hatası: " + (errorData.message || "Bilinmeyen hata"));
                cuzdanBilgileriTemizlensinMi(true);
                return false;
            }

            const dakGuncelleHazirlaSonucu = await dakGuncelleHazirla.json();
            switch (dakGuncelleHazirlaSonucu.mesajKodu) 
            {
                case "40": break;
                default: mesajKutusunuGoster(dakGuncelleHazirlaSonucu.mesajAciklamasi); return;
            }

            if(dakGuncelleHazirlaSonucu.mesajKodu == 40)
            {
                const secim = await onayKutusunuGoster(dakGuncelleHazirlaSonucu.mesajAciklamasi);
                if(secim === "olustur")
                {
                    const dakGuncelleBaslat = await fetch('/app/dakGuncelleBaslat.php',
                    {
                        method: 'POST',
                        credentials: 'include',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(
                        {
                            from: seciliCuzdanAdresi,
                            chainId: seciliZincir,
                            userAgent: userAgent,
                            dakId: seciliDakId,
                            sessionId: sessionId,
                            nonceNewPurchase: dakGuncelleHazirlaSonucu.nonceNewPurchase
                        })
                    });

                    if(dakGuncelleBaslat.ok === false)
                    {
                        const sorguJsonMesaji = await dakGuncelleBaslat.json().catch(() => ({ message: "Bilinmeyen hata" }));
                        mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                        return false;
                    }
        
                    const dakGuncelleBaslatSonucu = await dakGuncelleBaslat.json();
                    switch (dakGuncelleBaslatSonucu.mesajKodu) 
                    {
                        case "43": break;
                        default: mesajKutusunuGoster(dakGuncelleBaslatSonucu.mesajAciklamasi); return;
                    }

                    if(dakGuncelleBaslatSonucu.mesajKodu === "43")
                    {
                        // İşlem Verilerini Hazırla
                        const transactionParameters = {
                            from: dakGuncelleBaslatSonucu.userWallet,
                            to: dakGuncelleBaslatSonucu.contractAddress,
                            value: dakGuncelleBaslatSonucu.priceHex,
                            data: dakGuncelleBaslatSonucu.data,
                        };

                        let txHashUpdate;
                        try 
                        {
                            txHashUpdate = await ethereum.request({
                                method: 'eth_sendTransaction',
                                params: [transactionParameters],
                            });
                        } 
                        catch (txHashUpdateHataMesaji) 
                        {
                            const dakGuncelleDurdur = await fetch('/app/dakGuncelleDurdur.php',
                            {
                                method: 'POST',
                                credentials: 'include',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(
                                {
                                    from: seciliCuzdanAdresi,
                                    chainId: seciliZincir,
                                    userAgent: userAgent,
                                    sessionId: sessionId,
                                    nonceNewPurchase: dakGuncelleHazirlaSonucu.nonceNewPurchase
                                })
                            });
        
                            if(dakGuncelleDurdur.ok === false)
                            {
                                const sorguJsonMesaji = await dakGuncelleDurdur.json().catch(() => ({ message: "Bilinmeyen hata" }));
                                mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                                return false;
                            }
                
                            const dakGuncelleDurdurSonucu = await dakGuncelleDurdur.json();
                            switch (dakGuncelleDurdurSonucu.mesajKodu)
                            {
                                case "46": mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); return;
                                default: mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); return;
                            }
                        }

                        const dakGuncelleTxDogrula = await fetch('/app/dakGuncelleTxDogrula.php',
                        {
                            method: 'POST',
                            credentials: 'include',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(
                            {
                                from: dakGuncelleBaslatSonucu.userWallet,
                                chainId: seciliZincir,
                                userAgent: userAgent,
                                dakId: seciliDakId,
                                sessionId: sessionId,
                                nonceNewPurchase: dakGuncelleHazirlaSonucu.nonceNewPurchase,
                                txHash: txHashUpdate
                            })
                        });
    
                        if(dakGuncelleTxDogrula.ok === false)
                        {
                            const sorguJsonMesaji = await dakGuncelleTxDogrula.json().catch(() => ({ message: "Bilinmeyen hata" }));
                            mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                            return false;
                        }

                        const dakGuncelleTxDogrulaSonucu = await dakGuncelleTxDogrula.json();
                        switch (dakGuncelleTxDogrulaSonucu.mesajKodu)
                        {
                            case "45": mesajKutusunuGoster(dakGuncelleTxDogrulaSonucu.mesajAciklamasi); break;
                            default: mesajKutusunuGoster(dakGuncelleTxDogrulaSonucu.mesajAciklamasi); return;
                        }
                    }
                }
                else
                {
                    const dakGuncelleDurdur = await fetch('/app/dakGuncelleDurdur.php',
                    {
                        method: 'POST',
                        credentials: 'include',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(
                        {
                            from: seciliCuzdanAdresi,
                            chainId: seciliZincir,
                            userAgent: userAgent,
                            dakId: seciliDakId,
                            sessionId: sessionId,
                            nonceNewPurchase: dakGuncelleHazirlaSonucu.nonceNewPurchase
                        })
                    });

                    if(dakGuncelleDurdur.ok === false)
                    {
                        const sorguJsonMesaji = await dakGuncelleDurdur.json().catch(() => ({ message: "Bilinmeyen hata" }));
                        mesajKutusunuGoster("Json hatası: " + (sorguJsonMesaji.message || "Bilinmeyen hata"));
                        return false;
                    }
        
                    const dakGuncelleDurdurSonucu = await dakGuncelleDurdur.json();
                    switch (dakGuncelleDurdurSonucu.mesajKodu)
                    {
                        case "46": mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); return;
                        default: mesajKutusunuGoster(dakGuncelleDurdurSonucu.mesajAciklamasi); return;
                    }
                }
            }
        }
        else
        {
            mesajKutusunuGoster("Metamask yüklü değil! Lütfen Metamask cüzdanını yükleyin.");
            cuzdanBilgileriTemizlensinMi(true);
            return false;
        }
    } 
    catch (hataMesaji) 
    {
        mesajKutusunuGoster(hataMesaji.message);
        console.log(hataMesaji.stack);
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