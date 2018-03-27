**Reinitialize Mascara**

You might force to recreate Mascara by unregistering the Service Worker.

In Firefox `about:debugging#workers`
In Chrome dev tools go to Application>Service Workers


Build

```
npm install --global browserify
cd ethereum/ethereum_txsigner/js/metamask
npm run build
```

Develop

```
npm install --global watchify
cd ethereum/ethereum_txsigner/js/metamask
npm run dev
```

Currently only testing in 
*/ethereum/ethereum_txsigner/js/metamask/index.html*


Todo for merge 
* Automatically load when TX signer is enabled

