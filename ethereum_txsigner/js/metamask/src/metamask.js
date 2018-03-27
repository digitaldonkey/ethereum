const MascaraWrapper = require('./mascara')


window.addEventListener('load', () => {

  function initMyApp() {
    console.log('Your App is no ready to interact with Web3')
  }

  /**
  *  Initialize Web3
  *
  *  @param string
   *    Dom Id where the icon, actions and feedback are appended to.
   *
  *  @param callback
  *    Function to run when web3 is ready and unlocked.
  */
  new MascaraWrapper('web3status', initMyApp)
})


// function setupButtons (web3) {
//   const accountButton = document.getElementById('action-button-1')
//
//   accountButton.addEventListener('click', async () => {
//     const accounts = await web3.eth.getAccounts()
//     window.METAMASK_ACCOUNT = accounts[0] || 'locked'
//     logToDom(accounts.length ? accounts[0] : 'LOCKED or undefined', 'account')
//   })
//
//
//   const txButton = document.getElementById('action-button-2')
//   txButton.addEventListener('click', async () => {
//
//     logToDom(window.METAMASK_ACCOUNT, 'account')
//
//     if (!window.METAMASK_ACCOUNT || window.METAMASK_ACCOUNT === 'locked') {
//       openMascara()
//     } else {
//       const txHash = await web3.eth.sendTransaction({
//         from: window.METAMASK_ACCOUNT,
//         to: window.METAMASK_ACCOUNT,
//         data: '',
//       })
//       logToDom(txHash, 'cb-value')
//     }
//   })
// }
