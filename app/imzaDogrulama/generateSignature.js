const ethers = require("ethers");

async function createTestSignature() {
    const wallet = ethers.Wallet.createRandom();
    const message = "Please sign this message to verify your wallet.";
    const signature = await wallet.signMessage(message);
    console.log("Message:", message);
    console.log("Signature:", signature);
    console.log("Wallet Address:", wallet.address);
}

createTestSignature();
