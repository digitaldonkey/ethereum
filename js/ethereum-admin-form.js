(function ($, Drupal, Web3, log) {
  "use strict";

  /**
   * Live Server Info.
   *
   * Enrich Ethereum Server table with current info
   * if Web3 Class is available.
   *
  **/
  Drupal.behaviors.ethereumSettingsForm = {

    attach: function (context, settings, variable) {

      $('.server-info[data-server-enabled="true"]', context).once('dataServerEnabled').each(function (ind, el) {
        var elm = $(el),
          server = {
            url: elm.data('server-address'),
            isConnected: false,
            nodeVersion: null,
            netVersion: null,
            expectedNetworkId: elm.data('server-network_id').toString(),
            wrongNetId: false
          },
          web3 = new Web3(new Web3.providers.HttpProvider(server.url)),
          updateItem = Drupal.behaviors.ethereumSettingsForm.updateItem;

        // This will throw an unavoidable net::ERR_INTERNET_DISCONNECTED error
        // in console if server is not reachable.
        web3.eth.net.isListening()
          .then(function (result) {
            server.isConnected = result;
            updateItem(server, elm);
          }, function(error) {
            // No connection.
            updateItem(server, elm);
          });

        web3.eth.net.getId()
          .then(function (result) {
            server.netVersion = result;
            updateItem(server, elm);
          }, function(error) {
            log('Error getting Network version', error);
          });

        web3.eth.getProtocolVersion()
          .then(function (result) {
            server.nodeVersion = result;
            updateItem(server, elm);
          }, function(error) {
            log('Error getting Ethereum version', error);
          });
      });
    },
    updateItem: function (server, context) {

      // Check if network ID is as expected.
      var hasNetId = (server.netVersion !== null && server.expectedNetworkId !== '*');

      if (hasNetId && server.expectedNetworkId !== server.netVersion.toString()) {
        server.wrongNetId = true;
      }

      // Replace status message in DOM.
      context.find('.live-info')
        .children().remove().prevObject
        .append(Drupal.theme('serverStatus', server));
    }
  };

  /**
   * Updates server status.
   *
   * @param server Object - With containing server info.
   * @param context Dom element.
   *
   * */
  Drupal.theme.serverStatus = function (server, context) {
    var type = 'error',
        content = '';

    if (server.isConnected) {
      content += 'Successfully connected to node.';
      type = 'status';

      if (server.netVersion) {
        content += '<br />netVersion: ' + server.netVersion;
      }
      if (server.nodeVersion) {
        content += '<br />nodeVersion: ' + server.nodeVersion;
      }
      if (server.wrongNetId) {
        type = 'error';
        content += '<br /><b>Network ID mismatch!</b>';
      }

    }
    else {
      content += 'Could not connect to node.';
    }

    var statusInfo = '<div role="contentinfo" class="messages messages--' + type + '"><div role="alert">';
    statusInfo += content + '</div>';
    return statusInfo;
  };
})(window.jQuery, window.Drupal, window.Web3, window.console.log);
