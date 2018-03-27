# Ethereum Transaction Signer


Provide transaction signers to let users and admins interact with Ethereum using their browser. 

Currently only web3 browsers and Metamasks "mascara" are supported. 

The **Metamask** default provider is using web3 if provided by the browser (Metamask Browser extension or Mist browser) or fall back on [Mascara](https://github.com/MetaMask/mascara).

Browser support for mascara depends on [serviceworkers](https://caniuse.com/#feat=serviceworkers).