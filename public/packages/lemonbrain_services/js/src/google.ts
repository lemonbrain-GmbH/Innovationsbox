interface Window {
  google: any;
  spa: any;
  dataLayer: any;
  whiteListHash: string[];
}
declare const ga: any;

//https://developers.google.com/tag-platform/devguides/privacy?sjid=10554438146362836452-EU&hl=de#disable_google_analytics

window.google = (function () {
  const GTM_EVENT_NAME = "cookieAllowed";

  const GA_DISABLE = "ga-disable-";

  const COOKIE_BLOCKER_EVENT_NAME = "cookieSettingsLoaded";
  const COOKIE_BLOCKER_SETTINGS_NAME = "cookieSettings";

  const SCRIPT = "script";
  const DATA_LAYER = "dataLayer";

  let configMap: {
    ga4Id: string;
    gtmId: string;
    whiteListHash: string[];
  } = {
    ga4Id: "",
    gtmId: "",
    whiteListHash: [],
  },
  stateMap: {
    globalSettings?: {
      google_cookies: boolean;
    };
  } = {
    globalSettings: undefined,
  };

  //Utility Methods
  const _startUpGoogle = function () {
    if (configMap.ga4Id !== "") {
      window.dataLayer = window.dataLayer || [];
      function gtag() {
        window.dataLayer.push(arguments);
      }
      //@ts-ignore Google wants the arguments of type arguments no hack helps here
      gtag("js", new Date());
      //@ts-ignore Google wants the arguments of type arguments no hack helps here
      gtag("config", configMap.ga4Id);

      const firstScriptTag = document.getElementsByTagName(SCRIPT)[0];
      const script = document.createElement(SCRIPT);
      script.async = true;
      script.src =
        "https://www.googletagmanager.com/gtag/js?id=" + configMap.ga4Id;

      document.addEventListener("DOMContentLoaded", () => {
        firstScriptTag.parentNode?.insertBefore(script, firstScriptTag);
      });
    }

    if (configMap.gtmId !== "") {
      (function (window, document, script, layer, i) {
        window[layer] = window[layer] || [];
        window[layer].push({
          "gtm.start": new Date().getTime(),
          event: "gtm.js",
        });
        const firstScriptTag = document.getElementsByTagName(script)[0],
          scriptTag = document.createElement(script) as HTMLScriptElement,
          dl = layer != DATA_LAYER ? "&l=" + layer : "";
        scriptTag.async = true;
        scriptTag.src = "https://www.googletagmanager.com/gtm.js?id=" + i + dl;

        document.addEventListener("DOMContentLoaded", () => {
          firstScriptTag.parentNode?.insertBefore(scriptTag, firstScriptTag);
        });
      })(window, document, SCRIPT, DATA_LAYER, configMap.gtmId);
      (window.dataLayer = window.dataLayer || []).push({
        event: GTM_EVENT_NAME,
      });
    }
  };

  const _checkClientSettings = function () {
    stateMap.globalSettings = localStorage
      ? JSON.parse(localStorage.getItem(COOKIE_BLOCKER_SETTINGS_NAME) || "{}")
      : null;

    if (!stateMap.globalSettings) {
      window[`${GA_DISABLE}${configMap.ga4Id}`] = true;
    }

    return stateMap.globalSettings;
  };

  const _init = function () {
    if (!stateMap.globalSettings) {
      _checkClientSettings();
    }

    if (stateMap.globalSettings?.google_cookies) {
      _startUpGoogle();
    }
  };
  //DOM Methods

  //Event Handlers
  const _initEvent = function () {
    _addCookieSettingsChangeListener();
  };

  const _addCookieSettingsChangeListener = function () {
    window.addEventListener(COOKIE_BLOCKER_EVENT_NAME, (e: Event) => {
      if (e && (e as CustomEvent).detail) {
        const settings = (e as CustomEvent).detail as {
          google_cookies: boolean;
        };

        if (
          !stateMap.globalSettings || stateMap.globalSettings.google_cookies !== settings.google_cookies
        ) {
          if (!settings.google_cookies) {
            window[`${GA_DISABLE}${configMap.ga4Id}`] = true;
          } else {
            window[`${GA_DISABLE}${configMap.ga4Id}`] = false;
          }
          window.location.reload();
        }
      }
    });
  };

  //Public Methods
  const configModule = function (input_map) {
    configMap = {
      ...configMap,
      ...input_map,
    };

    return true;
  };

  const initModule = function () {
    _initEvent();
    _init();

    return true;
  };

  return {
    initModule: initModule,
    configModule: configModule,
  };
})();
