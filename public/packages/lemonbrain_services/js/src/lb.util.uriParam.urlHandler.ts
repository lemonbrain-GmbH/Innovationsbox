window.spa.util.uriParam.urlHandler = (function () {
  const configMap = {
    clean0_regex: /^[#!]*/,
    clean1_regex: /\?[^?]*$/,
    settable_map_key: { schema_map: true },
    schema_map: null,
  }

  const _getErrorReject = function (message) {
    var error = new Error()
    error.name = 'Parameter Schema Reject'
    error.message = message
    return error
  }

  // Begin internal utility to clean bookmark
  const _getCleanParameterString = function () {
    const urlParams = decodeURI(window.location.search)
    return urlParams.substring(1)
  }
  // End internal utility to clean bookmark

  // Begin internal utility /parseStringToMap/
  const _parseStringToMap = function (arg_map) {
    const input_string = arg_map.input_string || '',
      delimit_char = arg_map.delimit_char || '&',
      delimit_kv_char = arg_map.delimit_kv_char || '=',
      output_map = {}

    let splitter_array, i, key_val_array

    splitter_array = input_string.split(delimit_char)

    for (i = 0; i < splitter_array.length; i++) {
      key_val_array = splitter_array[i].split(delimit_kv_char)
      if (key_val_array.length === 1) {
        output_map[decodeURIComponent(key_val_array[0])] = true
      } else if (key_val_array.length === 2) {
        output_map[decodeURIComponent(key_val_array[0])] = decodeURIComponent(
          key_val_array[1]
        )
      }
    }
    return output_map
  }
  // End internal utility /parseStringToMap/

  const _uriEncodeDependentMap = function (parameterMap) {
    const keys = Object.keys(parameterMap)
    for (let i = 0; i < keys.length; i++) {
      if (typeof parameterMap[keys[i]] === 'object') {
        const subKeys = Object.keys(parameterMap[keys[i]])
        let newValues = {}
        for (let j = 0; j < subKeys.length; j++) {
          newValues[subKeys[j]] = encodeURIComponent(
            parameterMap[keys[i]][subKeys[j]]
          )
        }
        parameterMap[keys[i]] = newValues
      } else {
        parameterMap[keys[i]] = parameterMap[keys[i]]
      }
    }
    return parameterMap
  }

  // Begin utility /makeParameterString/
  // -- all the heavy lifting for setParameter ( see below )
  // Converts a map into the parameter component as described
  // in setParameter
  const _makeParameterString = function (parameter_map_in, option_map_in) {
    var parameter_map = parameter_map_in || {},
      option_map = option_map_in || {},
      delimit_char = option_map.delimit_char || '&',
      delimit_kv_char = option_map.delimit_kv_char || '=',
      sub_delimit_char = option_map.sub_delimit_char || ':',
      dep_delimit_char = option_map.dep_delimit_char || '|',
      dep_kv_delimit_char = option_map.dep_kv_delimit_char || ',',
      schema_map = configMap.schema_map,
      key_val_array = [],
      schema_map_val,
      schema_map_dep,
      schema_map_dep_val,
      key_name,
      key_value,
      class_name,
      output_kv_string,
      sub_key_name,
      dep_map,
      dep_key_name,
      dep_key_value,
      dep_kv_array

    if (getVarType(parameter_map) !== 'Object') {
      return false
    }

    for (key_name in parameter_map) {
      // filter out inherited properties
      if (parameter_map.hasOwnProperty(key_name)) {
        // skip empty and dependent keys
        if (!key_name) {
          continue
        }
        if (key_name.indexOf('_') === 0) {
          continue
        }

        // check against parameter schema if provided
        if (schema_map) {
          if (!schema_map[key_name]) {
            throw _getErrorReject(
              'Independent key |' + key_name + '| not authorized by parameter schema'
            )
          }
        }

        output_kv_string = ''
        key_value = parameter_map[key_name]

        if (key_value === undefined) {
          key_value = ''
        }

        class_name = getVarType(key_value)

        // check against parameter schema map of allowable
        // values is provided
        if (schema_map) {
          schema_map_val = schema_map[key_name]
          if (
            getVarType(schema_map_val) === 'Object' &&
            !schema_map_val[String(key_value)]
          ) {
            throw _getErrorReject(
              'Independent key-value pair |' +
              key_name +
              '|' +
              String(key_value) +
              '| not authorized by parameter schema'
            )
          }
        }
        // Booleans, we skip false
        if (class_name === 'Boolean') {
          if (key_value) {
            output_kv_string += encodeURIComponent(key_name)
          }
        }
        // String and Number
        else {
          output_kv_string +=
            encodeURIComponent(key_name) +
            delimit_kv_char +
            encodeURIComponent(key_value)
        }

        sub_key_name = '_' + key_name
        if (parameter_map.hasOwnProperty(sub_key_name)) {
          dep_map = parameter_map[sub_key_name]
          dep_kv_array = []

          if (schema_map) {
            schema_map_dep = schema_map[sub_key_name]
            if (!schema_map_dep) {
              throw _getErrorReject(
                'Dependent key |' +
                sub_key_name +
                '| not authorized by parameter schema'
              )
            }
          } else {
            schema_map_dep = null
          }

          for (dep_key_name in dep_map) {
            if (dep_map.hasOwnProperty(dep_key_name)) {
              dep_key_value = dep_map[dep_key_name]

              if (schema_map_dep) {
                schema_map_dep_val = schema_map_dep[dep_key_name]
                if (
                  getVarType(schema_map_dep_val) === 'Object' &&
                  !schema_map_dep_val[String(dep_key_value)]
                ) {
                  throw _getErrorReject(
                    'Dependent key-value pair |' +
                    dep_key_name +
                    '|' +
                    String(dep_key_value) +
                    '| not authorized by parameter schema'
                  )
                }
              }

              // Booleans, we skip false
              if (class_name === 'Boolean') {
                if (dep_key_value === true) {
                  dep_kv_array.push(encodeURIComponent(dep_key_name))
                }
              }
              // String and Number
              else {
                dep_kv_array.push(
                  encodeURIComponent(dep_key_name) +
                  dep_kv_delimit_char +
                  encodeURIComponent(dep_key_value)
                )
              }
            }
          }
          // append dependent arguments if there are any
          if (dep_kv_array.length > 0) {
            output_kv_string +=
              sub_delimit_char + dep_kv_array.join(dep_delimit_char)
          }
        }
        key_val_array.push(output_kv_string)
      }
    }

    return key_val_array.join(delimit_char)
  }
  // End utility /makeParameterString/
  //-------------------- END UTILITY METHODS -------------------

  //------------------- BEGIN PUBLIC METHODS -------------------
  // Begin public method /setParameter/
  // Purpose     :
  //   Sets Parameter component of the URI from a Map
  //   (The Parameter component is also known as the
  //   'hash fragment' or 'bookmark component')
  // Arguments  : positional -
  //   * 1 ( parameter_map )   : The map to be encoded to the URI parameter
  //   * 2 ( option_map )   : map of options
  //   * 3 ( replace_flag )  : boolean flag to replace the URI
  //     When true, the URI is replaced, which means the prior URI
  //     is not entered into the browser history
  // Environment : Expects the document.location browser object
  // Settings    : none
  // Returns     : boolean: true on success, false on failure
  // Throws      : none
  // Discussion  :
  //
  //  The first positional argument, parameter_map, may be a simple map:
  //    $.uriParameter.setParameter({
  //      page   : 'profile',
  //      slider : 'confirm',
  //      color  : 'red'
  //    });
  //
  //  This changes the URI parameter to:
  //     #!page=profile&slider=confirm&color=red
  //
  //  All these arguments are independent, that is, they can vary
  //  independent of each other. We also support dependent values -
  //  values that depend on others.
  //
  //  An independent argument key has no '_' prefix.  The same key name,
  //  prefixed by an '_', holds the arguments that are dependent on
  //  an independent argument.  The dependent key always points
  //  to a map.  Consider:
  //
  //    uriParameter.setParameter({
  //      page   : 'profile',
  //      _page  : {
  //        uname   : 'wendy',
  //        online  : 'today'
  //      }
  //    });
  //
  //  This changes the URI Parameter to:
  //    #!page=profile:uname,wendy|online,today
  //
  //  Only independent keys and their matching dependent keys are
  //  processed.  All other keys are ignored.  Importantly, this includes
  //  keys of the form _s_/key/ ( e.g. '_s_page' ) returned by makeParameterMap
  //
  //  Setting a more complex parameter map is illustrated below:
  //    $.uriParameter.setParameter({
  //      page : 'profile',
  //      _page : {
  //        uname   : 'wendy',
  //        online  : 'today'
  //      },
  //      slider  : 'confirm',
  //      _slider : {
  //       text   : 'hello',
  //       pretty : false
  //      },
  //      color : 'red'
  //    });
  //
  //  This sets the URI Parameter to:
  //     #!page=profile:uname,wendy|online,today&slider=confirm:text,hello\
  //       |pretty,false&color=red
  //
  //   Options: The second positional argument tp this method, option_map,
  //   provides a number of options for delimiters:
  //     * delimit_char     : delimiter independent args
  //       Defaults to '&'
  //     * delimit_kv_char  : delimiter key-value of independent args
  //       Defaults to '='
  //     * sub_delimit_char : delimiter independent and dependent args
  //       Defaults to ':'
  //     * dep_delimit_char : delimiter between key-value of dependent args
  //       Defaults to '|'
  //     * dep_kv_delimit_char : key-value delimiter for dependent args.
  //       Defaults to ','
  //
  //   Boolean values ( as part of a key-value pair ) are convert into
  //     the stings 'true' or 'false'.
  //
  //  Validation:
  //
  //  As of 1.0, the ability to optionally check the validity of the
  //  Parameter against a schema has been included.  Since we don't expect
  //  the allowable schema to change during run-time, we use a
  //  module configuration to set the schema, like so:
  //
  //    $uriParameter.configModule({
  //      schema_map : {
  //        page    : { profile : true, pdf : true },
  //        _page   : {
  //          uname   : true,
  //          online  : { 'today','yesterday','earlier' }
  //        },
  //        slider  : { confirm : 'deny' },
  //        _slider : { text : 'goodbye' },
  //        color   : { red : true, green : true, blue : true }
  //      }
  //    });
  //
  //  This check occurs only during setting of the Parameter, not
  //  during its parsing ( See makeParameterMap )
  //
  //  The replace_flag instructs the routine to replace the uri,
  //  discarding browser history
  //
  const setParameter = function (
    parameter_map,
    option_map,
    replace_flag: boolean = false,
    title: string | null = null,  // this is no longer used, but kept for backwards compatibility
    navigateTo: string | null = null,
  ) {
    parameter_map = _uriEncodeDependentMap(parameter_map)
    const queryString = _makeParameterString(parameter_map, option_map)
    const hash = window.location.hash

    if (queryString && queryString.length > 0 || hash && hash.length > 0) {
      let newUrl: string | null = null
      if (navigateTo) {
        newUrl = navigateTo
      } else {
        newUrl = window.location.origin + window.location.pathname
      }
      newUrl += queryString && queryString.length > 0 ? '?' + queryString : ''
      newUrl += hash && hash.length > 0 ? hash : ''
      if (replace_flag) {
        window.history.replaceState(
          {},
          "",
          newUrl
        )
      } else {
        window.history.pushState(
          {},
          "",
          newUrl
        )
      }
    }
  }
  // End public method /setParameter/

  // Begin public method /makeParameterMap/
  // Purpose     : Parses URI parameter and returns as map
  // Arguments  : none
  // Environment : Expects the document.location browser object
  // Settings    : none
  // Returns     : Map
  // Throws      : none
  //
  // Discussion :
  //   Parses the browser URI parameter into a map using the same
  //   rules used to set the parameter in the method setParameter
  //   ( see above ).
  //
  //   This method creates an additional key type, _s_<indendent_arg>
  //   for each independent argument with dependent arguments.
  //
  //   These keys point to a string representation of the independent
  //   argument along with all its dependent arguments.
  //
  //   These values are ignored by setParameter, but they are useful
  //   for routines using setParameter to check if a part of the parameter
  //   has changed.
  //
  // Example:
  //   If the browser URI Parameter looks like this:
  //     #!page=profile:uname,wendy|online,true&slider=confirm:text,hello\
  //     |pretty,false&color=red
  //
  //   Then calling $.uriParameter.makeParameterMap();
  //   will return a map that looks like so:
  //
  //     { page : 'profile',
  //       _page : {
  //         uname   : 'wendy',
  //         online  : 'today'
  //       },
  //       _s_page : 'profile:uname,wendy|online,today',
  //       slider  : 'confirm',
  //       _slider : {
  //        text   : 'hello',
  //        pretty : false
  //       },
  //       _s_slider : 'confirm:text,hello|pretty,false',
  //       color : 'red'
  //     };
  //

  const makeParameterMap = function () {
    var parameter_string = _getCleanParameterString(),
      parameter_map,
      idx,
      keys_array,
      key_name,
      key_value,
      dep_array

    if (parameter_string === '') {
      return {}
    }
    // first pass decompose
    parameter_map = _parseStringToMap({
      input_string: parameter_string,
      delimit_char: '&',
      delimit_kv_char: '=',
    })

    // extract keys to prevent run-away recursion when
    // adding keys to parameter_map, below
    keys_array = []
    for (key_name in parameter_map) {
      if (parameter_map.hasOwnProperty(key_name)) {
        keys_array.push(key_name)
      }
    }

    for (idx = 0; idx < keys_array.length; idx++) {
      key_name = keys_array[idx]
      key_value = parameter_map[key_name]

      if (getVarType(key_value) !== 'String' || key_name === '') {
        continue
      }

      // include string representation with all dependent keys and values
      parameter_map['_s_' + key_name] = key_value

      dep_array = key_value.split(':')

      if (dep_array[1] && dep_array[1] !== '') {
        parameter_map[key_name] = dep_array[0]
        parameter_map['_' + key_name] = _parseStringToMap({
          input_string: dep_array[1],
          delimit_char: '|',
          delimit_kv_char: ',',
        })
      }
    }
    return parameter_map
  }
  // End public method /makeParameterMap/

  // Begin public method /getVarType/
  // Returns 'Object', 'Array', 'String', 'Number', 'Boolean', 'Undefined'
  const getVarType = function (data) {
    if (data === undefined) {
      return 'Undefined'
    }
    if (data === null) {
      return 'Null'
    }
    return {}.toString.call(data).slice(8, -1)
  }
  // End public method /getVarType/

  // Begin public method /configModule/
  // Set configuration options
  const configModule = function (arg_map) {
    var settable_map = configMap.settable_map_key,
      key_name,
      error

    for (key_name in arg_map) {
      if (arg_map.hasOwnProperty(key_name)) {
        if (settable_map.hasOwnProperty(key_name)) {
          configMap[key_name] = arg_map[key_name]
        } else {
          error = new Error()
          error.name = 'Bad Input'
          error.message = 'Setting config key |' + key_name + '| is not supported'
          throw error
        }
      }
    }
  }
  // End public method /configModule/



  const initModule = function () {
    // -- We overwrite default behavior, so we can get a event if uri changes
    const pushState = history.pushState
    history.pushState = function () {
      pushState.apply(history, arguments)
      const eventResite = new CustomEvent('pushState', {
        detail: arguments,
      })
      window.dispatchEvent(eventResite)
    }

    const replaceState = history.replaceState
    history.replaceState = function () {
      replaceState.apply(history, arguments)
      const eventResite = new CustomEvent('replaceState', {
        detail: arguments,
      })
      window.dispatchEvent(eventResite)
    }
    // -- We overwrite default behavior, so we can get a event if uri changes
  }

  return {
    initModule,
    setParameter,
    makeParameterMap,
    getVarType,
    configModule,
  }
})()