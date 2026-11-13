/*jslint       browser: true, continue: true,
 devel: true, indent: 2, maxerr: 50,
 newcap: true, nomen: true, plusplus: true,
 regexp: true, sloppy: true, vars: false,
 white: true
 */
interface Window {
  spa: {
    UnsupportedBrowsers : () => {
      initModule: (a_t: HTMLElement, t_m: boolean) => boolean
    }
  }
}

window.spa.UnsupportedBrowsers = function() {
  'use strict'
  const HIDE_KEY = 'hideUnsupportedBrowser'
  const HIDE_VALUE = '1'

  const stateMap: {
    append_target: HTMLElement | null
    testMode: boolean
    hide: boolean
  } = {
    append_target: null,
    testMode: false,
    hide: false,
  }

  const htmlElementMap: {
    closeButton?: HTMLDivElement | null
    backdrop?: HTMLDivElement | null
  } = {
    closeButton: null,
    backdrop: null,
  }

  //Utility Methods
  const _setMessage = function(version) {
    stateMap.append_target?.setAttribute('hidden', "true");

    let hide = false
    if (sessionStorage) {
      hide = sessionStorage.getItem(HIDE_KEY) === HIDE_VALUE
    }

    if (!stateMap.hide && !hide) {
      if (version === false) {
        //document.getElementById('result').innerHTML = '<s>IE/Edge</s>';
      } else if (version >= 16) {
        //document.getElementById('result').innerHTML = 'Edge ' + version;
      } else {
        //document.getElementById('result').innerHTML = 'IE ' + version;
        stateMap.append_target?.removeAttribute('hidden');
      }
    }
  }

  const _detectIE = function() {
    var ua = window.navigator.userAgent

    // Test values; Uncomment to check result …

    // IE 10
    // ua = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)';

    // IE 11
    // ua = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';

    // Edge 12 (Spartan)
    // ua = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0';

    // Edge 13
    // ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586';

    var msie = ua.indexOf('MSIE ')
    if (msie > 0) {
      // IE 10 or older => return version number
      return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10)
    }

    var trident = ua.indexOf('Trident/')
    if (trident > 0) {
      // IE 11 => return version number
      var rv = ua.indexOf('rv:')
      return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10)
    }

    var edge = ua.indexOf('Edge/')
    if (edge > 0) {
      // Edge (IE 12+) => return version number
      return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10)
    }

    // other browser
    return false
  }

  const _updateView = function() {
    const version = _detectIE()
    if (!stateMap.testMode) {
      _setMessage(version)
    } else {
      _setMessage(1)
    }
  }

  const _setHtmlMap = function() {
    const append_target = stateMap.append_target

    htmlElementMap.closeButton = append_target?.querySelector('.close-button')
    htmlElementMap.backdrop = append_target?.querySelector('.backdrop')
  }

  const _initEvents = function() {
    htmlElementMap.closeButton?.addEventListener('click', _closeHandler)
    htmlElementMap.backdrop?.addEventListener('click', _closeHandler)
  }

  const _closeHandler = function() {
    if (sessionStorage) {
      sessionStorage.setItem(HIDE_KEY, HIDE_VALUE)
    }
    stateMap.hide = true
    _updateView()
  }

  const initModule = function(append_target: HTMLElement, testMode: boolean = false) {
    stateMap.append_target = append_target
    stateMap.testMode = testMode;

    _setHtmlMap()
    _initEvents()

    _updateView()

    return true
  }

  return {
    initModule: initModule,
  }
}
