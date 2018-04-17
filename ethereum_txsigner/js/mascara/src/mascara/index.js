const metamask = require('metamascara')
const Web3 = require('web3')
const Web3StatusIndicator = require('../web3status')

// Holds the popup window reference.
let mascaraPopup = null

// Url for Mascara wallet.
const MASCARA_URL = 'https://wallet.metamask.io'

/*!
 * Mascara app launcher.
 *
 * @license MIT
 */
module.exports = class MascaraWrapper {

  // EXAMPLE init config see mascara/src/index.js

  /**
   *
   * @param contextId
   *   Dom Id for the Web3 user feedback.
   * @param config
   *   Config. See above.
   */
  constructor(contextId, config) {
    this.config = config
    this.wrapper = this.getWrapper(contextId)
    this._web3 = null
    this._account = null
    this._network = 'unknown'
    this.provider = 'unknown'
    this.status = new Web3StatusIndicator(this.wrapper)

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
   * Main wrapper for Mascara
   *
   * Create inside <div id="mascaraStatus"> inside <div id="contextId">.
   *
   * @param contextId
   * @returns {Node}
   */
  getWrapper(contextId) {
    const wrapper = document.getElementById('mascaraStatus')
    if (!wrapper) {
      const div = document.createElement('span')
      div.setAttribute('id', 'mascara-status')
      return document.getElementById(contextId).appendChild(div)
    }
    return wrapper
  }


  /**
   *
   * @returns void
   */
  async isExpectedNetwork() {

    // Don't validate if expected network is: any.
    if (this.config.network.id === '*') return

    try {
      const netId = await this.web3.eth.net.getId()
      if (netId.toString() === this.config.network.id) {
        this.network = 'ok'
        this.logToDom('Network ID is ok.')
      }
      else {
        this.network = 'error'
        this.logToDom(`Network ID is invalid. Expected ${this.config.network.label} (id: ${this.config.network.id})`, true)
      }
    }
    catch (err) {
      this.logToDom('There was an error getting networks.', true)
      window.console.log(err)
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

    window.console.log(typeof(window.mist) !== "undefined", 'typeof(mist)')


    if (this.provider !== 'mascara') {
      // Detect Metamask
      if (this.web3.currentProvider.isMetaMask === true) {
        this.provider = 'metamask'
      }
      // Detect Mist browser
      // https://ethereum.stackexchange.com/questions/11046/how-to-check-if-the-browser-is-mist
      if (typeof(window.mist) !== 'undefined') {
        this.provider = 'mist'
      }
    }



    this.status.update({
      isLocked: !(this._account && this._account.length),
      provider: this.provider,
      network: this.network,
    })
    this.tryInitDapp()
  }

  /**
   * Getters and setters allow to act on changing properties.
   * @returns {*}
   */
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

  getAccountStatus() {
    return !this.config.requireUnlocked ||
        (this.config.requireUnlocked && this.account && this.account.length === 42)
  }

  get network() {
    return this._network
  }
  set network(val) {
    this._network = val
    this.onPropertyChanged('network', val)
  }

  getNetworkStatus() {
    window.console.log('getNetworkStatus()', this.network === 'unknown')

    return this.network === 'ok' ||
          (this.network === 'unknown' && this.config.network.id === '*')
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
    window.console.log('web3Changed()')
    this.updateStatus()
    this.isExpectedNetwork()
    this.isAccountUnlocked()
  }

  /**
   * web3Changed()
   */
  networkChanged() {
    window.console.log('networkChanged()')
    this.updateStatus()
  }

  /**
   * tryInitDapp()
   */
  tryInitDapp() {
    window.console.log('Try initialize dapp.')
    if (this.getNetworkStatus() && this.getAccountStatus()) {

      // @todo Mascara TX trigger is bound to window object.
      // In node_modules/metamascara/mascara.js the popup trigger is bound to window object
      // window.addEventListener('click', maybeTriggerPopup)
      // For now we just add an empty button to visualize that we can now trigger the TX call.
      // It also only works once. So if you didn't sign on right away,
      // you need to reload the page :?
      this.logToDom('Dapp init success.')
      this.config.initApp(this.web3, this.account)
    }
  }

  /**
   * accountChanged()
   */
  accountChanged() {
    if (this.account) {
      this.logToDom('Account is unlocked.')}
    this.updateStatus()
  }


  /**
   * This is required to open Mascara.
   *
   * Currently it is not possible to trigger Metamask open,
   * But to open a Mascara TX popup you require a click by the user.
   *
   * @todo This should be smartly combined.
   *
   * @param bindEvent
   * @param text
   */
  addActionButton(bindEvent = false, text = 'Open Mascara') {
    const button = document.createElement('button')
    button.setAttribute('id', 'mascara-action')
    button.innerHTML = text
    this.wrapper.insertBefore(button, this.wrapper.childNodes[2])
    if (bindEvent) {
      button.addEventListener('click', this.openMascara)
    }
  }

}
