interface Window {
  spa: any
}

window.spa.util.fade = (function () {
  const INLINE = 'inline'
  const NONE = 'none'
  enum DIRECTIONS {
    IN = 'in',
    OUT = 'out',
  }

  const fade = function (
    element: HTMLElement,
    type: DIRECTIONS,
    ms: number,
    visibleDisplay: string = INLINE
  ) {
    return new Promise((resolve) => {
      let isIn = type === DIRECTIONS.IN,
        opacity = element.style.opacity
          ? parseInt(element.style.opacity)
          : isIn
          ? 0
          : 1,
        interval = 50,
        duration = ms,
        gap = interval / duration

      function func() {
        opacity = isIn ? opacity + gap : opacity - gap

        if (opacity <= 0) {
          window.clearInterval(fading)
          element.style.display = NONE
          element.style.opacity = '0'
          resolve(true)
        } else if (opacity >= 1) {
          window.clearInterval(fading)
          element.style.opacity = '1'
          resolve(true)
        } else {
          element.style.display = visibleDisplay
          element.style.opacity = opacity < 0.001 ? '0' : opacity.toString()
        }
      }

      let fading = window.setInterval(func, interval)
    })
  }

  const show = function (
    element: HTMLElement,
    ms: number,
    visibleDisplay: string = INLINE
  ) {
    return fade(element, DIRECTIONS.IN, ms, visibleDisplay)
  }

  const hide = function (
    element: HTMLElement,
    ms: number,
    visibleDisplay: string = INLINE
  ) {
    return fade(element, DIRECTIONS.OUT, ms, visibleDisplay)
  }

  return {
    show: show,
    hide: hide,
  }
})()
