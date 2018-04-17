const MascaraWrapper = require('./mascara')

const myApp = {
  network: {
    id: '42',
    label: 'Kovan test net.',
  },
  requireUnlocked: true,
  initApp: (web3, account) => {

    this.web3 = web3
    this.account = account

    // Run a example TX
    // Add button to trigger popup if <div id="testAppWrap"> exists.
    const testAppWrap = document.getElementById('testAppWrap')

    if (testAppWrap && testAppWrap.children.length === 0) {
      const b = document.createElement('button')
      b.setAttribute('id', 'app-action')
      b.innerHTML = 'Start my dapp call'
      testAppWrap.append(b)
      b.addEventListener('click', myApp.testCallTx)
    }
  },
  testCallTx: async () => {
    try {
      // E.g: Ask the user to send money to himself.
      const txHash = await this.web3.eth.sendTransaction({
        from: this.account,
        to: this.account,
        data: '',
      })
      window.console.log(txHash, 'testCallTx: success')
    }
    catch (error) {
      window.console.log('Catch errors from testCallTx()')
      if (error.message.includes('User denied transaction')) {
        // @todo handle User rejected your transaction.
        window.console.log('User denied transaction', error.message.includes('User denied transaction'))
      }
    }
  },
}

window.addEventListener('load', () => {
  /**
  *  Initialize Web3
  *
  *  @param string
  *    Dom Id where the icon, actions and feedback are appended to.
  *
  *  @param settings App
  *    Function to run when web3 is ready (and unlocked if required)
  *    {
  *       network: {
  *          // Ethereum Network Id. use '*' bypass network validation.
  *         id: '42',
  *         label: 'Kovan test net.',
  *       },
  *       requireUnlocked: true,
  *       initApp: (web3, account) => {
  *          // Your code....
  *          // If requireUnlocked is false account might be null.
  *       }
  *     },
  */
  new MascaraWrapper('web3status', myApp)

})
