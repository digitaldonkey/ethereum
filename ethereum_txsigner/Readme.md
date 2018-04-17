# Ethereum Transaction Signer

Provide transaction signers to let users and admins interact with Ethereum using their browser. 

Currently there is only one TX signer: **Metamask Mascara** default provider is using web3 if provided by the browser (Metamask Browser extension or Mist browser) or fall back on [Mascara](https://github.com/MetaMask/mascara).

Read more in the [Mascara Readme](https://github.com/digitaldonkey/ethereum/blob/feature-GlobalTransactionSigner/ethereum_txsigner/js/mascara/Readme.md).

Note: Browser support for Mascara depends on [serviceworkers](https://caniuse.com/#feat=serviceworkers). Currently Firefox and Chrom(ium), but Safari should work with next release.
