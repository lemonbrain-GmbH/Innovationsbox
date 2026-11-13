"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/blocks/autonav/templates/innovationsbox/view"],{

/***/ "./public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.ts"
/*!***************************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.ts ***!
  \***************************************************************************************************/
() {



function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
var __classPrivateFieldSet = this && this.__classPrivateFieldSet || function (receiver, state, value, kind, f) {
  if (kind === "m") throw new TypeError("Private method is not writable");
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a setter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot write private member to an object whose class did not declare it");
  return kind === "a" ? f.call(receiver, value) : f ? f.value = value : state.set(receiver, value), value;
};
var __classPrivateFieldGet = this && this.__classPrivateFieldGet || function (receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
};
var _NavigationDrawer_opener, _NavigationDrawer_closer, _NavigationDrawer_drawer, _NavigationDrawer_overlay;
var NavSelectors;
(function (NavSelectors) {
  NavSelectors["NAV_CONTAINER"] = ".nav-container";
  NavSelectors["NAV_DRAWER"] = ".nav-drawer";
  NavSelectors["NAV_OPENER"] = ".nav-toggle";
  NavSelectors["NAV_CLOSER"] = ".nav-close";
  NavSelectors["NAV_OVERLAY"] = ".nav-overlay";
})(NavSelectors || (NavSelectors = {}));
var NavigationDrawer = /*#__PURE__*/function () {
  function NavigationDrawer(container) {
    _classCallCheck(this, NavigationDrawer);
    _NavigationDrawer_opener.set(this, void 0);
    _NavigationDrawer_closer.set(this, void 0);
    _NavigationDrawer_drawer.set(this, void 0);
    _NavigationDrawer_overlay.set(this, void 0);
    __classPrivateFieldSet(this, _NavigationDrawer_opener, container.querySelector(NavSelectors.NAV_OPENER), "f");
    __classPrivateFieldSet(this, _NavigationDrawer_closer, container.querySelector(NavSelectors.NAV_CLOSER), "f");
    __classPrivateFieldSet(this, _NavigationDrawer_drawer, container.querySelector(NavSelectors.NAV_DRAWER), "f");
    __classPrivateFieldSet(this, _NavigationDrawer_overlay, container.querySelector(NavSelectors.NAV_OVERLAY), "f");
    this.bindEvents();
  }
  return _createClass(NavigationDrawer, [{
    key: "bindEvents",
    value: function bindEvents() {
      var _this = this;
      var _a, _b, _c;
      (_a = __classPrivateFieldGet(this, _NavigationDrawer_opener, "f")) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
        _this.open();
      });
      (_b = __classPrivateFieldGet(this, _NavigationDrawer_closer, "f")) === null || _b === void 0 ? void 0 : _b.addEventListener('click', function () {
        _this.close();
      });
      (_c = __classPrivateFieldGet(this, _NavigationDrawer_overlay, "f")) === null || _c === void 0 ? void 0 : _c.addEventListener('click', function () {
        _this.close();
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') _this.close();
      });
    }
  }, {
    key: "open",
    value: function open() {
      var _a, _b, _c, _d;
      (_a = __classPrivateFieldGet(this, _NavigationDrawer_drawer, "f")) === null || _a === void 0 ? void 0 : _a.classList.add('active');
      (_b = __classPrivateFieldGet(this, _NavigationDrawer_drawer, "f")) === null || _b === void 0 ? void 0 : _b.setAttribute('aria-hidden', 'false');
      (_c = __classPrivateFieldGet(this, _NavigationDrawer_overlay, "f")) === null || _c === void 0 ? void 0 : _c.classList.add('active');
      (_d = __classPrivateFieldGet(this, _NavigationDrawer_opener, "f")) === null || _d === void 0 ? void 0 : _d.setAttribute('aria-expanded', 'true');
    }
  }, {
    key: "close",
    value: function close() {
      var _a, _b, _c, _d;
      (_a = __classPrivateFieldGet(this, _NavigationDrawer_drawer, "f")) === null || _a === void 0 ? void 0 : _a.classList.remove('active');
      (_b = __classPrivateFieldGet(this, _NavigationDrawer_drawer, "f")) === null || _b === void 0 ? void 0 : _b.setAttribute('aria-hidden', 'true');
      (_c = __classPrivateFieldGet(this, _NavigationDrawer_overlay, "f")) === null || _c === void 0 ? void 0 : _c.classList.remove('active');
      (_d = __classPrivateFieldGet(this, _NavigationDrawer_opener, "f")) === null || _d === void 0 ? void 0 : _d.setAttribute('aria-expanded', 'false');
    }
  }]);
}();
_NavigationDrawer_opener = new WeakMap(), _NavigationDrawer_closer = new WeakMap(), _NavigationDrawer_drawer = new WeakMap(), _NavigationDrawer_overlay = new WeakMap();
function initNavigation() {
  document.querySelectorAll(NavSelectors.NAV_CONTAINER).forEach(function (el) {
    return new NavigationDrawer(el);
  });
  // Move auth buttons from header into nav drawer
  var headerAuth = document.querySelector('[data-header-auth]');
  var navAuth = document.querySelector('[data-nav-auth]');
  if (headerAuth && navAuth) {
    while (headerAuth.firstChild) {
      navAuth.appendChild(headerAuth.firstChild);
    }
  }
}
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initNavigation);
} else {
  initNavigation();
}

/***/ },

/***/ "./public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.scss"
/*!***********************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.scss ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./public/packages/inno_frontend/components/organisms/innovation-teaser/innovation-teaser.scss"
/*!*****************************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/organisms/innovation-teaser/innovation-teaser.scss ***!
  \*****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./public/packages/inno_frontend/components/organisms/update-teaser/update-teaser.scss"
/*!*********************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/organisms/update-teaser/update-teaser.scss ***!
  \*********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./public/packages/inno_frontend/components/molecules/info-panel/info-panel.scss"
/*!***************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/molecules/info-panel/info-panel.scss ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/avatar.scss"
/*!************************************************!*\
  !*** ./resources/css/single_pages/avatar.scss ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/edit-profile.scss"
/*!******************************************************!*\
  !*** ./resources/css/single_pages/edit-profile.scss ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/expert-anfragen.scss"
/*!*********************************************************!*\
  !*** ./resources/css/single_pages/expert-anfragen.scss ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/innovationen.scss"
/*!******************************************************!*\
  !*** ./resources/css/single_pages/innovationen.scss ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/login.scss"
/*!***********************************************!*\
  !*** ./resources/css/single_pages/login.scss ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/members-directory.scss"
/*!***********************************************************!*\
  !*** ./resources/css/single_pages/members-directory.scss ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/members-profile.scss"
/*!*********************************************************!*\
  !*** ./resources/css/single_pages/members-profile.scss ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/moderation-benutzer.scss"
/*!*************************************************************!*\
  !*** ./resources/css/single_pages/moderation-benutzer.scss ***!
  \*************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/moderation-detail.scss"
/*!***********************************************************!*\
  !*** ./resources/css/single_pages/moderation-detail.scss ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/moderation-kategorien.scss"
/*!***************************************************************!*\
  !*** ./resources/css/single_pages/moderation-kategorien.scss ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/moderation.scss"
/*!****************************************************!*\
  !*** ./resources/css/single_pages/moderation.scss ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/projektidee-einreichen.scss"
/*!****************************************************************!*\
  !*** ./resources/css/single_pages/projektidee-einreichen.scss ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/projektidee-meine.scss"
/*!***********************************************************!*\
  !*** ./resources/css/single_pages/projektidee-meine.scss ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/projektraum.scss"
/*!*****************************************************!*\
  !*** ./resources/css/single_pages/projektraum.scss ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/single_pages/register.scss"
/*!**************************************************!*\
  !*** ./resources/css/single_pages/register.scss ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./public/packages/inno_frontend/components/_storybook/components.scss"
/*!*****************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/_storybook/components.scss ***!
  \*****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./resources/css/main.scss"
/*!*********************************!*\
  !*** ./resources/css/main.scss ***!
  \*********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss"
/*!*****************************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss ***!
  \*****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./public/packages/inno_frontend/components/organisms/hero-image/hero-image.scss"
/*!***************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/organisms/hero-image/hero-image.scss ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./public/packages/inno_frontend/components/organisms/content-block/content-block.scss"
/*!*********************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/organisms/content-block/content-block.scss ***!
  \*********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ },

/***/ "./public/packages/inno_frontend/components/molecules/feature-button/feature-button.scss"
/*!***********************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/molecules/feature-button/feature-button.scss ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["blocks/feature_link/templates/innovationsbox/view","blocks/content/templates/innovationsbox/view","blocks/hero_image/templates/innovationsbox/view","blocks/autonav/templates/innovationsbox/view","themes/innovationsbox/css/skins/default","storybook/components","themes/innovationsbox/css/single_pages/register","themes/innovationsbox/css/single_pages/projektraum","themes/innovationsbox/css/single_pages/projektidee-meine","themes/innovationsbox/css/single_pages/projektidee-einreichen","themes/innovationsbox/css/single_pages/moderation","themes/innovationsbox/css/single_pages/moderation-kategorien","themes/innovationsbox/css/single_pages/moderation-detail","themes/innovationsbox/css/single_pages/moderation-benutzer","themes/innovationsbox/css/single_pages/members-profile","themes/innovationsbox/css/single_pages/members-directory","themes/innovationsbox/css/single_pages/login","themes/innovationsbox/css/single_pages/innovationen","themes/innovationsbox/css/single_pages/expert-anfragen","themes/innovationsbox/css/single_pages/edit-profile","themes/innovationsbox/css/single_pages/avatar","blocks/info_panel/view","blocks/update_teaser/view","blocks/innovation_teaser/view","blocks/page_list/templates/innovationsbox/view"], () => (__webpack_exec__("./public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.ts"), __webpack_exec__("./public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss"), __webpack_exec__("./public/packages/inno_frontend/components/organisms/hero-image/hero-image.scss"), __webpack_exec__("./public/packages/inno_frontend/components/organisms/content-block/content-block.scss"), __webpack_exec__("./public/packages/inno_frontend/components/molecules/feature-button/feature-button.scss"), __webpack_exec__("./public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.scss"), __webpack_exec__("./public/packages/inno_frontend/components/organisms/innovation-teaser/innovation-teaser.scss"), __webpack_exec__("./public/packages/inno_frontend/components/organisms/update-teaser/update-teaser.scss"), __webpack_exec__("./public/packages/inno_frontend/components/molecules/info-panel/info-panel.scss"), __webpack_exec__("./resources/css/single_pages/avatar.scss"), __webpack_exec__("./resources/css/single_pages/edit-profile.scss"), __webpack_exec__("./resources/css/single_pages/expert-anfragen.scss"), __webpack_exec__("./resources/css/single_pages/innovationen.scss"), __webpack_exec__("./resources/css/single_pages/login.scss"), __webpack_exec__("./resources/css/single_pages/members-directory.scss"), __webpack_exec__("./resources/css/single_pages/members-profile.scss"), __webpack_exec__("./resources/css/single_pages/moderation-benutzer.scss"), __webpack_exec__("./resources/css/single_pages/moderation-detail.scss"), __webpack_exec__("./resources/css/single_pages/moderation-kategorien.scss"), __webpack_exec__("./resources/css/single_pages/moderation.scss"), __webpack_exec__("./resources/css/single_pages/projektidee-einreichen.scss"), __webpack_exec__("./resources/css/single_pages/projektidee-meine.scss"), __webpack_exec__("./resources/css/single_pages/projektraum.scss"), __webpack_exec__("./resources/css/single_pages/register.scss"), __webpack_exec__("./public/packages/inno_frontend/components/_storybook/components.scss"), __webpack_exec__("./resources/css/main.scss")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);