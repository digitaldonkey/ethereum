(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
/*!
 * EthereumSignup - Ethereum based hallenge/response authentication for Drupal.
 * @author Thorsten Krug
 * @license  GPL2
 */
class SubmitTxHelper {

  /* eslint-disable max-len */

  constructor(web3, account, submitUrl, chainExplorer) {
    this.web3 = web3
    this.submitUrl = submitUrl
    this.chainExplorer = chainExplorer
    this.process = this.process.bind(this)
  }

  async submit(txHash, callback, elementId = 'mascara-logger') {
    this.addLogger(elementId, txHash)
    const result = await this.process(txHash)
    this.removeLogger(elementId, txHash)
    callback(result)
  }

  /**
   * Process until TX is mined.
   *
   * @param {string} txHash
   *    0x prefixed, 32bit hex string
   * @return {Promise<Response>}
   *    ethereum_smartcontract Event processor response.
   */
  async process(txHash) {
    const tx = await this.web3.eth.getTransaction(txHash)
    if (tx && tx.blockNumber) {
      // Let Drupal backend verify the transaction submitted by user.
      const url = `${this.submitUrl + txHash}?_format=json`
      return fetch(url, { method: 'get', credentials: 'same-origin' })
    }
    await this.timeout(1000)
    return this.process(txHash)
  }

  /**
   * Add a logger for TX.
   *
   * @param {string} elementId
   *   Element to attach logger to.
   * @param {string} txHash
   *   32bit 0x prefixed hex string.
   */
  addLogger(elementId, txHash) {
    let chainExplorerUrl = ''
    if (this.chainExplorer.tx) {
      chainExplorerUrl = this.chainExplorer.tx.replace('@tx', txHash)
    }
    document.getElementById(elementId).insertAdjacentHTML(
      'afterbegin',
      Drupal.theme.logger(txHash, chainExplorerUrl)
    )
  }

  /**
   * Remove logger for TX.
   *
   * @param {string} elementId
   *   Element to attach logger to.
   * @param {string} txHash
   *   32bit 0x prefixed hex string.
   */
  removeLogger(elementId, txHash) {
    document.getElementById(elementId).removeChild(document.getElementById(txHash))
  }

  /**
   * Wait some time.
   *
   * @param {integer} ms
   *    Time to wait in ms.
   * @return {Promise<any>} Promise
   */
  async timeout(ms) {
    return new Promise(resolve => setTimeout(resolve, ms))
  }

}

/**
 * web3Ready is fired by ethereum_txsigner.txsigner.mascara
 */
window.addEventListener('web3Ready', () => {
  window.web3Runner.runWhenReady({
    requireAccount: false,
    networkId: drupalSettings.ethereum_txsigner.network.id,
    run: (web3, account = null) => {
      Drupal.behaviors.txHash = new SubmitTxHelper(
        web3,
        account,
        drupalSettings.ethereum_smartcontract.processTxUrl,
        drupalSettings.ethereum_txsigner.network.blockExplorerLinks
      )
    }
  })
})


Drupal.theme.logger = (txHash, blockExplorerUrl) => {
  let link = `<span class="blockchain-explorer-hash">${txHash}</span>`
  // Wrap into a Blockchain explorer link if available.
  if (blockExplorerUrl) {
    link = `<a title="Show on Blockchain explorer" class="blockchain-explorer-hash" target="_blank" href="${blockExplorerUrl}">${txHash}</a> `
    link += `&nbsp;<a title="Show on Blockchain explorer" class="blockchain-explorer-link" target="_blank" href="${blockExplorerUrl}">${Drupal.theme.newTab()}</a>`
  }
  return `<span class="transaction" id="${txHash}">${Drupal.theme.clock()}${Drupal.t('Processing transaction')}${link}</span>`
}


/**
 * SVG Icon "running clock".
 *
 * Based on https://codepen.io/nikhil8krishnan/pen/rVoXJa
 * @return {string} Icon
 */
Drupal.theme.clock = () => `
<span class="clock">
  <svg class="clock--svg" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
    viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
  <circle fill="none" stroke="#fff" stroke-width="4" stroke-miterlimit="10" cx="50" cy="50" r="48"/>
  <line fill="none" stroke-linecap="round" stroke="#fff" stroke-width="4" stroke-miterlimit="10" x1="50" y1="50" x2="85" y2="50.5">
    <animateTransform 
         attributeName="transform" 
         dur="2s"
         type="rotate"
         from="0 50 50"
         to="360 50 50"
         repeatCount="indefinite" />
  </line>
  <line fill="none" stroke-linecap="round" stroke="#fff" stroke-width="4" stroke-miterlimit="10" x1="50" y1="50" x2="49.5" y2="74">
    <animateTransform 
         attributeName="transform" 
         dur="15s"
         type="rotate"
         from="0 50 50"
         to="360 50 50"
         repeatCount="indefinite" />
  </line>
  </svg>
</span>
`

/**
 * SVG icon "new tab"
 *
 * Based on https://commons.wikimedia.org/wiki/File:OOjs_UI_icon_newWindow-ltr.svg
 *
 * @return {string} Icon
 */
Drupal.theme.newTab = () => `
<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 20 20">
  <path d="M17 17H3V3h5V1H3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5h-2z"/>
  <path d="M19 1h-8l3.29 3.29-5.73 5.73 1.42 1.42 5.73-5.73L19 9V1z"/>
</svg>`

},{}]},{},[1]);
