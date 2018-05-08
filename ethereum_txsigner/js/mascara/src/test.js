



// Init App if Web3 is available and
//   account is unlocked on EXPECTED networkId
const myAccountApp = {
  requireAccount: true,
  networkId: drupalSettings.ethereum.network.id,
  run: (web3, account = null) => {
    window.console.log('SUCCESS myAccountApp')
  },
}

window.addEventListener('web3Ready', () => {
  window.web3Runner.runWhenReady(myAccountApp)
})



