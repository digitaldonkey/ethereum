# Metamask Mascara wrapper

This is an experiment to approach a general way to implement Mascara along with other TX signers. 

It's work in progress. Tested in in Chrome and Firefox. Safari (I'm testing with the future version 11.1) still has some issues. 

Gol is to mplement transaction signers (web3 providers) and fall back to [Metamask Mascara](https://github.com/MetaMask/mascara/) if user is not using a web3 enabled browser.

**Why Mascara wrapper?**

* App launcher with consistent state handling. Try to unify the web3 experience. Allow Mascara to coexist nicely with other TX signers to reach a broader audience. 
* Unify web3 version: Web3 was up to now delivered with Metamask browser extension of Ethereum Mist browser. But its now [recommended](https://github.com/MetaMask/faq/blob/master/detecting_metamask.md#deprecation-of-global-web3js) to let the developer decide which web3 implementation and version should be used. Mascara wrapper uses the web3 specified in `mascara/package.json`.
* Providing a consistent app environment and ensure the right Ethereum network is used. Providing [Network validation](https://github.com/MetaMask/faq/blob/master/DEVELOPERS.md#construction_worker-network-check).
* Allow to experiment with different UX approaches. 
* Provide a unified way to 
	* handle user reject TX
	* Register net Ethereum accounts
	* Ask to unlock accounts
	* Network id check
* Optimize, minimize JS. Enhance browser support.

**How Mascara wrapper looks?**

A tiny UI showing the web3 account and network status. It should look like one of the following lines.

![Mascara wrapper ui](https://raw.githubusercontent.com/digitaldonkey/mascara_wrapper/master/doc-assets/mascara-wrapper-ui.gif)
 
**What it does?**
 
![Maskara wrapper current state](https://raw.githubusercontent.com/digitaldonkey/mascara_wrapper/master/doc-assets/mascara-wrapper-UML.gif)

**How to launch my app?**

Short version here. Please check out the [example code](https://github.com/digitaldonkey/ethereum/blob/feature-GlobalTransactionSigner/ethereum_txsigner/js/mascara/src/index.js). 

```
const myAccountApp = {
  requireAccount: true,
  networkId: drupalSettings.ethereum.network.id,
  run: (web3, account = null) => {
    window.console.log('SUCCESS myAccountApp', [web3, account])
  },
}

window.addEventListener('load', () => {
  const net = {id: '42', name: 'kovan test net'};
  window.web3Runner = new MascaraWrapper('web3status', net)
})

window.addEventListener('web3Ready', () => {
  window.console.log('web3Ready')
  window.web3Runner.runWhenReady(myAccountApp)
})
```

## Development

**Build**

This job creates a minified version.

```
npm install --global browserify
cd js/mascara
npm install
npm run build
```

**Develop**

```
npm install --global watchify
cd js/mascara
npm install
npm run dev
```

**Reinitialize Mascara**

**This will destroy you wallet!** Make sure you backed up your seed phrase before. 

You might force to recreate Mascara by unregistering the Service Worker and deleting data from any metamask domain.

In Firefox `about:debugging#workers`
In Chrome dev tools go to Application>Service Workers

**Supported Browsers**

* Any web3 browser (including Metamask extension)
* Websockets supporting browsers by Chrome, Chromium, Firefox (Suporting Metamask Mascara)
*  In next IOS and OSX Safari will support websockets. Looking ahead. 

