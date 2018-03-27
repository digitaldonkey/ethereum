require('./styles.css')

module.exports = class Web3StatusIndicator {

  constructor(node) {
    // init status
    this.state = {
      isLocked: true,
      provider: 'unknown',
    }
    // Image map positioning
    this.IMAGEMAP = {
      unknown: 0,
      ganache: -16,
      metamask: -32,
      mascara: -64,
    }
    // IDs
    this.outer = 'web3-status--indicator'
    this.inner = 'web3-status--locked'


    const inner = document.createElement('span')
    inner.setAttribute('id', this.inner)
    const outer = document.createElement('span')
    outer.setAttribute('id', this.outer)
    outer.setAttribute('class', 'is-locked')
    outer.appendChild(inner)
    node.appendChild(outer)
  }

  update(state) {
    this.state = state
    this.render()
  }

  render() {
    const elm = document.getElementById(this.outer)

    // Toggle locked state
    if (this.state.isLocked) {
      if (!elm.classList.contains('is-locked')) {
        elm.classList.add('is-locked')
      }
    }
    else {
      elm.classList.remove('is-locked')
    }
    elm.style.backgroundPosition = `0 ${this.IMAGEMAP[this.state.provider]}px`
  }

}
