const { ethers } = require("ethers");

// Komut satırından argümanları al
const args = process.argv.slice(2);
if (args.length < 2) {
    console.error("Usage: node sign.js <message> <privateKey>");
    process.exit(1);
}

const message = args[0]; // Hashlenmiş mesaj
const privateKey = args[1]; // Özel anahtar

// Özel anahtarın doğru formatta olup olmadığını kontrol et
if (!privateKey.startsWith("0x") || privateKey.length !== 66) {
    console.error("Invalid private key format. Must be a 66-character string starting with '0x'.");
    process.exit(1);
}

try {
    // Özel anahtar ile bir imza oluştur
    const wallet = new ethers.Wallet(privateKey);

    // Mesajı imzala
    const signature = wallet.signMessage(ethers.utils.arrayify(message));

    // İmzayı konsola yazdır
    signature.then((sig) => console.log(sig));
} catch (error) {
    console.error("Error during signing:", error.message);
    process.exit(1);
}