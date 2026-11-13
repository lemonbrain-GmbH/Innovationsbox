"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/themes/innovationsbox/js/single_pages/moderation-kategorien"],{

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

/***/ "./resources/js/single_pages/moderation-kategorien.ts"
/*!************************************************************!*\
  !*** ./resources/js/single_pages/moderation-kategorien.ts ***!
  \************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {



function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
var __awaiter = this && this.__awaiter || function (thisArg, _arguments, P, generator) {
  function adopt(value) {
    return value instanceof P ? value : new P(function (resolve) {
      resolve(value);
    });
  }
  return new (P || (P = Promise))(function (resolve, reject) {
    function fulfilled(value) {
      try {
        step(generator.next(value));
      } catch (e) {
        reject(e);
      }
    }
    function rejected(value) {
      try {
        step(generator["throw"](value));
      } catch (e) {
        reject(e);
      }
    }
    function step(result) {
      result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected);
    }
    step((generator = generator.apply(thisArg, _arguments || [])).next());
  });
};
Object.defineProperty(exports, "__esModule", ({
  value: true
}));
var api_client_1 = __webpack_require__(/*! ../api-client */ "./resources/js/api-client.ts");
var escape_html_1 = __webpack_require__(/*! ../utils/escape-html */ "./resources/js/utils/escape-html.ts");
var API_BASE = '/api/moderation/category';
var ERROR_ICON = '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>';
function extractErrorMessage(res) {
  var _a, _b;
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee() {
    var json, _t;
    return _regenerator().w(function (_context) {
      while (1) switch (_context.p = _context.n) {
        case 0:
          _context.p = 0;
          _context.n = 1;
          return res.json();
        case 1:
          json = _context.v;
          if (!(typeof json.error === 'string')) {
            _context.n = 2;
            break;
          }
          return _context.a(2, json.error);
        case 2:
          return _context.a(2, (_b = (_a = json.error) === null || _a === void 0 ? void 0 : _a.message) !== null && _b !== void 0 ? _b : 'Ein unbekannter Fehler ist aufgetreten.');
        case 3:
          _context.p = 3;
          _t = _context.v;
          return _context.a(2, 'Ein unbekannter Fehler ist aufgetreten.');
      }
    }, _callee, null, [[0, 3]]);
  }));
}
function showInlineError(message, container) {
  var target = container !== null && container !== void 0 ? container : document.getElementById('categories-container');
  if (!target) return;
  target.querySelectorAll('.alert--error[data-inline-error]').forEach(function (el) {
    el.remove();
  });
  var alert = document.createElement('div');
  alert.className = 'alert alert--error';
  alert.setAttribute('role', 'alert');
  alert.dataset['inlineError'] = '';
  alert.innerHTML = "<span class=\"alert__icon\" aria-hidden=\"true\">".concat(ERROR_ICON, "</span>") + "<div class=\"alert__content\"><p class=\"alert__message\">".concat((0, escape_html_1.escapeHtml)(message), "</p></div>");
  target.prepend(alert);
}
function fetchCategories() {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee2() {
    var res, json;
    return _regenerator().w(function (_context2) {
      while (1) switch (_context2.n) {
        case 0:
          _context2.n = 1;
          return fetch(API_BASE);
        case 1:
          res = _context2.v;
          _context2.n = 2;
          return res.json();
        case 2:
          json = _context2.v;
          return _context2.a(2, json.data);
      }
    }, _callee2);
  }));
}
function renderCategories(categories) {
  var container = document.getElementById('categories-container');
  if (!container) return;
  if (categories.length === 0) {
    container.innerHTML = '<p class="moderation-kategorien-page__empty">Keine Kategorien vorhanden.</p>';
    return;
  }
  container.innerHTML = "\n    <div class=\"category-list\">\n      <div class=\"category-list__header\">\n        <span class=\"category-list__col category-list__col--name\">Name</span>\n        <span class=\"category-list__col category-list__col--slug\">Slug</span>\n        <span class=\"category-list__col category-list__col--sort\">Sortierung</span>\n        <span class=\"category-list__col category-list__col--actions\">Aktionen</span>\n      </div>\n      ".concat(categories.map(function (cat) {
    return "\n        <div class=\"category-list__item\" data-id=\"".concat((0, escape_html_1.escapeHtml)(cat.id), "\">\n          <span class=\"category-list__col category-list__col--name\">").concat((0, escape_html_1.escapeHtml)(cat.name), "</span>\n          <span class=\"category-list__col category-list__col--slug\">").concat((0, escape_html_1.escapeHtml)(cat.slug), "</span>\n          <span class=\"category-list__col category-list__col--sort\">").concat(String(cat.sortOrder), "</span>\n          <span class=\"category-list__col category-list__col--actions\">\n            <button type=\"button\" class=\"button button--muted button--sm\" data-edit=\"").concat((0, escape_html_1.escapeHtml)(cat.id), "\" data-name=\"").concat((0, escape_html_1.escapeHtml)(cat.name), "\" data-slug=\"").concat((0, escape_html_1.escapeHtml)(cat.slug), "\" data-sort=\"").concat(String(cat.sortOrder), "\">Bearbeiten</button>\n            <button type=\"button\" class=\"button button--danger button--sm\" data-delete=\"").concat((0, escape_html_1.escapeHtml)(cat.id), "\" data-name=\"").concat((0, escape_html_1.escapeHtml)(cat.name), "\">L\xF6schen</button>\n          </span>\n        </div>");
  }).join(''), "\n    </div>");
  initEditButtons();
  initDeleteButtons();
}
function initAddButton() {
  var _a;
  var modal = document.getElementById('category-modal');
  var nameInput = document.getElementById('category-name');
  var slugInput = document.getElementById('category-slug');
  var sortInput = document.getElementById('category-sort');
  var titleEl = document.getElementById('category-modal-title');
  var submitBtn = document.getElementById('category-submit');
  if (!modal || !nameInput || !submitBtn) return;
  modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  (_a = document.getElementById('btn-add-category')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
    if (titleEl) titleEl.textContent = 'Neue Kategorie';
    nameInput.value = '';
    if (slugInput) slugInput.value = '';
    if (sortInput) sortInput.value = '0';
    modal.showModal();
    var freshSubmit = submitBtn.cloneNode(true);
    submitBtn.replaceWith(freshSubmit);
    freshSubmit.addEventListener('click', function () {
      var name = nameInput.value.trim();
      if (!name) return;
      var formData = new URLSearchParams();
      formData.append('name', name);
      if (slugInput === null || slugInput === void 0 ? void 0 : slugInput.value.trim()) formData.append('slug', slugInput.value.trim());
      if (sortInput === null || sortInput === void 0 ? void 0 : sortInput.value) formData.append('sort_order', sortInput.value);
      void (0, api_client_1.apiFetch)(API_BASE, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
      }).then(function (res) {
        if (res.ok) {
          modal.close();
          window.location.reload();
        } else {
          void extractErrorMessage(res).then(function (msg) {
            showInlineError(msg, modal);
          });
        }
      });
    });
  });
}
function initEditButtons() {
  var modal = document.getElementById('category-modal');
  var nameInput = document.getElementById('category-name');
  var slugInput = document.getElementById('category-slug');
  var sortInput = document.getElementById('category-sort');
  var titleEl = document.getElementById('category-modal-title');
  var submitBtn = document.getElementById('category-submit');
  if (!modal || !nameInput || !submitBtn) return;
  document.querySelectorAll('[data-edit]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a, _b, _c, _d;
      var catId = (_a = btn.dataset['edit']) !== null && _a !== void 0 ? _a : '';
      var name = (_b = btn.dataset['name']) !== null && _b !== void 0 ? _b : '';
      var slug = (_c = btn.dataset['slug']) !== null && _c !== void 0 ? _c : '';
      var sort = (_d = btn.dataset['sort']) !== null && _d !== void 0 ? _d : '0';
      if (titleEl) titleEl.textContent = 'Kategorie bearbeiten';
      nameInput.value = name;
      if (slugInput) slugInput.value = slug;
      if (sortInput) sortInput.value = sort;
      modal.showModal();
      var freshSubmit = submitBtn.cloneNode(true);
      submitBtn.replaceWith(freshSubmit);
      freshSubmit.addEventListener('click', function () {
        var newName = nameInput.value.trim();
        if (!newName) return;
        var formData = new URLSearchParams();
        formData.append('name', newName);
        if (slugInput === null || slugInput === void 0 ? void 0 : slugInput.value.trim()) formData.append('slug', slugInput.value.trim());
        if (sortInput === null || sortInput === void 0 ? void 0 : sortInput.value) formData.append('sort_order', sortInput.value);
        void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(encodeURIComponent(catId)), {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: formData.toString()
        }).then(function (res) {
          if (res.ok) {
            modal.close();
            window.location.reload();
          } else {
            void extractErrorMessage(res).then(function (msg) {
              showInlineError(msg, modal);
            });
          }
        });
      });
    });
  });
}
function initDeleteButtons() {
  var modal = document.getElementById('delete-category-modal');
  var nameEl = document.getElementById('delete-category-name');
  var confirmBtn = document.getElementById('delete-category-confirm');
  if (!modal || !confirmBtn) return;
  modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  document.querySelectorAll('[data-delete]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a, _b;
      var catId = (_a = btn.dataset['delete']) !== null && _a !== void 0 ? _a : '';
      var name = (_b = btn.dataset['name']) !== null && _b !== void 0 ? _b : '';
      if (nameEl) nameEl.textContent = "\xAB".concat(name, "\xBB");
      var freshConfirm = confirmBtn.cloneNode(true);
      confirmBtn.replaceWith(freshConfirm);
      freshConfirm.addEventListener('click', function () {
        void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(encodeURIComponent(catId)), {
          method: 'DELETE'
        }).then(function (res) {
          if (res.ok) {
            modal.close();
            window.location.reload();
          } else {
            void extractErrorMessage(res).then(function (msg) {
              showInlineError(msg, modal);
            });
          }
        });
      });
      modal.showModal();
    });
  });
}
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('categories-container')) return;
  initAddButton();
  void fetchCategories().then(renderCategories)["catch"](function () {
    showInlineError('Fehler beim Laden der Kategorien.');
  });
});

/***/ },

/***/ "./resources/js/utils/escape-html.ts"
/*!*******************************************!*\
  !*** ./resources/js/utils/escape-html.ts ***!
  \*******************************************/
(__unused_webpack_module, exports, __webpack_require__) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.sanitizeHtml = exports.escapeHtml = void 0;
var DOMPurify = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.cjs.js");
/**
 * Escapes a plain-text string for safe insertion into HTML.
 *
 * Uses the browser's own DOM to perform the escaping so that all HTML
 * special characters (`&`, `<`, `>`, `"`, `'`) are converted to their
 * entity equivalents.  The result is safe to place inside an HTML
 * attribute value or text node via `innerHTML`.
 *
 * @param text - The raw string to escape.
 * @returns The HTML-escaped string.
 */
function escapeHtml(text) {
  var div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
exports.escapeHtml = escapeHtml;
/**
 * Sanitizes an HTML string that is allowed to contain markup (e.g. rich
 * text entered through a WYSIWYG editor).
 *
 * Uses DOMPurify with a restricted allow-list so that only safe formatting
 * tags survive.  Script elements, event handlers and other dangerous
 * constructs are stripped unconditionally.
 *
 * Permitted tags: b, strong, i, em, u, s, br, p, ul, ol, li, blockquote,
 * a (href / target / rel only), span, div.
 *
 * @param dirty - The potentially unsafe HTML string.
 * @returns Sanitized HTML that is safe to assign to `innerHTML`.
 */
function sanitizeHtml(dirty) {
  return DOMPurify.sanitize(dirty, {
    ALLOWED_TAGS: ['b', 'strong', 'i', 'em', 'u', 's', 'br', 'p', 'ul', 'ol', 'li', 'blockquote', 'a', 'span', 'div'],
    ALLOWED_ATTR: ['href', 'target', 'rel', 'class'],
    ALLOW_DATA_ATTR: false,
    FORCE_BODY: false
  });
}
exports.sanitizeHtml = sanitizeHtml;

/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["/themes/innovationsbox/js/vendor"], () => (__webpack_exec__("./resources/js/single_pages/moderation-kategorien.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);