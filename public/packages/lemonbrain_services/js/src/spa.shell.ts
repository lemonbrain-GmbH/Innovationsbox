/*jslint       browser: true, continue: true,
 devel: true, indent: 2, maxerr: 50,
 newcap: true, nomen: true, plusplus: true,
 regexp: true, sloppy: true, vars: false,
 white: true
 */
/*global window: true, $: true */

interface Window {
  spa: any
}

window.spa.shell = (function () {
  'use strict'

  const configMap: {
      resize_interval: number
      scroll_interval: number
      countMax: number
    } = {
      resize_interval: 10,
      scroll_interval: 10,
      countMax: 5,
    },
    stateMap: {
      anchor_map: Object
      resize_idto: number | undefined
      scroll_idto: number | undefined
      resize_count: number
      scroll_count: number
    } = {
      anchor_map: {},
      resize_idto: undefined,
      scroll_idto: undefined,
      resize_count: 0,
      scroll_count: 0,
    }

  //Event Handlers
  const _onResize = function () {
    const eventResite = new CustomEvent('onResize')

    if (stateMap.resize_count > configMap.countMax) {
      stateMap.resize_count = 0
      window.dispatchEvent(eventResite)
    }
    stateMap.resize_count++

    clearTimeout(stateMap.resize_idto)
    //@ts-ignore
    stateMap.resize_idto = setTimeout(function () {
      window.dispatchEvent(eventResite)
    }, configMap.resize_interval)

    return true
  }

  const _onScroll = function () {
    const eventScroll = new CustomEvent('onScroll')

    if (stateMap.scroll_count > configMap.countMax) {
      stateMap.scroll_count = 0
      window.dispatchEvent(eventScroll)
    }
    stateMap.scroll_count++

    clearTimeout(stateMap.scroll_idto)
    //@ts-ignore
    stateMap.scroll_idto = setTimeout(function () {
      window.dispatchEvent(eventScroll)
    }, configMap.scroll_interval)

    return true
  }

  //use like var(--vh, 1vh)
  const _onResizeHandler = function() {
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
  }

  //Public Methods
  const configModule = function () {
    return true
  }

  const initModule = function () {
    window.addEventListener('resize', _onResize)
    window.addEventListener('scroll', _onScroll)

    if (window.document.querySelector('#scroller')) {
      window.document
        .querySelector('#scroller')
        .addEventListener('scroll', _onScroll)
    }

    window.addEventListener('onResize', _onResizeHandler)

    return true
  }

  return {
    initModule: initModule,
    configModule: configModule,
  }
})()
