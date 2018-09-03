const MascaraWrapper = require('./mascara')

//
// Examples how to init a app with ethereum_txsigner
//
// Include thd following to start you App.
//
// window.addEventListener('web3Ready', () => {
//   window.web3Runner.runWhenReady(myWhateverApp)
// })
//
// Where myWhateverApp is one of the following:
//
// Run if Web3 is available on ANY network
// const myAnyNetworkApp = {
//   requireAccount: false,
//   networkId: '*',
//   run: (web3, account = null) => {
//     window.console.log('SUCCESS myAnyNetworkApp', [web3, account])
//   },
// }
//
// Run if Web3 is available on EXPECTED networkId:
//
// const myAccountlessApp = {
//   requireAccount: false,
//   networkId: drupalSettings.ethereum_txsigner.network.id,
//   run: (web3, account = null) => {
//     window.console.log('SUCCESS myAccountlessApp', [web3, account])
//   },
// }
//
// Run if Web3 is available and account is unlocked on ANY network:
//
// const myAccountOnAnyNetwork = {
//   requireAccount: true,
//   networkId: '*',
//   run: (web3, account = null) => {
//     window.console.log('SUCCESS myAccountApp', [web3, account])
//   },
// }
//
// Run if Web3 is available and account is unlocked on EXPECTED networkId:
//
// const myAccountApp = {
//   requireAccount: true,
//   networkId: drupalSettings.ethereum_txsigner.network.id,
//   run: (web3, account = null) => {
//     window.console.log('SUCCESS myAccountApp', [web3, account])
//   },
// }

window.addEventListener('load', () => {
  /**
   *  Initialize Web3
   *
   *  @param string
   *    Dom Id where the icon, actions and feedback are appended to.
   *
   *  @param settings App.settings
   *    {
   *      requireAccount: false, // Bool. Need the Ethereum user address to init your app?
   *      network: drupalSettings.ethereum.network, // Ethereum Network ID or "*" for any.
   *    }
   */
  window.web3Runner = new MascaraWrapper('web3status', drupalSettings.ethereum_txsigner.network)
})
