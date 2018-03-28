const metamask = require('metamascara')
const Web3 = require('web3')
const Web3StatusIndicator = require('../web3status')


// Holds the popup window reference.
let mascaraPopup = null

// Url for Mascara wallet.
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
    this.wrapper = document.getElementById(contextId)
    this.web3 = null
    this._account = null
    this.provider = 'unknown'
    this.status = new Web3StatusIndicator(this.wrapper)
    this.callback = callback
    // Messages set with logToDom will be displayed for this time [ms] if net set permanent.
    this.MESSAGE_DISPLAY_TIMEOUT = 800
    this.logTimer = null

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
      this.logToDom('Your browser does not support web3.', true)
      window.console.log('Note that firefox does not support ServiceWorkers in private browsing/incognito mode.')
    }

  }

  /**
   *
   * @returns void
   */
  async isAccountUnlocked() {
    try {
      const accounts = await this.web3.eth.getAccounts()

      // @todo this does not work in Safari 11.1

      if (accounts.length) {
        this.account = accounts[0]
      }
      else {
        if (this.provider === 'mascara') {
          this.addActionButton(true)
          // Can't distinguish if account is locked or not existing.
          // @see https://github.com/MetaMask/mascara/issues/20
          this.logToDom('Can not find account. Is it locked? You may create one opening Mascara.', true)
        }
        else {
          this.logToDom('Can not find account. Is it locked?', true)
        }
      }
    }
    catch (err) {
      this.logToDom('There was an error getting accounts.', true)
      window.console.log(err)
    }
  }

  /**
   * Display user feedback
   *
   * @param message string
   *   Text to display.
   * @param permanent bool
   *   if true message will disappear after MESSAGE_DISPLAY_TIMEOUT ms.
   */
  logToDom(message, permanent = false) {
    let logger = document.getElementById('mascara-logger')
    window.console.log(message)
    if (!logger) {
      const div = document.createElement('span')
      div.setAttribute('id', 'mascara-logger')
      this.wrapper.appendChild(div)
      logger = document.getElementById('mascara-logger')
    }
    window.clearTimeout(this.logTimer)
    logger.textContent = message
    if (!permanent) {
      this.logTimer = window.setTimeout(() => {
        this.logToDom('', true)
      }, this.MESSAGE_DISPLAY_TIMEOUT)
    }
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
      isLocked: !(this._account && this._account.length),
      provider: this.provider,
    })
  }

  /**
   * Getters and setters allow to act on changing properties.
   * @returns {*}
   */
  get web3() {
    return this._web3
  }
  /**
   * Getters and setters allow to act on changing properties.
   * @returns {*}
   */
  set web3(val) {
    this._web3 = val
    this.onPropertyChanged('web3', val)
  }
  /**
   * Getters and setters allow to act on changing properties.
   * @returns {*}
   */
  get account() {
    return this._account
  }
  /**
   * Getters and setters allow to act on changing properties.
   * @returns void
   */
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
      this[`${propName}Changed`]()
    }
  }

  /**
   * web3Changed()
   */
  web3Changed() {
    this.updateStatus()
    this.isAccountUnlocked()
  }

  /**
   * accountChanged()
   */
  accountChanged() {
    if (this.account) {
      this.logToDom('Account is unlocked.')
      // @todo Mascara TX trigger is bound to window object.
      // In node_modules/metamascara/mascara.js the popup trigger is bound to window object
      // window.addEventListener('click', maybeTriggerPopup)
      // For now we just add an empty button to visualize that we can now trigger the TX call.
      // It also only works once. So if you didn't sign on right away,
      // you need to reload the page :?
      this.addActionButton(false, 'Call your TX')
      this.callback(this.account, this.web3)
    }
    this.updateStatus()
  }

  addActionButton(bindEvent = false, text = 'Open Mascara') {
    const button = document.createElement('button')
    button.setAttribute('id', 'mascara-action')
    button.innerHTML = text
    this.wrapper.insertBefore(button, this.wrapper.childNodes[1])
    if (bindEvent) {
      button.addEventListener('click', this.openMascara)
    }
  }

}
