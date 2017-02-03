(function ($, Drupal, drupalSettings) {
  "use strict";

  if (typeof web3 !== 'undefined') {
    web3 = new Web3(web3.currentProvider);

    Drupal.behaviors.validateEthereumAccount = {
      attach: function (context, settings) {

        $(context).find('#eth-account-validate').once('validateEthereumAccount').each(function () {

          // Remove missing web3 message.
          $('#missing-web3-message').remove();
          $(this).removeClass('hidden');

          var button = $(this).find('.button');

          console.log('button', button);
          console.log('Settings', drupalSettings);

          console.log(Web3, 'Web3');

          $(this).on({
            click: function (event) {

              alert('Hello world');
              event.preventDefault();
            }
          });

        });
      }
    };

  } else {
    // No web3 available.
  }

})(window.jQuery, window.Drupal, window.drupalSettings);

