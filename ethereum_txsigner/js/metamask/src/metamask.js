const MascaraWrapper = require('./mascara')

window.addEventListener('load', () => {

  // @todo We might get the networkId as well.

  async function initMyApp(account, web3) {
    const visualize = {
      account,
      web3,
    }
    window.console.log('Your App is no ready to interact with Web3', visualize)

    // Run a example TX to trigger popup.
    const txHash = await web3.eth.sendTransaction({
      from: account,
      to: account,
      data: '',
    })
    window.console.log(txHash, 'cb-value')
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
