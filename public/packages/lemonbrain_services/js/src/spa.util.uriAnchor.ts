/*jslint       browser: true, continue: true,
 devel: true, indent: 2, maxerr: 50,
 newcap: true, nomen: true, plusplus: true,
 regexp: true, sloppy: true, vars: false,
 white: true
 */
/*global window: true, $: true */

interface JQueryStatic {
  uriAnchor: any
}

interface Window {
  whiteListHash: string[]
  disableStr: string
}

declare const ga: any

interface Window {
  spa: any
}

declare const $: any

window.spa.util.uriAnchor = (function () {
  //Module Variables
  let stateMap = {
    anchor_map: {},
    function_map: {},
    mergedDelayedInput: {},
  }

  //Utility Methods
  const _cloneDeep = function (entity) {
    const referenceTypes = ['Array', 'Object']
    const entityType = Object.prototype.toString.call(entity)

    if (!new RegExp(referenceTypes.join('|')).test(entityType)) return entity
    const c = new entity.constructor()
    return Object.assign(
      c,
      ...Object.keys(entity).map((prop) => ({
        [prop]: _cloneDeep(entity[prop]),
      }))
    )
  }

  const _uriEncodeDependentMap = function (anchorMap) {
    const keys = Object.keys(anchorMap)
    for (let i = 0; i < keys.length; i++) {
      if (typeof anchorMap[keys[i]] === 'object') {
        const subKeys = Object.keys(anchorMap[keys[i]])
        let newValues = {}
        for (let j = 0; j < subKeys.length; j++) {
          newValues[subKeys[j]] = encodeURIComponent(
            anchorMap[keys[i]][subKeys[j]]
          )
        }
        anchorMap[keys[i]] = newValues
      } else {
        anchorMap[keys[i]] = anchorMap[keys[i]]
      }
    }
    return anchorMap
  }

  const _copyAnchorMap = function () {
    return _cloneDeep(stateMap.anchor_map)
  }

  // if there are dependent paramerters check these also for changes (_s_)
  const _argsChanged = function (
    anchor_map_previous,
    anchor_map_proposed,
    key_name
  ) {
    let changed = false
    const key_name_s = '_s_' + key_name

    if (anchor_map_proposed[key_name_s]) {
      // has dependent parameter, compare stringified anchor map.
      changed =
        anchor_map_proposed[key_name_s] !== anchor_map_previous[key_name_s]
    } else {
      // compare only parameter with key_name
      changed = anchor_map_proposed[key_name] !== anchor_map_previous[key_name]
    }

    return changed
  }

  //DOM Methods
  const _handleHashChanged = function () {
    let anchor_map_previous = _copyAnchorMap(),
      anchor_map_proposed,
      is_ok = true,
      key_names,
      retVal,
      key_name_dep

    try {
      anchor_map_proposed = $.uriAnchor.makeAnchorMap()
    } catch (error) {
      $.uriAnchor.setAnchor(anchor_map_previous, null, true)
      return false
    }
    stateMap.anchor_map = anchor_map_proposed

    // filter out the dependent parameters, so they are not interfering in the below loop. they are checked in the argsChanged()-function.
    key_names = Object.keys(anchor_map_proposed)
    key_names = key_names.filter(function (key) {
      return key[0] !== '_'
    })

    key_names.forEach(function (key_name) {
      if (
        !anchor_map_previous ||
        _argsChanged(anchor_map_previous, anchor_map_proposed, key_name)
      ) {
        if (stateMap.function_map[key_name]) {
          for (let i = 0; i < stateMap.function_map[key_name].length; i++) {
            if (typeof stateMap.function_map[key_name][i] === 'function') {
              retVal = {}
              // get the dependent parameter if ther is one
              key_name_dep = '_' + key_name
              retVal[key_name] = anchor_map_proposed[key_name]
              retVal[key_name_dep] = anchor_map_proposed[key_name_dep]

              if (retVal[key_name_dep]) {
                // pass parameter and dependent parameter to registered function
                is_ok = is_ok && !!stateMap.function_map[key_name][i](retVal)
              } else {
                // only pass parameter
                is_ok = is_ok && !!stateMap.function_map[key_name][i](retVal[key_name])
              }
            }
          }
        }
      }
    })

    if (!is_ok) {
      if (anchor_map_previous) {
        $.uriAnchor.setAnchor(anchor_map_previous, null, true)
        stateMap.anchor_map = anchor_map_previous
      } else {
        delete anchor_map_proposed.page
        $.uriAnchor.setAnchor(anchor_map_proposed, null, true)
      }
    }
    if (
      window.whiteListHash &&
      window.whiteListHash.length > 0 &&
      typeof ga !== 'undefined' &&
      window[window.disableStr] === false
    ) {
      if (
        location.hash.indexOf('!&') < 0 &&
        window.whiteListHash.filter(function (item) {
          return location.hash.indexOf(item) >= 0
        }).length > 0
      ) {
        ga('send', 'pageview', {
          page: location.pathname + location.search + location.hash,
        })
      }
    }

    return false
  }

  //Public Methods
  const registerFunction = function (key, callback) {
    if (!stateMap.function_map[key]) {
      stateMap.function_map[key] = []
    }
    stateMap.function_map[key].push(callback)
  }

  const removeFunction = function (key) {
    delete stateMap.function_map[key]
  }

  const getCurrentValue = function (key, getDependentArgs?: any) {
    let anchor_map_proposed
    const _getDependentArgs = getDependentArgs ? true : false
    try {
      anchor_map_proposed = $.uriAnchor.makeAnchorMap()
    } catch (error) {
      return false
    }
    if (_getDependentArgs) {
      let retVal = {}
      retVal[key] = anchor_map_proposed[key]
      retVal['_' + key] = anchor_map_proposed['_' + key]
      return retVal
    }
    return anchor_map_proposed[key]
  }

  const changeAnchorPart = function (arg_map, arg_link?: string, doNotrespectInHistory: boolean = true) {
    // If a link is provided we open it directly and append the anchor
    if (arg_link) {
      window.location.assign(
        arg_link + '#!' + $.uriAnchor.makeAnchorString(arg_map)
      )
      return true
    }

    let anchor_map_revise = _copyAnchorMap(),
      bool_return = true,
      key_name,
      key_name_dep

    //Begin merge changes into anchor map
    for (key_name in arg_map) {
      //skip dependent keys durin iteration
      if (key_name.indexOf('_') !== 0 && arg_map[key_name] !== undefined) {
        //update independent key value

        anchor_map_revise[key_name] = arg_map[key_name]

        //update matching dependet key
        key_name_dep = '_' + key_name
        if (arg_map[key_name_dep]) {
          anchor_map_revise[key_name_dep] = arg_map[key_name_dep]
        } else {
          delete anchor_map_revise[key_name_dep]
          delete anchor_map_revise['_s' + key_name_dep]
        }
      }
    }

    $(window).unbind('hashchange', _handleHashChanged)
    try {
      //true gesetzt, damit es nicht mehr in history geht, funktioniert natürlich nicht wenn man #! über link aufruft
      $.uriAnchor.setAnchor(
        _uriEncodeDependentMap(anchor_map_revise),
        null,
        doNotrespectInHistory
      )

      //update state, so we don't get races
      _handleHashChanged()
    } catch (error) {
      $.uriAnchor.setAnchor(stateMap.anchor_map, null, doNotrespectInHistory)
      bool_return = false
      console.log('Error in set Anchor map')
      console.log(error)
    }

    $(window).bind('hashchange', _handleHashChanged).trigger('hashchange')


    return true
  }

  const configModule = function () {
    $.uriAnchor.configModule()

    return true
  }

  const initModule = function () {
    $(window).unbind('hashchange', _handleHashChanged)

    $(window).bind('hashchange', _handleHashChanged).trigger('hashchange')

    return true
  }

  return {
    configModule: configModule,
    initModule: initModule,
    changeAnchorPart: changeAnchorPart,
    registerFunction: registerFunction,
    removeFunction: removeFunction,
    getCurrentValue: getCurrentValue,
  }
})()
