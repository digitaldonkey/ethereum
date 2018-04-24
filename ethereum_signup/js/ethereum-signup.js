/**
 * @file
 *
 * In order to use this script in development you require watchify (or browserify):
 *
 * cd ethereum_signup/js
 * npm install
 * npm install -g watchify
 * watchify ethereum-signup.js -o ethereum-signup.bundle.js
 *
 */
(function ($, Drupal) {
  "use strict";

  var message = document.getElementById('ethereum-signup-messages');
  var ethUtil = require('ethereumjs-util');

  /**
   * Drupal's standard init.
   */
  Drupal.behaviors.ethereumSignup = {
    attach: function (context, settings) {
      $('#ethereum-signup', context).once('ethereumSignup').each(function () {
        getWeb3(web3isReady, $(this), settings);
      });
    }
  };

  /**
   * Check if web3 is available.
   */
  function getWeb3(callback, wrapper, settings) {
    liveLog('Get web3...');
    if (typeof window.web3 === 'undefined') {
      missingWeb3(false, wrapper, settings);
    }
    else {
      var myWeb3 = new Web3(window.web3.currentProvider);
      callback(myWeb3, wrapper, settings);
    }
  }

  /**
   * Error/info handler.
   *
   * This will be visible if " Display Ethereum for everybody" is selected in settings,
   * but no web3 provider is available.
   */
  function missingWeb3(web3, wrapper, settings) {
    var msg ='Currently <a href="https://metamask.io/"> Metamask</a> <a href="https://chrome.google.com/webstore/detail/metamask/nkbihfbeogaeaoehlefnkodbefgpgknn">Chrome(ium)</a>, <a href="https://addons.mozilla.org/en-us/firefox/addon/filter-metafilter/?src=cb-dl-recentlyadded">Firefox</a> extension and  <a href="https://github.com/ethereum/mist/releases"> Mist browser</a> are supported and required to use Ethereum signup.';
    errorMessage(msg);
  }

  /**
   * Check if web3 has a account to act with.
   */
  function web3isReady(web3, wrapper, settings) {
    liveLog('Web3 is ready. Please unlock your account to proceed.');

    // Make Block visible if it is hidden for no-web3 providers.
    wrapper.is(":hidden") && wrapper.show();

    // Ensure the account is unlocked before proceeding.
    var waitForUnlock;
    waitForUnlock = setInterval(function(){
      web3.eth.getAccounts(function (error, accounts) {
        if (!error && accounts.length) {
          clearInterval(waitForUnlock);
          // Make sure defaultAccount is populated.
          web3.eth.defaultAccount = accounts[0];
          web3isUnlocked(web3, wrapper, settings);
        }
      });
    }, 600);
  }

  /**
   * Ready to attach web3 actions.
   */
  function web3isUnlocked(web3, wrapper, settings) {
    liveLog('Web3 is unlocked...');

    // Show visualize web3-(un)lock status.
    visualizeAccountStatus(wrapper, true);

    var register_link = $('.ethereum-signup-register--link', wrapper);
    if (register_link) ethereumSignupRegister (web3, wrapper, settings);

    var register_link = $('.ethereum-signup--login-link', wrapper);
    if (register_link) ethereumSignupLogin (web3, wrapper, settings);
  }

  /**
   * Get a login challenge.
   *
   * Drupal will deliver a new challenge to sign for every log-in.
   */
  function getLoginChallenge(web3, wrapper, settings) {
    liveLog('Get log-in challenge...');

    var data = {
      "action": 'challenge',
      "address": web3.eth.defaultAccount,
    };

    postRequest({
      url: settings.ethereum_signup.api + '?_format=json&XDEBUG_SESSION_START=PHPSTORM',
      data: JSON.stringify(data),
      success: function (data, textStatus) {
        liveLog('Got challenge asking for signature...');
        signLoginRequest(data, textStatus, web3, wrapper, settings);
      }
    });
  }

  /**
   * Default post request.
   *
   * @param vars object
   *   Params are the same as the jQuery ajax request.
  */
  function postRequest(vars) {
    $.ajax($.extend(vars, {
      method: 'POST',
      dataType: 'json',
      contentType: 'application/json',
      headers: {'Content-Type': 'application/json'},
      error: ajaxError
    }));
  }


  /**
   * Request user signature.
   *
   * @param web3
   *  web3 instance.
   * @param wrapper
   * @param settings
   * @param msg_utf8
   *    Message text to sign.
   * @param type string
   *    Could be "signup" or "login".
   * @param email string
   *    Only for signup.
   */
  function requestSignature(web3, wrapper, settings, msg_utf8, type, email) {
    var msg = ethUtil.bufferToHex(new Buffer(msg_utf8, 'utf8'));

    web3.currentProvider.sendAsync({
        method: 'personal_sign',
        params: [msg, web3.eth.defaultAccount],
        from: web3.eth.defaultAccount
      },
      function (err, result) {
        liveLog(ucFirst(type) + ' request processed...');
        if (err) {
          liveLog(ucFirst(type) + ' process failed: ' + err);
        }
        else if (result.error) {
          // User might have locked account.
          if (!web3.eth.defaultAccount) {
            visualizeAccountStatus(wrapper, false);
            liveLog('Failed. Please unlock your account.');
          }
          else {
            // Return the first line only. Metamask returns a full-stack Error trailed by a readable message.
            liveLog(result.error.message.split('\n')[0]);
          }
        }
        else {
          liveLog('Signature processed successfully.');
          switch (type) {
            case "signup":
              verifySignup(web3, wrapper, settings, result.result, email);
              break;
            case "login":
              verifyLogin(web3, wrapper, settings, result.result);
              break;
          }
          // eval('verify'+ucFirst(type) + '(web3, wrapper, settings, result.result);');
        }
      });
  }

  /**
   * Default post request.
   *
   * @param signature string
   *   Signature of the login challenge.
   */
  function verifyLogin(web3, wrapper, settings, signature) {
    var data = {
      "action": 'response',
      "address": web3.eth.defaultAccount,
      "signature": signature
    };

    postRequest({
      url: settings.ethereum_signup.api + '?_format=json&XDEBUG_SESSION_START=PHPSTORM',
      data: JSON.stringify(data),
      success: function (data, textStatus) {
        finalizeLogin(data, textStatus, web3, wrapper, settings);
      }
    });
  }

  /**
   * Finally redirect user after successful login.
   */
  function finalizeLogin(data, textStatus, web3, wrapper, settings) {

    if(!data.success) {
      errorMessage(data.error);
    }
    if(data.success) {
      window.location.href = data.reload;
    }
  }

  /**
   * Request user signature on login challenge.
   */
  function signLoginRequest(data, textStatus, web3, wrapper, settings) {
    if(!data.success) {
      errorMessage(data.error);
    }
    if(data.success) {
      requestSignature(web3, wrapper, settings, data.challenge, 'login');
    }
  }


  /**
   * Start log-in process.
   */
  function ethereumSignupLogin (web3, wrapper, settings) {

    wrapper.find('.ethereum-signup--login-link').on({

      click: function (event) {
        clearMessage();
        liveLog('Request log in...');
        event.preventDefault();
        getLoginChallenge(web3, wrapper, settings);
      }
    });
  }


  /**
   * Start registration process.
   */
  function ethereumSignupRegister (web3, wrapper, settings) {

    wrapper.find('.ethereum-signup-register--link').on({
      click: function (event) {
        clearMessage();
        liveLog('Request signup...');
        event.preventDefault();

        // Check for email.
        var mail = getEmail();
        if (!mail && settings.ethereum_signup.require_mail) {
          // Trigger form validation for email.
          requireEmail();
          liveLog('Missing required email field.');
          return false;
        }
        requestSignature(web3, wrapper, settings, settings.ethereum_signup.register_terms_text, 'signup', mail);
      }
    });
  }

  /**
   * Call API to verify signup request.
   */
  function verifySignup(web3, wrapper, settings, signature, email) {
    liveLog('Verifying signature...');

    var data = {
      "address": web3.eth.defaultAccount,
      "signature": signature
    };
    if (email) {
      data.email = email;
    }

    postRequest({
      url: settings.ethereum_signup.api + '?_format=json&XDEBUG_SESSION_START=PHPSTORM',
      data: JSON.stringify(data),
      success: function (data, textStatus) {
        verifySignupSuccess(web3, wrapper, settings, data, textStatus);
      }
    });
  }

  /**
   * Finalize signup process.
   */
  function verifySignupSuccess(web3, wrapper, settings, data, textStatus) {
    if(!data.success) {
      errorMessage(data.error);
    }
    if(data.success) {
      successMessage('Successfully created account.');
    }
    if (data.success && data.reload) {
      window.location.href = data.reload;
    }
  }

  function ajaxError(data, textStatus) {
    errorMessage(textStatus + ' ' + data.error);
  }


  /**
   * Livelog.
   *
   * The livelog is used to visualize ongoing actions.
   * It will be removed when successMessage() or errorMessage() is finally called.
   *
   * @param content string|bool
   *   Text to display or false to empty.
   */
  function liveLog(content) {
    var log = $('#ethereum-signup-livelog');
    log.css({
      fontSize: '.95em',
      fontFamily: 'courier',
      paddingLeft: '20px',
      marginTop: '0.5em'
    });
    if (content === false) {
      log.remove();
    }
    else {
      log.text(content);
    }
  }


  /**
   * Toggle web3 account un/locked.
   *
   * Visualize account status and dis/enables button to submit.
   *
   * @param wrapper Object
   * @param setActive bool
   *    If param is set the account is displayed unlocked.
   */
  function visualizeAccountStatus(wrapper, setActive) {
    if (setActive) {
      wrapper.find('.ethereum-signup--web3-status-locked').hide();
      wrapper.find('.ethereum-signup--web3-status-unlocked').show();
      wrapper.find('.button').removeClass('is-disabled').removeAttr('disabled');
    }
    else {
      wrapper.find('.ethereum-signup--web3-status-unlocked').hide();
      wrapper.find('.ethereum-signup--web3-status-locked').show();
      wrapper.find('.button').addClass('is-disabled').attr('disabled', 'disabled');
    }
  }


  /**
   * Get email in user-register form.
   *
   * @return bool|string
   *   Returns email if field value contains a valid email, otherwise false.
   */
  function getEmail() {
    var    mail = ($('#edit-mail').val().trim());
    return (isEmail(mail)) ? mail : false;
  }

  /**
   * Trigger form validation for email-field only.
   */
  function requireEmail() {
    var form = $('#user-register-form');

    // Require only email.
    form.find('input, .form-required').each(function (ind, elm) {
      elm = $(elm);
      if (elm.is('input') && elm.attr('required') && elm.attr('id') !== 'edit-mail') {
        elm.attr('required', false);
      }
      if (elm.is('.form-required') && elm.attr('for') && elm.attr('for') !== 'edit-mail') {
        elm.removeClass('form-required');
      }
    });

    // Trigger the validation by simulated submit.
    form.on({
      submit: function (e) {
        e.preventDefault();
      }
    });
    $('#edit-submit').trigger('click');
  }

  /**
   * Test for valid email.
   *
   * Sets a final Error message.
   *
   * @param email String
   *   String to verify if it is an email.
   *
   * @return bool
   *   If email true otherwise false.
   */
  function isEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
  }

  /**
   * Error message.
   *
   * Sets a final Error message.
   *
   * @param msg String - Message text (may contain html).
   */
  function errorMessage(msg) {
    liveLog(false);
    message.innerHTML += Drupal.theme('message', msg, 'error');
  }

  /**
   * Success message.
   *
   * Sets a final Success message.
   *
   * @param msg String - Message text (may contain html)
   */
  function successMessage(msg) {
    liveLog(false);
    message.innerHTML += Drupal.theme('message', msg, 'status');
  }

  /**
   * Clear message.
   *
   * Removes final messages (e.g. on restart of signup).
   */
  function clearMessage() {
    liveLog(false);
    message.innerHTML = '';
  }


  function ucFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
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
