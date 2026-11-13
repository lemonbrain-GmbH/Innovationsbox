/*jslint       browser: true, continue: true,
 devel: true, indent: 2, maxerr: 50,
 newcap: true, nomen: true, plusplus: true,
 regexp: true, sloppy: true, vars: false,
 white: true
 */
/*global window: true, $: true */

enum Cursor {
    Progress = 'progress',
    Default = 'default'
}

enum Display {
    None = 'none',
    block = 'block'
}

interface Window {
  spa: any
}

 window.spa.util = (function () {
    'use strict';
    
    const 
    configMap : {
        showDebugInfos: boolean
    } = {
        showDebugInfos: true
    },
    globals: {
        screenXs: number,
        screenSm: number,
        screenMd: number,
        screenLg: number,
        screenXl: number,
        cocnreteEditPanelHeight: number
    } = {
        screenXs: 0,
        screenSm: 576,
        screenMd: 768,
        screenLg: 992,
        screenXl: 1200,
        cocnreteEditPanelHeight: 48
    },
    stateMap : {
        countLoaders: Object,
        showDebugInfos: boolean
    } = {
        countLoaders: {},
        showDebugInfos: true
    };

    const getGlobals = function(key: any) {
        if(key in globals) {
            return globals[key];
        } else {
            let error: any;
            log('Bad Key: ' + 
                'Global key |' + key + '| is not supportd');
            error = makeError('Bad Key', 
                'Global key |' + key + '| is not supportd');
            throw error;
        }
    };

    const log = function(message: string) {
        if(configMap.showDebugInfos) {
            console.log(message);
        }
    };

    const loaderShow = function(key: string, loader: HTMLDivElement) {
        stateMap.countLoaders[key] = (stateMap.countLoaders[key] || 0) + 1

        document.body.style.cursor = Cursor.Progress;
        loader.style.display = Display.block
 
        //log('add ' + key + ': ' + stateMap.countLoaders[key]);
    };

    const loaderHide = function (key: string, loader: HTMLDivElement) {
        --stateMap.countLoaders[key];

        if (stateMap.countLoaders[key] < 1) {
            delete stateMap.countLoaders[key];
            loader.style.display = Display.None
            document.body.style.cursor = Cursor.Default;
        }

        if(Object.keys(stateMap.countLoaders).length === 0) {
            document.body.style.cursor = Cursor.Default;
        }

        //log('remove ' + key + ': ' + stateMap.countLoaders[key]);
        //log(stateMap.countLoaders);
    };

    const makeError = function (name_text: string, msg_text: string) {
        const error = new Error();
        error.name = name_text;
        error.message = msg_text;
        
        return error;
    };

    const setConfigMap = function (arg_map: any) {
        const input_map = arg_map.input_map,
        settable_map = arg_map.settable_map,
        config_map = arg_map.config_map;
        let key_name: string, error: any;

        for (key_name in input_map) {
            if (input_map.hasOwnProperty(key_name)) {
                if (settable_map.hasOwnProperty(key_name)) {
                    config_map[key_name] = input_map[key_name];
                } else {
                    log('Bad Input: ' + 
                        'Setting config key |' + key_name + '| is not supportd');
                    error = makeError('Bad Input', 
                        'Setting config key |' + key_name + '| is not supportd');
                    throw error;
                }
            }
        }
    };

    return {
        makeError: makeError,
        setConfigMap: setConfigMap,
        getGlobals: getGlobals,
        log: log,
        loaderShow: loaderShow,
        loaderHide: loaderHide,
    };
}());