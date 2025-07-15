// scripts/deploy.js
import hardhat from "hardhat";
const { ethers } = hardhat;

async function main() {
  const Arisan = await ethers.getContractFactory("Arisan");
  const arisan = await Arisan.deploy(); // ✅ TANPA parameter!

  await arisan.deployed();

  console.log(`✅ Contract Arisan deployed to: ${arisan.address}`);
}

main().catch((error) => {
  console.error("❌ Deployment error:", error);
  process.exitCode = 1;
});
