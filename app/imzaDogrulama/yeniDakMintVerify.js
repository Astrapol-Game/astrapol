const { ethers } = require("ethers");

// Komut satırından argümanları al
const args = process.argv.slice(2);
if (args.length < 3) {
    console.error("Usage: node verifySignature.js <message> <signature> <expectedAddress>");
    process.exit(1);
}

const message = args[0]; // İmzalanan mesaj
const signature = args[1]; // Üretilen imza
const expectedAddress = args[2]; // İmzanın kime ait olması gerektiği

try {
    // Mesajı arrayify ederek doğru formatta kullan
    const hashedMessage = ethers.utils.arrayify(message);

    // İmzadan adresi geri al
    const recoveredAddress = ethers.utils.verifyMessage(hashedMessage, signature);

    // Adresleri karşılaştır
    if (recoveredAddress.toLowerCase() === expectedAddress.toLowerCase()) {
        console.log("Signature is valid and matches the expected address.");
    } else {
        console.log("Signature is invalid or does not match the expected address.");
    }
} catch (error) {
    console.error("Error during verification:", error.message);
    process.exit(1);
}