/*!
 * AddressStatus - Visualize Signature status for Address field.
 * @author Thorsten Krug
 * @license  GPL2
 */
/**
 * Handling Ethereum address status
 *
 * Interaction with field-ethereum-address.
 *
 * If the user has no verified address, but there is one injected, we will use it like:
 *
 * ```
 *  if (!Drupal.behaviors.ethereumUserConnectorAddressStatus.hasVerifiedAddress()) {
 *     this.getAuthHash()
 *    Drupal.behaviors.ethereumUserConnectorAddressStatus.setAddress(this.address)
 *  }
 * ```
 */
class AddressStatus {

  constructor(statusField) {
    this.elm = statusField

    // Enable interaction with address field.
    this.addressField = document.getElementById('edit-field-ethereum-address-0-value')
    this.addressField.addEventListener('change', (event) => {
      this.addressChanged(event, this)
    })

    // Prev address, to visualize state changes.
    this.previousAddress = {
      val: this.elm.getAttribute('data-valid-address'),
      status: (this.elm.getAttribute('data-val')) ? this.elm.getAttribute('data-val') : '0',
    }
    // Cloning.
    this.currentAddress = JSON.parse(JSON.stringify(this.previousAddress))

    // Mapping field values to text.
    // @see ethereum_user_connector/src/Plugin/Field/FieldType/EthereumStatus.php
    this.dataMap = JSON.parse(this.elm.getAttribute('data-map'))

    // Mapping field values to css color classes.
    // @see ethereum_user_connector/templates/ethereum_address_confirm.html.twig
    this.cssMap = JSON.parse(this.elm.getAttribute('data-css-map'))

  }

  addressChanged(event, self) {

    const input = event.target
    // In Drupal Ethereum we always save Ethereum addresses in lowercase!
    const val = input.value.trim().toLocaleLowerCase()
    // Check if Address is valid.
    // event.target.pattern = "0[x,X]{1}[0-9,a-f,A-F]{40}"
    if (val && input.validity.valid) {
      input.classList.remove('error')
      self.setAddress(val)
    }
    else if (val) {
      input.classList.add('error')
    }
  }

  setAddress(address) {
    this.currentAddress.val = address
    if (this.isValidated(address)) {
      this.currentAddress.status = '2'
    }
    else {
      this.currentAddress.status = '1'
    }
    this.addressField.value = address
    this.updateStatusField()
  }

  updateStatusField() {
    document.getElementById('ethereum-status-address').textContent = this.currentAddress.val
    document.getElementById('ethereum-status-message').textContent = this.dataMap[parseInt(this.currentAddress.status, 10)]

    // Remove all classMap classes and add the current one.
    this.elm.classList.forEach((className) => {
      if (this.cssMap.indexOf(className) !== -1) {
        this.elm.classList.remove(className)
      }
    })
    this.elm.classList.add(this.cssMap[this.currentAddress.status])

    // Show verify button if not verified
    document.getElementById('ethereum-verify').style.display = (this.currentAddress.status === '2') ? 'none' : 'inline-block'
  }

  hasVerifiedAddress() {
    return (this.previousAddress.val && this.previousAddress.status === '2')
  }

  setAddressIfEmpty(address) {
    if (!this.currentAddress.val) {
      this.currentAddress.val = address
      this.updateStatusField()
    }
  }

  isValidated(address) {
    return (address === this.previousAddress.val) && (this.previousAddress.status === '2')
  }

}

/**
 * Attaching AddressStatus behaviour.
 *
 * Replacing attach with a "service", so that I can use it from elsewhere.
 * @todo is there a better practice?
 */
Drupal.behaviors.ethereumUserConnectorAddressStatus = {
  attach: () => {
    this.elm = document.getElementById('ethereum-status')
    if (!this.elm.getAttribute('data-initialized')) {
      // Do stuff once.
      this.elm.setAttribute('data-initialized', true)
      Drupal.behaviors.ethereumUserConnectorAddressStatus = new AddressStatus(this.elm)
    }
  },
}
