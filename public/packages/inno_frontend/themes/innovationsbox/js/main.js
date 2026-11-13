"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/themes/innovationsbox/js/main"],{

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

/***/ "./resources/js/main.ts"
/*!******************************!*\
  !*** ./resources/js/main.ts ***!
  \******************************/
(__unused_webpack_module, exports, __webpack_require__) {



// webpack entry point — mix.extract() requires this file
Object.defineProperty(exports, "__esModule", ({
  value: true
}));
// Auth components
__webpack_require__(/*! ../../public/packages/inno_frontend/components/molecules/user-menu/user-menu */ "./public/packages/inno_frontend/components/molecules/user-menu/user-menu.ts");
__webpack_require__(/*! ../../public/packages/inno_frontend/components/molecules/password-field/password-field */ "./public/packages/inno_frontend/components/molecules/password-field/password-field.ts");

/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./resources/js/main.ts"));
/******/ }
]);