(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
var _this = this;

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
/*!
 * UserConnector - Ethereum Registry Contract.
 * @author Thorsten Krug
 * @license  GPL2
 */
class UserConnector {

  /* global drupalSettings */

  constructor(web3, account) {
    this.web3 = web3;
    this.address = account.toLowerCase();
    this.cnf = drupalSettings.ethereumUserConnector;
    this.processTxUrl = drupalSettings.ethereum_smartcontract.processTxUrl;
    this.verifySubmission = this.verifySubmission.bind(this);

    this.authHash = null;

    // Dom Elements
    this.message = document.getElementById('mascara-logger');
    this.button = document.getElementById('ethereum-verify').getElementsByTagName('button')[0];

    this.button.addEventListener('click', e => {
      this.validateEthereumUser(e);
    });

    // Instantiate contract.
    this.contract = new web3.eth.Contract(drupalSettings.ethereum_smartcontract.contracts.register_drupal.jsonInterface, drupalSettings.ethereum_smartcontract.contracts.register_drupal.address);
    this.validateContract();

    // Update Hash in background if there is no verified address yet.
    if (!Drupal.behaviors.ethereumUserConnectorAddressStatus.hasVerifiedAddress()) {
      Drupal.behaviors.ethereumUserConnectorAddressStatus.setAddress(this.address);
      this.getAuthHash();
    }
  }

  /**
   * validateEthereumUser()
   *
   * @param {Event} event
   *   User initiated Click event.
   */
  async validateEthereumUser(event) {
    event.preventDefault();
    let userRejected = false;

    try {
      if (!this.authHash) {
        await this.getAuthHash();
      }
      const previousTx = await this.checkPreviousSubmission();
      if (previousTx) {
        Drupal.behaviors.txHash.submit(previousTx, this.verifySubmission);
      } else {
        // Ask user to submit to registry contract.
        await this.contract.methods.newUser(`0x${this.authHash}`).send({ from: this.address }).on('transactionHash', hash => {
          this.button.remove();
          this.transactionHash = hash;
          // This will delay submission to Drupal until th TX is mined/confirmed.
          Drupal.behaviors.txHash.submit(hash, this.verifySubmission);
        });
      }
    } catch (err) {
      userRejected = err.message.includes('denied transaction');
      if (userRejected) {
        this.message.innerHTML += Drupal.theme('message', 'You rejected the Transaction', 'error');
      } else {
        const msg = `<pre> ${err.message}</pre>`;
        this.message.innerHTML += Drupal.theme('message', msg, 'error');
      }
      window.console.error(err);
    }
  }

  /**
   * Set this.contractVerified
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
   */
  async getAuthHash() {
    const url = `${this.cnf.updateAccountUrl + this.address}?_format=json&t=${new Date().getTime()}`;
    const response = await fetch(url, { method: 'get', credentials: 'same-origin' });
    if (!response.ok) {
      const msg = 'Can not get a hash. Check permission for \'Access GET on Update Ethereum account resource\'';
      this.message.innerHTML += Drupal.theme('message', msg, 'error');
    }
    const authHash = await response.json();
    this.authHash = authHash.hash;
  }

  /**
   * Check if the user all ready submitted a TX to the registry contract.
   *
   * @return {string|null}
   *   TX-hash of this address in the contract or null.
   */
  async checkPreviousSubmission() {
    let ret = null;
    const accountCreatedEvents = await this.web3.eth.getPastLogs({
      fromBlock: '0x0',
      address: drupalSettings.ethereum_smartcontract.contracts.register_drupal.address,
      topics: [
      // topics[0] is the hash of the event signature
      this.web3.utils.sha3('AccountCreated(address,bytes32)'),
      // Indexed value is the address padded to 32bit.
      `0x000000000000000000000000${this.address.slice(2)}`]
    });
    if (accountCreatedEvents.length) {
      accountCreatedEvents.forEach(tx => {
        if (tx.data === `0x${this.authHash}`) {
          ret = tx.transactionHash;
        }
      });
    }
    return ret;
  }

  /**
   * Trigger backend to validate user submission.
   *
   * There is a AJAX handler which will assign an new role to the user if submission
   * was verified by Drupal.
   *
   * @param {Response} ajaxResponse
   *   32bit 0x-prefixed hex value.
   */
  async verifySubmission(ajaxResponse) {
    if (!ajaxResponse.ok) {
      const msg = 'Can not get verificationUrl';
      this.message.innerHTML += Drupal.theme('message', msg, 'error');
    }
    const result = await ajaxResponse.json();

    // Note on EventIndex:
    // in case your contract emits the same Event twice within one TX you might have more than one.
    // result.<ContractName>.<EventName>.[EventIndex]
    const resultType = result.register_drupal.AccountCreated[0].error ? 'error' : 'status';
    this.message.innerHTML = Drupal.theme('message', result.register_drupal.AccountCreated[0].message, resultType);
    if (!result.register_drupal.AccountCreated[0].error) {
      window.location.reload(true);
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
  let msg = `<div role="contentinfo" class="messages messages--${type}"><div role="alert">`;
  msg += `<h2 class="visually-hidden">${type.charAt(0).toUpperCase()}${type.slice(1)} message</h2>`;
  msg += `${content}</div></div>`;
  return msg;
};

/**
 * web3Ready is fired by ethereum_txsigner.txsigner.mascara
 */
window.addEventListener('web3Ready', () => {
  window.web3Runner.runWhenReady({
    requireAccount: true,
    networkId: drupalSettings.ethereum_txsigner.network.id,
    run: (web3, account = null) => {
      Drupal.behaviors.ethereum_user_connector = new UserConnector(web3, account);
    }
  });
});

},{}]},{},[2,1]);
