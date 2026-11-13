"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/storybook/components"],{

/***/ "./public/packages/inno_frontend/components/_storybook/components.ts"
/*!***************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/_storybook/components.ts ***!
  \***************************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {



// Aggregates all component TypeScript for Storybook preview.
// When adding a new component with JS behavior, add an import here.
Object.defineProperty(exports, "__esModule", ({
  value: true
}));
__webpack_require__(/*! ../molecules/alert/alert */ "./public/packages/inno_frontend/components/molecules/alert/alert.ts");
__webpack_require__(/*! ../molecules/dropdown/dropdown */ "./public/packages/inno_frontend/components/molecules/dropdown/dropdown.ts");
__webpack_require__(/*! ../molecules/modal/modal */ "./public/packages/inno_frontend/components/molecules/modal/modal.ts");
__webpack_require__(/*! ../molecules/tabs/tabs */ "./public/packages/inno_frontend/components/molecules/tabs/tabs.ts");
__webpack_require__(/*! ../molecules/password-field/password-field */ "./public/packages/inno_frontend/components/molecules/password-field/password-field.ts");
__webpack_require__(/*! ../molecules/user-menu/user-menu */ "./public/packages/inno_frontend/components/molecules/user-menu/user-menu.ts");
__webpack_require__(/*! ../organisms/navigation-drawer/navigation-drawer */ "./public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.ts");

/***/ },

/***/ "./public/packages/inno_frontend/components/molecules/alert/alert.ts"
/*!***************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/molecules/alert/alert.ts ***!
  \***************************************************************************/
() {



// Alert dismiss behavior
document.addEventListener('click', function (event) {
  var target = event.target;
  var dismissButton = target.closest('[data-alert-dismiss]');
  if (!dismissButton) return;
  var alert = dismissButton.closest('.alert');
  alert === null || alert === void 0 ? void 0 : alert.remove();
});

/***/ },

/***/ "./public/packages/inno_frontend/components/molecules/dropdown/dropdown.ts"
/*!*********************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/molecules/dropdown/dropdown.ts ***!
  \*********************************************************************************/
(__unused_webpack_module, exports) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.initDropdowns = void 0;
function initDropdowns() {
  var root = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
  root.querySelectorAll('[data-dropdown]').forEach(function (dropdown) {
    if (dropdown.dataset.dropdownInit === 'true') return;
    dropdown.dataset.dropdownInit = 'true';
    var trigger = dropdown.querySelector('[data-dropdown-trigger]');
    var menu = dropdown.querySelector('.dropdown__menu');
    if (!trigger || !menu) return;
    var items = function items() {
      return Array.from(menu.querySelectorAll('[role="menuitem"]:not([aria-disabled="true"])'));
    };
    var open = function open() {
      menu.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      var first = items()[0];
      first === null || first === void 0 ? void 0 : first.focus();
      document.addEventListener('click', onOutside);
    };
    var close = function close() {
      var restoreFocus = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
      menu.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      document.removeEventListener('click', onOutside);
      if (restoreFocus) trigger.focus();
    };
    var onOutside = function onOutside(e) {
      if (!dropdown.contains(e.target)) close(false);
    };
    trigger.addEventListener('click', function () {
      trigger.getAttribute('aria-expanded') === 'true' ? close() : open();
    });
    dropdown.addEventListener('keydown', function (e) {
      var _a, _b, _c, _d;
      var ke = e;
      var list = items();
      var idx = list.indexOf(document.activeElement);
      if (ke.key === 'Escape') {
        e.preventDefault();
        close();
      } else if (ke.key === 'ArrowDown') {
        e.preventDefault();
        if (menu.hidden) open();else (_a = list[(idx + 1) % list.length]) === null || _a === void 0 ? void 0 : _a.focus();
      } else if (ke.key === 'ArrowUp') {
        e.preventDefault();
        (_b = list[(idx - 1 + list.length) % list.length]) === null || _b === void 0 ? void 0 : _b.focus();
      } else if (ke.key === 'Home') {
        e.preventDefault();
        (_c = list[0]) === null || _c === void 0 ? void 0 : _c.focus();
      } else if (ke.key === 'End') {
        e.preventDefault();
        (_d = list[list.length - 1]) === null || _d === void 0 ? void 0 : _d.focus();
      }
    });
  });
}
exports.initDropdowns = initDropdowns;
if (typeof document !== 'undefined') {
  if (document.readyState !== 'loading') initDropdowns();else document.addEventListener('DOMContentLoaded', function () {
    return initDropdowns();
  });
}

/***/ },

/***/ "./public/packages/inno_frontend/components/molecules/modal/modal.ts"
/*!***************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/molecules/modal/modal.ts ***!
  \***************************************************************************/
() {



// Modal open/close behavior using native <dialog> API.
document.addEventListener('click', function (event) {
  var target = event.target;
  // Open trigger: [data-modal-open="#modal-id"]
  var openTrigger = target.closest('[data-modal-open]');
  if (openTrigger) {
    var selector = openTrigger.dataset.modalOpen;
    if (selector) {
      var dialog = document.querySelector(selector);
      dialog === null || dialog === void 0 ? void 0 : dialog.showModal();
      return;
    }
  }
  // Close trigger: button inside modal with [data-modal-close]
  var closeTrigger = target.closest('[data-modal-close]');
  if (closeTrigger) {
    var _dialog = closeTrigger.closest('dialog.modal');
    _dialog === null || _dialog === void 0 ? void 0 : _dialog.close();
    return;
  }
  // Backdrop click (click landed on the dialog itself, not its content)
  if (target.tagName === 'DIALOG' && target.classList.contains('modal')) {
    var _dialog2 = target;
    var rect = _dialog2.getBoundingClientRect();
    var clickedBackdrop = event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
    if (clickedBackdrop) {
      _dialog2.close();
    }
  }
});

/***/ },

/***/ "./public/packages/inno_frontend/components/molecules/password-field/password-field.ts"
/*!*********************************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/molecules/password-field/password-field.ts ***!
  \*********************************************************************************************/
() {



function initPasswordFields() {
  document.querySelectorAll('[data-password-field]').forEach(function (field) {
    var input = field.querySelector('input[type="password"], input[type="text"]');
    var toggle = field.querySelector('[data-password-toggle]');
    var showIcon = field.querySelector('.password-field__icon--show');
    var hideIcon = field.querySelector('.password-field__icon--hide');
    var strengthFill = field.querySelector('[data-password-strength-fill]');
    var strengthLabel = field.querySelector('[data-password-strength-label]');
    if (!input) return;
    // Toggle show/hide password
    if (toggle && showIcon && hideIcon) {
      toggle.addEventListener('click', function () {
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        field.classList.toggle('password-field--visible', isPassword);
        toggle.setAttribute('aria-label', isPassword ? 'Passwort verbergen' : 'Passwort anzeigen');
        if (isPassword) {
          showIcon.setAttribute('hidden', '');
          hideIcon.removeAttribute('hidden');
        } else {
          showIcon.removeAttribute('hidden');
          hideIcon.setAttribute('hidden', '');
        }
      });
    }
    // Password strength indicator
    if (strengthFill && strengthLabel) {
      var calculateStrength = function calculateStrength(password) {
        if (password.length === 0) return {
          score: 0,
          label: '',
          color: ''
        };
        var score = 0;
        if (password.length >= 5) score += 20;
        if (password.length >= 8) score += 20;
        if (password.length >= 12) score += 10;
        if (/[a-z]/.test(password)) score += 10;
        if (/[A-Z]/.test(password)) score += 10;
        if (/[0-9]/.test(password)) score += 15;
        if (/[^a-zA-Z0-9]/.test(password)) score += 15;
        score = Math.min(100, score);
        var label;
        var color;
        if (score < 40) {
          label = 'Schwach';
          color = 'var(--color-status-error-500)';
        } else if (score < 70) {
          label = 'Mittel';
          color = 'var(--color-status-warning-500)';
        } else {
          label = 'Stark';
          color = 'var(--color-status-success-500)';
        }
        return {
          score: score,
          label: label,
          color: color
        };
      };
      input.addEventListener('input', function () {
        var _calculateStrength = calculateStrength(input.value),
          score = _calculateStrength.score,
          label = _calculateStrength.label,
          color = _calculateStrength.color;
        strengthFill.style.width = "".concat(score, "%");
        strengthFill.style.setProperty('background-color', color);
        strengthLabel.textContent = label;
      });
    }
  });
}
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPasswordFields);
} else {
  initPasswordFields();
}

/***/ },

/***/ "./public/packages/inno_frontend/components/molecules/tabs/tabs.ts"
/*!*************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/molecules/tabs/tabs.ts ***!
  \*************************************************************************/
(__unused_webpack_module, exports) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.initTabs = void 0;
function initTabs() {
  var root = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
  root.querySelectorAll('[data-tabs]').forEach(function (wrapper) {
    if (wrapper.dataset.tabsInit === 'true') return;
    wrapper.dataset.tabsInit = 'true';
    var tabs = Array.from(wrapper.querySelectorAll('[role="tab"]'));
    var panels = Array.from(wrapper.querySelectorAll('[role="tabpanel"]'));
    var activate = function activate(index) {
      var focus = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
      tabs.forEach(function (tab, i) {
        var selected = i === index;
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.setAttribute('tabindex', selected ? '0' : '-1');
        tab.classList.toggle('tabs__tab--active', selected);
        panels[i].hidden = !selected;
      });
      if (focus) tabs[index].focus();
    };
    var enabled = function enabled(i) {
      return !tabs[i].hasAttribute('disabled');
    };
    var nextEnabled = function nextEnabled(from, step) {
      var n = tabs.length;
      for (var k = 1; k <= n; k++) {
        var i = ((from + step * k) % n + n) % n;
        if (enabled(i)) return i;
      }
      return from;
    };
    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function () {
        return enabled(i) && activate(i);
      });
      tab.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') {
          e.preventDefault();
          activate(nextEnabled(i, 1));
        } else if (e.key === 'ArrowLeft') {
          e.preventDefault();
          activate(nextEnabled(i, -1));
        } else if (e.key === 'Home') {
          e.preventDefault();
          activate(nextEnabled(-1, 1));
        } else if (e.key === 'End') {
          e.preventDefault();
          activate(nextEnabled(tabs.length, -1));
        }
      });
    });
  });
}
exports.initTabs = initTabs;
if (typeof document !== 'undefined') {
  if (document.readyState !== 'loading') initTabs();else document.addEventListener('DOMContentLoaded', function () {
    return initTabs();
  });
}

/***/ },

/***/ "./public/packages/inno_frontend/components/molecules/user-menu/user-menu.ts"
/*!***********************************************************************************!*\
  !*** ./public/packages/inno_frontend/components/molecules/user-menu/user-menu.ts ***!
  \***********************************************************************************/
() {



function initUserMenus() {
  document.querySelectorAll('[data-user-menu]').forEach(function (menu) {
    var trigger = menu.querySelector('[data-user-menu-trigger]');
    var dropdown = menu.querySelector('.user-menu__dropdown');
    if (!trigger || !dropdown) return;
    // Remove the hidden attribute so CSS controls visibility
    dropdown.removeAttribute('hidden');
    var menuItems = dropdown.querySelectorAll('.user-menu__item');
    function open() {
      var _a;
      menu.classList.add('user-menu--open');
      trigger.setAttribute('aria-expanded', 'true');
      (_a = menuItems[0]) === null || _a === void 0 ? void 0 : _a.focus();
    }
    function close() {
      menu.classList.remove('user-menu--open');
      trigger.setAttribute('aria-expanded', 'false');
    }
    function isOpen() {
      return menu.classList.contains('user-menu--open');
    }
    // Click toggle
    trigger.addEventListener('click', function () {
      isOpen() ? close() : open();
    });
    // Close on Escape
    menu.addEventListener('keydown', function (e) {
      var keyEvent = e;
      if (keyEvent.key === 'Escape' && isOpen()) {
        close();
        trigger.focus();
      }
    });
    // Close when clicking outside
    document.addEventListener('click', function (e) {
      if (isOpen() && !menu.contains(e.target)) {
        close();
      }
    });
    // Keyboard navigation
    dropdown.addEventListener('keydown', function (e) {
      var keyEvent = e;
      var items = Array.from(menuItems);
      var current = document.activeElement;
      var index = items.indexOf(current);
      if (keyEvent.key === 'ArrowDown') {
        keyEvent.preventDefault();
        var next = items[(index + 1) % items.length];
        next === null || next === void 0 ? void 0 : next.focus();
      } else if (keyEvent.key === 'ArrowUp') {
        keyEvent.preventDefault();
        var prev = items[(index - 1 + items.length) % items.length];
        prev === null || prev === void 0 ? void 0 : prev.focus();
      }
    });
  });
}
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initUserMenus);
} else {
  initUserMenus();
}

/***/ },

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

/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./public/packages/inno_frontend/components/_storybook/components.ts"));
/******/ }
]);