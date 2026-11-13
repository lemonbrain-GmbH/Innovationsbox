interface Window {
  spa: {
    AcceptCookies: any
    util: any
  }
}

window.spa.AcceptCookies = function () {
  'use strict'
  enum DISPLAY {
    NONE = 'none',
    BLOCK = 'block',
  }
  const AC_HINT = 'ac_hint'
  const CLICK = 'click'
  const VISIBLE = 'visible'
  const COOKIE_SETTINGS_LOADED = 'cookieSettingsLoaded'

  const configMap = {
    settable_map: {
      sessionName: true,
      defaultTokenName: true,
      saveSettingsUrl: true,
      saveSettingsToken: true,
      quickAcceptButtonSelector: true,
    },
    sessionName: 'cookieSettings',
    defaultTokenName: 'ccm_token',
    saveSettingsUrl: null,
    saveSettingsToken: null,
    quickAcceptButtonSelector: '',
  },
    stateMap: {
      append_target: HTMLDivElement | null
      fingerprintId: string | null
      parentZIndex: string | null
    } = {
      append_target: null,
      fingerprintId: null,
      parentZIndex: null,
    }

  const htmlElementMap: {
    closeButton?: HTMLDivElement | null
    settingsButton?: HTMLAnchorElement | null
    hint?: HTMLDivElement | null
    popup?: HTMLDivElement | null
    backdrop?: HTMLDivElement | null
    saveButton?: HTMLSpanElement | null
    cancelButton?: HTMLSpanElement | null
    inputs?: NodeListOf<HTMLInputElement>
    parent?: HTMLElement | null
    quickAcceptButton?: HTMLElement | null
  } = {}

  //Utility Methods
  const _getSettings = function (cancel) {
    let settings = {}

    if (cancel) {
      let settings = _loadSettings()

      if (!!settings && settings.length > 0) {
        return settings
      }
    }

    htmlElementMap.inputs?.forEach((input) => {
      settings[input.name] = !!input.checked
    })

    return settings
  }

  const _setSettings = function (settings) {
    if (settings && Object.keys(settings).length > 0) {
      htmlElementMap.inputs?.forEach((input) => {
        if (settings[input.name] === undefined) {
          // enable checkbox if not found in settings
          input.checked = true
        } else {
          input.checked = !!settings[input.name]
        }
      })
    } else {
      htmlElementMap.inputs?.forEach((input) => {
        input.checked = true
      })
    }
  }

  const _loadSettings = function () {
    if (window.localStorage) {
      let settings = localStorage.getItem(configMap.sessionName)
      if (!!settings && settings.length > 0) {
        settings = JSON.parse(settings)
        _setSettings(settings)

        const event = new CustomEvent(COOKIE_SETTINGS_LOADED, {
          detail: settings,
        })
        window.dispatchEvent(event)

        return settings
      }
    }

    _setSettings(null)

    const event = new CustomEvent(COOKIE_SETTINGS_LOADED, undefined)
    window.dispatchEvent(event)

    return false
  }

  const _saveSettings = async function (cancel) {
    if (window.localStorage) {
      localStorage.setItem(
        configMap.sessionName,
        JSON.stringify(_getSettings(cancel))
      )
    }

    const event = new CustomEvent(COOKIE_SETTINGS_LOADED, {
      detail: _getSettings(cancel),
    })
    window.dispatchEvent(event)
  }

  const _initAcceptCookies = function () {
    _showHint()
  }

  const _showHint = function () {
    const currentSettings = _loadSettings()

    // check if input ids match current settings and vice versa (if not show hint)
    const inputNames = Array.from(htmlElementMap.inputs || []).map((input) => input.name)
    const settingKeys = Object.keys(currentSettings || [])
    const allNamesInKeys = inputNames.every((id) => settingKeys.includes(id))
    const allKeysInNames = settingKeys.every((key) => inputNames.includes(key))

    if (!currentSettings || Object.keys(currentSettings).length === 0 || !allNamesInKeys || !allKeysInNames) {
      if (window.spa.util.uriParam) {
        window.spa.util.uriParam.changeAnchorPart({ ac_hint: VISIBLE })
      } else if (window.spa.util.uriAnchor) {
        window.spa.util.uriAnchor.changeAnchorPart({ ac_hint: VISIBLE })
      } else {
        console.error('no uri handler found')
      }
    }
  }

  const _hideHint = function (cancel) {
    if (window.spa.util.uriParam) {
      window.spa.util.uriParam.changeAnchorPart({ ac_hint: '' })
    } else if (window.spa.util.uriAnchor) {
      window.spa.util.uriAnchor.changeAnchorPart({ ac_hint: '' })
    } else {
      console.error('no uri handler found')
    }
    _saveSettings(cancel)
  }

  //DOM Methods
  const _setHtmlMap = function () {
    const append_target = stateMap.append_target

    if (append_target) {
      htmlElementMap.closeButton =
        append_target.querySelector<HTMLDivElement>('.close')
      htmlElementMap.settingsButton =
        append_target.querySelector<HTMLAnchorElement>('.settings')
      htmlElementMap.hint = append_target.querySelector<HTMLDivElement>('.hint')
      htmlElementMap.popup =
        append_target.querySelector<HTMLDivElement>('.popup')
      htmlElementMap.backdrop =
        append_target.querySelector<HTMLDivElement>('.backdrop')
      htmlElementMap.saveButton = append_target.querySelector<HTMLSpanElement>(
        '.header-button.save'
      )
      htmlElementMap.cancelButton =
        append_target.querySelector<HTMLSpanElement>('.header-button.cancel')
      htmlElementMap.inputs = append_target.querySelectorAll(
        'input[type=checkbox]'
      )
      if (typeof configMap.quickAcceptButtonSelector === 'string' && configMap.quickAcceptButtonSelector.length > 0) {
          htmlElementMap.quickAcceptButton = append_target.querySelector<HTMLElement>(configMap.quickAcceptButtonSelector)
        }
      htmlElementMap.parent = append_target.parentElement
    }
  }

  const _initEvents = function () {
    htmlElementMap.closeButton?.addEventListener(CLICK, _closeHandler)
    htmlElementMap.settingsButton?.addEventListener(CLICK, _settingsHandler)
    htmlElementMap.backdrop?.addEventListener(CLICK, _backdropHandler)
    htmlElementMap.cancelButton?.addEventListener(CLICK, _cancelHandler)
    htmlElementMap.saveButton?.addEventListener(CLICK, _saveHandler)
    htmlElementMap.quickAcceptButton?.addEventListener(CLICK, _saveHandler)

    if (window.spa.util.uriParam) {
      window.spa.util.uriParam.registerFunction(AC_HINT, _acceptCookieHintHandler)
      _acceptCookieHintHandler(window.spa.util.uriParam.getCurrentValue(AC_HINT))
    } else if (window.spa.util.uriAnchor) {
      window.spa.util.uriAnchor.registerFunction(AC_HINT, _acceptCookieHintHandler)
      _acceptCookieHintHandler(window.spa.util.uriAnchor.getCurrentValue(AC_HINT))
    } else {
      console.error('no uri handler found')
    }
  }

  //Event Handlers
  const _closeHandler = function () {
    _hideHint(true)
  }

  const _settingsHandler = function () {
    if (htmlElementMap.popup && htmlElementMap.backdrop) {
      htmlElementMap.popup.style.display = DISPLAY.BLOCK
      htmlElementMap.backdrop.style.display = DISPLAY.BLOCK
    }
  }

  const _backdropHandler = function () {
    if (
      htmlElementMap.parent &&
      htmlElementMap.popup &&
      htmlElementMap.backdrop
    ) {
      htmlElementMap.parent.style.zIndex = stateMap.parentZIndex || '1'
      htmlElementMap.popup.style.display = DISPLAY.NONE
      htmlElementMap.backdrop.style.display = DISPLAY.NONE
      _hideHint(true)
    }
  }

  const _cancelHandler = function () {
    if (
      htmlElementMap.parent &&
      htmlElementMap.popup &&
      htmlElementMap.backdrop
    ) {
      htmlElementMap.parent.style.zIndex = stateMap.parentZIndex || '1'
      htmlElementMap.popup.style.display = DISPLAY.NONE
      htmlElementMap.backdrop.style.display = DISPLAY.NONE
      _hideHint(true)
    }
  }

  const _saveHandler = function () {
    if (
      htmlElementMap.parent &&
      htmlElementMap.popup &&
      htmlElementMap.backdrop
    ) {
      htmlElementMap.parent.style.zIndex = stateMap.parentZIndex || '1'
      htmlElementMap.popup.style.display = DISPLAY.NONE
      htmlElementMap.backdrop.style.display = DISPLAY.NONE
      _hideHint(false)
    }
  }

  const _acceptCookieHintHandler = function (newState) {
    if (htmlElementMap.parent && htmlElementMap.hint) {
      if (newState === 'visible') {
        htmlElementMap.parent.style.zIndex = '999'
        htmlElementMap.hint.style.display = DISPLAY.BLOCK
      } else {
        htmlElementMap.parent.style.zIndex = stateMap.parentZIndex || '1'
        htmlElementMap.hint.style.display = DISPLAY.NONE
      }
    }

    return true
  }

  //Public Methods
  const configModule = function (input_map) {
    window.spa.util.setConfigMap({
      input_map: input_map,
      settable_map: configMap.settable_map,
      config_map: configMap,
    })

    return true
  }

  const initModule = function (append_target: HTMLDivElement) {
    stateMap.append_target = append_target

    _setHtmlMap()
    _initEvents()

    if (htmlElementMap.parent) {
      stateMap.parentZIndex = htmlElementMap.parent.style.zIndex
    }

    _initAcceptCookies()

    return true
  }

  return {
    initModule: initModule,
    configModule: configModule,
    showSettings: _settingsHandler,
  }
}
