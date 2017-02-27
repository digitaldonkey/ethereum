(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.ethereumUserConnector = {
    attach: function (context, settings) {

      var cnf = settings.ethereumUserConnector;

      // Message container.
      var message = document.getElementById('myMessage');
      var button = false;

      $('.ethereum-user-connector', context).once('validateEthereumAccount').each(function () {

        button = $(this).find('.button');
        button.on({
          click: function (event) {
            event.preventDefault();

            if (initWeb3() && ensureProviderAddress()) {
              ensureContractCode(validateEthereumUser);
            }

          }
        });

      });

      function validateEthereumUser() {

        // Add transaction to register.
        web3.eth.sendTransaction({
          // Address the contract is deployed.
          to: cnf.contractAddress,
          // Hash of the method signature and encoded parameters.
          data: cnf.contractNewUserCall
        },
        function (err, transactionHash) {

          var msg = '';
          if (!err) {
            cnf.transactionHash = transactionHash;
            msg = 'Submitted your verification. TX ID: ' + transactionHash;
            msg += '<br /> Transaction is pending Ethereum Network Approval.';
            msg += '<br /> It takes some time for Drupal to validate the transaction. Be patient or just check back later.';
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


      function watchForChanges() {

        console.log('watchForChanges()');

        // Add filter to act on web3 Block confirmation.
        var filter = web3.eth.filter('latest', function(error, result) {

          if (!error) {

            // Block created. Let's get a TX receipt.
            web3.eth.getTransactionReceipt(cnf.transactionHash, function (error, receipt) {

              console.log(cnf.transactionHash, 'transactionHash');

              if (!error) {
                if (receipt !== null && receipt.transactionHash === cnf.transactionHash) {

                  console.log(receipt.blockHash, 'Block found receipt @ watchForChanges()');
                  console.log(receipt);

                  verifySubmission(receipt);

                  filter.stopWatching();
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

        if (typeof web3 !== 'undefined') {
          web3 = new Web3(web3.currentProvider);
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

        var matching = (web3.eth.defaultAccount.toUpperCase() === cnf.userEthereumAddress.toUpperCase());
        if (!matching) {
          var msg ='The address submitted and the signing address must match. <br /> Web3 Address is ' + web3.eth.accounts[0].toUpperCase();
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
      function ensureContractCode(callback) {

        web3.eth.getCode(cnf.contractAddress , 'latest', function(error, result) {

          if(!error && result.substr(1).indexOf(cnf.contractCode) > -1) {
            console.log('ensureContractCode() success;');
            callback();
          }
          else {
            var msg = 'Can not verify code at address: ' + cnf.contractAddress;
            message.innerHTML += Drupal.theme('message', msg, 'error');
          }
        });
      }


      function verifySubmission(receipt) {

        console.log(receipt, 'receipt @ verifySubmission()');

        web3.eth.getStorageAt(cnf.contractAddress, receipt.blockNumber, function (err, result) {

          if (!err) {

            console.log(result, 'result @ verifySubmission()');

            // Let Drupal backend verify that.
            var url = cnf.verificationUrl + cnf.drupalHash;
            $.get(url + '?_format=json', {}, function(data, textStatus){

              if (textStatus === 'success' && data.success) {
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


