"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/themes/innovationsbox/js/single_pages/innovationen"],{

/***/ "./resources/js/single_pages/innovationen.ts"
/*!***************************************************!*\
  !*** ./resources/js/single_pages/innovationen.ts ***!
  \***************************************************/
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
var escape_html_1 = __webpack_require__(/*! ../utils/escape-html */ "./resources/js/utils/escape-html.ts");
var project_card_renderer_1 = __webpack_require__(/*! ../utils/project-card-renderer */ "./resources/js/utils/project-card-renderer.ts");
var API_BASE = '/api/project-space';
var LIMIT = 12;
var currentPage = 1;
var currentStatus = '';
var currentCategory = '';
var searchTimeout = null;
var currentSearch = '';
var currentMine = new URLSearchParams(window.location.search).get('mine') === 'true';
function fetchSpaces(status, page, category, search) {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee() {
    var params, response;
    return _regenerator().w(function (_context) {
      while (1) switch (_context.n) {
        case 0:
          params = new URLSearchParams({
            page: String(page),
            limit: String(LIMIT)
          });
          if (status) params.set('status', status);
          if (category) params.set('category', category);
          if (search) params.set('search', search);
          if (currentMine) params.set('mine', 'true');
          _context.n = 1;
          return fetch("".concat(API_BASE, "?").concat(params));
        case 1:
          response = _context.v;
          return _context.a(2, response.json());
      }
    }, _callee);
  }));
}
function loadSpaces() {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee2() {
    var grid, result, archivedBtn;
    return _regenerator().w(function (_context2) {
      while (1) switch (_context2.n) {
        case 0:
          grid = document.getElementById('project-spaces-grid');
          if (grid) {
            _context2.n = 1;
            break;
          }
          return _context2.a(2);
        case 1:
          grid.innerHTML = '<div class="innovationen-page__loading"><div class="spinner"></div></div>';
          _context2.n = 2;
          return fetchSpaces(currentStatus, currentPage, currentCategory, currentSearch);
        case 2:
          result = _context2.v;
          // Show archived filter for admins
          if (result.isAdmin) {
            archivedBtn = document.getElementById('filter-archived');
            if (archivedBtn) archivedBtn.hidden = false;
          }
          if (!(result.data.length === 0)) {
            _context2.n = 3;
            break;
          }
          grid.innerHTML = '<p class="innovationen-page__empty">Keine Innovationen gefunden.</p>';
          return _context2.a(2);
        case 3:
          grid.innerHTML = result.data.map(project_card_renderer_1.renderCard).join('');
        case 4:
          return _context2.a(2);
      }
    }, _callee2);
  }));
}
function initFilters() {
  var filterContainer = document.getElementById('project-filters');
  if (!filterContainer) return;
  filterContainer.addEventListener('click', function (e) {
    var _a;
    var target = e.target;
    var btn = target.closest('[data-value]');
    if (!btn) return;
    currentStatus = (_a = btn.dataset['value']) !== null && _a !== void 0 ? _a : '';
    currentPage = 1;
    filterContainer.querySelectorAll('[data-value]').forEach(function (el) {
      el.classList.remove('innovationen-page__filter--active');
    });
    btn.classList.add('innovationen-page__filter--active');
    void loadSpaces();
  });
}
function fetchCategories() {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee3() {
    var response, json;
    return _regenerator().w(function (_context3) {
      while (1) switch (_context3.n) {
        case 0:
          _context3.n = 1;
          return fetch("".concat(API_BASE, "/categories"));
        case 1:
          response = _context3.v;
          _context3.n = 2;
          return response.json();
        case 2:
          json = _context3.v;
          return _context3.a(2, json.data);
      }
    }, _callee3);
  }));
}
function initCategoryFilters() {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee4() {
    var container, categories, buttons;
    return _regenerator().w(function (_context4) {
      while (1) switch (_context4.n) {
        case 0:
          container = document.getElementById('category-filters');
          if (container) {
            _context4.n = 1;
            break;
          }
          return _context4.a(2);
        case 1:
          _context4.n = 2;
          return fetchCategories();
        case 2:
          categories = _context4.v;
          buttons = categories.map(function (c) {
            return "<button class=\"unstyled-button innovationen-page__filter\" data-category=\"".concat((0, escape_html_1.escapeHtml)(c.slug), "\" type=\"button\">").concat((0, escape_html_1.escapeHtml)(c.name), "</button>");
          }).join('');
          container.insertAdjacentHTML('beforeend', buttons);
          container.addEventListener('click', function (e) {
            var _a;
            var btn = e.target.closest('[data-category]');
            if (!btn) return;
            currentCategory = (_a = btn.dataset['category']) !== null && _a !== void 0 ? _a : '';
            currentPage = 1;
            container.querySelectorAll('[data-category]').forEach(function (el) {
              el.classList.remove('innovationen-page__filter--active');
            });
            btn.classList.add('innovationen-page__filter--active');
            void loadSpaces();
          });
        case 3:
          return _context4.a(2);
      }
    }, _callee4);
  }));
}
function initSearch() {
  var input = document.getElementById('project-search');
  if (!input) return;
  input.addEventListener('input', function () {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function () {
      currentSearch = input.value.trim();
      currentPage = 1;
      void loadSpaces();
    }, 300);
  });
}
document.addEventListener('DOMContentLoaded', function () {
  initFilters();
  initSearch();
  void initCategoryFilters();
  void loadSpaces();
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

/***/ },

/***/ "./resources/js/utils/project-card-renderer.ts"
/*!*****************************************************!*\
  !*** ./resources/js/utils/project-card-renderer.ts ***!
  \*****************************************************/
(__unused_webpack_module, exports, __webpack_require__) {



Object.defineProperty(exports, "__esModule", ({
  value: true
}));
exports.renderCard = exports.getActionButton = exports.getInitials = exports.getStatusVariant = exports.getStatusLabel = exports.formatDate = void 0;
var escape_html_1 = __webpack_require__(/*! ./escape-html */ "./resources/js/utils/escape-html.ts");
function formatDate(isoDate) {
  try {
    var date = new Date(isoDate);
    var day = date.getDate();
    var month = date.getMonth() + 1;
    var year = date.getFullYear() % 100;
    return "".concat(String(day), ".").concat(String(month), ".").concat(String(year));
  } catch (_a) {
    return isoDate;
  }
}
exports.formatDate = formatDate;
function getStatusLabel(status) {
  var _a;
  var labels = {
    active: 'Aktiv',
    completed: 'Abgeschlossen',
    archived: 'Archiviert'
  };
  return (_a = labels[status]) !== null && _a !== void 0 ? _a : status;
}
exports.getStatusLabel = getStatusLabel;
function getStatusVariant(status) {
  if (status === 'active') return 'success';
  if (status === 'archived') return 'warning';
  return 'neutral';
}
exports.getStatusVariant = getStatusVariant;
function getInitials(name) {
  return name.split(' ').map(function (w) {
    return w.charAt(0);
  }).join('').substring(0, 2).toUpperCase();
}
exports.getInitials = getInitials;
function getActionButton(space) {
  var href = "/projektraum/view/".concat(space.id);
  var role = space.userRole;
  if (space.status === 'active') {
    if (role === 'initiator' || role === 'admin') {
      return "<a href=\"".concat(href, "\" class=\"project-card__btn button button--accent button--lg\">Verwalten</a>");
    }
    if (role === 'member') {
      return "<a href=\"".concat(href, "\" class=\"project-card__btn button button--muted button--lg\">Eintreten</a>");
    }
    return "<a href=\"".concat(href, "\" class=\"project-card__btn button button--accent button--lg\">Mitmachen</a>");
  }
  return "<a href=\"".concat(href, "\" class=\"project-card__btn button button--muted button--lg\">Mehr erfahren</a>");
}
exports.getActionButton = getActionButton;
function renderCard(space) {
  var isActive = space.status === 'active';
  var statusLabel = getStatusLabel(space.status);
  return "\n    <article class=\"project-card project-card--".concat(space.status, "\">\n      <div class=\"project-card__image\">").concat(space.headerImageUrl ? "<img src=\"".concat((0, escape_html_1.escapeHtml)(space.headerImageUrl), "\" alt=\"\" loading=\"lazy\">") : '', "</div>\n      <div class=\"project-card__body\">\n        <div class=\"project-card__header\">\n          ").concat(space.categoryName ? "<span class=\"project-card__badge project-card__badge--category\">".concat((0, escape_html_1.escapeHtml)(space.categoryName), "</span>") : '', "\n          <span class=\"project-card__badge project-card__badge--").concat(getStatusVariant(space.status), "\">").concat(statusLabel, "</span>\n        </div>\n        <h3 class=\"project-card__title\">").concat((0, escape_html_1.escapeHtml)(space.title), "</h3>\n        <div class=\"project-card__meta\">\n          <div class=\"project-card__meta-item\">\n            <svg class=\"project-card__meta-icon\" width=\"14\" height=\"14\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><path d=\"M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2\"/><circle cx=\"9\" cy=\"7\" r=\"4\"/><path d=\"M23 21v-2a4 4 0 00-3-3.87\"/><path d=\"M16 3.13a4 4 0 010 7.75\"/></svg>\n            <span><strong>").concat(String(space.memberCount), "</strong> Mitglieder</span>\n          </div>\n          <span class=\"project-card__age\">Erstellt: ").concat(formatDate(space.createdAt), "</span>\n        </div>\n        <div class=\"project-card__footer\">\n          <div class=\"project-card__initiator\">\n            ").concat(space.initiatorAvatarUrl ? "<img class=\"project-card__avatar-img\" src=\"".concat((0, escape_html_1.escapeHtml)(space.initiatorAvatarUrl), "\" alt=\"").concat((0, escape_html_1.escapeHtml)(space.initiatorName), "\">") : "<div class=\"project-card__avatar".concat(isActive ? '' : ' project-card__avatar--muted', "\">").concat((0, escape_html_1.escapeHtml)(getInitials(space.initiatorName)), "</div>"), "\n            <div class=\"project-card__initiator-info\">\n              <span class=\"project-card__initiator-name\">").concat((0, escape_html_1.escapeHtml)(space.initiatorName), "</span>\n              <span class=\"project-card__initiator-role\">Initiator\xB7in</span>\n            </div>\n          </div>\n          ").concat(getActionButton(space), "\n        </div>\n      </div>\n    </article>");
}
exports.renderCard = renderCard;

/***/ }

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["/themes/innovationsbox/js/vendor"], () => (__webpack_exec__("./resources/js/single_pages/innovationen.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);