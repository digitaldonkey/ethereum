/*!
 * EthereumSignup - Ethereum based hallenge/response authentication for Drupal.
 * @author Thorsten Krug
 * @license  GPL2
 */
class EthereumSignup {

  constructor(web3, account, settings) {
    /* eslint-disable global-require */
    this.ethUtil = require('ethereumjs-util')

    this.web3 = web3
    this.account = account
    this.settings = settings
    this.email = null

    this.wrapper = document.getElementById('ethereum-signup')
    this.messageWrapper = document.getElementById('ethereum-signup-messages')

    this.registerSignatureSuccess = this.registerSignatureSuccess.bind(this)
    this.loginProcessSignature = this.loginProcessSignature.bind(this)
    this.loginRequestSignature = this.loginRequestSignature.bind(this)


    this.showWrapper()
    this.attachAction()
  }


  /**
   * Request a auth challenge from Drupal.
   */
  loginInit() {
    const data = {
      action: 'challenge',
      address: this.account,
    }
    this.postRequest(`${this.settings.api}?_format=json`, data, this.loginRequestSignature)
  }

  /**
   * Request user signature.
   */
  loginRequestSignature(response) {
    this.signatureRequest(response.challenge, this.loginProcessSignature)
  }

  /**
   * Submit signed login request to Drupal.
   *
   * @param signature
   */
  loginProcessSignature(signature) {
    const data = {
      action: 'response',
      address: this.account,
      signature,
    }
    this.postRequest(`${this.settings.api}?_format=json`, data, EthereumSignup.loginFinalize)
  }

  /**
   * Reload page after Drupal verification.
   */
  static loginFinalize(response) {
    if (!response.error && response.reload) {
      window.location.href = response.reload
    }
  }


  /**
   * Request user signature for signup.
   */
  registerInit() {
    if (this.settings.require_mail && !EthereumSignup.getEmail()) {
      // Trigger form validation for email.
      EthereumSignup.triggerEmailRequired()
    }
    else {
      this.email = EthereumSignup.getEmail()
      this.signatureRequest(this.settings.register_terms_text, this.registerSignatureSuccess)
    }
  }

  /**
   * Verify Signature in Drupal
   *
   * @param signature
   *    0x prefixed 32 byte hex string.
   */
  registerSignatureSuccess(signature) {
    const data = {
      address: this.account,
      signature,
    }
    if (this.email) {
      data.email = this.email
    }
    this.postRequest(`${this.settings.api}?_format=json`, data, EthereumSignup.registerFinalize)
  }

  /**
   * Reload page after Drupal verification.
   */
  static registerFinalize(response) {
    if (!response.error && response.reload) {
      window.location.href = response.reload
    }
  }


  /**
   * Add actions to buttons.
   */
  attachAction() {
    const buttons = this.wrapper.querySelectorAll('.ethereum-signup-register--link, .ethereum-signup--login-link')
    for (let i = 0; i < buttons.length; i++) {
      buttons[i].classList.remove('is-disabled')
      buttons[i].removeAttribute('disabled')
      buttons[i].addEventListener('click', (e) => {
        e.preventDefault()
        if (e.currentTarget.classList.contains('ethereum-signup-register--link')) {
          this.registerInit()
        }
        else {
          this.loginInit()
        }
      })
    }
  }


  /**
   * Common: Ajax request.
   *
   * @param url
   * @param data
   * @param successCallback
   */
  postRequest(url, data, successCallback) {
    const xhr = new XMLHttpRequest()
    xhr.open('POST', url)
    xhr.setRequestHeader('Content-Type', 'application/json')
    xhr.onload = () => {
      const resp = JSON.parse(xhr.response)
      if (xhr.status === 200 && !resp.error) {
        successCallback(resp)
      }
      else if (xhr.status !== 200) {
        this.handleError(resp.message)
      }
      else {
        this.handleError(resp.error)
      }
    }
    xhr.send(JSON.stringify(data))
  }


  /**
   * Common: Request Ethereum signature
   *
   * @param text
   *   Utf-8 Text to sign.
   * @param successCallback
   */
  signatureRequest(text, successCallback) {
    const msg = this.ethUtil.bufferToHex(new Buffer(text, 'utf8'));
    this.web3.currentProvider.sendAsync({
      method: 'personal_sign',
      params: [msg, this.account],
      from: this.account,
    }, (error, result) => {
      if (error || result.error) {
        this.handleError(result.error.message)
      }
      else {
        successCallback(result.result)
      }
    })
  }


  /**
   * Common: Display error.
   */
  handleError(errorMsg) {
    this.messageWrapper.innerHTML += Drupal.theme.message(errorMsg, 'error')
  }


  /**
   * Get email in user-register form.
   *
   * @return bool|string
   *   Returns email if field value contains a valid email, otherwise false.
   */
  static getEmail() {
    const email = document.getElementById('edit-mail').value.trim()
    const regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/
    return regex.test(email) ? email : false
  }


  /**
   * Trigger form validation for email field only.
   */
  static triggerEmailRequired() {
    const form = document.getElementById('user-register-form')
    // Alter validation to only require email.
    form.querySelectorAll('input, .form-required').forEach((elm) => {
      if (elm.matches('input') && elm.getAttribute('required') && elm.getAttribute('id') !== 'edit-mail') {
        elm.removeAttribute('required')
      }
      if (elm.classList.contains('form-required') && elm.getAttribute('for') && elm.getAttribute('for') !== 'edit-mail') {
        elm.classList.remove('form-required')
      }
    })
    form.reportValidity()
  }

  /**
   * Show wrapper block if hidden.
   */
  showWrapper() {
    if (this.wrapper.offsetWidth === 0 && this.wrapper.offsetHeight === 0) {
      this.wrapper.style.display = 'block'
    }
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
  let msg = `<div role="contentinfo" class="messages messages--${type}"><div role="alert">`
  msg += `<h2 class="visually-hidden">${type.charAt(0).toUpperCase()}${type.slice(1)} message</h2>`
  msg += `${content}</div></div>`
  return msg
}

/**
 * web3Ready is fired by ethereum_txsigner.txsigner.mascara
 */
window.addEventListener('web3Ready', () => {
  window.web3Runner.runWhenReady({
    requireAccount: true,
    networkId: '*',
    run: (web3, account = null) => {
      Drupal.behaviors.ethereum_signup = new EthereumSignup(
        web3,
        account,
        window.drupalSettings.ethereum_signup
      )
    },
  })
})
