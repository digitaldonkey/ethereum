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
  }

  async submit(txHash, callback, elementId = 'mascara-logger') {
    this.addLogger(elementId, txHash)
    const result = await this.process(txHash)
    this.removeLogger(elementId, txHash)
    return result
  }

  async process(txHash) {
    const tx = await this.web3.eth.getTransaction(txHash)
    if (tx && tx.blockNumber) {
      return this.verifySubmission(tx.hash)
    }
    this.trayAgainInOneSec(tx.hash)
  }

  trayAgainInOneSec(txHash) {
    setTimeout(() => {
      this.web3.eth.getTransaction(txHash, this.process)
    }, 1000)
  }

  async verifySubmission(txHash) {
    // Let Drupal backend verify the transaction submitted by user.
    const url = `${this.submitUrl + txHash}?_format=json`
    const response = await fetch(url, { method: 'get', credentials: 'same-origin' })
    return response.json()
  }

  /**
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

  removeLogger(elementId, txHash) {
    document.getElementById(elementId).removeChild(document.getElementById(txHash))
  }

}

Drupal.theme.logger = (txHash, blockExplorerUrl) => {

  let link = txHash
  // Wrap into a Blockchain explorer link if available.
  if (blockExplorerUrl) {
    link = `<a title="Show on Blockchain explorer" class="blockchain-explorer-hash" target="_blank" href="${blockExplorerUrl}">${txHash}</a> `
    link += `&nbsp;<a title="Show on Blockchain explorer" class="blockchain-explorer-link" target="_blank" href="${blockExplorerUrl}">${Drupal.theme.newTab()}</a>`
  }
  return `<span class="transaction" id="${txHash}">${Drupal.theme.clock()}${Drupal.t('Processing transaction')}${link}</span>`
}

// Loader icon based on https://codepen.io/nikhil8krishnan/pen/rVoXJa
Drupal.theme.clock = () => {
  return `
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
}

/**
 * https://commons.wikimedia.org/wiki/File:OOjs_UI_icon_newWindow-ltr.svg
 *
 * @return {string}
 *   Svg Icon.
 */
Drupal.theme.newTab = () => {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 20 20">
            <path d="M17 17H3V3h5V1H3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5h-2z"/>
            <path d="M19 1h-8l3.29 3.29-5.73 5.73 1.42 1.42 5.73-5.73L19 9V1z"/>
          </svg>`
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
