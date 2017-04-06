(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.ethereumUserConnector = {
    attach: function (context, settings) {

      var cnf = settings.ethereumUserConnector;

      // Message container.
      var message = document.getElementById('myMessage');
      var button = false;
      var addressField = $('#edit-field-ethereum-address-0-value');
      var status = $('#ethereum-status');

      $('.ethereum-user-connector', context).once('validateEthereumAccount').each(function () {

        button = $(this).find('.button');
        button.on({
          click: function (event) {
            event.preventDefault();

            liveLog('Start validating address ' + addressStatus.get());

            if (initWeb3() && ensureProviderAddress()) {
              validateContract(validateEthereumUser);
            }
          }
        });

        addressField.on({
          change: function (event) {

            var val = $.trim(event.target.value);

            // Check if Address is valid.
            // event.target.pattern = "0[x,X]{1}[0-9,a-f,A-F]{40}"
            if (val && event.target.validity.valid) {
              $(event.target).removeClass('error');
              addressStatus.set(val);
            }
            else if (val) {
              $(event.target).addClass('error');
            }
          }
        });
      });

      /**
       * Manage the user Ethereum Address.
       *
       * This enables the user to change and verify the Address on one step.
       */
      var addressStatus = {
        get: function() {
          // Equals cnf.userEthereumAddress, but might be updated by user.
          return $.trim(status.find('.ethereum-status--address').text());
        },
        isValidated: function (address) {
          // In Drupal Ethereum we always save Ethereum addresses in lowercase!
          address = (typeof address === 'undefined') ? this.get() : address.toLowerCase();
          return (address === status.data('status--valid-address')) && (parseInt(status.data('status--val')) === 2);
        },
        setStatus: function (status_id) {
          status.find('.ethereum-status--message').text(status.data('status--map')[status_id]);
          // Remove color-* classes
          status.attr('class', function(i, c){ return c.replace(/(^|\s)color-\S+/g, ''); });
          status.addClass(status.data('status--css-map')[status_id]);
          if (status_id !== 2) {
            $('#ethereum-verify').show();
          }
        },
        set: function (address) {
          if (this.isValidated(address)) {
            this.setStatus(2);
          }
          else {
            this.setStatus(1);
          }
          status.find('.ethereum-status--address').text(address);
        },
        isChangedAddress: function () {
          return (this.get().toLowerCase() !== cnf.userEthereumAddress.toLowerCase());
        }
      };

      function validateEthereumUser() {

        if (addressStatus.isChangedAddress()) {
          return ensureUserHash(validateEthereumUser);
        }

        liveLog('Start validating user Ethereum address.');

        // Add transaction to register.
        web3.eth.sendTransaction({
          // Address the contract is deployed.
          to: cnf.contractAddress,
          // Hash of the method signature and encoded parameters.
          data: cnf.contractNewUserCall + removePrefix(web3.toHex(cnf.drupalHash))
        },
        function (err, transactionHash) {
          liveLog('Submitted address.');
          var msg = '';
          if (!err) {
            cnf.transactionHash = transactionHash;
            msg = 'Submitted your verification. TX ID: ' + transactionHash;
            msg += '<br /> Transaction is pending Ethereum Network Approval.';
            msg += '<br /> It takes some time for Drupal to validate the transaction. Please be patient.';
            message.innerHTML += Drupal.theme('message', msg);
            button.remove();
            watchForChanges();
          }
          else {
            msg = '<pre>' + err.message + '</pre>';
            message.innerHTML += Drupal.theme('message', msg, 'error');
          }
        });
      }

      /**
       * Blockchain event filter.
       */
      function watchForChanges() {
        liveLog('Watching Blockchain for changes.');

        // Add filter to act on web3 Block confirmation.
        var filter = web3.eth.filter('latest', function(error, result) {

          liveLog('New Block found. Getting receipt.');

          if (!error) {

            // Block created. Let's get a TX receipt.
            web3.eth.getTransactionReceipt(cnf.transactionHash, function (error, receipt) {

              liveLog('Blockchain transaction found. TX hash: ' + cnf.transactionHash);

              if (!error) {
                if (receipt !== null && receipt.transactionHash === cnf.transactionHash) {
                  liveLog('Found Block: ' + receipt.blockHash);
                  filter.stopWatching();
                  verifySubmission(receipt);
                }
              }
              else {
                message.innerHTML += Drupal.theme('message', 'Error getting Transaction receipt.', 'error');
                console.error(error, 'ERROR @ web3.eth.getTransactionReceipt()');
              }
            });
          }
          else {
            message.innerHTML += Drupal.theme('message', 'Error getting latest block.', 'error');
            console.error(error, 'ERROR @ web3.eth.filter()');
          }
        });

      }

      /**
       * Ensure the User Ethereum Address and Signing Account address match.
       */
      function initWeb3() {
        liveLog('Initialize web3');
        if (typeof web3 !== 'undefined') {
          web3 = new Web3(web3.currentProvider);
          liveLog('Web3 initialized');
          return true;
        }
        else {
          // Add missing web3.js message.
          var msg ='Currently <a href="https://metamask.io/"> Metamask</a> Chrome(ium) extension and  <a href="https://github.com/ethereum/mist/releases"> Mist browser</a> are supported and required as transaction signer. Get <a href="https://chrome.google.com/webstore/detail/metamask/nkbihfbeogaeaoehlefnkodbefgpgknn">metamask extension here</a>'
          message.innerHTML += Drupal.theme('message', msg, 'error');
          return false;
        }
      }

     /**
      * Ensure the User Ethereum Address and Signing Account address match.
      */
      function ensureProviderAddress() {
       liveLog('Ensure web3 account and submitted address match.');
       var matching = (web3.eth.defaultAccount.toLowerCase() === addressStatus.get().toLowerCase());

        if (!matching) {
          var msg ='The address submitted and the signing address must match. <br /> Web3 Address is ' + web3.eth.accounts[0];
          message.innerHTML += Drupal.theme('message', msg, 'error');
          return false;
        }
        else {
          return true;
        }
      }

      /**
       * Ensure the User Ethereum Address and Signing Account address match.
       */
      function validateContract(callback) {
        liveLog('Validate contract at address ' + cnf.contractAddress);
        web3.eth.call({to: cnf.contractAddress, data: cnf.validateContractCall}, 'latest', function(error, result) {
          if(!error && result.substr(0, 2) === '0x' && result.substr(-1) === '1') {
            liveLog('Successfully validated contract.');
            callback();
          }
          else {
            var msg = 'Can not verify contract at given address: ' + cnf.contractAddress;
            message.innerHTML += Drupal.theme('message', msg, 'error');
          }
        });
      }

      /**
       * Trigger backend to validate user submission.
       *
       * There is a AJAX handler which will assign an new role to the user if submission
       * was verified by Drupal.
       */
      function verifySubmission(receipt) {

        liveLog('Start verifying submission.');

        web3.eth.getStorageAt(cnf.contractAddress, receipt.blockNumber, function (err, result) {

          if (!err) {
            liveLog('New Block found. Request Drupal to verify submission.');

            // Let Drupal backend verify that.
            var url = cnf.verificationUrl + cnf.drupalHash;
            $.get(url + '?_format=json', {}, function(data, textStatus){

              if (textStatus === 'success' && data.success) {
                liveLog('Success. Reloading page.');
                window.location.reload();
              }
              else {
                console.log(data, 'data');
                console.log(textStatus, 'textStatus');
              }
            });

          }
          else {
            console.log(err, 'Error getStorageAt: ' + cnf.contractAddress + ' blockNumber: ' +  receipt.blockNumber);
          }
        });
      }

      /**
      * Update hash from Ethereum address.
      *
      * User can verify a new address without submitting the form first.
      * We update the user's Eth address and get a new hash.
      */
      function ensureUserHash(callback) {

      liveLog('Updating account address.');
        console.log(addressStatus.get(), 'addressStatus.get()');

       var url = cnf.updateAccountUrl + addressStatus.get();
       $.get(url + '?_format=json&t=' + new Date().getTime(), {}, function(data, textStatus){
         if (textStatus === 'success' && data.hash) {
           liveLog('Got new user hash.');
           cnf.userEthereumAddress = addressStatus.get();
           cnf.drupalHash = data.hash;
           callback();
         }
         else {
           liveLog('Could not get new user hash. Failed :(');
           console.log(textStatus, 'textStatus');
           console.log(data, 'data');
         }
      });
    }

      /**
       * Ensure the User Ethereum Address and Signing Account address match.
       */
      function liveLog(content) {
        var log = $('#littleLog');
        if (!log.length) {
          $('#myMessage').after('<div id="littleLog" class="color-success"></div>');
          log = $('#littleLog').css({
            fontSize: '80%',
            padding: '0 .25em',
            display: 'inline-block',
            marginTop: '0.5em'

          });
        }
        if (content === false) {
          log.remove();
        }
        else {
          log.text(content);
        }
      }

      /**
       * Remove "0x" Prefix if there is one.
       */
      function removePrefix(str) {
        if (str.toLowerCase().substr(0, 2) === '0x') {
          return str.substr(2);
        }
        else {
          return str;
        }
      }

    }
  };


 /**
  * Message template
  *
  * @param content String - Message string (may contain html)
  * @param type String [status|warning|error] - Message type.
  *   Defaults to status - a green info message.
  *
  * @return String HTML.
  */
  Drupal.theme.message = function(content, type) {
    if (typeof type === 'undefined') {
      type = 'status';
    }
    var msg =  '<div role="contentinfo" class="messages messages--' + type + '"><div role="alert">';
    msg += '<h2 class="visually-hidden">' + type.charAt(0).toUpperCase() + type.slice(1) + ' message</h2>';
    msg += content + '</div></div>';
    return msg;
  };

})(window.jQuery, window.Drupal);
