/**
 * Quelle: http://www.bestjs.net/basicSwipe.js/demo.html
 */

interface Window {
    spa: any
}

window.spa.util.swipe = (function () {
    const THRESHOLD = 30;
    enum EVENTS {
        TOUCH_START = 'touchstart',
        MOOUSE_DOWN = 'mousedown',
        TOUCH_MOVE = 'touchmove',
        MOUSE_MOVE = 'mousemove',
        TOUCH_END = 'touchend',
        MOUSE_UP = 'mouseup',
    }
    enum CUSTOM_EVENTS {
        SWIPE_DOWN = 'swipeDown',
        SWIPE_UP = 'swipeUp',
        SWIPE_LEFT = 'swipeLeft',
        SWIPE_RIGHT = 'swipeRight',
    }

    const _executeEvent = function (eventName: string, eStart: number, eEnd: number, handleEvent: any) {
        const moveVal = eEnd - eStart;
        const moveAbsVal = Math.abs(moveVal);

        if (
            moveVal < 0 && 
            moveAbsVal > THRESHOLD && 
            eventName === CUSTOM_EVENTS.SWIPE_UP
        ) {
            handleEvent();
        }
        if (
            moveVal > 0 && 
            moveAbsVal > THRESHOLD && 
            eventName === CUSTOM_EVENTS.SWIPE_DOWN
        ) {
            handleEvent();
        }
        if (
            moveVal < 0 && 
            moveAbsVal > THRESHOLD && 
            eventName === CUSTOM_EVENTS.SWIPE_LEFT
        ) {
            handleEvent(); 
        }
        if (
            moveVal > 0 && 
            moveAbsVal > THRESHOLD && 
            eventName === CUSTOM_EVENTS.SWIPE_RIGHT
        ) {
            handleEvent();
        }
    }

    const initModule = function (theDom: HTMLElement, eventName: string, handleEvent: any) {
        let eStart = 0, 
            eEnd = 0;
    
        theDom.addEventListener(EVENTS.TOUCH_START, function(e){
            switch (eventName) {
                case CUSTOM_EVENTS.SWIPE_DOWN:
                case CUSTOM_EVENTS.SWIPE_UP:
                    eStart = e.targetTouches[0].clientY;
                    break;
                case CUSTOM_EVENTS.SWIPE_LEFT:
                case CUSTOM_EVENTS.SWIPE_RIGHT:
                    eStart = e.targetTouches[0].clientX;
                    break;
                default:
                    eStart = e.targetTouches[0].clientY;
                    break;
            }
        }, false);
    
        theDom.addEventListener(EVENTS.MOOUSE_DOWN, function(e){
            switch (eventName) {
                case CUSTOM_EVENTS.SWIPE_DOWN:
                case CUSTOM_EVENTS.SWIPE_UP:
                    eStart = e.clientY;
                    break;
                case CUSTOM_EVENTS.SWIPE_LEFT:
                case CUSTOM_EVENTS.SWIPE_RIGHT:
                    eStart = e.clientX;
                    break;
                default:
                    eStart = e.clientY;
                    break;
            }
        }, false);
    
        theDom.addEventListener(EVENTS.TOUCH_MOVE, function(e){
            e.preventDefault();
        }, false)
    
        theDom.addEventListener(EVENTS.MOUSE_MOVE, function(e){
            e.preventDefault();
        }, false)
    
        theDom.addEventListener(EVENTS.TOUCH_END, function(e){    
            switch (eventName) {
                case CUSTOM_EVENTS.SWIPE_DOWN:
                case CUSTOM_EVENTS.SWIPE_UP:
                    eEnd = e.changedTouches[0].clientY;
                    break;
                case CUSTOM_EVENTS.SWIPE_LEFT:
                case CUSTOM_EVENTS.SWIPE_RIGHT:
                    eEnd = e.changedTouches[0].clientX;
                    break;
                default:
                    eEnd = e.changedTouches[0].clientY;
                    break;
            }

            _executeEvent(eventName, eStart, eEnd, handleEvent);

        }, false);
    
        theDom.addEventListener(EVENTS.MOUSE_UP, function(e){    
            switch (eventName) {
                case CUSTOM_EVENTS.SWIPE_DOWN:
                case CUSTOM_EVENTS.SWIPE_UP:
                    eEnd = e.clientY;
                    break;
                case CUSTOM_EVENTS.SWIPE_LEFT:
                case CUSTOM_EVENTS.SWIPE_RIGHT:
                    eEnd = e.clientX;
                    break;
                default:
                    eEnd = e.clientY;
                    break;
            }
    
            _executeEvent(eventName, eStart, eEnd, handleEvent);
    
        }, false);
    }

    return {
        initModule: initModule
    }
}())