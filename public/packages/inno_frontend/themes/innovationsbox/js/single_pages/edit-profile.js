"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/themes/innovationsbox/js/single_pages/edit-profile"],{

/***/ "./resources/js/api-client.ts"
/*!************************************!*\
  !*** ./resources/js/api-client.ts ***!
  \************************************/
(__unused_webpack_module, exports) {



/**
 * Shared API fetch wrapper that automatically attaches the CSRF token
 * from the <meta name="csrf-token"> tag on state-changing requests.
 *
 * Usage:
 *   import { apiFetch } from '../api-client';
 *   const res = await apiFetch('/api/moderation/123/approve', {
 *     method: 'POST',
 *     headers: { 'Content-Type': 'application/json' },
 *     body: JSON.stringify(data),
 *   });
 */
Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.apiFetch = void 0;
function getCsrfToken() {
  var _a;
  var meta = document.querySelector('meta[name="csrf-token"]');
  return (_a = meta === null || meta === void 0 ? void 0 : meta.content) !== null && _a !== void 0 ? _a : '';
}
var SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
/**
 * Drop-in replacement for fetch() that adds the X-CSRF-Token header
 * on non-safe HTTP methods (POST, PUT, DELETE, PATCH).
 */
function apiFetch(input, init) {
  var _a;
  var method = ((_a = init === null || init === void 0 ? void 0 : init.method) !== null && _a !== void 0 ? _a : 'GET').toUpperCase();
  if (SAFE_METHODS.indexOf(method) === -1) {
    var token = getCsrfToken();
    if (token) {
      var headers = new Headers(init === null || init === void 0 ? void 0 : init.headers);
      headers.set('X-CSRF-Token', token);
      init = Object.assign(Object.assign({}, init), {
        headers: headers
      });
    }
  }
  return fetch(input, init);
}
exports.apiFetch = apiFetch;

/***/ },

/***/ "./resources/js/single_pages/edit-profile.ts"
/*!***************************************************!*\
  !*** ./resources/js/single_pages/edit-profile.ts ***!
  \***************************************************/
(__unused_webpack_module, exports, __webpack_require__) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
// resources/js/single_pages/edit-profile.ts
var api_client_1 = __webpack_require__(/*! ../api-client */ "./resources/js/api-client.ts");
document.addEventListener('DOMContentLoaded', function () {
  var deleteBtn = document.getElementById('btn-delete-account');
  var modal = document.getElementById('delete-account-modal');
  var passwordInput = document.getElementById('delete-account-password');
  var confirmBtn = document.getElementById('delete-account-confirm');
  var errorEl = document.getElementById('delete-account-error');
  if (!deleteBtn || !modal || !passwordInput || !confirmBtn) return;
  // Close button
  modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  deleteBtn.addEventListener('click', function () {
    passwordInput.value = '';
    if (errorEl) errorEl.hidden = true;
    modal.showModal();
  });
  confirmBtn.addEventListener('click', function () {
    var password = passwordInput.value.trim();
    if (!password) {
      passwordInput.focus();
      return;
    }
    confirmBtn.setAttribute('disabled', '');
    confirmBtn.textContent = 'Wird gelöscht...';
    void (0, api_client_1.apiFetch)('/api/user/account', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        password: password
      })
    }).then(function (res) {
      if (res.ok) {
        modal.close();
        window.location.href = '/';
      } else {
        void res.json().then(function (data) {
          var _a;
          if (errorEl) {
            errorEl.textContent = (_a = data.error) !== null && _a !== void 0 ? _a : 'Ein Fehler ist aufgetreten.';
            errorEl.hidden = false;
          }
          confirmBtn.removeAttribute('disabled');
          confirmBtn.textContent = 'Endgültig löschen';
        });
      }
    });
  });
});

/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./resources/js/single_pages/edit-profile.ts"));
/******/ }
]);