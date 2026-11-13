/*jslint       browser: true, continue: true,
 devel: true, indent: 2, maxerr: 50,
 newcap: true, nomen: true, plusplus: true,
 regexp: true, sloppy: true, vars: false,
 white: true
 */
 /*global window: true */
 
 window.spa.util.FunctionQueue = function() {
    'use strict';
    
    const 
    configMap = {
        waitDelay: 30
    },
    stateMap = {
        queue: [],
        isRunning: false,
        currentFunctionDone: false
    };

    //Utility Methods
    const _waitFunction = function(callback) {
        if(!stateMap.currentFunctionDone) {
            setTimeout(function() {
                _waitFunction(callback);
            }, configMap.waitDelay);
        } else {
            callback();
        }
    };

    //DOM Methods

    //Public Methods 
    const add = function(callback) {
        stateMap.queue.push(callback);
    };

    const functionDone = function() {
        stateMap.currentFunctionDone = true;
    };

    const run = function() {
        if(!stateMap.isRunning) {
            stateMap.isRunning = true;
            //call non blocking
            setTimeout(function() {
                const runner = function() {
                    if(stateMap.queue.length > 0) {
                        if(typeof stateMap.queue[0] === 'function') {
                            stateMap.currentFunctionDone = false;

                            try {
                                stateMap.queue[0]();
                            } catch(e) {
                                window.spa.util.log('Function in function queue error: ');
                                window.spa.util.log(e);
                            }
                            _waitFunction(function() {
                                stateMap.queue.splice(0, 1);
                                runner();
                            });
                        }
                    }
                };

                runner();

                stateMap.isRunning = false;
            }, 10);
        }
    };

    return {
        add: add,
        run: run,
        functionDone: functionDone
    };
};