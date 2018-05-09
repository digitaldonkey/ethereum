/**
 * @file
 * Provides a window.web3 object.
 */

(function (window, drupalSettings) {
  // Backup any old Web3 object if the browser has it injected and replace it
  // with the library version provided by the module.
  if (window.web3) {
    window.web3old = window.web3;
    window.web3 = new Web3(window.web3.currentProvider);
  }
  else {
    window.web3 = new Web3(Web3.givenProvider || drupalSettings.ethereum.frontendServerHost);
  }
})(window, drupalSettings);
