/* jshint unused:vars, undef:true, browser:true, jquery:true */
/* global grecaptcha */

declare const grecaptcha: any
interface Window {
  RecaptchaV3: any
}

;(function(global) {
  'use strict'

  function render(element: HTMLElement) {
    const clientId = grecaptcha.render(element.id, {
      sitekey: element.dataset.sitekey,
      badge: element.dataset.badge,
      theme: element.dataset.theme,
      size: 'invisible',
    })
    grecaptcha.ready(function() {
      element.dataset.clientid = clientId
    })
  }

  global.RecaptchaV3 = function() {
    document.querySelectorAll<HTMLElement>('.recaptcha-v3').forEach(function(element: HTMLElement) {
        render(element)
    })
  }

  global.RecaptchaV3.execute = function(element: HTMLElement, callback: (success: boolean) => void) {
    grecaptcha
      .execute(element.querySelector<HTMLElement>('.recaptcha-v3').dataset.clientid, {
        action: 'submit',
      })
      .then(function() {
        if (callback) {
          callback(true)
        }
      })
      .catch(function(e) {
          console.error(e)
          callback(false)
      })
  }
})(window)
