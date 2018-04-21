/*!
 * Mascara app launcher Ethereum status visualization.
 *
 * @license MIT
 */
module.exports = class Web3StatusIndicator {

  constructor(node) {
    // init status
    this.state = {
      isLocked: true,
      provider: 'unknown',
      network: 'undefined', // [undefined, ok, error]
    }
    // Image map positioning
    this.IMAGEMAP = {
      unknown: 0,
      ganache: -16,
      metamask: -32,
      mascara: -64,
      mist: -48,
    }
    // IDs
    this.accountState = 'web3-status--indicator'
    this.network = 'web3-status--network'
    this.lock = 'web3-status--locked'
    this.netState = 'web3-status--netStatus'

    // Address status (lock).
    const lock = document.createElement('span')
    lock.setAttribute('id', this.lock)
    const accountState = document.createElement('span')
    accountState.setAttribute('id', this.accountState)
    accountState.appendChild(lock)

    // Network Status
    const network = document.createElement('span')
    network.setAttribute('id', this.network)
    network.setAttribute('class', 'is-unknown')
    const netState = document.createElement('span')
    netState.setAttribute('id', this.netState)
    network.appendChild(netState)

    node.appendChild(accountState)
    node.appendChild(network)
  }

  update(state) {
    this.state = state
    this.render()
  }

  render() {
    const accountState = document.getElementById(this.accountState)
    const netState = document.getElementById(this.network)

    // Toggle netState
    if (this.state.network) {
      if (this.state.network === 'undefined' && !netState.classList.contains('is-unknown')) {
        netState.classList.add('is-unknown')
        netState.classList.remove('is-ok')
        netState.classList.remove('is-error')
      }
      if (this.state.network === 'ok' && !netState.classList.contains('is-ok')) {
        netState.classList.add('is-ok')
        netState.classList.remove('is-unknown')
        netState.classList.remove('is-error')
      }
      if (this.state.network === 'error' && !netState.classList.contains('is-error')) {
        netState.classList.add('is-error')
        netState.classList.remove('is-ok')
        netState.classList.remove('is-unknown')
      }
    }


    // Toggle locked state
    if (this.state.isLocked) {
      if (!accountState.classList.contains('is-locked')) {
        accountState.classList.add('is-locked')
      }
    }
    else {
      accountState.classList.remove('is-locked')
    }
    accountState.style.backgroundPosition = `0 ${this.IMAGEMAP[this.state.provider]}px`
  }

}
