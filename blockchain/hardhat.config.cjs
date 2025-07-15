require("@nomiclabs/hardhat-ethers");
require("dotenv").config();

console.log("Private Key Panjang:", process.env.GANACHE_PRIVATE_KEY.length);

module.exports = {
  solidity: "0.8.20",
  networks: {
    ganache: {
      url: "http://127.0.0.1:7545",
      accounts: [process.env.GANACHE_PRIVATE_KEY]
    }
  },
  paths: {
    sources: "./contracts",
    tests: "./test",
    cache: "./cache",
    artifacts: "./artifacts"
  }
};
