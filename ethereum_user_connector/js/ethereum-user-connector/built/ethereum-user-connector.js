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

    this.authHash = null;

    // Dom Elements
    this.message = document.getElementById('myMessage');
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
        this.verifySubmission(previousTx);
      } else {
        // Ask user to submit to registry contract.
        await this.contract.methods.newUser(`0x${this.authHash}`).send({ from: this.address }).on('transactionHash', hash => {
          const msg = `Submitted your verification. Transaction ID: ${hash}`;
          this.message.innerHTML += Drupal.theme('message', msg);
          this.button.remove();
          this.transactionHash = hash;
        }).on('receipt', receipt => {
          this.receipt = receipt;
          // This will delay submission to Drupal until th TX is mined/confirmed.
          Drupal.behaviors.txHash.submit(receipt.transactionHash, this.verifySubmission);
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
   * @param {string} txHash
   *   32bit 0x-prefixed hex value.
   */
  async verifySubmission(txHash) {
    // Let Drupal backend verify the transaction submitted by user.
    const url = `${this.processTxUrl + txHash}?_format=json&t=${new Date().getTime()}`;
    const response = await fetch(url, { method: 'get', credentials: 'same-origin' });

    if (!response.ok) {
      const msg = 'Can not get verificationUrl';
      this.message.innerHTML += Drupal.theme('message', msg, 'error');
    }
    const result = await response.json();

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

},{}]},{},[2,1])
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIi4uLy4uLy4uLy4uLy4uLy4uLy4uLy4uLy4uLy4uLy4uLy4uLy5udm0vdmVyc2lvbnMvbm9kZS92MTAuOC4wL2xpYi9ub2RlX21vZHVsZXMvd2F0Y2hpZnkvbm9kZV9tb2R1bGVzL2Jyb3dzZXItcGFjay9fcHJlbHVkZS5qcyIsInNyYy9hZGRyZXNzU3RhdHVzLmpzIiwic3JjL3VzZXJDb25uZWN0b3IuanMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6IkFBQUE7OztBQ0FBOzs7OztBQUtBOzs7Ozs7Ozs7Ozs7OztBQWNBLE1BQU0sYUFBTixDQUFvQjs7QUFFbEIsY0FBWSxXQUFaLEVBQXlCO0FBQ3ZCLFNBQUssR0FBTCxHQUFXLFdBQVg7O0FBRUE7QUFDQSxTQUFLLFlBQUwsR0FBb0IsU0FBUyxjQUFULENBQXdCLHFDQUF4QixDQUFwQjtBQUNBLFNBQUssWUFBTCxDQUFrQixnQkFBbEIsQ0FBbUMsUUFBbkMsRUFBOEMsS0FBRCxJQUFXO0FBQ3RELFdBQUssY0FBTCxDQUFvQixLQUFwQixFQUEyQixJQUEzQjtBQUNELEtBRkQ7O0FBSUE7QUFDQSxTQUFLLGVBQUwsR0FBdUI7QUFDckIsV0FBSyxLQUFLLEdBQUwsQ0FBUyxZQUFULENBQXNCLG9CQUF0QixDQURnQjtBQUVyQixjQUFTLEtBQUssR0FBTCxDQUFTLFlBQVQsQ0FBc0IsVUFBdEIsQ0FBRCxHQUFzQyxLQUFLLEdBQUwsQ0FBUyxZQUFULENBQXNCLFVBQXRCLENBQXRDLEdBQTBFO0FBRXBGO0FBSnVCLEtBQXZCLENBS0EsS0FBSyxjQUFMLEdBQXNCLEtBQUssS0FBTCxDQUFXLEtBQUssU0FBTCxDQUFlLEtBQUssZUFBcEIsQ0FBWCxDQUF0Qjs7QUFFQTtBQUNBO0FBQ0EsU0FBSyxPQUFMLEdBQWUsS0FBSyxLQUFMLENBQVcsS0FBSyxHQUFMLENBQVMsWUFBVCxDQUFzQixVQUF0QixDQUFYLENBQWY7O0FBRUE7QUFDQTtBQUNBLFNBQUssTUFBTCxHQUFjLEtBQUssS0FBTCxDQUFXLEtBQUssR0FBTCxDQUFTLFlBQVQsQ0FBc0IsY0FBdEIsQ0FBWCxDQUFkO0FBRUQ7O0FBRUQsaUJBQWUsS0FBZixFQUFzQixJQUF0QixFQUE0Qjs7QUFFMUIsVUFBTSxRQUFRLE1BQU0sTUFBcEI7QUFDQTtBQUNBLFVBQU0sTUFBTSxNQUFNLEtBQU4sQ0FBWSxJQUFaLEdBQW1CLGlCQUFuQixFQUFaO0FBQ0E7QUFDQTtBQUNBLFFBQUksT0FBTyxNQUFNLFFBQU4sQ0FBZSxLQUExQixFQUFpQztBQUMvQixZQUFNLFNBQU4sQ0FBZ0IsTUFBaEIsQ0FBdUIsT0FBdkI7QUFDQSxXQUFLLFVBQUwsQ0FBZ0IsR0FBaEI7QUFDRCxLQUhELE1BSUssSUFBSSxHQUFKLEVBQVM7QUFDWixZQUFNLFNBQU4sQ0FBZ0IsR0FBaEIsQ0FBb0IsT0FBcEI7QUFDRDtBQUNGOztBQUVELGFBQVcsT0FBWCxFQUFvQjtBQUNsQixTQUFLLGNBQUwsQ0FBb0IsR0FBcEIsR0FBMEIsT0FBMUI7QUFDQSxRQUFJLEtBQUssV0FBTCxDQUFpQixPQUFqQixDQUFKLEVBQStCO0FBQzdCLFdBQUssY0FBTCxDQUFvQixNQUFwQixHQUE2QixHQUE3QjtBQUNELEtBRkQsTUFHSztBQUNILFdBQUssY0FBTCxDQUFvQixNQUFwQixHQUE2QixHQUE3QjtBQUNEO0FBQ0QsU0FBSyxZQUFMLENBQWtCLEtBQWxCLEdBQTBCLE9BQTFCO0FBQ0EsU0FBSyxpQkFBTDtBQUNEOztBQUVELHNCQUFvQjtBQUNsQixhQUFTLGNBQVQsQ0FBd0IseUJBQXhCLEVBQW1ELFdBQW5ELEdBQWlFLEtBQUssY0FBTCxDQUFvQixHQUFyRjtBQUNBLGFBQVMsY0FBVCxDQUF3Qix5QkFBeEIsRUFBbUQsV0FBbkQsR0FBaUUsS0FBSyxPQUFMLENBQWEsU0FBUyxLQUFLLGNBQUwsQ0FBb0IsTUFBN0IsRUFBcUMsRUFBckMsQ0FBYixDQUFqRTs7QUFFQTtBQUNBLFNBQUssR0FBTCxDQUFTLFNBQVQsQ0FBbUIsT0FBbkIsQ0FBNEIsU0FBRCxJQUFlO0FBQ3hDLFVBQUksS0FBSyxNQUFMLENBQVksT0FBWixDQUFvQixTQUFwQixNQUFtQyxDQUFDLENBQXhDLEVBQTJDO0FBQ3pDLGFBQUssR0FBTCxDQUFTLFNBQVQsQ0FBbUIsTUFBbkIsQ0FBMEIsU0FBMUI7QUFDRDtBQUNGLEtBSkQ7QUFLQSxTQUFLLEdBQUwsQ0FBUyxTQUFULENBQW1CLEdBQW5CLENBQXVCLEtBQUssTUFBTCxDQUFZLEtBQUssY0FBTCxDQUFvQixNQUFoQyxDQUF2Qjs7QUFFQTtBQUNBLGFBQVMsY0FBVCxDQUF3QixpQkFBeEIsRUFBMkMsS0FBM0MsQ0FBaUQsT0FBakQsR0FBNEQsS0FBSyxjQUFMLENBQW9CLE1BQXBCLEtBQStCLEdBQWhDLEdBQXVDLE1BQXZDLEdBQWdELGNBQTNHO0FBQ0Q7O0FBRUQsdUJBQXFCO0FBQ25CLFdBQVEsS0FBSyxlQUFMLENBQXFCLEdBQXJCLElBQTRCLEtBQUssZUFBTCxDQUFxQixNQUFyQixLQUFnQyxHQUFwRTtBQUNEOztBQUVELG9CQUFrQixPQUFsQixFQUEyQjtBQUN6QixRQUFJLENBQUMsS0FBSyxjQUFMLENBQW9CLEdBQXpCLEVBQThCO0FBQzVCLFdBQUssY0FBTCxDQUFvQixHQUFwQixHQUEwQixPQUExQjtBQUNBLFdBQUssaUJBQUw7QUFDRDtBQUNGOztBQUVELGNBQVksT0FBWixFQUFxQjtBQUNuQixXQUFRLFlBQVksS0FBSyxlQUFMLENBQXFCLEdBQWxDLElBQTJDLEtBQUssZUFBTCxDQUFxQixNQUFyQixLQUFnQyxHQUFsRjtBQUNEOztBQXRGaUI7O0FBMEZwQjs7Ozs7O0FBTUEsT0FBTyxTQUFQLENBQWlCLGtDQUFqQixHQUFzRDtBQUNwRCxVQUFRLE1BQU07QUFDWixVQUFLLEdBQUwsR0FBVyxTQUFTLGNBQVQsQ0FBd0IsaUJBQXhCLENBQVg7QUFDQSxRQUFJLENBQUMsTUFBSyxHQUFMLENBQVMsWUFBVCxDQUFzQixrQkFBdEIsQ0FBTCxFQUFnRDtBQUM5QztBQUNBLFlBQUssR0FBTCxDQUFTLFlBQVQsQ0FBc0Isa0JBQXRCLEVBQTBDLElBQTFDO0FBQ0EsYUFBTyxTQUFQLENBQWlCLGtDQUFqQixHQUFzRCxJQUFJLGFBQUosQ0FBa0IsTUFBSyxHQUF2QixDQUF0RDtBQUNEO0FBQ0Y7QUFSbUQsQ0FBdEQ7OztBQ25IQTs7Ozs7QUFLQSxNQUFNLGFBQU4sQ0FBb0I7O0FBRWxCOztBQUVBLGNBQVksSUFBWixFQUFrQixPQUFsQixFQUEyQjtBQUN6QixTQUFLLElBQUwsR0FBWSxJQUFaO0FBQ0EsU0FBSyxPQUFMLEdBQWUsUUFBUSxXQUFSLEVBQWY7QUFDQSxTQUFLLEdBQUwsR0FBVyxlQUFlLHFCQUExQjtBQUNBLFNBQUssWUFBTCxHQUFvQixlQUFlLHNCQUFmLENBQXNDLFlBQTFEOztBQUVBLFNBQUssUUFBTCxHQUFnQixJQUFoQjs7QUFFQTtBQUNBLFNBQUssT0FBTCxHQUFlLFNBQVMsY0FBVCxDQUF3QixXQUF4QixDQUFmO0FBQ0EsU0FBSyxNQUFMLEdBQWMsU0FBUyxjQUFULENBQXdCLGlCQUF4QixFQUEyQyxvQkFBM0MsQ0FBZ0UsUUFBaEUsRUFBMEUsQ0FBMUUsQ0FBZDs7QUFFQSxTQUFLLE1BQUwsQ0FBWSxnQkFBWixDQUE2QixPQUE3QixFQUF1QyxDQUFELElBQU87QUFBRSxXQUFLLG9CQUFMLENBQTBCLENBQTFCO0FBQThCLEtBQTdFOztBQUVBO0FBQ0EsU0FBSyxRQUFMLEdBQWdCLElBQUksS0FBSyxHQUFMLENBQVMsUUFBYixDQUNkLGVBQWUsc0JBQWYsQ0FBc0MsU0FBdEMsQ0FBZ0QsZUFBaEQsQ0FBZ0UsYUFEbEQsRUFFZCxlQUFlLHNCQUFmLENBQXNDLFNBQXRDLENBQWdELGVBQWhELENBQWdFLE9BRmxELENBQWhCO0FBSUEsU0FBSyxnQkFBTDs7QUFFQTtBQUNBLFFBQUksQ0FBQyxPQUFPLFNBQVAsQ0FBaUIsa0NBQWpCLENBQW9ELGtCQUFwRCxFQUFMLEVBQStFO0FBQzdFLGFBQU8sU0FBUCxDQUFpQixrQ0FBakIsQ0FBb0QsVUFBcEQsQ0FBK0QsS0FBSyxPQUFwRTtBQUNBLFdBQUssV0FBTDtBQUNEO0FBQ0Y7O0FBRUQ7Ozs7OztBQU1BLFFBQU0sb0JBQU4sQ0FBMkIsS0FBM0IsRUFBa0M7QUFDaEMsVUFBTSxjQUFOO0FBQ0EsUUFBSSxlQUFlLEtBQW5COztBQUVBLFFBQUk7QUFDRixVQUFJLENBQUMsS0FBSyxRQUFWLEVBQW9CO0FBQ2xCLGNBQU0sS0FBSyxXQUFMLEVBQU47QUFDRDtBQUNELFlBQU0sYUFBYSxNQUFNLEtBQUssdUJBQUwsRUFBekI7QUFDQSxVQUFJLFVBQUosRUFBZ0I7QUFDZCxhQUFLLGdCQUFMLENBQXNCLFVBQXRCO0FBQ0QsT0FGRCxNQUdLO0FBQ0g7QUFDQSxjQUFNLEtBQUssUUFBTCxDQUFjLE9BQWQsQ0FBc0IsT0FBdEIsQ0FBK0IsS0FBSSxLQUFLLFFBQVMsRUFBakQsRUFBb0QsSUFBcEQsQ0FBeUQsRUFBRSxNQUFNLEtBQUssT0FBYixFQUF6RCxFQUNILEVBREcsQ0FDQSxpQkFEQSxFQUNvQixJQUFELElBQVU7QUFDL0IsZ0JBQU0sTUFBTyxnREFBK0MsSUFBSyxFQUFqRTtBQUNBLGVBQUssT0FBTCxDQUFhLFNBQWIsSUFBMEIsT0FBTyxLQUFQLENBQWEsU0FBYixFQUF3QixHQUF4QixDQUExQjtBQUNBLGVBQUssTUFBTCxDQUFZLE1BQVo7QUFDQSxlQUFLLGVBQUwsR0FBdUIsSUFBdkI7QUFDRCxTQU5HLEVBT0gsRUFQRyxDQU9BLFNBUEEsRUFPWSxPQUFELElBQWE7QUFDMUIsZUFBSyxPQUFMLEdBQWUsT0FBZjtBQUNBO0FBQ0EsaUJBQU8sU0FBUCxDQUFpQixNQUFqQixDQUF3QixNQUF4QixDQUErQixRQUFRLGVBQXZDLEVBQXdELEtBQUssZ0JBQTdEO0FBQ0QsU0FYRyxDQUFOO0FBWUQ7QUFDRixLQXZCRCxDQXdCQSxPQUFPLEdBQVAsRUFBWTtBQUNWLHFCQUFlLElBQUksT0FBSixDQUFZLFFBQVosQ0FBcUIsb0JBQXJCLENBQWY7QUFDQSxVQUFJLFlBQUosRUFBa0I7QUFDaEIsYUFBSyxPQUFMLENBQWEsU0FBYixJQUEwQixPQUFPLEtBQVAsQ0FBYSxTQUFiLEVBQXdCLDhCQUF4QixFQUF3RCxPQUF4RCxDQUExQjtBQUNELE9BRkQsTUFHSztBQUNILGNBQU0sTUFBTyxTQUFRLElBQUksT0FBUSxRQUFqQztBQUNBLGFBQUssT0FBTCxDQUFhLFNBQWIsSUFBMEIsT0FBTyxLQUFQLENBQWEsU0FBYixFQUF3QixHQUF4QixFQUE2QixPQUE3QixDQUExQjtBQUNEO0FBQ0QsYUFBTyxPQUFQLENBQWUsS0FBZixDQUFxQixHQUFyQjtBQUNEO0FBQ0Y7O0FBRUQ7OztBQUdBLFFBQU0sZ0JBQU4sR0FBeUI7QUFDdkIsVUFBTSxtQkFBbUIsTUFBTSxLQUFLLFFBQUwsQ0FBYyxPQUFkLENBQXNCLGNBQXRCLEdBQXVDLElBQXZDLEVBQS9COztBQUVBLFFBQUksZ0JBQUosRUFBc0I7QUFDcEI7QUFDRCxLQUZELE1BR0s7QUFDSCxZQUFNLE1BQU8sNkNBQTRDLEtBQUssR0FBTCxDQUFTLGVBQWdCLEVBQWxGO0FBQ0EsV0FBSyxPQUFMLENBQWEsU0FBYixJQUEwQixPQUFPLEtBQVAsQ0FBYSxTQUFiLEVBQXdCLEdBQXhCLEVBQTZCLE9BQTdCLENBQTFCO0FBQ0Q7QUFDRCxTQUFLLGdCQUFMLEdBQXdCLGdCQUF4QjtBQUNEOztBQUVEOzs7QUFHQSxRQUFNLFdBQU4sR0FBb0I7QUFDbEIsVUFBTSxNQUFPLEdBQUUsS0FBSyxHQUFMLENBQVMsZ0JBQVQsR0FBNEIsS0FBSyxPQUFRLG1CQUFrQixJQUFJLElBQUosR0FBVyxPQUFYLEVBQXFCLEVBQS9GO0FBQ0EsVUFBTSxXQUFXLE1BQU0sTUFBTSxHQUFOLEVBQVcsRUFBRSxRQUFRLEtBQVYsRUFBaUIsYUFBYSxhQUE5QixFQUFYLENBQXZCO0FBQ0EsUUFBSSxDQUFDLFNBQVMsRUFBZCxFQUFrQjtBQUNoQixZQUFNLE1BQU0sNkZBQVo7QUFDQSxXQUFLLE9BQUwsQ0FBYSxTQUFiLElBQTBCLE9BQU8sS0FBUCxDQUFhLFNBQWIsRUFBd0IsR0FBeEIsRUFBNkIsT0FBN0IsQ0FBMUI7QUFDRDtBQUNELFVBQU0sV0FBVyxNQUFNLFNBQVMsSUFBVCxFQUF2QjtBQUNBLFNBQUssUUFBTCxHQUFnQixTQUFTLElBQXpCO0FBQ0Q7O0FBSUQ7Ozs7OztBQU1BLFFBQU0sdUJBQU4sR0FBZ0M7QUFDOUIsUUFBSSxNQUFNLElBQVY7QUFDQSxVQUFNLHVCQUF1QixNQUFNLEtBQUssSUFBTCxDQUFVLEdBQVYsQ0FBYyxXQUFkLENBQTBCO0FBQzNELGlCQUFVLEtBRGlEO0FBRTNELGVBQVMsZUFBZSxzQkFBZixDQUFzQyxTQUF0QyxDQUFnRCxlQUFoRCxDQUFnRSxPQUZkO0FBRzNELGNBQVE7QUFDTjtBQUNBLFdBQUssSUFBTCxDQUFVLEtBQVYsQ0FBZ0IsSUFBaEIsQ0FBcUIsaUNBQXJCLENBRk07QUFHTjtBQUNDLG1DQUE0QixLQUFLLE9BQUwsQ0FBYSxLQUFiLENBQW1CLENBQW5CLENBQXNCLEVBSjdDO0FBSG1ELEtBQTFCLENBQW5DO0FBVUEsUUFBSSxxQkFBcUIsTUFBekIsRUFBaUM7QUFDL0IsMkJBQXFCLE9BQXJCLENBQThCLEVBQUQsSUFBUTtBQUNuQyxZQUFJLEdBQUcsSUFBSCxLQUFhLEtBQUksS0FBSyxRQUFTLEVBQW5DLEVBQXNDO0FBQ3BDLGdCQUFNLEdBQUcsZUFBVDtBQUNEO0FBQ0YsT0FKRDtBQUtEO0FBQ0QsV0FBTyxHQUFQO0FBQ0Q7O0FBRUQ7Ozs7Ozs7OztBQVNBLFFBQU0sZ0JBQU4sQ0FBdUIsTUFBdkIsRUFBK0I7QUFDN0I7QUFDQSxVQUFNLE1BQU8sR0FBRSxLQUFLLFlBQUwsR0FBb0IsTUFBTyxtQkFBa0IsSUFBSSxJQUFKLEdBQVcsT0FBWCxFQUFxQixFQUFqRjtBQUNBLFVBQU0sV0FBVyxNQUFNLE1BQU0sR0FBTixFQUFXLEVBQUUsUUFBUSxLQUFWLEVBQWlCLGFBQWEsYUFBOUIsRUFBWCxDQUF2Qjs7QUFFQSxRQUFJLENBQUMsU0FBUyxFQUFkLEVBQWtCO0FBQ2hCLFlBQU0sTUFBTSw2QkFBWjtBQUNBLFdBQUssT0FBTCxDQUFhLFNBQWIsSUFBMEIsT0FBTyxLQUFQLENBQWEsU0FBYixFQUF3QixHQUF4QixFQUE2QixPQUE3QixDQUExQjtBQUNEO0FBQ0QsVUFBTSxTQUFTLE1BQU0sU0FBUyxJQUFULEVBQXJCOztBQUVBO0FBQ0E7O0FBRUE7QUFDQSxVQUFNLGFBQWEsT0FBTyxlQUFQLENBQXVCLGNBQXZCLENBQXNDLENBQXRDLEVBQXlDLEtBQXpDLEdBQWlELE9BQWpELEdBQTJELFFBQTlFO0FBQ0EsU0FBSyxPQUFMLENBQWEsU0FBYixHQUF5QixPQUFPLEtBQVAsQ0FBYSxTQUFiLEVBQXdCLE9BQU8sZUFBUCxDQUF1QixjQUF2QixDQUFzQyxDQUF0QyxFQUF5QyxPQUFqRSxFQUEwRSxVQUExRSxDQUF6QjtBQUNBLFFBQUksQ0FBQyxPQUFPLGVBQVAsQ0FBdUIsY0FBdkIsQ0FBc0MsQ0FBdEMsRUFBeUMsS0FBOUMsRUFBcUQ7QUFDbkQsYUFBTyxRQUFQLENBQWdCLE1BQWhCLENBQXVCLElBQXZCO0FBQ0Q7QUFDRjtBQXhLaUI7O0FBMktwQjs7Ozs7Ozs7O0FBU0EsT0FBTyxLQUFQLENBQWEsT0FBYixHQUF1QixDQUFDLE9BQUQsRUFBVSxPQUFPLFFBQWpCLEtBQThCO0FBQ25ELE1BQUksTUFBTyxxREFBb0QsSUFBSyxzQkFBcEU7QUFDQSxTQUFRLCtCQUE4QixLQUFLLE1BQUwsQ0FBWSxDQUFaLEVBQWUsV0FBZixFQUE2QixHQUFFLEtBQUssS0FBTCxDQUFXLENBQVgsQ0FBYyxlQUFuRjtBQUNBLFNBQVEsR0FBRSxPQUFRLGNBQWxCO0FBQ0EsU0FBTyxHQUFQO0FBQ0QsQ0FMRDs7QUFPQTs7O0FBR0EsT0FBTyxnQkFBUCxDQUF3QixXQUF4QixFQUFxQyxNQUFNO0FBQ3pDLFNBQU8sVUFBUCxDQUFrQixZQUFsQixDQUErQjtBQUM3QixvQkFBZ0IsSUFEYTtBQUU3QixlQUFXLGVBQWUsaUJBQWYsQ0FBaUMsT0FBakMsQ0FBeUMsRUFGdkI7QUFHN0IsU0FBSyxDQUFDLElBQUQsRUFBTyxVQUFVLElBQWpCLEtBQTBCO0FBQzdCLGFBQU8sU0FBUCxDQUFpQix1QkFBakIsR0FBMkMsSUFBSSxhQUFKLENBQWtCLElBQWxCLEVBQXdCLE9BQXhCLENBQTNDO0FBQ0Q7QUFMNEIsR0FBL0I7QUFPRCxDQVJEIiwiZmlsZSI6ImdlbmVyYXRlZC5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzQ29udGVudCI6WyIoZnVuY3Rpb24oKXtmdW5jdGlvbiByKGUsbix0KXtmdW5jdGlvbiBvKGksZil7aWYoIW5baV0pe2lmKCFlW2ldKXt2YXIgYz1cImZ1bmN0aW9uXCI9PXR5cGVvZiByZXF1aXJlJiZyZXF1aXJlO2lmKCFmJiZjKXJldHVybiBjKGksITApO2lmKHUpcmV0dXJuIHUoaSwhMCk7dmFyIGE9bmV3IEVycm9yKFwiQ2Fubm90IGZpbmQgbW9kdWxlICdcIitpK1wiJ1wiKTt0aHJvdyBhLmNvZGU9XCJNT0RVTEVfTk9UX0ZPVU5EXCIsYX12YXIgcD1uW2ldPXtleHBvcnRzOnt9fTtlW2ldWzBdLmNhbGwocC5leHBvcnRzLGZ1bmN0aW9uKHIpe3ZhciBuPWVbaV1bMV1bcl07cmV0dXJuIG8obnx8cil9LHAscC5leHBvcnRzLHIsZSxuLHQpfXJldHVybiBuW2ldLmV4cG9ydHN9Zm9yKHZhciB1PVwiZnVuY3Rpb25cIj09dHlwZW9mIHJlcXVpcmUmJnJlcXVpcmUsaT0wO2k8dC5sZW5ndGg7aSsrKW8odFtpXSk7cmV0dXJuIG99cmV0dXJuIHJ9KSgpIiwiLyohXG4gKiBBZGRyZXNzU3RhdHVzIC0gVmlzdWFsaXplIFNpZ25hdHVyZSBzdGF0dXMgZm9yIEFkZHJlc3MgZmllbGQuXG4gKiBAYXV0aG9yIFRob3JzdGVuIEtydWdcbiAqIEBsaWNlbnNlICBHUEwyXG4gKi9cbi8qKlxuICogSGFuZGxpbmcgRXRoZXJldW0gYWRkcmVzcyBzdGF0dXNcbiAqXG4gKiBJbnRlcmFjdGlvbiB3aXRoIGZpZWxkLWV0aGVyZXVtLWFkZHJlc3MuXG4gKlxuICogSWYgdGhlIHVzZXIgaGFzIG5vIHZlcmlmaWVkIGFkZHJlc3MsIGJ1dCB0aGVyZSBpcyBvbmUgaW5qZWN0ZWQsIHdlIHdpbGwgdXNlIGl0IGxpa2U6XG4gKlxuICogYGBgXG4gKiAgaWYgKCFEcnVwYWwuYmVoYXZpb3JzLmV0aGVyZXVtVXNlckNvbm5lY3RvckFkZHJlc3NTdGF0dXMuaGFzVmVyaWZpZWRBZGRyZXNzKCkpIHtcbiAqICAgICB0aGlzLmdldEF1dGhIYXNoKClcbiAqICAgIERydXBhbC5iZWhhdmlvcnMuZXRoZXJldW1Vc2VyQ29ubmVjdG9yQWRkcmVzc1N0YXR1cy5zZXRBZGRyZXNzKHRoaXMuYWRkcmVzcylcbiAqICB9XG4gKiBgYGBcbiAqL1xuY2xhc3MgQWRkcmVzc1N0YXR1cyB7XG5cbiAgY29uc3RydWN0b3Ioc3RhdHVzRmllbGQpIHtcbiAgICB0aGlzLmVsbSA9IHN0YXR1c0ZpZWxkXG5cbiAgICAvLyBFbmFibGUgaW50ZXJhY3Rpb24gd2l0aCBhZGRyZXNzIGZpZWxkLlxuICAgIHRoaXMuYWRkcmVzc0ZpZWxkID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2VkaXQtZmllbGQtZXRoZXJldW0tYWRkcmVzcy0wLXZhbHVlJylcbiAgICB0aGlzLmFkZHJlc3NGaWVsZC5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCAoZXZlbnQpID0+IHtcbiAgICAgIHRoaXMuYWRkcmVzc0NoYW5nZWQoZXZlbnQsIHRoaXMpXG4gICAgfSlcblxuICAgIC8vIFByZXYgYWRkcmVzcywgdG8gdmlzdWFsaXplIHN0YXRlIGNoYW5nZXMuXG4gICAgdGhpcy5wcmV2aW91c0FkZHJlc3MgPSB7XG4gICAgICB2YWw6IHRoaXMuZWxtLmdldEF0dHJpYnV0ZSgnZGF0YS12YWxpZC1hZGRyZXNzJyksXG4gICAgICBzdGF0dXM6ICh0aGlzLmVsbS5nZXRBdHRyaWJ1dGUoJ2RhdGEtdmFsJykpID8gdGhpcy5lbG0uZ2V0QXR0cmlidXRlKCdkYXRhLXZhbCcpIDogJzAnLFxuICAgIH1cbiAgICAvLyBDbG9uaW5nLlxuICAgIHRoaXMuY3VycmVudEFkZHJlc3MgPSBKU09OLnBhcnNlKEpTT04uc3RyaW5naWZ5KHRoaXMucHJldmlvdXNBZGRyZXNzKSlcblxuICAgIC8vIE1hcHBpbmcgZmllbGQgdmFsdWVzIHRvIHRleHQuXG4gICAgLy8gQHNlZSBldGhlcmV1bV91c2VyX2Nvbm5lY3Rvci9zcmMvUGx1Z2luL0ZpZWxkL0ZpZWxkVHlwZS9FdGhlcmV1bVN0YXR1cy5waHBcbiAgICB0aGlzLmRhdGFNYXAgPSBKU09OLnBhcnNlKHRoaXMuZWxtLmdldEF0dHJpYnV0ZSgnZGF0YS1tYXAnKSlcblxuICAgIC8vIE1hcHBpbmcgZmllbGQgdmFsdWVzIHRvIGNzcyBjb2xvciBjbGFzc2VzLlxuICAgIC8vIEBzZWUgZXRoZXJldW1fdXNlcl9jb25uZWN0b3IvdGVtcGxhdGVzL2V0aGVyZXVtX2FkZHJlc3NfY29uZmlybS5odG1sLnR3aWdcbiAgICB0aGlzLmNzc01hcCA9IEpTT04ucGFyc2UodGhpcy5lbG0uZ2V0QXR0cmlidXRlKCdkYXRhLWNzcy1tYXAnKSlcblxuICB9XG5cbiAgYWRkcmVzc0NoYW5nZWQoZXZlbnQsIHNlbGYpIHtcblxuICAgIGNvbnN0IGlucHV0ID0gZXZlbnQudGFyZ2V0XG4gICAgLy8gSW4gRHJ1cGFsIEV0aGVyZXVtIHdlIGFsd2F5cyBzYXZlIEV0aGVyZXVtIGFkZHJlc3NlcyBpbiBsb3dlcmNhc2UhXG4gICAgY29uc3QgdmFsID0gaW5wdXQudmFsdWUudHJpbSgpLnRvTG9jYWxlTG93ZXJDYXNlKClcbiAgICAvLyBDaGVjayBpZiBBZGRyZXNzIGlzIHZhbGlkLlxuICAgIC8vIGV2ZW50LnRhcmdldC5wYXR0ZXJuID0gXCIwW3gsWF17MX1bMC05LGEtZixBLUZdezQwfVwiXG4gICAgaWYgKHZhbCAmJiBpbnB1dC52YWxpZGl0eS52YWxpZCkge1xuICAgICAgaW5wdXQuY2xhc3NMaXN0LnJlbW92ZSgnZXJyb3InKVxuICAgICAgc2VsZi5zZXRBZGRyZXNzKHZhbClcbiAgICB9XG4gICAgZWxzZSBpZiAodmFsKSB7XG4gICAgICBpbnB1dC5jbGFzc0xpc3QuYWRkKCdlcnJvcicpXG4gICAgfVxuICB9XG5cbiAgc2V0QWRkcmVzcyhhZGRyZXNzKSB7XG4gICAgdGhpcy5jdXJyZW50QWRkcmVzcy52YWwgPSBhZGRyZXNzXG4gICAgaWYgKHRoaXMuaXNWYWxpZGF0ZWQoYWRkcmVzcykpIHtcbiAgICAgIHRoaXMuY3VycmVudEFkZHJlc3Muc3RhdHVzID0gJzInXG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgdGhpcy5jdXJyZW50QWRkcmVzcy5zdGF0dXMgPSAnMSdcbiAgICB9XG4gICAgdGhpcy5hZGRyZXNzRmllbGQudmFsdWUgPSBhZGRyZXNzXG4gICAgdGhpcy51cGRhdGVTdGF0dXNGaWVsZCgpXG4gIH1cblxuICB1cGRhdGVTdGF0dXNGaWVsZCgpIHtcbiAgICBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnZXRoZXJldW0tc3RhdHVzLWFkZHJlc3MnKS50ZXh0Q29udGVudCA9IHRoaXMuY3VycmVudEFkZHJlc3MudmFsXG4gICAgZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2V0aGVyZXVtLXN0YXR1cy1tZXNzYWdlJykudGV4dENvbnRlbnQgPSB0aGlzLmRhdGFNYXBbcGFyc2VJbnQodGhpcy5jdXJyZW50QWRkcmVzcy5zdGF0dXMsIDEwKV1cblxuICAgIC8vIFJlbW92ZSBhbGwgY2xhc3NNYXAgY2xhc3NlcyBhbmQgYWRkIHRoZSBjdXJyZW50IG9uZS5cbiAgICB0aGlzLmVsbS5jbGFzc0xpc3QuZm9yRWFjaCgoY2xhc3NOYW1lKSA9PiB7XG4gICAgICBpZiAodGhpcy5jc3NNYXAuaW5kZXhPZihjbGFzc05hbWUpICE9PSAtMSkge1xuICAgICAgICB0aGlzLmVsbS5jbGFzc0xpc3QucmVtb3ZlKGNsYXNzTmFtZSlcbiAgICAgIH1cbiAgICB9KVxuICAgIHRoaXMuZWxtLmNsYXNzTGlzdC5hZGQodGhpcy5jc3NNYXBbdGhpcy5jdXJyZW50QWRkcmVzcy5zdGF0dXNdKVxuXG4gICAgLy8gU2hvdyB2ZXJpZnkgYnV0dG9uIGlmIG5vdCB2ZXJpZmllZFxuICAgIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdldGhlcmV1bS12ZXJpZnknKS5zdHlsZS5kaXNwbGF5ID0gKHRoaXMuY3VycmVudEFkZHJlc3Muc3RhdHVzID09PSAnMicpID8gJ25vbmUnIDogJ2lubGluZS1ibG9jaydcbiAgfVxuXG4gIGhhc1ZlcmlmaWVkQWRkcmVzcygpIHtcbiAgICByZXR1cm4gKHRoaXMucHJldmlvdXNBZGRyZXNzLnZhbCAmJiB0aGlzLnByZXZpb3VzQWRkcmVzcy5zdGF0dXMgPT09ICcyJylcbiAgfVxuXG4gIHNldEFkZHJlc3NJZkVtcHR5KGFkZHJlc3MpIHtcbiAgICBpZiAoIXRoaXMuY3VycmVudEFkZHJlc3MudmFsKSB7XG4gICAgICB0aGlzLmN1cnJlbnRBZGRyZXNzLnZhbCA9IGFkZHJlc3NcbiAgICAgIHRoaXMudXBkYXRlU3RhdHVzRmllbGQoKVxuICAgIH1cbiAgfVxuXG4gIGlzVmFsaWRhdGVkKGFkZHJlc3MpIHtcbiAgICByZXR1cm4gKGFkZHJlc3MgPT09IHRoaXMucHJldmlvdXNBZGRyZXNzLnZhbCkgJiYgKHRoaXMucHJldmlvdXNBZGRyZXNzLnN0YXR1cyA9PT0gJzInKVxuICB9XG5cbn1cblxuLyoqXG4gKiBBdHRhY2hpbmcgQWRkcmVzc1N0YXR1cyBiZWhhdmlvdXIuXG4gKlxuICogUmVwbGFjaW5nIGF0dGFjaCB3aXRoIGEgXCJzZXJ2aWNlXCIsIHNvIHRoYXQgSSBjYW4gdXNlIGl0IGZyb20gZWxzZXdoZXJlLlxuICogQHRvZG8gaXMgdGhlcmUgYSBiZXR0ZXIgcHJhY3RpY2U/XG4gKi9cbkRydXBhbC5iZWhhdmlvcnMuZXRoZXJldW1Vc2VyQ29ubmVjdG9yQWRkcmVzc1N0YXR1cyA9IHtcbiAgYXR0YWNoOiAoKSA9PiB7XG4gICAgdGhpcy5lbG0gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnZXRoZXJldW0tc3RhdHVzJylcbiAgICBpZiAoIXRoaXMuZWxtLmdldEF0dHJpYnV0ZSgnZGF0YS1pbml0aWFsaXplZCcpKSB7XG4gICAgICAvLyBEbyBzdHVmZiBvbmNlLlxuICAgICAgdGhpcy5lbG0uc2V0QXR0cmlidXRlKCdkYXRhLWluaXRpYWxpemVkJywgdHJ1ZSlcbiAgICAgIERydXBhbC5iZWhhdmlvcnMuZXRoZXJldW1Vc2VyQ29ubmVjdG9yQWRkcmVzc1N0YXR1cyA9IG5ldyBBZGRyZXNzU3RhdHVzKHRoaXMuZWxtKVxuICAgIH1cbiAgfSxcbn1cbiIsIi8qIVxuICogVXNlckNvbm5lY3RvciAtIEV0aGVyZXVtIFJlZ2lzdHJ5IENvbnRyYWN0LlxuICogQGF1dGhvciBUaG9yc3RlbiBLcnVnXG4gKiBAbGljZW5zZSAgR1BMMlxuICovXG5jbGFzcyBVc2VyQ29ubmVjdG9yIHtcblxuICAvKiBnbG9iYWwgZHJ1cGFsU2V0dGluZ3MgKi9cblxuICBjb25zdHJ1Y3Rvcih3ZWIzLCBhY2NvdW50KSB7XG4gICAgdGhpcy53ZWIzID0gd2ViM1xuICAgIHRoaXMuYWRkcmVzcyA9IGFjY291bnQudG9Mb3dlckNhc2UoKVxuICAgIHRoaXMuY25mID0gZHJ1cGFsU2V0dGluZ3MuZXRoZXJldW1Vc2VyQ29ubmVjdG9yXG4gICAgdGhpcy5wcm9jZXNzVHhVcmwgPSBkcnVwYWxTZXR0aW5ncy5ldGhlcmV1bV9zbWFydGNvbnRyYWN0LnByb2Nlc3NUeFVybFxuXG4gICAgdGhpcy5hdXRoSGFzaCA9IG51bGxcblxuICAgIC8vIERvbSBFbGVtZW50c1xuICAgIHRoaXMubWVzc2FnZSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdteU1lc3NhZ2UnKVxuICAgIHRoaXMuYnV0dG9uID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2V0aGVyZXVtLXZlcmlmeScpLmdldEVsZW1lbnRzQnlUYWdOYW1lKCdidXR0b24nKVswXVxuXG4gICAgdGhpcy5idXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoZSkgPT4geyB0aGlzLnZhbGlkYXRlRXRoZXJldW1Vc2VyKGUpIH0pXG5cbiAgICAvLyBJbnN0YW50aWF0ZSBjb250cmFjdC5cbiAgICB0aGlzLmNvbnRyYWN0ID0gbmV3IHdlYjMuZXRoLkNvbnRyYWN0KFxuICAgICAgZHJ1cGFsU2V0dGluZ3MuZXRoZXJldW1fc21hcnRjb250cmFjdC5jb250cmFjdHMucmVnaXN0ZXJfZHJ1cGFsLmpzb25JbnRlcmZhY2UsXG4gICAgICBkcnVwYWxTZXR0aW5ncy5ldGhlcmV1bV9zbWFydGNvbnRyYWN0LmNvbnRyYWN0cy5yZWdpc3Rlcl9kcnVwYWwuYWRkcmVzcyxcbiAgICApXG4gICAgdGhpcy52YWxpZGF0ZUNvbnRyYWN0KClcblxuICAgIC8vIFVwZGF0ZSBIYXNoIGluIGJhY2tncm91bmQgaWYgdGhlcmUgaXMgbm8gdmVyaWZpZWQgYWRkcmVzcyB5ZXQuXG4gICAgaWYgKCFEcnVwYWwuYmVoYXZpb3JzLmV0aGVyZXVtVXNlckNvbm5lY3RvckFkZHJlc3NTdGF0dXMuaGFzVmVyaWZpZWRBZGRyZXNzKCkpIHtcbiAgICAgIERydXBhbC5iZWhhdmlvcnMuZXRoZXJldW1Vc2VyQ29ubmVjdG9yQWRkcmVzc1N0YXR1cy5zZXRBZGRyZXNzKHRoaXMuYWRkcmVzcylcbiAgICAgIHRoaXMuZ2V0QXV0aEhhc2goKVxuICAgIH1cbiAgfVxuXG4gIC8qKlxuICAgKiB2YWxpZGF0ZUV0aGVyZXVtVXNlcigpXG4gICAqXG4gICAqIEBwYXJhbSB7RXZlbnR9IGV2ZW50XG4gICAqICAgVXNlciBpbml0aWF0ZWQgQ2xpY2sgZXZlbnQuXG4gICAqL1xuICBhc3luYyB2YWxpZGF0ZUV0aGVyZXVtVXNlcihldmVudCkge1xuICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICBsZXQgdXNlclJlamVjdGVkID0gZmFsc2VcblxuICAgIHRyeSB7XG4gICAgICBpZiAoIXRoaXMuYXV0aEhhc2gpIHtcbiAgICAgICAgYXdhaXQgdGhpcy5nZXRBdXRoSGFzaCgpXG4gICAgICB9XG4gICAgICBjb25zdCBwcmV2aW91c1R4ID0gYXdhaXQgdGhpcy5jaGVja1ByZXZpb3VzU3VibWlzc2lvbigpXG4gICAgICBpZiAocHJldmlvdXNUeCkge1xuICAgICAgICB0aGlzLnZlcmlmeVN1Ym1pc3Npb24ocHJldmlvdXNUeClcbiAgICAgIH1cbiAgICAgIGVsc2Uge1xuICAgICAgICAvLyBBc2sgdXNlciB0byBzdWJtaXQgdG8gcmVnaXN0cnkgY29udHJhY3QuXG4gICAgICAgIGF3YWl0IHRoaXMuY29udHJhY3QubWV0aG9kcy5uZXdVc2VyKGAweCR7dGhpcy5hdXRoSGFzaH1gKS5zZW5kKHsgZnJvbTogdGhpcy5hZGRyZXNzIH0pXG4gICAgICAgICAgLm9uKCd0cmFuc2FjdGlvbkhhc2gnLCAoaGFzaCkgPT4ge1xuICAgICAgICAgICAgY29uc3QgbXNnID0gYFN1Ym1pdHRlZCB5b3VyIHZlcmlmaWNhdGlvbi4gVHJhbnNhY3Rpb24gSUQ6ICR7aGFzaH1gXG4gICAgICAgICAgICB0aGlzLm1lc3NhZ2UuaW5uZXJIVE1MICs9IERydXBhbC50aGVtZSgnbWVzc2FnZScsIG1zZylcbiAgICAgICAgICAgIHRoaXMuYnV0dG9uLnJlbW92ZSgpXG4gICAgICAgICAgICB0aGlzLnRyYW5zYWN0aW9uSGFzaCA9IGhhc2hcbiAgICAgICAgICB9KVxuICAgICAgICAgIC5vbigncmVjZWlwdCcsIChyZWNlaXB0KSA9PiB7XG4gICAgICAgICAgICB0aGlzLnJlY2VpcHQgPSByZWNlaXB0XG4gICAgICAgICAgICAvLyBUaGlzIHdpbGwgZGVsYXkgc3VibWlzc2lvbiB0byBEcnVwYWwgdW50aWwgdGggVFggaXMgbWluZWQvY29uZmlybWVkLlxuICAgICAgICAgICAgRHJ1cGFsLmJlaGF2aW9ycy50eEhhc2guc3VibWl0KHJlY2VpcHQudHJhbnNhY3Rpb25IYXNoLCB0aGlzLnZlcmlmeVN1Ym1pc3Npb24pXG4gICAgICAgICAgfSlcbiAgICAgIH1cbiAgICB9XG4gICAgY2F0Y2ggKGVycikge1xuICAgICAgdXNlclJlamVjdGVkID0gZXJyLm1lc3NhZ2UuaW5jbHVkZXMoJ2RlbmllZCB0cmFuc2FjdGlvbicpXG4gICAgICBpZiAodXNlclJlamVjdGVkKSB7XG4gICAgICAgIHRoaXMubWVzc2FnZS5pbm5lckhUTUwgKz0gRHJ1cGFsLnRoZW1lKCdtZXNzYWdlJywgJ1lvdSByZWplY3RlZCB0aGUgVHJhbnNhY3Rpb24nLCAnZXJyb3InKVxuICAgICAgfVxuICAgICAgZWxzZSB7XG4gICAgICAgIGNvbnN0IG1zZyA9IGA8cHJlPiAke2Vyci5tZXNzYWdlfTwvcHJlPmBcbiAgICAgICAgdGhpcy5tZXNzYWdlLmlubmVySFRNTCArPSBEcnVwYWwudGhlbWUoJ21lc3NhZ2UnLCBtc2csICdlcnJvcicpXG4gICAgICB9XG4gICAgICB3aW5kb3cuY29uc29sZS5lcnJvcihlcnIpXG4gICAgfVxuICB9XG5cbiAgLyoqXG4gICAqIFNldCB0aGlzLmNvbnRyYWN0VmVyaWZpZWRcbiAgICovXG4gIGFzeW5jIHZhbGlkYXRlQ29udHJhY3QoKSB7XG4gICAgY29uc3QgY29udHJhY3RWZXJpZmllZCA9IGF3YWl0IHRoaXMuY29udHJhY3QubWV0aG9kcy5jb250cmFjdEV4aXN0cygpLmNhbGwoKVxuXG4gICAgaWYgKGNvbnRyYWN0VmVyaWZpZWQpIHtcbiAgICAgIC8vIGxpdmVMb2coJ1N1Y2Nlc3NmdWxseSB2YWxpZGF0ZWQgY29udHJhY3QuJylcbiAgICB9XG4gICAgZWxzZSB7XG4gICAgICBjb25zdCBtc2cgPSBgQ2FuIG5vdCB2ZXJpZnkgY29udHJhY3QgYXQgZ2l2ZW4gYWRkcmVzczogJHt0aGlzLmNuZi5jb250cmFjdEFkZHJlc3N9YFxuICAgICAgdGhpcy5tZXNzYWdlLmlubmVySFRNTCArPSBEcnVwYWwudGhlbWUoJ21lc3NhZ2UnLCBtc2csICdlcnJvcicpXG4gICAgfVxuICAgIHRoaXMuY29udHJhY3RWZXJpZmllZCA9IGNvbnRyYWN0VmVyaWZpZWRcbiAgfVxuXG4gIC8qKlxuICAgKiBnZXRBdXRoSGFzaCBmcm9tIERydXBhbC5cbiAgICovXG4gIGFzeW5jIGdldEF1dGhIYXNoKCkge1xuICAgIGNvbnN0IHVybCA9IGAke3RoaXMuY25mLnVwZGF0ZUFjY291bnRVcmwgKyB0aGlzLmFkZHJlc3N9P19mb3JtYXQ9anNvbiZ0PSR7bmV3IERhdGUoKS5nZXRUaW1lKCl9YFxuICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2godXJsLCB7IG1ldGhvZDogJ2dldCcsIGNyZWRlbnRpYWxzOiAnc2FtZS1vcmlnaW4nIH0pXG4gICAgaWYgKCFyZXNwb25zZS5vaykge1xuICAgICAgY29uc3QgbXNnID0gJ0NhbiBub3QgZ2V0IGEgaGFzaC4gQ2hlY2sgcGVybWlzc2lvbiBmb3IgXFwnQWNjZXNzIEdFVCBvbiBVcGRhdGUgRXRoZXJldW0gYWNjb3VudCByZXNvdXJjZVxcJydcbiAgICAgIHRoaXMubWVzc2FnZS5pbm5lckhUTUwgKz0gRHJ1cGFsLnRoZW1lKCdtZXNzYWdlJywgbXNnLCAnZXJyb3InKVxuICAgIH1cbiAgICBjb25zdCBhdXRoSGFzaCA9IGF3YWl0IHJlc3BvbnNlLmpzb24oKVxuICAgIHRoaXMuYXV0aEhhc2ggPSBhdXRoSGFzaC5oYXNoXG4gIH1cblxuXG5cbiAgLyoqXG4gICAqIENoZWNrIGlmIHRoZSB1c2VyIGFsbCByZWFkeSBzdWJtaXR0ZWQgYSBUWCB0byB0aGUgcmVnaXN0cnkgY29udHJhY3QuXG4gICAqXG4gICAqIEByZXR1cm4ge3N0cmluZ3xudWxsfVxuICAgKiAgIFRYLWhhc2ggb2YgdGhpcyBhZGRyZXNzIGluIHRoZSBjb250cmFjdCBvciBudWxsLlxuICAgKi9cbiAgYXN5bmMgY2hlY2tQcmV2aW91c1N1Ym1pc3Npb24oKSB7XG4gICAgbGV0IHJldCA9IG51bGxcbiAgICBjb25zdCBhY2NvdW50Q3JlYXRlZEV2ZW50cyA9IGF3YWl0IHRoaXMud2ViMy5ldGguZ2V0UGFzdExvZ3Moe1xuICAgICAgZnJvbUJsb2NrOicweDAnLFxuICAgICAgYWRkcmVzczogZHJ1cGFsU2V0dGluZ3MuZXRoZXJldW1fc21hcnRjb250cmFjdC5jb250cmFjdHMucmVnaXN0ZXJfZHJ1cGFsLmFkZHJlc3MsXG4gICAgICB0b3BpY3M6IFtcbiAgICAgICAgLy8gdG9waWNzWzBdIGlzIHRoZSBoYXNoIG9mIHRoZSBldmVudCBzaWduYXR1cmVcbiAgICAgICAgdGhpcy53ZWIzLnV0aWxzLnNoYTMoJ0FjY291bnRDcmVhdGVkKGFkZHJlc3MsYnl0ZXMzMiknKSxcbiAgICAgICAgLy8gSW5kZXhlZCB2YWx1ZSBpcyB0aGUgYWRkcmVzcyBwYWRkZWQgdG8gMzJiaXQuXG4gICAgICAgIGAweDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMCR7dGhpcy5hZGRyZXNzLnNsaWNlKDIpfWBcbiAgICAgIF1cbiAgICB9KVxuICAgIGlmIChhY2NvdW50Q3JlYXRlZEV2ZW50cy5sZW5ndGgpIHtcbiAgICAgIGFjY291bnRDcmVhdGVkRXZlbnRzLmZvckVhY2goKHR4KSA9PiB7XG4gICAgICAgIGlmICh0eC5kYXRhID09PSBgMHgke3RoaXMuYXV0aEhhc2h9YCkge1xuICAgICAgICAgIHJldCA9IHR4LnRyYW5zYWN0aW9uSGFzaFxuICAgICAgICB9XG4gICAgICB9KVxuICAgIH1cbiAgICByZXR1cm4gcmV0XG4gIH1cblxuICAvKipcbiAgICogVHJpZ2dlciBiYWNrZW5kIHRvIHZhbGlkYXRlIHVzZXIgc3VibWlzc2lvbi5cbiAgICpcbiAgICogVGhlcmUgaXMgYSBBSkFYIGhhbmRsZXIgd2hpY2ggd2lsbCBhc3NpZ24gYW4gbmV3IHJvbGUgdG8gdGhlIHVzZXIgaWYgc3VibWlzc2lvblxuICAgKiB3YXMgdmVyaWZpZWQgYnkgRHJ1cGFsLlxuICAgKlxuICAgKiBAcGFyYW0ge3N0cmluZ30gdHhIYXNoXG4gICAqICAgMzJiaXQgMHgtcHJlZml4ZWQgaGV4IHZhbHVlLlxuICAgKi9cbiAgYXN5bmMgdmVyaWZ5U3VibWlzc2lvbih0eEhhc2gpIHtcbiAgICAvLyBMZXQgRHJ1cGFsIGJhY2tlbmQgdmVyaWZ5IHRoZSB0cmFuc2FjdGlvbiBzdWJtaXR0ZWQgYnkgdXNlci5cbiAgICBjb25zdCB1cmwgPSBgJHt0aGlzLnByb2Nlc3NUeFVybCArIHR4SGFzaH0/X2Zvcm1hdD1qc29uJnQ9JHtuZXcgRGF0ZSgpLmdldFRpbWUoKX1gXG4gICAgY29uc3QgcmVzcG9uc2UgPSBhd2FpdCBmZXRjaCh1cmwsIHsgbWV0aG9kOiAnZ2V0JywgY3JlZGVudGlhbHM6ICdzYW1lLW9yaWdpbicgfSlcblxuICAgIGlmICghcmVzcG9uc2Uub2spIHtcbiAgICAgIGNvbnN0IG1zZyA9ICdDYW4gbm90IGdldCB2ZXJpZmljYXRpb25VcmwnXG4gICAgICB0aGlzLm1lc3NhZ2UuaW5uZXJIVE1MICs9IERydXBhbC50aGVtZSgnbWVzc2FnZScsIG1zZywgJ2Vycm9yJylcbiAgICB9XG4gICAgY29uc3QgcmVzdWx0ID0gYXdhaXQgcmVzcG9uc2UuanNvbigpXG5cbiAgICAvLyBOb3RlIG9uIEV2ZW50SW5kZXg6XG4gICAgLy8gaW4gY2FzZSB5b3VyIGNvbnRyYWN0IGVtaXRzIHRoZSBzYW1lIEV2ZW50IHR3aWNlIHdpdGhpbiBvbmUgVFggeW91IG1pZ2h0IGhhdmUgbW9yZSB0aGFuIG9uZS5cblxuICAgIC8vIHJlc3VsdC48Q29udHJhY3ROYW1lPi48RXZlbnROYW1lPi5bRXZlbnRJbmRleF1cbiAgICBjb25zdCByZXN1bHRUeXBlID0gcmVzdWx0LnJlZ2lzdGVyX2RydXBhbC5BY2NvdW50Q3JlYXRlZFswXS5lcnJvciA/ICdlcnJvcicgOiAnc3RhdHVzJ1xuICAgIHRoaXMubWVzc2FnZS5pbm5lckhUTUwgPSBEcnVwYWwudGhlbWUoJ21lc3NhZ2UnLCByZXN1bHQucmVnaXN0ZXJfZHJ1cGFsLkFjY291bnRDcmVhdGVkWzBdLm1lc3NhZ2UsIHJlc3VsdFR5cGUpXG4gICAgaWYgKCFyZXN1bHQucmVnaXN0ZXJfZHJ1cGFsLkFjY291bnRDcmVhdGVkWzBdLmVycm9yKSB7XG4gICAgICB3aW5kb3cubG9jYXRpb24ucmVsb2FkKHRydWUpXG4gICAgfVxuICB9XG59XG5cbi8qKlxuICogTWVzc2FnZSB0ZW1wbGF0ZVxuICpcbiAqIEBwYXJhbSB7c3RyaW5nfSBjb250ZW50IC0gTWVzc2FnZSBzdHJpbmcgKG1heSBjb250YWluIGh0bWwpXG4gKiBAcGFyYW0ge3N0cmluZ30gdHlwZSAtIFtzdGF0dXN8d2FybmluZ3xlcnJvcl0gLSBNZXNzYWdlIHR5cGUuXG4gKiAgIERlZmF1bHRzIHRvIHN0YXR1c1xuICpcbiAqIEByZXR1cm4ge3N0cmluZ30gSFRNTCBjb250ZW50LlxuICovXG5EcnVwYWwudGhlbWUubWVzc2FnZSA9IChjb250ZW50LCB0eXBlID0gJ3N0YXR1cycpID0+IHtcbiAgbGV0IG1zZyA9IGA8ZGl2IHJvbGU9XCJjb250ZW50aW5mb1wiIGNsYXNzPVwibWVzc2FnZXMgbWVzc2FnZXMtLSR7dHlwZX1cIj48ZGl2IHJvbGU9XCJhbGVydFwiPmBcbiAgbXNnICs9IGA8aDIgY2xhc3M9XCJ2aXN1YWxseS1oaWRkZW5cIj4ke3R5cGUuY2hhckF0KDApLnRvVXBwZXJDYXNlKCl9JHt0eXBlLnNsaWNlKDEpfSBtZXNzYWdlPC9oMj5gXG4gIG1zZyArPSBgJHtjb250ZW50fTwvZGl2PjwvZGl2PmBcbiAgcmV0dXJuIG1zZ1xufVxuXG4vKipcbiAqIHdlYjNSZWFkeSBpcyBmaXJlZCBieSBldGhlcmV1bV90eHNpZ25lci50eHNpZ25lci5tYXNjYXJhXG4gKi9cbndpbmRvdy5hZGRFdmVudExpc3RlbmVyKCd3ZWIzUmVhZHknLCAoKSA9PiB7XG4gIHdpbmRvdy53ZWIzUnVubmVyLnJ1bldoZW5SZWFkeSh7XG4gICAgcmVxdWlyZUFjY291bnQ6IHRydWUsXG4gICAgbmV0d29ya0lkOiBkcnVwYWxTZXR0aW5ncy5ldGhlcmV1bV90eHNpZ25lci5uZXR3b3JrLmlkLFxuICAgIHJ1bjogKHdlYjMsIGFjY291bnQgPSBudWxsKSA9PiB7XG4gICAgICBEcnVwYWwuYmVoYXZpb3JzLmV0aGVyZXVtX3VzZXJfY29ubmVjdG9yID0gbmV3IFVzZXJDb25uZWN0b3Iod2ViMywgYWNjb3VudClcbiAgICB9XG4gIH0pXG59KVxuIl19
