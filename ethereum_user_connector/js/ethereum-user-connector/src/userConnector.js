/*!
 * UserConnector - Ethereum Registry Contract.
 * @author Thorsten Krug
 * @license  GPL2
 */
class UserConnector {

  /* global drupalSettings */

  constructor(web3, account) {
    this.web3 = web3
    this.address = account.toLowerCase()
    this.cnf = drupalSettings.ethereumUserConnector
    this.processTxUrl = drupalSettings.ethereum_smartcontract.processTxUrl

    this.authHash = null

    // Dom Elements
    this.message = document.getElementById('myMessage')
    this.button = document.getElementById('ethereum-verify').getElementsByTagName('button')[0]

    this.button.addEventListener('click', (e) => { this.validateEthereumUser(e) })

    // Instantiate contract.
    this.contract = new web3.eth.Contract(
      drupalSettings.ethereum_smartcontract.contracts.register_drupal.jsonInterface,
      drupalSettings.ethereum_smartcontract.contracts.register_drupal.address,
    )
    this.validateContract()

    // Update Hash in background if there is no verified address yet.
    if (!Drupal.behaviors.ethereumUserConnectorAddressStatus.hasVerifiedAddress()) {
      Drupal.behaviors.ethereumUserConnectorAddressStatus.setAddress(this.address)
      this.getAuthHash()
    }
  }

  /**
   * validateEthereumUser()
   *
   * @param {Event} event
   *   User initiated Click event.
   */
  async validateEthereumUser(event) {
    event.preventDefault()
    let userRejected = false

    try {
      if (!this.authHash) {
        await this.getAuthHash()
      }
      const previousTx = await this.checkPreviousSubmission()
      if (previousTx) {
        this.verifySubmission(previousTx)
      }
      else {
        // Ask user to submit to registry contract.
        await this.contract.methods.newUser(`0x${this.authHash}`).send({ from: this.address })
          .on('transactionHash', (hash) => {
            const msg = `Submitted your verification. Transaction ID: ${hash}`
            this.message.innerHTML += Drupal.theme('message', msg)
            this.button.remove()
            this.transactionHash = hash
          })
          .on('receipt', (receipt) => {
            this.receipt = receipt
            // This will delay submission to Drupal until th TX is mined/confirmed.
            Drupal.behaviors.txHash.submit(receipt.transactionHash, this.verifySubmission)
          })
      }
    }
    catch (err) {
      userRejected = err.message.includes('denied transaction')
      if (userRejected) {
        this.message.innerHTML += Drupal.theme('message', 'You rejected the Transaction', 'error')
      }
      else {
        const msg = `<pre> ${err.message}</pre>`
        this.message.innerHTML += Drupal.theme('message', msg, 'error')
      }
      window.console.error(err)
    }
  }

  /**
   * Set this.contractVerified
   */
  async validateContract() {
    const contractVerified = await this.contract.methods.contractExists().call()

    if (contractVerified) {
      // liveLog('Successfully validated contract.')
    }
    else {
      const msg = `Can not verify contract at given address: ${this.cnf.contractAddress}`
      this.message.innerHTML += Drupal.theme('message', msg, 'error')
    }
    this.contractVerified = contractVerified
  }

  /**
   * getAuthHash from Drupal.
   */
  async getAuthHash() {
    const url = `${this.cnf.updateAccountUrl + this.address}?_format=json&t=${new Date().getTime()}`
    const response = await fetch(url, { method: 'get', credentials: 'same-origin' })
    if (!response.ok) {
      const msg = 'Can not get a hash. Check permission for \'Access GET on Update Ethereum account resource\''
      this.message.innerHTML += Drupal.theme('message', msg, 'error')
    }
    const authHash = await response.json()
    this.authHash = authHash.hash
  }



  /**
   * Check if the user all ready submitted a TX to the registry contract.
   *
   * @return {string|null}
   *   TX-hash of this address in the contract or null.
   */
  async checkPreviousSubmission() {
    let ret = null
    const accountCreatedEvents = await this.web3.eth.getPastLogs({
      fromBlock:'0x0',
      address: drupalSettings.ethereum_smartcontract.contracts.register_drupal.address,
      topics: [
        // topics[0] is the hash of the event signature
        this.web3.utils.sha3('AccountCreated(address,bytes32)'),
        // Indexed value is the address padded to 32bit.
        `0x000000000000000000000000${this.address.slice(2)}`
      ]
    })
    if (accountCreatedEvents.length) {
      accountCreatedEvents.forEach((tx) => {
        if (tx.data === `0x${this.authHash}`) {
          ret = tx.transactionHash
        }
      })
    }
    return ret
  }

  /**
   * Trigger backend to validate user submission.
   *
   * There is a AJAX handler which will assign an new role to the user if submission
   * was verified by Drupal.
   *
   * @param {string} txHash
   *   32bit 0x-prefixed hex value.
   */
  async verifySubmission(txHash) {
    // Let Drupal backend verify the transaction submitted by user.
    const url = `${this.processTxUrl + txHash}?_format=json&t=${new Date().getTime()}`
    const response = await fetch(url, { method: 'get', credentials: 'same-origin' })

    if (!response.ok) {
      const msg = 'Can not get verificationUrl'
      this.message.innerHTML += Drupal.theme('message', msg, 'error')
    }
    const result = await response.json()

    // Note on EventIndex:
    // in case your contract emits the same Event twice within one TX you might have more than one.

    // result.<ContractName>.<EventName>.[EventIndex]
    const resultType = result.register_drupal.AccountCreated[0].error ? 'error' : 'status'
    this.message.innerHTML = Drupal.theme('message', result.register_drupal.AccountCreated[0].message, resultType)
    if (!result.register_drupal.AccountCreated[0].error) {
      window.location.reload(true)
    }
  }
}

/**
 * Message template
 *
 * @param {string} content - Message string (may contain html)
 * @param {string} type - [status|warning|error] - Message type.
 *   Defaults to status
 *
 * @return {string} HTML content.
 */
Drupal.theme.message = (content, type = 'status') => {
  let msg = `<div role="contentinfo" class="messages messages--${type}"><div role="alert">`
  msg += `<h2 class="visually-hidden">${type.charAt(0).toUpperCase()}${type.slice(1)} message</h2>`
  msg += `${content}</div></div>`
  return msg
}

/**
 * web3Ready is fired by ethereum_txsigner.txsigner.mascara
 */
window.addEventListener('web3Ready', () => {
  window.web3Runner.runWhenReady({
    requireAccount: true,
    networkId: drupalSettings.ethereum_txsigner.network.id,
    run: (web3, account = null) => {
      Drupal.behaviors.ethereum_user_connector = new UserConnector(web3, account)
    }
  })
})
