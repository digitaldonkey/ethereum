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

  /**
   * constructor(contextId, expectedNetwork)
   *
   * @param contextId
   *    Dom Id, to w attach the wrapperto.
   *
   * @param expectedNetwork
   *    Ethereum Network Id
   *
   * @returns {*}
   */
  constructor(contextId, expectedNetwork) {

    // Limit to one instance.
    if (window.web3Runner) {
      return window.web3Runner
    }

    this.expectedNetwork = expectedNetwork
    this.networkState = 'unknown'
    this._clientNetworkId = null

    this.apps = []
    this.debugMode = false
    this.wrapper = this.getWrapper(contextId)
    this._web3 = null
    this._account = 'undefined'
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
      this.logIt('Note that firefox does not support ServiceWorkers in private browsing/incognito mode.')
    }
    window.web3Runner = this
    window.dispatchEvent(new Event('web3Ready'))
  }

  /**
   * @param config
   */
  runWhenReady(config) {
    this.apps.push(config)
    this.tryInitDapps()
  }

  /**
   * checkAccount.
   *
   * This function polls for account changes.
   *
   * @see https://github.com/MetaMask/faq/blob/master/DEVELOPERS.md#ear-listening-for-selected-account-changes
   *
   * @returns {Promise<void>}
   */
  async checkAccount() {

    // Refresh every POLL_INTERVAL [ms].
    const POLL_INTERVAL = 800

    const accounts = await this.web3.eth.getAccounts()

    if (accounts && this.account !== accounts[0]) {
      this.account = accounts[0]
      this.updateStatus()
    }
    await this.waitFor(POLL_INTERVAL)
    this.checkAccount()
  }

  /**
   *
   * @param ms
   * @returns {*}
   */
  waitFor(ms) {
    return new Promise(resolve => setTimeout(resolve, ms))
  }

  /**
   *
   * @returns void
   */
  async updateClientNetworkId() {

    try {
      const netId = await this.web3.eth.net.getId()
      this.clientNetworkId = netId.toString()
    }
    catch (err) {
      this.logToDom('There was an error getting networks.', true)
      this.logIt(err)
    }
  }

  /**
   *
   * @returns void
   */
  isExpectedNetwork() {
    // Don't validate if expected network is: any.
    if (this.expectedNetwork.id === '*') {
      this.networkState = 'unknown'
    }
    else if (this.expectedNetwork.id === this.clientNetworkId) {
      this.networkState = 'ok'
      this.logToDom('Network ID is ok.')
    }
    else {
      this.networkState = 'error'
      this.logToDom(`Network ID is invalid. Expected ${this.expectedNetwork.name} (id: ${this.expectedNetwork.id})`, true)
    }
  }

  /**
   *
   * @param config
   * @returns {boolean}
   */
  verifyAppNetwork(config) {

    if (this.clientNetworkId) {
      const networkOkAndConfigMatch = (this.clientNetworkId === config.networkId)
      const networkOkAndConfigAny = (this.clientNetworkId && config.networkId === '*')
      return networkOkAndConfigMatch || networkOkAndConfigAny
    }
    return false
  }

  /**
   *
   * @returns void
   */
  async isAccountUnlocked() {
    try {
      const accounts = await this.web3.eth.getAccounts()

      // Start polling for account changes.
      this.checkAccount()

      if (accounts.length) {
        this.account = accounts[0]
      }
      else {
        if (this.provider === 'mascara') {
          this.addActionButton(true, 'Open Mascara')
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
      this.logIt(err)
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
    this.logIt(message)
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
  async updateStatus() {
    if (this.provider !== 'mascara') {
      // Detect Metamask
      if (this.web3.currentProvider.isMetaMask === true) {
        this.provider = 'metamask'
      }
      // Detect Mist browser
      // https://ethereum.stackexchange.com/questions/11046/how-to-check-if-the-browser-is-mist
      if (typeof window.mist !== 'undefined') {
        this.provider = 'mist'
      }
    }

    this.status.update({
      isLocked: !(this.account && this.account.length),
      provider: this.provider,
      network: this.networkState,
    })

    if (this.web3) {
      this.tryInitDapps()
    }
  }


  /**
   * tryInitDapps()
   */
  tryInitDapps() {
    this.apps.forEach((config, index) => {

      if (!this.web3) return

      // Network required? Right network in browser available?
      if (this.verifyAppNetwork(config)) {

        // Account unlock required?
        if (config.requireAccount && !this.getAccountStatus(config)) {
          return
        }
        config.run(this.web3, this.account)
      }
    })
  }

  /**
   *
   * @param config
   * @returns {boolean|*}
   */
  getAccountStatus(config) {
    return !config.requireAccount ||
        (config.requireAccount && this.account && this.account.length === 42)
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

  get clientNetworkId() {
    return this._clientNetworkId
  }
  set clientNetworkId(val) {
    this._clientNetworkId = val
    this.onPropertyChanged('clientNetworkId', val)
  }

  /**
   * Act on property changes of the above setters.
   */
  onPropertyChanged(propName, val) {
    if (val) {
      this.logIt(`Property ${propName} changed to: `, val)
      this[`${propName}Changed`]()
    }
  }

  /**
   * web3Changed()
   */
  web3Changed() {
    this.updateStatus()
    this.updateClientNetworkId()
    this.isAccountUnlocked()
  }

  /**
   * clientNetworkIdChanged()
   */
  clientNetworkIdChanged() {
    this.isExpectedNetwork()
    this.updateStatus()
  }

  /**
   * accountChanged()
   */
  accountChanged() {
    if (this.account) {
      this.logToDom('Account is unlocked.')
    }
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
  addActionButton(bindEvent = false, text = 'Button without text') {
    const button = document.createElement('button')
    button.setAttribute('id', 'mascara-action')
    button.innerHTML = text
    this.wrapper.insertBefore(button, this.wrapper.childNodes[2])
    if (bindEvent) {
      button.addEventListener('click', this.openMascara)
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
   * logIt() Debug wrapper.
   *
   * @param a
   * @param b
   */
  logIt(a, b = null) {
    if (this.debugMode && b) {
      window.console.log(a, b)
    }
    else if (this.debugMode) {
      window.console.log(a)
    }
  }
}
