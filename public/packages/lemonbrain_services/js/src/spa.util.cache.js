/*jslint       browser: true, continue: true,
 devel: true, indent: 2, maxerr: 50,
 newcap: true, nomen: true, plusplus: true,
 regexp: true, sloppy: true, vars: false,
 white: true
 */
/*global window: true, localStorage: true, Exception: true */

// interface Cache {
//     get(k: string, local: boolean): any;
//     set(k: string, v: any, local: boolean): any;
//     clear(k: string, local: boolean): void;
// }

 
window.spa.util.cache = (function () {
    'use strict';
    
    function CacheProvider(prefix) {
        // values will be stored here
        this._cache = {};
        this._prefix = prefix + '-';
    }

    try {
        CacheProvider.hasLocalStorage = ('localStorage' in window) && window['localStorage'] !== null;
    } catch (ex) {
        CacheProvider.hasLocalStorage = false;
    }

    CacheProvider.prototype = {
        /**
         * {String} k - the key
         * {Boolean} local - get this from local storage?
         * {Boolean} o - is the value you put in local storage an object?
         */
        get: function (k, local) {
            if (local && CacheProvider.hasLocalStorage) {
                /*var action = o ? 'getObject' : 'getItem';
                return localStorage[action](k) || undefined;*/
                return localStorage.getItem(this._prefix + k);
            } else {
                return this._cache[this._prefix + k] || undefined;
            }
        },
        /**
         * {String} k - the key
         * {Object} v - any kind of value you want to store
         * however only objects and strings are allowed in local storage
         * {Boolean} local - put this in local storage
         */
        set: function (k, v, local) { 
            if (local && CacheProvider.hasLocalStorage) {
                /*if (typeof v !== 'string') {
                    // make assumption if it's not a string, then we're storing an object
                    localStorage.setObject(k, v);
                } else {*/
                    try {
                        localStorage.setItem(this._prefix + k, v);
                    } catch (ex) {
                        if (ex.name === 'QUOTA_EXCEEDED_ERR') {
                            // developer needs to figure out what to start invalidating
                            throw new Exception(v);
                        }
                    }
                //}
            } else {
                // put in our local object
                this._cache[this._prefix + k] = v;
            }
            // return our newly cached item
            return v;
        },
        /**
         * {String} k - the key
         * {Boolean} local - put this in local storage
         * {Boolean} o - is this an object you want to put in local storage?
         */
        clear: function (k, local) {
            if (local && CacheProvider.hasLocalStorage) {
                localStorage.removeItem(this._prefix + k);
            }
            // delete in both caches - doesn't hurt.
            delete this._cache[this._prefix + k];
        }

    };
    
    return {
        CacheProvider: CacheProvider
    };
}());