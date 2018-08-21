(function(){function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s}return e})()({1:[function(require,module,exports){
var _this = this;

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
    this.elm = statusField;

    // Enable interaction with address field.
    this.addressField = document.getElementById('edit-field-ethereum-address-0-value');
    this.addressField.addEventListener('change', event => {
      this.addressChanged(event, this);
    });

    // Prev address, to visualize state changes.
    this.previousAddress = {
      val: this.elm.getAttribute('data-valid-address'),
      status: this.elm.getAttribute('data-val') ? this.elm.getAttribute('data-val') : '0'
      // Cloning.
    };this.currentAddress = JSON.parse(JSON.stringify(this.previousAddress));

    // Mapping field values to text.
    // @see ethereum_user_connector/src/Plugin/Field/FieldType/EthereumStatus.php
    this.dataMap = JSON.parse(this.elm.getAttribute('data-map'));

    // Mapping field values to css color classes.
    // @see ethereum_user_connector/templates/ethereum_address_confirm.html.twig
    this.cssMap = JSON.parse(this.elm.getAttribute('data-css-map'));
  }

  addressChanged(event, self) {
    const input = event.target;
    // In Drupal Ethereum we always save Ethereum addresses in lowercase!
    const val = input.value.trim().toLocaleLowerCase();
    // Check if Address is valid.
    // event.target.pattern = "0[x,X]{1}[0-9,a-f,A-F]{40}"
    if (val && input.validity.valid) {
      input.classList.remove('error');
      self.setAddress(val);
    } else if (val) {
      input.classList.add('error');
    }
  }

  setAddress(address) {
    this.currentAddress.val = address;
    if (this.isValidated(address)) {
      this.currentAddress.status = '2';
    } else {
      this.currentAddress.status = '1';
    }
    this.addressField.value = address;
    this.updateStatusField();
  }

  updateStatusField() {
    document.getElementById('ethereum-status-address').textContent = this.currentAddress.val;
    document.getElementById('ethereum-status-message').textContent = this.dataMap[parseInt(this.currentAddress.status, 10)];

    // Remove all classMap classes and add the current one.
    this.elm.classList.forEach(className => {
      if (this.cssMap.indexOf(className) !== -1) {
        this.elm.classList.remove(className);
      }
    });
    this.elm.classList.add(this.cssMap[this.currentAddress.status]);

    // Show verify button if not verified
    document.getElementById('ethereum-verify').style.display = this.currentAddress.status === '2' ? 'none' : 'inline-block';
  }

  hasVerifiedAddress() {
    return this.previousAddress.val && this.previousAddress.status === '2';
  }

  setAddressIfEmpty(address) {
    if (!this.currentAddress.val) {
      this.currentAddress.val = address;
      this.updateStatusField();
    }
  }

  isValidated(address) {
    return address === this.previousAddress.val && this.previousAddress.status === '2';
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
    _this.elm = document.getElementById('ethereum-status');
    if (!_this.elm.getAttribute('data-initialized')) {
      // Do stuff once.
      _this.elm.setAttribute('data-initialized', true);
      Drupal.behaviors.ethereumUserConnectorAddressStatus = new AddressStatus(_this.elm);
    }
  }
};

},{}],2:[function(require,module,exports){

class UserConnector {

  constructor(web3, account) {
    this.web3 = web3;
    this.address = account.toLowerCase();
    this.cnf = drupalSettings.ethereumUserConnector;

    this.authHash = null;

    // Dom Elements
    this.message = document.getElementById('myMessage');
    this.button = document.getElementById('ethereum-verify').getElementsByTagName('button')[0];

    this.button.addEventListener('click', e => {
      this.validateEthereumUser(e);
    });

    // Instantiate contract.
    this.contract = new web3.eth.Contract(drupalSettings.ethereum.contracts.register_drupal.jsonInterface, drupalSettings.ethereum.contracts.register_drupal.address);
    this.validateContract();

    // Update Hash in background if ther is no verified address yet.
    if (!Drupal.behaviors.ethereumUserConnectorAddressStatus.hasVerifiedAddress()) {
      this.getAuthHash();
      Drupal.behaviors.ethereumUserConnectorAddressStatus.setAddress(this.address);
    }
  }

  /**
   * Set this.contractVerified
   *
   * @returns {Promise<*>}
   */
  async validateContract() {
    const contractVerified = await this.contract.methods.contractExists().call();

    if (contractVerified) {
      // liveLog('Successfully validated contract.')
    } else {
      const msg = `Can not verify contract at given address: ${this.cnf.contractAddress}`;
      this.message.innerHTML += Drupal.theme('message', msg, 'error');
    }
    this.contractVerified = contractVerified;
  }

  /**
   * getAuthHash from Drupal.
   *
   * @returns {Promise<void>}
   */
  async getAuthHash() {
    const url = `${this.cnf.updateAccountUrl + this.address}?_format=json&t=${new Date().getTime()}`;
    const response = await fetch(url, { method: 'get', credentials: 'same-origin' });
    if (!response.ok) {
      const msg = 'Can not get a hash for user address. Check permission for \'Access GET on Update Ethereum account resource\'';
      this.message.innerHTML += Drupal.theme('message', msg, 'error');
    }
    const authHash = await response.json();
    this.authHash = authHash.hash;
  }

  /**
   * validateEthereumUser()
   *
   * @param event
   * @returns {Promise<void>}
   */
  async validateEthereumUser(event) {
    event.preventDefault();
    let userRejected = false;

    if (!this.authHash) {
      await this.getAuthHash();
    }

    try {
      await this.contract.methods.newUser(`0x${this.authHash}`).send({ from: this.address }).on('transactionHash', hash => {
        const msg = `Submitted your verification. TX ID: ${hash}
                      <br /> Transaction is pending Ethereum Network Approval.
                      <br /> It takes some time for Drupal to validate the transaction. Please be patient.`;
        this.message.innerHTML += Drupal.theme('message', msg);
        this.button.remove();
        this.transactionHash = hash;
      }).on('receipt', receipt => {
        this.receipt = receipt;

        // Here we have the
        // AccountCreatedEvent(address from, bytes32 hash, int256 error)
        // @todo error handling.
        // Hash allready registered to address.
        // accountCreated(msg.sender, drupalUserHash, 4);
        //  --> In this case we can proceed as normal.
        // Hash allready registered to different address.
        // accountCreated(msg.sender, drupalUserHash, 3);
        // Hash too long
        // accountCreated(msg.sender, drupalUserHash, 2);
        // Registry is disabled because a newer version is available
        // accountCreated(msg.sender, drupalUserHash, 1);
        // Success
        // accountCreated(msg.sender, drupalUserHash, 0);
        // @todo The event would ideally be watched at server side.

        this.verifySubmission();
      })
      // Triggers for every confirm
      // .on('confirmation', (confirmationNumber, receipt) => {
      //   window.console.log('on confirmation', [confirmationNumber, receipt])
      // })
      .on('error', window.console.error);
    } catch (err) {
      userRejected = err.message.includes('denied transaction');
      if (userRejected) {
        this.message.innerHTML += Drupal.theme('message', 'You rejected the Transaction', 'error');
      } else {
        const msg = `<pre> ${err.message}</pre>`;
        this.message.innerHTML += Drupal.theme('message', msg, 'error');
      }
    }
  }

  /**
   * Trigger backend to validate user submission.
   *
   * There is a AJAX handler which will assign an new role to the user if submission
   * was verified by Drupal.
   */
  async verifySubmission() {

    // Let Drupal backend verify the transaction submitted by user.
    const url = `${this.cnf.verificationUrl + this.authHash}?_format=json&t=${new Date().getTime()}`;
    const response = await fetch(url, { method: 'get', credentials: 'same-origin' });
    if (!response.ok) {
      const msg = 'Can not get verificationUrl';
      this.message.innerHTML += Drupal.theme('message', msg, 'error');
    }
    const result = await response.json();

    const resultType = result.success ? 'status' : 'error';
    this.message.innerHTML = Drupal.theme('message', result.message, resultType);
    window.location.reload(true);
  }

}

/**
 * Message template
 *
 * @param content String - Message string (may contain html)
 * @param type String [status|warning|error] - Message type.
 *   Defaults to status - a green info message.
 *
 * @return String HTML.
 */
Drupal.theme.message = (content, type = 'status') => {
  let msg = `<div role="contentinfo" class="messages messages--${type}"><div role="alert">`;
  msg += `<h2 class="visually-hidden">${type.charAt(0).toUpperCase()}${type.slice(1)} message</h2>`;
  msg += `${content}</div></div>`;
  return msg;
};

/**
 * web3Ready is fired by ethereum_txsigner.txsigner.mascara
 */
window.addEventListener('web3Ready', () => {
  console.log('web3Ready');
  window.web3Runner.runWhenReady({
    requireAccount: true,
    networkId: drupalSettings.ethereum.network.id,
    run: (web3, account = null) => {
      Drupal.behaviors.ethereum_user_connector = new UserConnector(web3, account);
    }
  });
});

},{}]},{},[2,1]);
