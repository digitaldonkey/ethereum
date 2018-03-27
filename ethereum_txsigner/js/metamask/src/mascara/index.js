const metamask = require('metamascara')
const Web3 = require('web3')
const Web3StatusIndicator = require('../web3status')


// Holds the popup window reference.
let mascaraPopup = null
const MASCARA_URL = 'https://wallet.metamask.io'

module.exports = class MascaraWrapper {

  /**
   *
   * @param contextId
   *   Dom Id for the Web3 user feedback.
   * @param callback
   *   Function to be called when Web3 is present and accounts unlocked.
   */
  constructor(contextId, callback) {
    window.console.log('Hello ')

    this.wrapper = document.getElementById(contextId)
    this.web3 = null
    this.account = null
    this.provider = 'unknown'
    this.status = new Web3StatusIndicator(this.wrapper)
    this.logToDom('Initialize Mascara wrapper.')


    if (typeof web3 !== 'undefined') {
      this.logToDom('Using web3 from browser.')
      // eslint-disable-next-line no-undef
      this.web3 = new Web3(web3.currentProvider)
    }
    else if (this.browserSupportsMascara()) {
      this.logToDom('Initialize Mascara.')
      this.loadProvider()
    }
    else {
      this.logToDom('Your browser does not support web3.')
      window.console.log('Note that firefox does not support ServiceWorkers in private browsing/incognito mode.')
    }

  }

  async isAccountUnlocked() {
    try {
      const accounts = await this.web3.eth.getAccounts()
      this.logToDom('Account is unlocked.')
      this.account = accounts[0]
    }
    catch (err) {
      this.logToDom('There was an error getting accounts.')
      window.console.log(err)
    }
  }

  logToDom(message) {
    let logger = document.getElementById('mascara-logger')
    window.console.log(message)
    if (!logger) {
      const div = document.createElement('span')
      div.setAttribute('id', 'mascara-logger')
      this.wrapper.appendChild(div)
      logger = document.getElementById('mascara-logger')
    }
    logger.textContent = message
  }

  /**
   * openMascara popup window
  */
  openMascara() {
    if (mascaraPopup === null || mascaraPopup.closed) {
      const popupOpts = 'toolbar=0,scrollbars=1,resizable=0,status=0,titlebar=0,location=0,top= 30,left= 30,width=350,height=750'
      mascaraPopup = window.open(MASCARA_URL, 'mascara', popupOpts)
    }
    if (mascaraPopup.focus) {
      mascaraPopup.focus()
    }
  }

  /**
  * Load a Mascara in-page provider.
  */
  loadProvider() {
    const ethereumProvider = metamask.createDefaultProvider()
    this.provider = 'mascara'
    this.web3 = new Web3(ethereumProvider)
  }

  /**
  *  Test if browser can support Mascara.
  */
  browserSupportsMascara() {
    return ('serviceWorker' in navigator)
  }

  /**
   * Update ui Icon.
  */
  updateStatus() {
    if (this.provider !== 'mascara') {
      if (this.web3.currentProvider.isMetaMask === true) {
        this.provider = 'metamask'
        // @todo How to detect Mist and Others?
      }
    }

    this.status.update({
      isLocked: false,
      provider: this.provider,
    })
  }


  // Getters and setters allow to act on changing properties.
  get web3() {
    return this._web3
  }

  set web3(val) {
    this._web3 = val
    this.onPropertyChanged('web3', val)
  }

  get account() {
    return this._account
  }

  set account(val) {
    this._account = val
    this.onPropertyChanged('account', val)
  }

  /**
   * Act on property changes of the above setters.
   */
  onPropertyChanged(propName, val) {
    if (val) {
      // window.console.log('Property changed ' + propName, val)
      this[propName + 'Changed']()
    }
  }


  /**
   * web3Changed()
   */
  web3Changed() {
    window.console.log('web3', this.web3)
    this.updateStatus()
    this.isAccountUnlocked()
  }

  /**
   * accountChanged()
   */
  accountChanged() {
    window.console.log('account', this.account)
  }
}
