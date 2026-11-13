"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/themes/innovationsbox/js/single_pages/moderation-benutzer"],{

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

/***/ "./resources/js/single_pages/moderation-benutzer.ts"
/*!**********************************************************!*\
  !*** ./resources/js/single_pages/moderation-benutzer.ts ***!
  \**********************************************************/
(__unused_webpack_module, exports, __webpack_require__) {



function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
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
var API_BASE = '/api/moderation/user';
var PAGE_SIZE = 20;
var allUsers = [];
var filteredUsers = [];
var currentPage = 1;
var searchQuery = '';
function fetchUsers() {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee() {
    var res, json;
    return _regenerator().w(function (_context) {
      while (1) switch (_context.n) {
        case 0:
          _context.n = 1;
          return fetch(API_BASE);
        case 1:
          res = _context.v;
          if (res.ok) {
            _context.n = 2;
            break;
          }
          return _context.a(2, []);
        case 2:
          _context.n = 3;
          return res.json();
        case 3:
          json = _context.v;
          return _context.a(2, json.data || []);
      }
    }, _callee);
  }));
}
function matchesSearch(user, query) {
  var q = query.toLowerCase();
  return user.name.toLowerCase().includes(q) || user.email.toLowerCase().includes(q);
}
function applyFilter() {
  filteredUsers = searchQuery ? allUsers.filter(function (u) {
    return matchesSearch(u, searchQuery);
  }) : _toConsumableArray(allUsers);
  currentPage = 1;
  render();
}
function render() {
  var container = document.getElementById('users-container');
  if (!container) return;
  var totalPages = Math.max(1, Math.ceil(filteredUsers.length / PAGE_SIZE));
  var start = (currentPage - 1) * PAGE_SIZE;
  var pageUsers = filteredUsers.slice(start, start + PAGE_SIZE);
  if (filteredUsers.length === 0 && searchQuery) {
    container.innerHTML = "<p class=\"moderation-benutzer-page__empty\">Keine Benutzer f\xFCr \xAB".concat((0, escape_html_1.escapeHtml)(searchQuery), "\xBB gefunden.</p>");
    return;
  }
  if (filteredUsers.length === 0) {
    container.innerHTML = '<p class="moderation-benutzer-page__empty">Keine Benutzer vorhanden.</p>';
    return;
  }
  container.innerHTML = "\n    <div class=\"user-list__search\">\n      <input type=\"text\" id=\"user-search\" class=\"input\" placeholder=\"Name oder E-Mail suchen\u2026\" value=\"".concat((0, escape_html_1.escapeHtml)(searchQuery), "\" />\n    </div>\n    <div class=\"user-list__info\">").concat(String(filteredUsers.length), " Benutzer").concat(searchQuery ? " f\xFCr \xAB".concat((0, escape_html_1.escapeHtml)(searchQuery), "\xBB") : '', "</div>\n    <div class=\"user-list\">\n      <div class=\"user-list__header\">\n        <span class=\"user-list__col user-list__col--name\">Name</span>\n        <span class=\"user-list__col user-list__col--email\">E-Mail</span>\n        <span class=\"user-list__col user-list__col--expert\">Expert\xB7in</span>\n      </div>\n      ").concat(pageUsers.map(function (user) {
    return "\n        <div class=\"user-list__item\" data-id=\"".concat((0, escape_html_1.escapeHtml)(user.id), "\">\n          <span class=\"user-list__col user-list__col--name\">").concat((0, escape_html_1.escapeHtml)(user.name), "</span>\n          <span class=\"user-list__col user-list__col--email\">").concat((0, escape_html_1.escapeHtml)(user.email), "</span>\n          <span class=\"user-list__col user-list__col--expert\">\n            <button type=\"button\" class=\"button button--sm ").concat(user.isExpert ? 'button--accent' : 'button--muted', "\" data-toggle-expert=\"").concat((0, escape_html_1.escapeHtml)(user.id), "\" data-is-expert=\"").concat(user.isExpert ? 'true' : 'false', "\">\n              ").concat(user.isExpert ? 'Expert·in ✓' : 'Markieren', "\n            </button>\n            <button type=\"button\" class=\"button button--danger button--sm\" data-delete-user=\"").concat((0, escape_html_1.escapeHtml)(user.id), "\" data-user-name=\"").concat((0, escape_html_1.escapeHtml)(user.name), "\">Entfernen</button>\n          </span>\n        </div>");
  }).join(''), "\n    </div>\n    ").concat(totalPages > 1 ? renderPagination(totalPages) : '');
  attachHandlers(container);
}
function renderPagination(totalPages) {
  var pages = [];
  for (var i = 1; i <= totalPages; i++) {
    pages.push("<button type=\"button\" class=\"pagination__btn ".concat(i === currentPage ? 'pagination__btn--active' : '', "\" data-page=\"").concat(String(i), "\">").concat(String(i), "</button>"));
  }
  return "<div class=\"user-list__pagination\">".concat(pages.join(''), "</div>");
}
function attachHandlers(container) {
  // Search input
  var searchInput = container.querySelector('#user-search');
  if (searchInput) {
    var debounceTimer;
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        searchQuery = searchInput.value.trim();
        applyFilter();
        // Re-focus search input after re-render
        var newInput = document.getElementById('user-search');
        if (newInput) {
          newInput.focus();
          newInput.setSelectionRange(newInput.value.length, newInput.value.length);
        }
      }, 300);
    });
  }
  // Pagination buttons
  container.querySelectorAll('[data-page]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a;
      currentPage = parseInt((_a = btn.dataset.page) !== null && _a !== void 0 ? _a : '1', 10);
      render();
    });
  });
  // Toggle expert handlers
  container.querySelectorAll('[data-toggle-expert]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a;
      var userId = (_a = btn.dataset.toggleExpert) !== null && _a !== void 0 ? _a : '';
      var currentlyExpert = btn.dataset.isExpert === 'true';
      void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(userId, "/expert"), {
        method: 'PUT',
        body: JSON.stringify({
          expert: !currentlyExpert
        })
      }).then(function (res) {
        if (res.ok) {
          // Update local state instead of full page reload
          var user = allUsers.find(function (u) {
            return u.id === userId;
          });
          if (user) {
            user.isExpert = !currentlyExpert;
          }
          applyFilter();
        }
      });
    });
  });
  // Delete user handler
  container.addEventListener('click', function (e) {
    var _a, _b;
    var target = e.target;
    var btn = target.closest('[data-delete-user]');
    if (!btn) return;
    var userId = (_a = btn.dataset.deleteUser) !== null && _a !== void 0 ? _a : '';
    var userName = (_b = btn.dataset.userName) !== null && _b !== void 0 ? _b : '';
    var modal = document.getElementById('delete-user-modal');
    var nameEl = document.getElementById('delete-user-name');
    var reasonInput = document.getElementById('delete-user-reason');
    var confirmBtn = document.getElementById('delete-user-confirm');
    var errorEl = document.getElementById('delete-user-error');
    if (!modal || !reasonInput || !confirmBtn) return;
    if (nameEl) nameEl.textContent = "Benutzer \xAB".concat(userName, "\xBB wirklich entfernen?");
    reasonInput.value = '';
    if (errorEl) errorEl.hidden = true;
    modal.showModal();
    var freshConfirm = confirmBtn.cloneNode(true);
    confirmBtn.replaceWith(freshConfirm);
    modal.querySelectorAll('[data-modal-close]').forEach(function (closeBtn) {
      closeBtn.addEventListener('click', function () {
        modal.close();
      });
    });
    freshConfirm.addEventListener('click', function () {
      var reason = reasonInput.value.trim();
      if (!reason) {
        reasonInput.focus();
        return;
      }
      void (0, api_client_1.apiFetch)("/api/moderation/user/".concat(encodeURIComponent(userId)), {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          reason: reason
        })
      }).then(function (res) {
        if (res.ok) {
          modal.close();
          window.location.reload();
        } else {
          void res.json().then(function (data) {
            var _a;
            if (errorEl) {
              errorEl.textContent = (_a = data.error) !== null && _a !== void 0 ? _a : 'Ein Fehler ist aufgetreten.';
              errorEl.hidden = false;
            }
          });
        }
      });
    });
  });
}
document.addEventListener('DOMContentLoaded', function () {
  void fetchUsers().then(function (users) {
    allUsers = users;
    filteredUsers = _toConsumableArray(users);
    // Render search bar first, then full list
    render();
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
/******/ __webpack_require__.O(0, ["/themes/innovationsbox/js/vendor"], () => (__webpack_exec__("./resources/js/single_pages/moderation-benutzer.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);