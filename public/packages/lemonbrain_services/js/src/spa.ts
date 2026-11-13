/*jslint       browser: true, continue: true,
 devel: true, indent: 2, maxerr: 50,
 newcap: true, nomen: true, plusplus: true,
 regexp: true, sloppy: true, vars: false,
 white: true
 */
/*global window: true, $: true */

if (!String.prototype.startsWith) {
  String.prototype.startsWith = function (searchString, position) {
    position = position || 0
    return this.substr(position, searchString.length) === searchString
  }
}

if ('NodeList' in window && !NodeList.prototype.forEach) {
  NodeList.prototype.forEach = function (callback, thisArg) {
    thisArg = thisArg || window
    for (var i = 0; i < this.length; i++) {
      callback.call(thisArg, this[i], i, this)
    }
  }
}

if (!String.prototype.includes) {
  String.prototype.includes = function (search, start) {
    'use strict'
    if (typeof start !== 'number') {
      start = 0
    }

    if (start + search.length > this.length) {
      return false
    } else {
      return this.indexOf(search, start) !== -1
    }
  }
}

//@ts-ignore
if (!Array.prototype.includes) {
  //@ts-ignore
  Array.prototype.includes = function (searchElement /*, fromIndex*/) {
    'use strict'
    if (this == null) {
      throw new TypeError(
        'Array.prototype.includes called on null or undefined'
      )
    }

    var O = Object(this)
    var len: number = parseInt(O.length, 10) || 0
    if (len === 0) {
      return false
    }
    var n: number = parseInt(arguments[1], 10) || 0
    var k: number
    if (n >= 0) {
      k = n
    } else {
      k = len + n
      if (k < 0) {
        k = 0
      }
    }
    var currentElement: any
    while (k < len) {
      currentElement = O[k]
      if (
        searchElement === currentElement ||
        (searchElement !== searchElement && currentElement !== currentElement)
      ) {
        // NaN !== NaN
        return true
      }
      k++
    }
    return false
  }
}

interface Window {
  spa: any
}

window.spa = (function () {
  'use strict'

  const configModule = function () {
    window.spa.shell.configModule()

    const eventPre = new CustomEvent('spaConfigPreDone')
    window.dispatchEvent(eventPre)

    if (window.spa.util && window.spa.util.uriAnchor) {
      window.spa.util.uriAnchor.configModule()
    }

    const event = new CustomEvent('spaConfigDone')
    window.dispatchEvent(event)

  }

  const initModule = function () {
    window.spa.shell.initModule()

    const eventPre = new CustomEvent('spaInitPreDone')
    window.dispatchEvent(eventPre)

    if (window.spa.util && window.spa.util.uriAnchor) {
      window.spa.util.uriAnchor.initModule()
    }

    const event = new CustomEvent('spaInitDone')
    window.dispatchEvent(event)
  }

  return {
    initModule: initModule,
    configModule: configModule,
  }
})()
