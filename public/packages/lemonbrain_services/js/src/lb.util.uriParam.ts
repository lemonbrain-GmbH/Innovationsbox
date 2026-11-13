interface Window {
  spa: any
}

window.spa.util.uriParam = (function () {
  const configMap = {
    clean0_regex: /^[#!]*/,
    clean1_regex: /\?[^?]*$/,
    settable_map_key: { schema_map: true },
    schema_map: null,
  }
  //Module Variables
  let stateMap = {
    parameter_map: {},
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

  const _copyParameterMap = function () {
    return _cloneDeep(stateMap.parameter_map)
  }

  // if there are dependent paramerters check these also for changes (_s_)
  const _argsChanged = function (
    parameter_map_previous,
    parameter_map_proposed,
    key_name
  ) {
    let changed = false
    const key_name_s = '_s_' + key_name

    if (parameter_map_proposed[key_name_s]) {
      // has dependent parameter, compare stringified parameter map.
      changed = parameter_map_proposed[key_name_s] !== parameter_map_previous[key_name_s]
    } else {
      // compare only parameter with key_name
      changed = parameter_map_proposed[key_name] !== parameter_map_previous[key_name]
    }

    return changed
  }

  //DOM Methods
  const _handleParameterChanged = function () {
    let parameter_map_previous = _copyParameterMap(),
      parameter_map_proposed,
      is_ok = true,
      key_names,
      retVal,
      key_name_dep

    try {
      parameter_map_proposed = window.spa.util.uriParam.urlHandler.makeParameterMap()
    } catch (error) {
      window.spa.util.uriParam.urlHandler.setParameter(parameter_map_previous, null, true)
      return false
    }

    stateMap.parameter_map = parameter_map_proposed

    // filter out the dependent parameters, so they are not interfering in the below loop. they are checked in the argsChanged()-function.
    key_names = Object.keys(parameter_map_proposed)
    key_names = key_names.filter(function (key) {
      return key[0] !== '_'
    })

    key_names.forEach(function (key_name) {
      if (
        !parameter_map_previous ||
        _argsChanged(parameter_map_previous, parameter_map_proposed, key_name)
      ) {
        if (stateMap.function_map[key_name]) {
          for (let i = 0; i < stateMap.function_map[key_name].length; i++) {
            if (typeof stateMap.function_map[key_name][i] === 'function') {
              retVal = {}
              // get the dependent parameter if there is one
              key_name_dep = '_' + key_name
              retVal[key_name] = parameter_map_proposed[key_name]
              retVal[key_name_dep] = parameter_map_proposed[key_name_dep]

              if (retVal[key_name_dep]) {
                // pass parameter and dependent parameter to registered function
                is_ok = is_ok && !!stateMap.function_map[key_name][i](retVal)
              } else {
                // only pass parameter
                is_ok =
                  is_ok &&
                  !!stateMap.function_map[key_name][i](retVal[key_name])
              }
            }
          }
        }
      }
    })

    if (!is_ok) {
      if (parameter_map_previous) {
        window.spa.util.uriParam.urlHandler.setParameter(parameter_map_previous, null, true)
        stateMap.parameter_map = parameter_map_previous
      } else {
        delete parameter_map_proposed.page
        window.spa.util.uriParam.urlHandler.setParameter(parameter_map_proposed, null, true)
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
    let parameter_map_proposed
    const _getDependentArgs = getDependentArgs ? true : false
    try {
      parameter_map_proposed = window.spa.util.uriParam.urlHandler.makeParameterMap()
    } catch (error) {
      return false
    }
    if (parameter_map_proposed) {
      if (_getDependentArgs) {
        let retVal = {}
        retVal[key] = parameter_map_proposed[key]
        retVal['_' + key] = parameter_map_proposed['_' + key]
        return retVal
      }
      return parameter_map_proposed[key]
    }
    return null
  }

  const changeParameterPart = function (
    arg_map,
    arg_link?: string,
    goesToHistory = true
  ) {
    // If a link is provided we open it directly and append the parameter
    if (arg_link) {
      window.spa.util.uriParam.urlHandler.setParameter(arg_map, null, true, null, arg_link)
      return true
    }

    let parameter_map_revise = _copyParameterMap(),
      bool_return = true,
      key_name,
      key_name_dep

    //Begin merge changes into parameter map
    for (key_name in arg_map) {
      //skip dependent keys durin iteration
      if (key_name.indexOf('_') !== 0 && arg_map[key_name] !== undefined) {
        //update independent key value

        parameter_map_revise[key_name] = arg_map[key_name]

        //update matching dependet key
        key_name_dep = '_' + key_name
        if (arg_map[key_name_dep]) {
          parameter_map_revise[key_name_dep] = arg_map[key_name_dep]
        } else {
          delete parameter_map_revise[key_name_dep]
          delete parameter_map_revise['_s' + key_name_dep]
        }
      }
    }

    try {
      //true gesetzt, damit es nicht mehr in history geht, funktioniert natürlich nicht wenn man ? über link aufruft
      window.spa.util.uriParam.urlHandler.setParameter(parameter_map_revise, null, !goesToHistory)
    } catch (error) {
      window.spa.util.uriParam.urlHandler.setParameter(stateMap.parameter_map, null, true)
      bool_return = false
      console.error('Error in set Parameter map')
      console.error(error)
    }

    return true
  }

  const initModule = function () {
    window.spa.util.uriParam.urlHandler.initModule()
    //gets called with changeParameter
    window.addEventListener('pushState', _handleParameterChanged)
    window.addEventListener('replaceState', _handleParameterChanged)
    //gets called with back / foward
    window.addEventListener('popstate', _handleParameterChanged)
    _handleParameterChanged()

    return true
  }

  return {
    initModule: initModule,
    changeAnchorPart: changeParameterPart, //backwards compatibility
    changeParameter: changeParameterPart,
    registerFunction: registerFunction,
    removeFunction: removeFunction,
    getCurrentValue: getCurrentValue,
  }
})()
