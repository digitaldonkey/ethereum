**Reinitialize Mascara**

**This will destroy you wallet!** Make sure you backed up your seed phrase before. 

You might force to recreate Mascara by unregistering the Service Worker and deleting data from any metamask domain.

In Firefox `about:debugging#workers`
In Chrome dev tools go to Application>Service Workers



Build

```
npm install --global browserify
cd ethereum/ethereum_txsigner/js/metamask
npm install
npm run build
```

Develop

```
npm install --global watchify
cd ethereum/ethereum_txsigner/js/metamask
npm install
npm run dev
```

Currently only testing in 
*/ethereum/ethereum_txsigner/js/metamask/index.html*

Supported by Chrome, Chromium, Firefox.

Safari should be working too but Metamask [Mascara](https://github.com/MetaMask/mascara/) has still issues. 

Todo for merge 
* Automatically load when TX signer is enabled
* Fallback for incapable browsers

