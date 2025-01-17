const ethers = require("ethers");

function verifySignature(message, signature, walletAddress) 
{
    // İmzanın doğru formatta olup olmadığını kontrol edin
    if (!/^0x[a-fA-F0-9]{130}$/.test(signature)) 
    {
        throw new Error("Invalid signature format. Expected a valid hex string.");
    }

    try 
    {
        // Mesajı hashle
        const messageHash = ethers.utils.hashMessage(message);
        // İmzayı kullanarak adresi kurtar
        const recoveredAddress = ethers.utils.recoverAddress(
            ethers.utils.arrayify(messageHash), // Mesajı baytlara çevir
            signature // İmzayı kullan
        );

        // Kurtarılan adresi karşılaştır
        return recoveredAddress.toLowerCase() === walletAddress.toLowerCase();
    } 
    catch (error) 
    {
        // Hata durumunda, detaylı bir hata mesajı döndür
        throw new Error(`Signature verification failed: ${error.message}`);
    }
}

// Komut satırından argümanları al
const args = process.argv.slice(2); // İlk iki argüman (node ve script yolu) hariç

if (args.length < 3) 
{
    console.error("Error: Missing arguments. Expected: <message> <signature> <walletAddress>");
    process.exit(1);
}

const [message, signature, walletAddress] = args;

// Doğrulama işlemi
try 
{
    const isValid = verifySignature(message, signature, walletAddress);
    console.log(isValid ? "true" : "false");
} 
catch (error) 
{
    console.error("Error:", error.message);
    process.exit(1);
}
