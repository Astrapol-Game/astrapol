let cuzdanBagliMi = false;

        async function cuzdaniBagliMiBagla() 
        {
            if(cuzdanBagliMi == false)
            {
                // Metamask'ın yüklü olup olmadığını kontrol et
                if (typeof window.ethereum !== 'undefined' && window.ethereum.isMetaMask) 
                {
                    try 
                    {
                        // Cüzdanı bağla ve hesabı al
                        const accounts = await ethereum.request({ method: "eth_requestAccounts" });
                        const account = accounts[0];

                        // Adresi sayfada göster
                        document.getElementById("p_cuzdanAdresi").innerText = account;
                        document.getElementById("p_ag").innerText = "Avalanche";
                        document.getElementById("btn_cuzdanBagliMiBagla").innerText = "Disconnect";

                        cuzdanBagliMi = true;
                    } 
                    catch (error) 
                    {
                        alert("Cüzdan bağlantısı reddedildi veya bir hata oluştu.");
                    }
                } 
                else 
                {
                    // Metamask yüklü değilse uyarı göster
                    alert("Metamask yüklü değil! Lütfen Metamask cüzdanını yükleyin.");
                }
            }
            else
            {
                document.getElementById("p_cuzdanAdresi").innerText = "";
                document.getElementById("p_ag").innerText = "";
                document.getElementById("btn_cuzdanBagliMiBagla").innerText = "Connect";
                cuzdanBagliMi = false;
            }
        }

        function secenekleriYukle() 
        {
            if(cuzdanBagliMi == true)
            {
                document.getElementById('chbHepsiniSec_1').checked = false;
                document.getElementById('chbHepsiniSec_2').checked = false;

                const seciliNesne = document.getElementById("hangiNesne").value;
                const seciliNadirlik = document.getElementById("hangiNadirlik").value;
                const cuzdanAdresi = document.getElementById("p_cuzdanAdresi").innerText;

                const xhr = new XMLHttpRequest();
                xhr.open("GET", `/app/gen2ListesiniGetir.php?seciliNesne=${seciliNesne}&seciliNadirlik=${seciliNadirlik}&seciliCuzdanAdresi=${cuzdanAdresi}`, true);
                xhr.onreadystatechange = function()
                {
                    if (xhr.readyState == 4 && xhr.status == 200)
                    {
                        document.getElementById("veriyeGoreOlusanListe").innerHTML = "";
                        document.getElementById("yeniListe").innerHTML = "";
                        document.getElementById('secilenlerinSayisiniGosterenEtiket').innerHTML = "Selected Objects = 0";
                        document.getElementById('donuseceklerinSayisiniGosterenEtiket').innerHTML = "After Transformation = 0";
                        document.getElementById('donuseceklerinToplamXpGosterenEtiket_1').innerHTML = "Recovered XP = 0";
                        document.getElementById('donuseceklerinToplamXpGosterenEtiket_2').innerHTML = "Recovered XP = 0";
                        const yanit = JSON.parse(xhr.responseText);
                        if(yanit.length == 0)
                        {
                            mesajKutusunuGoster("No assets found for this wallet");
                        }
                        else
                        {
                            yanit.forEach(satir => 
                            {
                                document.getElementById("veriyeGoreOlusanListe").innerHTML += 
                                `
                                    <div class="panel_3_1_gen2SecimKutusu">
                                        <div class="panel_3_1_gen2SecimKutusu_1_resim_id_rarity_sembol">
                                            <div class="gen2SecimKutusu_1_resim">
                                                <img src="${satir.image}" onclick="ilgiliSecimKutusunuSec(${satir.item_id})">
                                                <input type="checkbox" class="chbListedekiNesne" id="${satir.item_id}" data-value="${satir.xp}">
                                            </div>
                                            <div class="gen2SecimKutusu_1_id">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                ID ${satir.item_id}
                                            </div>
                                            <div class="gen2SecimKutusu_1_rarity_sembol">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <div class="gen2SecimKutusu_1_rarity">
                                                    ${satir.rarity.toUpperCase()}
                                                </div>
                                                <div class="gen2SecimKutusu_1_sembol">
                                                    <img src="images/${satir.rarity}.png">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel_3_1_gen2SecimKutusu_1_ozellikler">
                                            <div class="ozellikler_name_secim">
                                                <div class="ozellikler_name">
                                                    ${satir.name}
                                                </div>
                                            </div>
                                            <div class="ozellikler_level_xp">
                                                <div class="ozellikler_level">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    Level ${satir.level}
                                                </div>
                                                <div class="ozellikler_xp">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    XP ${satir.xp}
                                                </div>
                                            </div>
                                            <div class="ozellikler_dna">
                                                <div class="dna_hp">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    <p>HP</p>
                                                    <p>${satir.skill_1_id !== null ? `${satir.skill_1_id}` : '0' }</p>   
                                                </div>
                                                <div class="dna_def">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    <p>DEF</p>
                                                    <p>${satir.skill_2_id !== null ? `${satir.skill_2_id}` : '0' }</p>
                                                </div>
                                                <div class="dna_atk">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    <p>ATK</p>
                                                    <p>${satir.skill_3_id !== null ? `${satir.skill_3_id}` : '0' }</p>
                                                </div>
                                                <div class="dna_speed">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    <p>SPD</p>
                                                    <p>${satir.skill_4_id !== null ? `${satir.skill_4_id}` : '0' }</p>
                                                </div>
                                            </div>
                                            <div class="ozellikler_rna">
                                                <div class="rna_detaylar">
                                                    <div class="rna_genus">
                                                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                        GENUS
                                                    </div>
                                                    <div class="rna_code">
                                                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                        CODE
                                                    </div>
                                                    <div class="rna_talent">
                                                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                        TALENT
                                                    </div>
                                                </div>
                                                <div class="rna_sembol">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });

                            var secilenler = document.querySelectorAll('#veriyeGoreOlusanListe .chbListedekiNesne');
                            secilenler.forEach(function(checkbox) 
                            {
                                checkbox.addEventListener('change', yeniListeyiGuncelle);
                            });
                        }
                    }
                };

                xhr.onerror = function () 
                {
                    mesajKutusunuGoster("An error occurred while trying to connect to the server. Please check your connection.");
                };

                xhr.send();
            }
            else
            {
                mesajKutusunuGoster("Connect Wallet");
            }
        }
       
        function ilgiliSecimKutusunuSec(id) 
        {
            // Checkbox'ların durumunu güncelleyen fonksiyon
            const checkbox = document.getElementById(`${id}`);
            checkbox.checked = !checkbox.checked; // Checkbox'ın checked durumunu değiştir
            document.getElementById('chbHepsiniSec_1').checked = false;
            document.getElementById('chbHepsiniSec_2').checked = false;
            yeniListeyiGuncelle(); // Seçimleri güncelle
        }
    
        function yeniListeyiGuncelle()
        {
            const secimKutulari = document.querySelectorAll('#veriyeGoreOlusanListe .chbListedekiNesne');

            var secilenSayisi = 0;
            let toplamXp = 0;

            secimKutulari.forEach(function(checkbox)
            {
                if(checkbox.checked)
                {
                    secilenSayisi++;
                    toplamXp += parseInt(checkbox.dataset.value || 0);
                }
            });

            document.getElementById("yeniListe").innerHTML = "";
            document.getElementById('secilenlerinSayisiniGosterenEtiket').innerHTML = "Selected Objects = " + secilenSayisi;
            document.getElementById('donuseceklerinSayisiniGosterenEtiket').innerHTML = "After Transformation = 0";
            document.getElementById('donuseceklerinToplamXpGosterenEtiket_1').innerHTML = "Recovered XP = " + toplamXp;
            document.getElementById('donuseceklerinToplamXpGosterenEtiket_2').innerHTML = "Recovered XP = " + toplamXp;

            const seciliNesne = document.getElementById("hangiNesne").value;
            const seciliNadirlik = document.getElementById("hangiNadirlik").value;

            if(seciliNesne == 'gen2')
            {
                if(seciliNadirlik == 'scientist')
                {
                    var yeniGen2Sayisi = 0;
                    if(secilenSayisi >= 1)
                    {
                        yeniGen2Sayisi = secilenSayisi;

                        document.getElementById('donuseceklerinSayisiniGosterenEtiket').innerHTML = "After Transformation = " + yeniGen2Sayisi;
                        for(say = 1; say <= yeniGen2Sayisi; say++)
                        {
                            document.getElementById("yeniListe").innerHTML += 
                            `
                                <div class="panel_3_1_gen2SecimKutusu">
                                    <div class="panel_3_1_gen2SecimKutusu_1_resim_id_rarity_sembol">
                                        <div class="gen2SecimKutusu_1_resim">
                                            <img src="images/yeniGen2Olusum2.png">
                                        </div>
                                        <div class="gen2SecimKutusu_1_id">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            ID
                                        </div>
                                        <div class="gen2SecimKutusu_1_rarity_sembol">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            <div class="gen2SecimKutusu_1_rarity">
                                                COMMON
                                            </div>
                                            <div class="gen2SecimKutusu_1_sembol">
                                                <img src="images/common.png">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel_3_1_gen2SecimKutusu_1_ozellikler">
                                        <div class="ozellikler_name_secim">
                                            <div class="ozellikler_name">
                                            
                                            </div>
                                        </div>
                                        <div class="ozellikler_level_xp">
                                            <div class="ozellikler_level">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                Level 1
                                            </div>
                                            <div class="ozellikler_xp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                XP 0
                                            </div>
                                        </div>
                                        <div class="ozellikler_dna">
                                            <div class="dna_hp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>HP</p>
                                                <p>0</p>   
                                            </div>
                                            <div class="dna_def">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>DEF</p>
                                                <p>0</p>
                                            </div>
                                            <div class="dna_atk">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>ATK</p>
                                                <p>0</p>
                                            </div>
                                            <div class="dna_speed">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>SPD</p>
                                                <p>0</p>
                                            </div>
                                        </div>
                                        <div class="ozellikler_rna">
                                            <div class="rna_detaylar">
                                                <div class="rna_genus">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    GENUS
                                                </div>
                                                <div class="rna_code">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    CODE
                                                </div>
                                                <div class="rna_talent">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    TALENT
                                                </div>
                                            </div>
                                            <div class="rna_sembol">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        toplamXp -= parseInt(secimKutulari[secimKutulari.length].dataset.value || 0);
                    }
                }

                if(seciliNadirlik == 'common')
                {
                    var yeniGen2Sayisi = 0;
                    if(secilenSayisi >= 2)
                    {
                        for(say = 1; say <= secilenSayisi; say++)
                        {
                            if(say % 2 === 0)
                            {
                                yeniGen2Sayisi++;
                            }
                        }

                        document.getElementById('donuseceklerinSayisiniGosterenEtiket').innerHTML = "After Transformation = " + yeniGen2Sayisi;
                        for(say = 1; say <= yeniGen2Sayisi; say++)
                        {
                            document.getElementById("yeniListe").innerHTML += 
                            `
                                <div class="panel_3_1_gen2SecimKutusu">
                                    <div class="panel_3_1_gen2SecimKutusu_1_resim_id_rarity_sembol">
                                        <div class="gen2SecimKutusu_1_resim">
                                            <img src="images/yeniGen2Olusum2.png">
                                        </div>
                                        <div class="gen2SecimKutusu_1_id">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            ID
                                        </div>
                                        <div class="gen2SecimKutusu_1_rarity_sembol">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            <div class="gen2SecimKutusu_1_rarity">
                                                UNCOMMON
                                            </div>
                                            <div class="gen2SecimKutusu_1_sembol">
                                                <img src="images/uncommon.png">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel_3_1_gen2SecimKutusu_1_ozellikler">
                                        <div class="ozellikler_name_secim">
                                            <div class="ozellikler_name">
                                                
                                            </div>
                                        </div>
                                        <div class="ozellikler_level_xp">
                                            <div class="ozellikler_level">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                Level 1
                                            </div>
                                            <div class="ozellikler_xp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                XP 0
                                            </div>
                                        </div>
                                        <div class="ozellikler_dna">
                                            <div class="dna_hp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>HP</p>
                                                <p>0</p>   
                                            </div>
                                            <div class="dna_def">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>DEF</p>
                                                <p>0</p>
                                            </div>
                                            <div class="dna_atk">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>ATK</p>
                                                <p>0</p>
                                            </div>
                                            <div class="dna_speed">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>SPD</p>
                                                <p>0</p>
                                            </div>
                                        </div>
                                        <div class="ozellikler_rna">
                                            <div class="rna_detaylar">
                                                <div class="rna_genus">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    GENUS
                                                </div>
                                                <div class="rna_code">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    CODE
                                                </div>
                                                <div class="rna_talent">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    TALENT
                                                </div>
                                            </div>
                                            <div class="rna_sembol">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        if(document.getElementById('chbHepsiniSec_1').checked == true)
                        {
                            // bu koşulda sadece chbHepsiniSec_1 elementini kullandım, chbHepsiniSec_1 elementi değeri ne olursa olsun chbHepsiniSec_2 de
                            // aynı olması, listeninHepsiniSec işleminde sağlanmıştı
                            var kalanMiktar = secilenSayisi % 2;
                            if(kalanMiktar > 0)
                            {
                                const listedekiToplamNesneSayisi = secimKutulari.length;
                                for(say = 1; say <= kalanMiktar; say++)
                                {
                                    secimKutulari[listedekiToplamNesneSayisi - say].checked = false;
                                }

                                document.getElementById('secilenlerinSayisiniGosterenEtiket').innerHTML = "Selected Objects = " + (secilenSayisi - kalanMiktar);
                            }
                        }
                    }
                }

                if(seciliNadirlik == 'uncommon')
                {
                    var yeniGen2Sayisi = 0;
                    if(secilenSayisi >= 4)
                    {
                        for(say = 1; say <= secilenSayisi; say++)
                        {
                            if(say % 4 === 0)
                            {
                                yeniGen2Sayisi++;
                            }
                        }

                        document.getElementById('donuseceklerinSayisiniGosterenEtiket').innerHTML = "After Transformation = " + yeniGen2Sayisi;
                        for(say = 1; say <= yeniGen2Sayisi; say++)
                        {
                            document.getElementById("yeniListe").innerHTML += 
                            `
                                <div class="panel_3_1_gen2SecimKutusu">
                                    <div class="panel_3_1_gen2SecimKutusu_1_resim_id_rarity_sembol">
                                        <div class="gen2SecimKutusu_1_resim">
                                            <img src="images/yeniGen2Olusum2.png">
                                        </div>
                                        <div class="gen2SecimKutusu_1_id">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            ID
                                        </div>
                                        <div class="gen2SecimKutusu_1_rarity_sembol">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            <div class="gen2SecimKutusu_1_rarity">
                                                RARE
                                            </div>
                                            <div class="gen2SecimKutusu_1_sembol">
                                                <img src="images/rare.png">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel_3_1_gen2SecimKutusu_1_ozellikler">
                                        <div class="ozellikler_name_secim">
                                            <div class="ozellikler_name">
                                                
                                            </div>
                                        </div>
                                        <div class="ozellikler_level_xp">
                                            <div class="ozellikler_level">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                Level 1
                                            </div>
                                            <div class="ozellikler_xp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                XP 0
                                            </div>
                                        </div>
                                        <div class="ozellikler_dna">
                                            <div class="dna_hp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>HP</p>
                                                <p>65</p>   
                                            </div>
                                            <div class="dna_def">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>DEF</p>
                                                <p>20</p>
                                            </div>
                                            <div class="dna_atk">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>ATK</p>
                                                <p>20</p>
                                            </div>
                                            <div class="dna_speed">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>SPD</p>
                                                <p>10</p>
                                            </div>
                                        </div>
                                        <div class="ozellikler_rna">
                                            <div class="rna_detaylar">
                                                <div class="rna_genus">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    GENUS
                                                </div>
                                                <div class="rna_code">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    CODE
                                                </div>
                                                <div class="rna_talent">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    TALENT
                                                </div>
                                            </div>
                                            <div class="rna_sembol">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        if(document.getElementById('chbHepsiniSec_1').checked == true)
                        {
                            // bu koşulda sadece chbHepsiniSec_1 elementini kullandım, chbHepsiniSec_1 elementi değeri ne olursa olsun chbHepsiniSec_2 de
                            // aynı olması, listeninHepsiniSec işleminde sağlanmıştı
                            var kalanMiktar = secilenSayisi % 4;
                            if(kalanMiktar > 0)
                            {
                                const listedekiToplamNesneSayisi = secimKutulari.length;
                                for(say = 1; say <= kalanMiktar; say++)
                                {
                                    secimKutulari[listedekiToplamNesneSayisi - say].checked = false;
                                }

                                document.getElementById('secilenlerinSayisiniGosterenEtiket').innerHTML = "Selected Objects = " + (secilenSayisi - kalanMiktar);
                            }
                        }
                    }
                }

                if(seciliNadirlik == 'rare')
                {
                    var yeniGen2Sayisi = 0;
                    if(secilenSayisi >= 5)
                    {
                        for(say = 1; say <= secilenSayisi; say++)
                        {
                            if(say % 5 === 0)
                            {
                                yeniGen2Sayisi++;
                            }
                        }

                        document.getElementById('donuseceklerinSayisiniGosterenEtiket').innerHTML = "After Transformation = " + yeniGen2Sayisi;
                        for(say = 1; say <= yeniGen2Sayisi; say++)
                        {
                            document.getElementById("yeniListe").innerHTML += 
                            `
                                <div class="panel_3_1_gen2SecimKutusu">
                                    <div class="panel_3_1_gen2SecimKutusu_1_resim_id_rarity_sembol">
                                        <div class="gen2SecimKutusu_1_resim">
                                            <img src="images/yeniGen2Olusum2.png">
                                        </div>
                                        <div class="gen2SecimKutusu_1_id">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            ID
                                        </div>
                                        <div class="gen2SecimKutusu_1_rarity_sembol">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            <div class="gen2SecimKutusu_1_rarity">
                                                EPIC
                                            </div>
                                            <div class="gen2SecimKutusu_1_sembol">
                                                <img src="images/epic.png">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel_3_1_gen2SecimKutusu_1_ozellikler">
                                        <div class="ozellikler_name_secim">
                                            <div class="ozellikler_name">
                                            
                                            </div>
                                        </div>
                                        <div class="ozellikler_level_xp">
                                            <div class="ozellikler_level">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                Level 1
                                            </div>
                                            <div class="ozellikler_xp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                XP 0
                                            </div>
                                        </div>
                                        <div class="ozellikler_dna">
                                            <div class="dna_hp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>HP</p>
                                                <p>78</p>   
                                            </div>
                                            <div class="dna_def">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>DEF</p>
                                                <p>24</p>
                                            </div>
                                            <div class="dna_atk">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>ATK</p>
                                                <p>24</p>
                                            </div>
                                            <div class="dna_speed">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>SPD</p>
                                                <p>12</p>
                                            </div>
                                        </div>
                                        <div class="ozellikler_rna">
                                            <div class="rna_detaylar">
                                                <div class="rna_genus">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    GENUS
                                                </div>
                                                <div class="rna_code">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    CODE
                                                </div>
                                                <div class="rna_talent">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    TALENT
                                                </div>
                                            </div>
                                            <div class="rna_sembol">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        if(document.getElementById('chbHepsiniSec_1').checked == true)
                        {
                            // bu koşulda sadece chbHepsiniSec_1 elementini kullandım, chbHepsiniSec_1 elementi değeri ne olursa olsun chbHepsiniSec_2 de
                            // aynı olması, listeninHepsiniSec işleminde sağlanmıştı
                            var kalanMiktar = secilenSayisi % 5;
                            if(kalanMiktar > 0)
                            {
                                const listedekiToplamNesneSayisi = secimKutulari.length;
                                for(say = 1; say <= kalanMiktar; say++)
                                {
                                    secimKutulari[listedekiToplamNesneSayisi - say].checked = false;
                                    toplamXp -= parseInt(secimKutulari[listedekiToplamNesneSayisi - say].dataset.value || 0);
                                }

                                document.getElementById('secilenlerinSayisiniGosterenEtiket').innerHTML = "Selected Objects = " + (secilenSayisi - kalanMiktar);
                                document.getElementById('donuseceklerinToplamXpGosterenEtiket_1').innerHTML = "Recovered XP = " + toplamXp;
                            }
                        }
                    }
                }
            
                if(seciliNadirlik == 'epic')
                {
                    var yeniGen2Sayisi = 0;
                    if(secilenSayisi >= 4)
                    {
                        for(say = 1; say <= secilenSayisi; say++)
                        {
                            if(say % 4 === 0)
                            {
                                yeniGen2Sayisi++;
                            }
                        }

                        document.getElementById('donuseceklerinSayisiniGosterenEtiket').innerHTML = "After Transformation = " + yeniGen2Sayisi;
                        for(say = 1; say <= yeniGen2Sayisi; say++)
                        {
                            document.getElementById("yeniListe").innerHTML += 
                            `
                                <div class="panel_3_1_gen2SecimKutusu">
                                    <div class="panel_3_1_gen2SecimKutusu_1_resim_id_rarity_sembol">
                                        <div class="gen2SecimKutusu_1_resim">
                                            <img src="images/yeniGen2Olusum2.png">
                                        </div>
                                        <div class="gen2SecimKutusu_1_id">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            ID
                                        </div>
                                        <div class="gen2SecimKutusu_1_rarity_sembol">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            <div class="gen2SecimKutusu_1_rarity">
                                                LEGEND
                                            </div>
                                            <div class="gen2SecimKutusu_1_sembol">
                                                <img src="images/legend.png">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel_3_1_gen2SecimKutusu_1_ozellikler">
                                        <div class="ozellikler_name_secim">
                                            <div class="ozellikler_name">
                                            
                                            </div>
                                        </div>
                                        <div class="ozellikler_level_xp">
                                            <div class="ozellikler_level">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                Level 1
                                            </div>
                                            <div class="ozellikler_xp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                XP 0
                                            </div>
                                        </div>
                                        <div class="ozellikler_dna">
                                            <div class="dna_hp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>HP</p>
                                                <p>94</p>   
                                            </div>
                                            <div class="dna_def">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>DEF</p>
                                                <p>29</p>
                                            </div>
                                            <div class="dna_atk">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>ATK</p>
                                                <p>29</p>
                                            </div>
                                            <div class="dna_speed">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>SPD</p>
                                                <p>14</p>
                                            </div>
                                        </div>
                                        <div class="ozellikler_rna">
                                            <div class="rna_detaylar">
                                                <div class="rna_genus">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    GENUS
                                                </div>
                                                <div class="rna_code">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    CODE
                                                </div>
                                                <div class="rna_talent">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    TALENT
                                                </div>
                                            </div>
                                            <div class="rna_sembol">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        if(document.getElementById('chbHepsiniSec_1').checked == true)
                        {
                            // bu koşulda sadece chbHepsiniSec_1 elementini kullandım, chbHepsiniSec_1 elementi değeri ne olursa olsun chbHepsiniSec_2 de
                            // aynı olması, listeninHepsiniSec işleminde sağlanmıştı
                            var kalanMiktar = secilenSayisi % 4;
                            if(kalanMiktar > 0)
                            {
                                const listedekiToplamNesneSayisi = secimKutulari.length;
                                for(say = 1; say <= kalanMiktar; say++)
                                {
                                    secimKutulari[listedekiToplamNesneSayisi - say].checked = false;
                                }

                                document.getElementById('secilenlerinSayisiniGosterenEtiket').innerHTML = "Selected Objects = " + (secilenSayisi - kalanMiktar);
                            }
                        }
                    }
                }

                if(seciliNadirlik == 'legend')
                {
                    var yeniGen2Sayisi = 0;
                    if(secilenSayisi >= 1)
                    {
                        yeniGen2Sayisi = secilenSayisi;

                        document.getElementById('donuseceklerinSayisiniGosterenEtiket').innerHTML = "After Transformation = " + yeniGen2Sayisi;
                        for(say = 1; say <= yeniGen2Sayisi; say++)
                        {
                            document.getElementById("yeniListe").innerHTML += 
                            `
                                <div class="panel_3_1_gen2SecimKutusu">
                                    <div class="panel_3_1_gen2SecimKutusu_1_resim_id_rarity_sembol">
                                        <div class="gen2SecimKutusu_1_resim">
                                            <img src="images/yeniGen2Olusum2.png">
                                        </div>
                                        <div class="gen2SecimKutusu_1_id">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            ID
                                        </div>
                                        <div class="gen2SecimKutusu_1_rarity_sembol">
                                            <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            <div class="gen2SecimKutusu_1_rarity">
                                                LEGEND
                                            </div>
                                            <div class="gen2SecimKutusu_1_sembol">
                                                <img src="images/legend.png">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel_3_1_gen2SecimKutusu_1_ozellikler">
                                        <div class="ozellikler_name_secim">
                                            <div class="ozellikler_name">
                                            
                                            </div>
                                        </div>
                                        <div class="ozellikler_level_xp">
                                            <div class="ozellikler_level">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                Level 1
                                            </div>
                                            <div class="ozellikler_xp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                XP 0
                                            </div>
                                        </div>
                                        <div class="ozellikler_dna">
                                            <div class="dna_hp">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>HP</p>
                                                <p>94</p>   
                                            </div>
                                            <div class="dna_def">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>DEF</p>
                                                <p>29</p>
                                            </div>
                                            <div class="dna_atk">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>ATK</p>
                                                <p>29</p>
                                            </div>
                                            <div class="dna_speed">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                <p>SPD</p>
                                                <p>14</p>
                                            </div>
                                        </div>
                                        <div class="ozellikler_rna">
                                            <div class="rna_detaylar">
                                                <div class="rna_genus">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    GENUS
                                                </div>
                                                <div class="rna_code">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    CODE
                                                </div>
                                                <div class="rna_talent">
                                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                                    TALENT
                                                </div>
                                            </div>
                                            <div class="rna_sembol">
                                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        toplamXp -= parseInt(secimKutulari[secimKutulari.length].dataset.value || 0);
                    }
                }

                const etiket_1 = document.getElementById('donuseceklerinToplamXpGosterenEtiket_1');
                const etiket_2 = document.getElementById('donuseceklerinToplamXpGosterenEtiket_2');
                const etiket1Gorunur = window.getComputedStyle(etiket_1).display !== 'none';
                const etiket2Gorunur = window.getComputedStyle(etiket_2).display !== 'none';

                if (etiket1Gorunur) 
                {
                    etiket1.innerHTML = "Recovered XP = " + toplamXp;
                } 
                else if (etiket2Gorunur) 
                {
                    etiket2.innerHTML = "Recovered XP = " + toplamXp;
                }
            }
        }
    
        function listeninHepsiniSec(chbKaynak)
        {
            const secimKutulari = document.querySelectorAll('#veriyeGoreOlusanListe .chbListedekiNesne');
            secimKutulari.forEach(checkbox => checkbox.checked = chbKaynak.checked);
            if(chbKaynak.id == "chbHepsiniSec_1")
            {
                document.getElementById("chbHepsiniSec_2").checked = chbKaynak.checked;
                yeniListeyiGuncelle();
            }
            else
            {
                document.getElementById("chbHepsiniSec_1").checked = chbKaynak.checked;
                yeniListeyiGuncelle();
            }
        }

        function listeyiDoldurTest()
        {
            document.getElementById("veriyeGoreOlusanListe").innerHTML = "";
            const seciliNesne = document.getElementById("hangiNesne").value;
            const seciliNadirlik = document.getElementById("hangiNadirlik").value;
            var level = 5000;
            for(say = 1; say <= 39; say++)
            {
                document.getElementById("veriyeGoreOlusanListe").innerHTML += 
                `
                    <div class="panel_3_1_gen2SecimKutusu">
                        <div class="panel_3_1_gen2SecimKutusu_1_resim_id_rarity_sembol">
                            <div class="gen2SecimKutusu_1_resim">
                                <img src="images/1.png" onclick="ilgiliSecimKutusunuSec(${say + 10})">
                                <input type="checkbox" class="chbListedekiNesne" id="${say + 10}" data-value="${level}">
                            </div>
                            <div class="gen2SecimKutusu_1_id">
                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                ID ${say + 10}
                            </div>
                            <div class="gen2SecimKutusu_1_rarity_sembol">
                                <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                <div class="gen2SecimKutusu_1_rarity">
                                    ${seciliNadirlik.toUpperCase()}
                                </div>
                                <div class="gen2SecimKutusu_1_sembol">
                                    <img src="images/${seciliNadirlik}.png">
                                </div>
                            </div>
                        </div>
                        <div class="panel_3_1_gen2SecimKutusu_1_ozellikler">
                            <div class="ozellikler_name_secim">
                                <div class="ozellikler_name">
                                    ${"test_" + say}
                                </div>
                            </div>
                            <div class="ozellikler_level_xp">
                                <div class="ozellikler_level">
                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                    Level 50
                                </div>
                                <div class="ozellikler_xp">
                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                    XP 500
                                </div>
                            </div>
                            <div class="ozellikler_dna">
                                <div class="dna_hp">
                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                    <p>HP</p>
                                    <p>999</p>   
                                </div>
                                <div class="dna_def">
                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                    <p>DEF</p>
                                    <p>999</p>
                                </div>
                                <div class="dna_atk">
                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                    <p>ATK</p>
                                    <p>999</p>
                                </div>
                                <div class="dna_speed">
                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                    <p>SPD</p>
                                    <p>999</p>
                                </div>
                            </div>
                            <div class="ozellikler_rna">
                                <div class="rna_detaylar">
                                    <div class="rna_genus">
                                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                        GENUS
                                    </div>
                                    <div class="rna_code">
                                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                        CODE
                                    </div>
                                    <div class="rna_talent">
                                        <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                        TALENT
                                    </div>
                                </div>
                                <div class="rna_sembol">
                                    <img src="images/gen2YatayDetayArkaPlanResmi.png" class="gen2YatayDetayArkaPlanResmi">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        function mesajKutusunuGoster(mesaj)
        {
            document.getElementById("divMesajKutusuIcerigi").textContent = mesaj;
            document.getElementById("divMesajKutusu").style.display = "flex";
        }

        function mesajKutusuKapat()
        {
            document.getElementById("divMesajKutusu").style.display = "none";
        }

        document.addEventListener('DOMContentLoaded', () =>
        {
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
                cuzdaniBagliMiBagla();
            });

            document.getElementById('btn_filtreyeGoreListele').addEventListener('click', () =>
            {
                //listeyiDoldurTest();
                secenekleriYukle();
            });

            document.getElementById('btn_mesajKutusunuKapat').addEventListener('click', () =>
            {
                mesajKutusuKapat();
            });

            document.getElementById('chbHepsiniSec_1').addEventListener('click', function () 
            {
                listeninHepsiniSec(this);
            });

            document.getElementById('chbHepsiniSec_2').addEventListener('click', function () 
            {
                listeninHepsiniSec(this);
            });

            document.getElementById('btnDakMenusu').addEventListener('click', function() 
            {
                alert('Butona tıklandı!');
            });
        });