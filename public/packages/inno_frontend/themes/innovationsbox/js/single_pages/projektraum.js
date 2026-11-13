"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/themes/innovationsbox/js/single_pages/projektraum"],{

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

/***/ "./resources/js/single_pages/projektraum.ts"
/*!**************************************************!*\
  !*** ./resources/js/single_pages/projektraum.ts ***!
  \**************************************************/
(__unused_webpack_module, exports, __webpack_require__) {



function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
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
var API_BASE = '/api/project-space';
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
          return _context.a(2, (_b = (_a = json.error) === null || _a === void 0 ? void 0 : _a.message) !== null && _b !== void 0 ? _b : 'Ein unbekannter Fehler ist aufgetreten.');
        case 2:
          _context.p = 2;
          _t = _context.v;
          return _context.a(2, 'Ein unbekannter Fehler ist aufgetreten.');
      }
    }, _callee, null, [[0, 2]]);
  }));
}
function showInlineError(message, container) {
  var target = container !== null && container !== void 0 ? container : document.querySelector('.projektraum-page__content');
  if (!target) return;
  target.querySelectorAll('.alert--error[data-inline-error]').forEach(function (el) {
    el.remove();
  });
  var alert = document.createElement('div');
  alert.className = 'alert alert--error';
  alert.setAttribute('role', 'alert');
  alert.dataset['inlineError'] = '';
  alert.innerHTML = "<span class=\"alert__icon\" aria-hidden=\"true\">".concat(ERROR_ICON, "</span>") + "<div class=\"alert__content\"><p class=\"alert__message\">".concat((0, escape_html_1.escapeHtml)(message), "</p></div>");
  // Inside a modal, place above the footer; otherwise prepend to container
  var footer = target.querySelector('.modal__footer');
  if (footer) {
    footer.before(alert);
  } else {
    target.prepend(alert);
  }
  alert.scrollIntoView({
    behavior: 'smooth',
    block: 'nearest'
  });
}
function handleApiError(res, container) {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee2() {
    var message;
    return _regenerator().w(function (_context2) {
      while (1) switch (_context2.n) {
        case 0:
          _context2.n = 1;
          return extractErrorMessage(res);
        case 1:
          message = _context2.v;
          showInlineError(message, container);
        case 2:
          return _context2.a(2);
      }
    }, _callee2);
  }));
}
function getSpaceId() {
  var parts = window.location.pathname.split('/');
  var viewIdx = parts.indexOf('view');
  return viewIdx >= 0 && parts[viewIdx + 1] ? parts[viewIdx + 1] : '';
}
function formatDateCH(isoDate) {
  try {
    var date = new Date(isoDate);
    return date.toLocaleDateString('de-CH', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  } catch (_a) {
    return isoDate;
  }
}
function formatDateTimeCH(isoDate) {
  try {
    var date = new Date(isoDate);
    return date.toLocaleDateString('de-CH', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    }) + ', ' + date.toLocaleTimeString('de-CH', {
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (_a) {
    return isoDate;
  }
}
function getInitials(name) {
  return name.split(' ').map(function (w) {
    return w.charAt(0);
  }).join('').substring(0, 2).toUpperCase();
}
function fetchSpace(id) {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee3() {
    var response, json;
    return _regenerator().w(function (_context3) {
      while (1) switch (_context3.n) {
        case 0:
          _context3.n = 1;
          return fetch("".concat(API_BASE, "/").concat(id));
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
function canManage(space) {
  return space.userRole === 'initiator' || space.userRole === 'admin';
}
function getStatusActions(space) {
  var actions = [];
  var isAdmin = space.userRole === 'admin';
  if (space.status === 'draft') {
    actions.push({
      label: 'Aktivieren',
      newStatus: 'active',
      modalTitle: 'Innovation aktivieren',
      modalMessage: 'Die Innovation wird öffentlich sichtbar und Mitglieder können beitreten.',
      confirmLabel: 'Aktivieren'
    });
  }
  if (space.status === 'active') {
    actions.push({
      label: 'Abschliessen',
      newStatus: 'completed',
      modalTitle: 'Innovation abschliessen',
      modalMessage: 'Die Innovation wird als abgeschlossen markiert. Du kannst ihn später wieder öffnen.',
      confirmLabel: 'Abschliessen'
    });
  }
  if (space.status === 'completed') {
    actions.push({
      label: 'Wieder öffnen',
      newStatus: 'active',
      modalTitle: 'Innovation wieder öffnen',
      modalMessage: 'Die Innovation wird wieder aktiv und Mitglieder können erneut beitreten.',
      confirmLabel: 'Wieder öffnen'
    });
    if (isAdmin) {
      actions.push({
        label: 'Archivieren',
        newStatus: 'archived',
        modalTitle: 'Innovation archivieren',
        modalMessage: 'Die Innovation wird archiviert und ist nur noch für Administratoren und Moderatoren sichtbar.',
        confirmLabel: 'Archivieren'
      });
    }
  }
  if (space.status === 'archived' && isAdmin) {
    actions.push({
      label: 'Dearchivieren',
      newStatus: 'completed',
      modalTitle: 'Innovation dearchivieren',
      modalMessage: 'Die Innovation wird wieder als abgeschlossen sichtbar. Er kann danach auch wieder geöffnet werden.',
      confirmLabel: 'Dearchivieren'
    });
  }
  return actions;
}
function canShowStatusActions(space) {
  if (canManage(space)) return true;
  return space.userRole === 'admin' && getStatusActions(space).length > 0;
}
function isEditableStatus(status) {
  return status === 'active' || status === 'draft';
}
function renderManageActions(space, actionsEl) {
  var manage = canManage(space);
  var actions = getStatusActions(space);
  var statusButtons = actions.map(function (a, i) {
    return "<button class=\"button button--muted button--sm\" data-status-action=\"".concat(String(i), "\">").concat(a.label, "</button>");
  }).join('');
  var writeUpdateBtn = manage && isEditableStatus(space.status) ? '<button class="button button--accent button--sm" id="btn-write-update">+ Update schreiben</button>' : '';
  var deleteBtn = space.status === 'archived' && space.userRole === 'admin' ? '<button class="button button--danger button--sm" id="btn-delete-space">Löschen</button>' : '';
  actionsEl.innerHTML = "".concat(statusButtons).concat(writeUpdateBtn).concat(deleteBtn);
  initStatusButtons(space);
  if (manage && isEditableStatus(space.status)) initWriteUpdateButton(space);
  if (space.status === 'archived' && space.userRole === 'admin') initDeleteSpaceButton(space);
}
function renderHeaderActions(space, actionsEl) {
  if (canShowStatusActions(space)) {
    renderManageActions(space, actionsEl);
  } else if (space.userRole === 'visitor' && space.status === 'active') {
    actionsEl.innerHTML = '<button class="button button--accent button--sm" id="btn-join-request">Mitmachen</button>';
    initJoinButton(space);
  } else if (space.userRole === null && space.status === 'active') {
    actionsEl.innerHTML = '<a href="/login" class="button button--accent button--sm">Anmelden um mitzumachen</a>';
  }
}
function renderHeader(space) {
  var _a;
  var titleEl = document.getElementById('space-title');
  var descEl = document.getElementById('space-description');
  var statusEl = document.getElementById('space-status-badge');
  var actionsEl = document.getElementById('space-actions');
  if (titleEl) titleEl.textContent = space.title;
  if (descEl) descEl.innerHTML = (0, escape_html_1.sanitizeHtml)(space.description);
  var statusLabels = {
    draft: 'Entwurf',
    active: 'Aktiv',
    completed: 'Abgeschlossen',
    archived: 'Archiviert'
  };
  if (statusEl) {
    var variant = space.status === 'active' ? 'success' : 'neutral';
    var categoryPill = space.categoryName ? "<span class=\"project-card__badge project-card__badge--category\">".concat((0, escape_html_1.escapeHtml)(space.categoryName), "</span> ") : '';
    statusEl.innerHTML = categoryPill + "<span class=\"project-card__badge project-card__badge--".concat(variant, "\">").concat((_a = statusLabels[space.status]) !== null && _a !== void 0 ? _a : (0, escape_html_1.escapeHtml)(space.status), "</span>");
  }
  if (actionsEl) renderHeaderActions(space, actionsEl);
}
function renderCollaboards(space) {
  var _a;
  var container = document.getElementById('collaboard-panel-container');
  if (!container) return;
  var links = (_a = space.collaboardLinks) !== null && _a !== void 0 ? _a : [];
  var manage = canManage(space);
  if (!space.collaboardLinks) {
    if (space.userRole === 'visitor') {
      container.innerHTML = '<p class="projektraum-page__notice">Tritt der Innovation bei, um die Collaboards zu sehen.</p>';
    } else {
      container.innerHTML = '';
    }
    return;
  }
  var linksHtml;
  if (links.length === 0) {
    linksHtml = '<p class="projektraum-page__empty">Keine Collaboards vorhanden.</p>';
  } else {
    linksHtml = links.map(function (link) {
      return "\n      <div class=\"collaboard-item\" data-id=\"".concat((0, escape_html_1.escapeHtml)(link.id), "\" data-url=\"").concat((0, escape_html_1.escapeHtml)(link.url), "\">\n        <div class=\"collaboard-item__icon\"><span class=\"collaboard-item__icon-text\">C</span></div>\n        <div class=\"collaboard-item__info\">\n          <span class=\"collaboard-item__title\">").concat((0, escape_html_1.escapeHtml)(link.title), "</span>\n          <span class=\"collaboard-item__url\">").concat((0, escape_html_1.escapeHtml)(link.url), "</span>\n        </div>\n        <button type=\"button\" class=\"button button--muted button--sm collaboard-item__action\">\xD6ffnen</button>\n        ").concat(manage ? "<button type=\"button\" class=\"button button--danger button--sm collaboard-item__remove\" data-link-id=\"".concat((0, escape_html_1.escapeHtml)(link.id), "\" title=\"Board \xAB").concat((0, escape_html_1.escapeHtml)(link.title), "\xBB entfernen\">Entfernen</button>") : '', "\n      </div>");
    }).join('');
  }
  container.innerHTML = "\n    <section class=\"collaboard-panel\">\n      <div class=\"collaboard-panel__header\">\n        <h3 class=\"collaboard-panel__title\">Collaboards</h3>\n        ".concat(manage ? '<button class="button button--muted button--sm" id="btn-add-collaboard">+ Board hinzufügen</button>' : '', "\n      </div>\n      <div class=\"collaboard-panel__list\">").concat(linksHtml, "</div>\n      <div class=\"collaboard-panel__iframe-container\" id=\"collaboard-iframe-container\" hidden>\n        <div class=\"collaboard-panel__iframe-toolbar\">\n          <span class=\"collaboard-panel__iframe-title\" id=\"collaboard-iframe-title\"></span>\n          <div class=\"collaboard-panel__iframe-actions\">\n            <a class=\"button button--muted button--sm collaboard-panel__iframe-newtab\" id=\"collaboard-iframe-newtab\" href=\"#\" target=\"_blank\" rel=\"noopener\">In neuem Tab \xF6ffnen \u2191</a>\n            <button class=\"button button--muted button--sm collaboard-panel__iframe-close\" id=\"collaboard-iframe-close\">Schliessen \xD7</button>\n          </div>\n        </div>\n        <iframe class=\"collaboard-panel__iframe\" id=\"collaboard-iframe\" src=\"\" title=\"Collaboard\"></iframe>\n      </div>\n    </section>");
  initCollaboardClicks();
  if (manage) {
    initAddCollaboardButton(space);
    initRemoveCollaboardButtons(space);
  }
}
function formatFileSize(bytes) {
  if (bytes < 1024) return String(bytes) + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}
function renderUpdateAttachments(attachments) {
  if (attachments.length === 0) return '';
  return "<div class=\"status-update-entry__attachments\">\n    ".concat(attachments.map(function (a) {
    return "<a href=\"".concat((0, escape_html_1.escapeHtml)(a.url), "\" target=\"_blank\" rel=\"noopener\" class=\"status-update-entry__attachment\">\n          <span>").concat(a.isImage ? "\uD83D\uDDBC" : "\uD83D\uDCC4", " ").concat((0, escape_html_1.escapeHtml)(a.filename), "</span>\n          <span class=\"status-update-entry__attachment-size\">").concat(formatFileSize(a.size), "</span>\n        </a>");
  }).join(''), "\n  </div>");
}
function renderUpdates(space) {
  var _a;
  var container = document.getElementById('status-update-timeline-container');
  if (!container) return;
  var updates = (_a = space.statusUpdates) !== null && _a !== void 0 ? _a : [];
  var manage = canManage(space);
  var updatesHtml;
  if (updates.length === 0) {
    updatesHtml = '<p class="projektraum-page__empty">Keine Updates vorhanden.</p>';
  } else {
    updatesHtml = "<div class=\"status-update-timeline__list\">\n      ".concat(updates.map(function (u, i) {
      return "\n        <div class=\"status-update-entry".concat(i === 0 ? ' status-update-entry--latest status-update-entry--open' : '', "\" data-update-id=\"").concat((0, escape_html_1.escapeHtml)(u.id), "\">\n          <div class=\"status-update-entry__dot\"></div>\n          <div class=\"status-update-entry__content\">\n            <span class=\"status-update-entry__meta\">").concat(formatDateTimeCH(u.createdAt), "</span>\n            <div class=\"status-update-entry__header\" data-toggle-update>\n              <h4 class=\"status-update-entry__title\">").concat((0, escape_html_1.escapeHtml)(u.title), "</h4>\n              <span class=\"status-update-entry__chevron\"></span>\n            </div>\n            <div class=\"status-update-entry__collapsible\">\n              <div class=\"status-update-entry__body\">").concat((0, escape_html_1.sanitizeHtml)(u.body), "</div>\n              ").concat(renderUpdateAttachments(u.attachments), "\n              ").concat(manage ? "<div class=\"status-update-entry__actions\">\n                <button type=\"button\" class=\"button button--muted button--sm\" data-edit-update=\"".concat((0, escape_html_1.escapeHtml)(u.id), "\" data-title=\"").concat((0, escape_html_1.escapeHtml)(u.title), "\" data-body=\"").concat((0, escape_html_1.escapeHtml)(u.body), "\">Bearbeiten</button>\n                <button type=\"button\" class=\"button button--danger button--sm\" data-delete-update=\"").concat((0, escape_html_1.escapeHtml)(u.id), "\" data-title=\"").concat((0, escape_html_1.escapeHtml)(u.title), "\">L\xF6schen</button>\n              </div>") : '', "\n            </div>\n          </div>\n        </div>");
    }).join(''), "\n    </div>");
  }
  container.innerHTML = "\n    <section class=\"status-update-timeline\">\n      <div class=\"status-update-timeline__header\">\n        <h3 class=\"status-update-timeline__title\">Projekt-Updates</h3>\n      </div>\n      ".concat(updatesHtml, "\n    </section>");
  container.querySelectorAll('[data-toggle-update]').forEach(function (header) {
    header.addEventListener('click', function () {
      var _a;
      (_a = header.closest('.status-update-entry')) === null || _a === void 0 ? void 0 : _a.classList.toggle('status-update-entry--open');
    });
  });
  if (manage) {
    initEditUpdateButtons(space);
    initDeleteUpdateButtons(space);
  }
}
function renderIdeaAttachments(space) {
  var _a;
  var container = document.getElementById('idea-attachments-container');
  if (!container) return;
  var attachments = (_a = space.ideaAttachments) !== null && _a !== void 0 ? _a : [];
  if (attachments.length === 0) return;
  container.innerHTML = "\n    <section class=\"idea-attachments-panel\">\n      <h3 class=\"idea-attachments-panel__title\">Urspr\xFCngliche Anh\xE4nge</h3>\n      <div class=\"idea-attachments-panel__list\">\n        ".concat(attachments.map(function (a) {
    return "<a href=\"".concat((0, escape_html_1.escapeHtml)(a.url), "\" class=\"idea-attachments-panel__item\" target=\"_blank\" rel=\"noopener\">\n              <span>").concat(a.isImage ? "\uD83D\uDDBC" : "\uD83D\uDCC4", " ").concat((0, escape_html_1.escapeHtml)(a.filename), "</span>\n              <span class=\"idea-attachments-panel__size\">").concat(a.size < 1000000 ? String(Math.round(a.size / 1000)) + ' KB' : (a.size / 1000000).toFixed(1) + ' MB', "</span>\n            </a>");
  }).join(''), "\n      </div>\n    </section>");
}
// --- Header image ---
var HEADER_IMAGE_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
var HEADER_IMAGE_MAX_BYTES = 10 * 1024 * 1024;
var HEADER_IMAGE_MIN_WIDTH = 800;
function validateHeaderImage(file) {
  if (HEADER_IMAGE_ALLOWED_TYPES.indexOf(file.type) === -1) {
    return Promise.resolve('Nur JPEG, PNG, GIF oder WebP erlaubt.');
  }
  if (file.size > HEADER_IMAGE_MAX_BYTES) {
    return Promise.resolve('Maximale Dateigrösse: 10 MB.');
  }
  return new Promise(function (resolve) {
    var img = new Image();
    img.onload = function () {
      resolve(img.naturalWidth < HEADER_IMAGE_MIN_WIDTH ? 'Mindestbreite: 800 Pixel.' : null);
      URL.revokeObjectURL(img.src);
    };
    img.onerror = function () {
      resolve('Bild konnte nicht gelesen werden.');
    };
    img.src = URL.createObjectURL(file);
  });
}
function renderHero(space) {
  var _a, _b, _c, _d;
  var container = document.getElementById('projektraum-hero-container');
  if (!container) return;
  var manage = canManage(space);
  var hasImage = Boolean(space.headerImageUrl);
  if (hasImage) {
    container.innerHTML = "\n      <div class=\"projektraum-hero\">\n        <img src=\"".concat((0, escape_html_1.escapeHtml)((_a = space.headerImageUrl) !== null && _a !== void 0 ? _a : ''), "\" alt=\"\" class=\"projektraum-hero__image\" id=\"hero-image\">\n        ").concat(manage ? "<div class=\"projektraum-hero__overlay\" id=\"hero-overlay\">\n                <button class=\"projektraum-hero__edit button button--accent button--sm\" id=\"hero-edit-btn\" aria-label=\"Titelbild \xE4ndern\">\n                  <svg viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z\"/></svg>\n                  Titelbild \xE4ndern\n                </button>\n                <button class=\"projektraum-hero__remove button button--muted button--sm\" id=\"hero-remove-btn\" aria-label=\"Titelbild entfernen\">\n                  <svg viewBox=\"0 0 24 24\" width=\"14\" height=\"14\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z\"/></svg>\n                  Entfernen\n                </button>\n              </div>" : '', "\n      </div>");
  } else if (manage) {
    container.innerHTML = "\n      <button class=\"projektraum-hero__add button button--muted button--lg\" id=\"hero-add-btn\">\n        <svg viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z\"/></svg>\n        Titelbild hinzuf\xFCgen\n      </button>";
  } else {
    container.innerHTML = '';
    return;
  }
  // Hidden file input (shared for both add and edit)
  var input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/jpeg,image/png,image/gif,image/webp';
  input.style.display = 'none';
  input.id = 'hero-file-input';
  container.appendChild(input);
  var triggerPicker = function triggerPicker() {
    input.value = '';
    input.click();
  };
  input.addEventListener('change', function () {
    var _a;
    var file = (_a = input.files) === null || _a === void 0 ? void 0 : _a[0];
    if (!file) return;
    void validateHeaderImage(file).then(function (error) {
      if (error) {
        showInlineError(error);
        return;
      }
      var formData = new FormData();
      formData.append('headerImage', file);
      void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/header-image"), {
        method: 'POST',
        body: formData
      }).then(function (res) {
        if (res.ok) {
          void res.json().then(function (json) {
            var _a;
            var data = json;
            var newUrl = (_a = data.headerImageUrl) !== null && _a !== void 0 ? _a : URL.createObjectURL(file);
            // Update the hero image src if it exists, otherwise re-render
            var heroImg = document.getElementById('hero-image');
            if (heroImg) {
              heroImg.src = newUrl;
            } else {
              space.headerImageUrl = newUrl;
              renderHero(space);
            }
          });
        } else {
          void handleApiError(res);
        }
      });
    });
  });
  (_b = document.getElementById('hero-add-btn')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', triggerPicker);
  (_c = document.getElementById('hero-edit-btn')) === null || _c === void 0 ? void 0 : _c.addEventListener('click', triggerPicker);
  (_d = document.getElementById('hero-remove-btn')) === null || _d === void 0 ? void 0 : _d.addEventListener('click', function () {
    if (!confirm('Titelbild wirklich entfernen?')) return;
    void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/header-image"), {
      method: 'DELETE'
    }).then(function (res) {
      if (res.ok) {
        space.headerImageUrl = null;
        renderHero(space);
      } else {
        void handleApiError(res);
      }
    });
  });
}
function renderMemberRow(member, space) {
  var isInitiator = member.role === 'initiator';
  var isSelf = space.currentUserId !== null && member.id === space.currentUserId;
  var canManageSpace = canManage(space) || space.userRole === 'initiator';
  var actionButton = '';
  if (!isInitiator) {
    if (isSelf) {
      actionButton = "<button type=\"button\" class=\"button button--danger button--sm\" data-leave-space=\"".concat((0, escape_html_1.escapeHtml)(space.id), "\" title=\"Innovation verlassen\">Verlassen</button>");
    } else if (canManageSpace) {
      actionButton = "<button type=\"button\" class=\"button button--danger button--sm\" data-remove-member=\"".concat((0, escape_html_1.escapeHtml)(member.id), "\" data-member-name=\"").concat((0, escape_html_1.escapeHtml)(member.name), "\" title=\"").concat((0, escape_html_1.escapeHtml)(member.name), " entfernen\">Entfernen</button>");
    }
  }
  return "\n    <div class=\"project-space-sidebar__member-row\">\n      ".concat(member.avatarUrl ? "<img class=\"project-space-sidebar__avatar-img\" src=\"".concat((0, escape_html_1.escapeHtml)(member.avatarUrl), "\" alt=\"").concat((0, escape_html_1.escapeHtml)(member.name), "\">") : "<div class=\"project-space-sidebar__avatar\">".concat((0, escape_html_1.escapeHtml)(getInitials(member.name)), "</div>"), "\n      <span class=\"project-space-sidebar__member-name\">").concat((0, escape_html_1.escapeHtml)(member.name)).concat(isInitiator ? ' (Initiator)' : '', "</span>\n      ").concat(actionButton, "\n    </div>");
}
function renderSidebar(space) {
  var _a, _b, _c;
  var container = document.getElementById('sidebar-container');
  if (!container) return;
  var members = (_a = space.members) !== null && _a !== void 0 ? _a : [];
  var requests = ((_b = space.membershipRequests) !== null && _b !== void 0 ? _b : []).filter(function (r) {
    return r.status === 'pending';
  });
  var manage = canManage(space);
  var membersHtml = members.map(function (member) {
    return renderMemberRow(member, space);
  }).join('');
  var requestsHtml = manage && requests.length > 0 ? "\n    <div class=\"project-space-sidebar__requests\">\n      <span class=\"project-space-sidebar__label\">Anfragen (".concat(String(requests.length), ")</span>\n      ").concat(requests.map(function (req) {
    return "\n        <div class=\"join-request-card\" data-request-id=\"".concat((0, escape_html_1.escapeHtml)(req.id), "\">\n          <div class=\"join-request-card__user\">\n            <span class=\"join-request-card__name\">").concat((0, escape_html_1.escapeHtml)(req.userName), "</span>\n          </div>\n          <div class=\"join-request-card__actions\">\n            <button class=\"unstyled-button join-request-card__btn join-request-card__btn--accept\" data-action=\"accept\" title=\"Anfrage von ").concat((0, escape_html_1.escapeHtml)(req.userName), " annehmen\">\u2713</button>\n            <button class=\"unstyled-button join-request-card__btn join-request-card__btn--reject\" data-action=\"reject\" title=\"Anfrage von ").concat((0, escape_html_1.escapeHtml)(req.userName), " ablehnen\">\u2715</button>\n          </div>\n        </div>");
  }).join(''), "\n    </div>") : '';
  container.innerHTML = "\n    <aside class=\"project-space-sidebar\">\n      <h3 class=\"project-space-sidebar__section-title\">Team</h3>\n      ".concat(members.length > 0 ? "\n        <div class=\"project-space-sidebar__members\">\n          <span class=\"project-space-sidebar__label\">Mitglieder (".concat(String(members.length), ")</span>\n          ").concat(membersHtml, "\n        </div>") : '', "\n      ").concat(requestsHtml, "\n      <div class=\"project-space-sidebar__stats\">\n        <span class=\"project-space-sidebar__label\">Statistik</span>\n        <div class=\"project-space-sidebar__stat-row\"><span>Mitglieder</span><span>").concat(String(space.memberCount), "</span></div>\n        <div class=\"project-space-sidebar__stat-row\"><span>Updates</span><span>").concat(String(((_c = space.statusUpdates) !== null && _c !== void 0 ? _c : []).length), "</span></div>\n        <div class=\"project-space-sidebar__stat-row\"><span>Erstellt</span><span>").concat(formatDateCH(space.createdAt), "</span></div>\n      </div>\n    </aside>");
  // Leave space buttons (self, non-initiator members)
  container.querySelectorAll('[data-leave-space]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('Möchtest du die Innovation wirklich verlassen?')) return;
      void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/membership"), {
        method: 'DELETE'
      }).then(function (res) {
        if (res.ok) {
          window.location.href = '/innovationen';
        } else {
          void handleApiError(res);
        }
      });
    });
  });
  // Remove member buttons (admin/initiator action)
  container.querySelectorAll('[data-remove-member]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a, _b;
      var memberId = (_a = btn.dataset['removeMember']) !== null && _a !== void 0 ? _a : '';
      var memberName = (_b = btn.dataset['memberName']) !== null && _b !== void 0 ? _b : '';
      if (!confirm("M\xF6chtest du ".concat(memberName, " wirklich aus der Innovation entfernen?"))) return;
      void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/member/").concat(memberId), {
        method: 'DELETE'
      }).then(function (res) {
        if (res.ok) {
          window.location.reload();
        } else {
          void handleApiError(res);
        }
      });
    });
  });
  if (manage) {
    initJoinRequestActions(space.id);
    renderExpertSection(space);
  }
  if (space.userRole === 'admin') {
    renderPrioritySection(space);
  }
}
function renderExpertSection(space) {
  var _a, _b;
  var sidebar = document.querySelector('.project-space-sidebar');
  if (!sidebar) return;
  var pendingRequests = ((_a = space.expertRequests) !== null && _a !== void 0 ? _a : []).filter(function (r) {
    return r.status === 'pending';
  });
  var pendingHtml = pendingRequests.length > 0 ? "<div class=\"project-space-sidebar__expert-requests\">\n          <span class=\"project-space-sidebar__label\">Ausstehende Anfragen (".concat(String(pendingRequests.length), ")</span>\n          ").concat(pendingRequests.map(function (req) {
    return "\n            <div class=\"project-space-sidebar__expert-request-row\">\n              <div>\n                <span class=\"project-space-sidebar__member-name\">".concat((0, escape_html_1.escapeHtml)(req.expertName), "</span>\n                <span class=\"project-space-sidebar__expert-expertise\">").concat((0, escape_html_1.escapeHtml)(req.expertExpertise), "</span>\n              </div>\n              <span class=\"badge badge--neutral badge--sm\">Ausstehend</span>\n            </div>");
  }).join(''), "\n        </div>") : '';
  var sectionEl = document.createElement('div');
  sectionEl.className = 'project-space-sidebar__expert-section';
  sectionEl.innerHTML = "\n    <h3 class=\"project-space-sidebar__section-title\">Experten anfragen</h3>\n    <div class=\"project-space-sidebar__expert-form\">\n      <div class=\"form-field\" style=\"margin-bottom: var(--space-3);\">\n        <label class=\"project-space-sidebar__label\" for=\"expert-select\">Experte ausw\xE4hlen</label>\n        <select id=\"expert-select\" class=\"input\">\n          <option value=\"\">Wird geladen\u2026</option>\n        </select>\n      </div>\n      <div class=\"form-field\" style=\"margin-bottom: var(--space-3);\">\n        <label class=\"project-space-sidebar__label\" for=\"expert-message\">Nachricht (optional)</label>\n        <textarea id=\"expert-message\" class=\"textarea\" rows=\"3\" placeholder=\"Nachricht an den Experten\u2026\"></textarea>\n      </div>\n      <button class=\"button button--accent button--sm\" id=\"btn-request-expert\">Anfragen</button>\n    </div>\n    ".concat(pendingHtml);
  sidebar.appendChild(sectionEl);
  // Load available experts
  void fetch("".concat(API_BASE, "/").concat(space.id, "/experts")).then(function (res) {
    return res.ok ? res.json() : Promise.reject(new Error('Failed to load experts'));
  }).then(function (json) {
    var select = document.getElementById('expert-select');
    if (!select) return;
    var experts = json.data;
    if (experts.length === 0) {
      select.innerHTML = '<option value="">Keine Experten verfügbar</option>';
      return;
    }
    select.innerHTML = '<option value="">Bitte auswählen…</option>' + experts.map(function (e) {
      return "<option value=\"".concat((0, escape_html_1.escapeHtml)(e.userId), "\">").concat((0, escape_html_1.escapeHtml)(e.name), " \u2013 ").concat((0, escape_html_1.escapeHtml)(e.expertise), "</option>");
    }).join('');
  })["catch"](function () {
    var select = document.getElementById('expert-select');
    if (select) select.innerHTML = '<option value="">Fehler beim Laden</option>';
  });
  // Handle request button
  (_b = document.getElementById('btn-request-expert')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', function () {
    var _a;
    var select = document.getElementById('expert-select');
    var messageEl = document.getElementById('expert-message');
    var expertUserId = (_a = select === null || select === void 0 ? void 0 : select.value) !== null && _a !== void 0 ? _a : '';
    if (!expertUserId) return;
    var message = (messageEl === null || messageEl === void 0 ? void 0 : messageEl.value.trim()) || null;
    void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/expert-request"), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        expertUserId: expertUserId,
        message: message
      })
    }).then(function (res) {
      if (res.ok) window.location.reload();else void handleApiError(res);
    });
  });
}
// --- Interaction handlers ---
function initCollaboardClicks() {
  var container = document.getElementById('collaboard-iframe-container');
  var iframe = document.getElementById('collaboard-iframe');
  var titleEl = document.getElementById('collaboard-iframe-title');
  var newtabEl = document.getElementById('collaboard-iframe-newtab');
  var closeBtn = document.getElementById('collaboard-iframe-close');
  document.querySelectorAll('.collaboard-item').forEach(function (item) {
    item.addEventListener('click', function (e) {
      var _a, _b, _c;
      if (e.target.closest('.collaboard-item__remove')) return;
      var el = item;
      var url = (_a = el.dataset['url']) !== null && _a !== void 0 ? _a : '';
      var title = (_c = (_b = el.querySelector('.collaboard-item__title')) === null || _b === void 0 ? void 0 : _b.textContent) !== null && _c !== void 0 ? _c : '';
      if (container && iframe && titleEl && newtabEl) {
        container.hidden = false;
        iframe.src = url;
        titleEl.textContent = title;
        newtabEl.href = url;
      }
      document.querySelectorAll('.collaboard-item').forEach(function (i) {
        i.classList.remove('collaboard-item--active');
      });
      el.classList.add('collaboard-item--active');
    });
  });
  closeBtn === null || closeBtn === void 0 ? void 0 : closeBtn.addEventListener('click', function () {
    if (container && iframe) {
      container.hidden = true;
      iframe.src = '';
    }
    document.querySelectorAll('.collaboard-item').forEach(function (i) {
      i.classList.remove('collaboard-item--active');
    });
  });
}
function initAddCollaboardButton(space) {
  var _a;
  var modal = document.getElementById('collaboard-modal');
  var submitBtn = document.getElementById('collaboard-submit');
  var titleInput = document.getElementById('collaboard-title');
  var urlInput = document.getElementById('collaboard-url');
  (_a = document.getElementById('btn-add-collaboard')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
    if (!modal) return;
    if (titleInput) titleInput.value = '';
    if (urlInput) urlInput.value = '';
    modal.showModal();
  });
  modal === null || modal === void 0 ? void 0 : modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  submitBtn === null || submitBtn === void 0 ? void 0 : submitBtn.addEventListener('click', function () {
    var _a, _b;
    var title = (_a = titleInput === null || titleInput === void 0 ? void 0 : titleInput.value.trim()) !== null && _a !== void 0 ? _a : '';
    var url = (_b = urlInput === null || urlInput === void 0 ? void 0 : urlInput.value.trim()) !== null && _b !== void 0 ? _b : '';
    if (!title || !url) return;
    void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/collaboard"), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        title: title,
        url: url
      })
    }).then(function (res) {
      if (res.ok) window.location.reload();else void handleApiError(res, modal);
    });
  });
}
function initRemoveCollaboardButtons(space) {
  document.querySelectorAll('.collaboard-item__remove').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      var _a;
      e.stopPropagation();
      var linkId = (_a = btn.dataset['linkId']) !== null && _a !== void 0 ? _a : '';
      if (!confirm('Board wirklich entfernen?')) return;
      void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/collaboard/").concat(linkId), {
        method: 'DELETE'
      }).then(function (res) {
        if (res.ok) window.location.reload();else void handleApiError(res);
      });
    });
  });
}
function getCKEditor() {
  var ckeditor = window['CKEDITOR'];
  if (ckeditor === null || ckeditor === void 0 ? void 0 : ckeditor.config) {
    ckeditor.config['versionCheck'] = false;
  }
  return ckeditor;
}
function getCkeContentsCss() {
  var _a;
  var ckeditor = window['CKEDITOR'];
  var themePath = ((_a = document.querySelector('meta[name="theme-path"]')) === null || _a === void 0 ? void 0 : _a.getAttribute('content')) || '/packages/inno_frontend/themes/innovationsbox';
  return [((ckeditor === null || ckeditor === void 0 ? void 0 : ckeditor.basePath) || '/concrete/js/ckeditor/') + 'contents.css', themePath + '/css/ckeditor-contents.css?v=' + String(Date.now())];
}
var editorInstance = null;
function initCKEditor() {
  var ckeditor = getCKEditor();
  if (!ckeditor || editorInstance) return;
  editorInstance = ckeditor.replace('update-body', {
    toolbar: [{
      name: 'basicstyles',
      items: ['Bold', 'Italic', 'Underline']
    }, {
      name: 'paragraph',
      items: ['NumberedList', 'BulletedList']
    }, {
      name: 'links',
      items: ['Link', 'Unlink']
    }, {
      name: 'styles',
      items: ['Format']
    }, {
      name: 'tools',
      items: ['Maximize']
    }],
    contentsCss: getCkeContentsCss(),
    removePlugins: 'elementspath',
    resize_enabled: false,
    versionCheck: false,
    height: 200
  });
}
/**
 * Open a <dialog> with .show() instead of .showModal() so that CKEditor
 * dialogs (appended to <body>) are not trapped behind the top-layer.
 * Adds a manual backdrop and Escape-key listener.
 */
function openDialogWithBackdrop(dialog) {
  var backdrop = dialog.previousElementSibling;
  if (!(backdrop === null || backdrop === void 0 ? void 0 : backdrop.classList.contains('modal-backdrop'))) {
    backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    dialog.insertAdjacentElement('beforebegin', backdrop);
    backdrop.addEventListener('click', function () {
      dialog.close();
    });
  }
  backdrop.style.display = 'block';
  dialog.show();
  var onKey = function onKey(e) {
    if (e.key === 'Escape' && dialog.open) {
      // Only close if no CKEditor dialog is open
      if (!document.querySelector('.cke_dialog_container')) {
        dialog.close();
      }
    }
  };
  dialog.addEventListener('keydown', onKey);
  dialog.addEventListener('close', function () {
    backdrop.style.display = 'none';
    dialog.removeEventListener('keydown', onKey);
  }, {
    once: true
  });
}
function getCkeditorContent() {
  var _a;
  if (editorInstance) {
    return editorInstance.getData();
  }
  var textarea = document.getElementById('update-body');
  return (_a = textarea === null || textarea === void 0 ? void 0 : textarea.value) !== null && _a !== void 0 ? _a : '';
}
var selectedUpdateFiles = [];
var MAX_UPDATE_FILES = 5;
var MAX_UPDATE_FILE_SIZE = 10000000;
function resetUpdateModal() {
  var existingContainer = document.getElementById('update-existing-files-container');
  var existingFiles = document.getElementById('update-existing-files');
  if (existingContainer) existingContainer.hidden = true;
  if (existingFiles) existingFiles.innerHTML = '';
  selectedUpdateFiles.length = 0;
  renderUpdateFileList();
}
function addUpdateFiles(files) {
  var _iterator = _createForOfIteratorHelper(files),
    _step;
  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var file = _step.value;
      if (selectedUpdateFiles.length >= MAX_UPDATE_FILES) {
        break;
      }
      if (file.size > MAX_UPDATE_FILE_SIZE) {
        continue;
      }
      selectedUpdateFiles.push(file);
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
  renderUpdateFileList();
}
function renderUpdateFileList() {
  var list = document.getElementById('update-file-list');
  if (!list) return;
  if (selectedUpdateFiles.length === 0) {
    list.innerHTML = '';
    return;
  }
  list.innerHTML = selectedUpdateFiles.map(function (file, idx) {
    return "\n    <div class=\"file-upload-zone__item\">\n      <span class=\"file-upload-zone__name\">".concat((0, escape_html_1.escapeHtml)(file.name), "</span>\n      <span class=\"file-upload-zone__size\">").concat((file.size / 1000000).toFixed(1), " MB</span>\n      <button type=\"button\" class=\"unstyled-button file-upload-zone__remove\" data-idx=\"").concat(String(idx), "\" title=\"Entfernen\">\xD7</button>\n    </div>");
  }).join('');
  list.querySelectorAll('.file-upload-zone__remove').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a;
      var idx = parseInt((_a = btn.dataset['idx']) !== null && _a !== void 0 ? _a : '0', 10);
      selectedUpdateFiles.splice(idx, 1);
      renderUpdateFileList();
    });
  });
}
function initUpdateFileZone() {
  var zone = document.getElementById('update-file-zone');
  var input = document.getElementById('update-files');
  if (!zone || !input) return;
  zone.addEventListener('click', function () {
    input.click();
  });
  zone.addEventListener('dragover', function (e) {
    e.preventDefault();
    zone.classList.add('file-upload-zone--dragover');
  });
  zone.addEventListener('dragleave', function () {
    zone.classList.remove('file-upload-zone--dragover');
  });
  zone.addEventListener('drop', function (e) {
    var _a;
    e.preventDefault();
    zone.classList.remove('file-upload-zone--dragover');
    if ((_a = e.dataTransfer) === null || _a === void 0 ? void 0 : _a.files) {
      addUpdateFiles(Array.from(e.dataTransfer.files));
    }
  });
  input.addEventListener('change', function () {
    if (input.files) {
      addUpdateFiles(Array.from(input.files));
    }
    input.value = '';
  });
}
function initWriteUpdateButton(space) {
  var _a;
  var modal = document.getElementById('update-modal');
  var submitBtn = document.getElementById('update-submit');
  var titleInput = document.getElementById('update-title');
  initUpdateFileZone();
  (_a = document.getElementById('btn-write-update')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
    if (!modal) return;
    if (titleInput) titleInput.value = '';
    if (editorInstance) editorInstance.setData('');
    resetUpdateModal();
    openDialogWithBackdrop(modal);
    setTimeout(function () {
      initCKEditor();
    }, 100);
  });
  modal === null || modal === void 0 ? void 0 : modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  submitBtn === null || submitBtn === void 0 ? void 0 : submitBtn.addEventListener('click', function () {
    var _a;
    var title = (_a = titleInput === null || titleInput === void 0 ? void 0 : titleInput.value.trim()) !== null && _a !== void 0 ? _a : '';
    var body = getCkeditorContent();
    if (!title || !body) return;
    var formData = buildUpdateFormData(title, body, []);
    void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/status-update"), {
      method: 'POST',
      body: formData
    }).then(function (res) {
      if (res.ok) window.location.reload();else void handleApiError(res, modal);
    });
  });
}
function buildUpdateFormData(title, body, filesToRemove) {
  var formData = new FormData();
  formData.append('title', title);
  formData.append('body', body);
  filesToRemove.forEach(function (attId) {
    formData.append('removeFiles[]', attId);
  });
  selectedUpdateFiles.forEach(function (file) {
    formData.append('files[]', file);
  });
  return formData;
}
function renderExistingUpdateAttachments(attachments, container, listEl, filesToRemove) {
  if (attachments.length === 0) return;
  container.hidden = false;
  listEl.innerHTML = attachments.map(function (a) {
    return "<div class=\"edit-file-item\" data-att-id=\"".concat((0, escape_html_1.escapeHtml)(a.id), "\">\n        <span>").concat(a.isImage ? "\uD83D\uDDBC" : "\uD83D\uDCC4", " ").concat((0, escape_html_1.escapeHtml)(a.filename), "</span>\n        <button type=\"button\" class=\"edit-file-item__remove\" title=\"Entfernen\">\xD7</button>\n      </div>");
  }).join('');
  listEl.querySelectorAll('.edit-file-item__remove').forEach(function (removeBtn) {
    removeBtn.addEventListener('click', function () {
      var _a;
      var item = removeBtn.closest('.edit-file-item');
      var attId = (_a = item.dataset['attId']) !== null && _a !== void 0 ? _a : '';
      filesToRemove.push(attId);
      item.style.opacity = '0.3';
      item.style.textDecoration = 'line-through';
      removeBtn.style.display = 'none';
    });
  });
}
function initEditUpdateButtons(space) {
  var modal = document.getElementById('update-modal');
  var titleInput = document.getElementById('update-title');
  var submitBtn = document.getElementById('update-submit');
  var existingContainer = document.getElementById('update-existing-files-container');
  var existingFilesEl = document.getElementById('update-existing-files');
  document.querySelectorAll('[data-edit-update]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!modal || !titleInput || !submitBtn) return;
      handleEditUpdateClick(btn, space, modal, titleInput, submitBtn, existingContainer, existingFilesEl);
    });
  });
}
function handleEditUpdateClick(btn, space, modal, titleInput, submitBtn, existingContainer, existingFilesEl) {
  var _a, _b, _c, _d, _e;
  var updateId = (_a = btn.dataset['editUpdate']) !== null && _a !== void 0 ? _a : '';
  var title = (_b = btn.dataset['title']) !== null && _b !== void 0 ? _b : '';
  var body = (_c = btn.dataset['body']) !== null && _c !== void 0 ? _c : '';
  var updateData = ((_d = space.statusUpdates) !== null && _d !== void 0 ? _d : []).find(function (u) {
    return u.id === updateId;
  });
  var attachments = (_e = updateData === null || updateData === void 0 ? void 0 : updateData.attachments) !== null && _e !== void 0 ? _e : [];
  var filesToRemove = [];
  titleInput.value = title;
  resetUpdateModal();
  if (existingContainer && existingFilesEl) {
    renderExistingUpdateAttachments(attachments, existingContainer, existingFilesEl, filesToRemove);
  }
  openDialogWithBackdrop(modal);
  setTimeout(function () {
    initCKEditor();
    if (editorInstance) editorInstance.setData(body);
  }, 100);
  var freshSubmit = submitBtn.cloneNode(true);
  freshSubmit.textContent = 'Speichern';
  submitBtn.replaceWith(freshSubmit);
  freshSubmit.addEventListener('click', function () {
    var newTitle = titleInput.value.trim();
    var newBody = getCkeditorContent();
    if (!newTitle || !newBody) return;
    var formData = buildUpdateFormData(newTitle, newBody, filesToRemove);
    void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/status-update/").concat(updateId, "/edit"), {
      method: 'POST',
      body: formData
    }).then(function (res) {
      if (res.ok) window.location.reload();else void handleApiError(res, modal);
    });
  });
}
function initDeleteUpdateButtons(space) {
  var modal = document.getElementById('delete-update-modal');
  var nameEl = document.getElementById('delete-update-name');
  var confirmBtn = document.getElementById('delete-update-confirm');
  if (!modal || !confirmBtn) return;
  modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  document.querySelectorAll('[data-delete-update]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a, _b;
      var updateId = (_a = btn.dataset['deleteUpdate']) !== null && _a !== void 0 ? _a : '';
      var title = (_b = btn.dataset['title']) !== null && _b !== void 0 ? _b : '';
      if (nameEl) nameEl.textContent = "\xAB".concat(title, "\xBB");
      var freshConfirm = confirmBtn.cloneNode(true);
      confirmBtn.replaceWith(freshConfirm);
      freshConfirm.addEventListener('click', function () {
        void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/status-update/").concat(updateId), {
          method: 'DELETE'
        }).then(function (res) {
          if (res.ok) window.location.reload();else void handleApiError(res);
        });
      });
      modal.showModal();
    });
  });
}
function initStatusButtons(space) {
  var modal = document.getElementById('status-modal');
  var titleEl = document.getElementById('status-modal-title');
  var messageEl = document.getElementById('status-modal-message');
  var confirmBtn = document.getElementById('status-modal-confirm');
  if (!modal || !confirmBtn) return;
  var actions = getStatusActions(space);
  modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  document.querySelectorAll('[data-status-action]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a;
      var idx = parseInt((_a = btn.dataset['statusAction']) !== null && _a !== void 0 ? _a : '0', 10);
      var action = actions[idx];
      if (!action) return;
      if (titleEl) titleEl.textContent = action.modalTitle;
      if (messageEl) messageEl.textContent = action.modalMessage;
      var freshConfirm = confirmBtn.cloneNode(true);
      freshConfirm.textContent = action.confirmLabel;
      confirmBtn.replaceWith(freshConfirm);
      freshConfirm.addEventListener('click', function () {
        void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/status"), {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            status: action.newStatus
          })
        }).then(function (res) {
          if (res.ok) window.location.reload();else void handleApiError(res, modal);
        });
      });
      modal.showModal();
    });
  });
}
function initDeleteSpaceButton(space) {
  var _a;
  var modal = document.getElementById('delete-space-modal');
  var confirmBtn = document.getElementById('delete-space-confirm');
  if (!modal || !confirmBtn) return;
  modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  (_a = document.getElementById('btn-delete-space')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
    modal.showModal();
  });
  confirmBtn.addEventListener('click', function () {
    void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id), {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json'
      }
    }).then(function (res) {
      if (res.ok) {
        window.location.href = '/innovationen';
      } else {
        void handleApiError(res, modal);
      }
    });
  });
}
function initJoinButton(space) {
  var btn = document.getElementById('btn-join-request');
  if (!btn) return;
  btn.addEventListener('click', function () {
    void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/join-request"), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      }
    }).then(function (res) {
      if (res.ok) {
        btn.textContent = 'Anfrage gesendet ✓';
        btn.classList.add('btn--disabled');
        btn.disabled = true;
        var header = document.querySelector('.projektraum-page__header');
        if (header) {
          header.insertAdjacentHTML('afterend', '<div class="projektraum-page__join-notice">' + '<p>Deine Anfrage wurde an die Initiatorin gesendet. Du erhältst Zugang, sobald sie bestätigt wurde.</p></div>');
        }
      } else {
        void handleApiError(res);
      }
    });
  });
}
function initJoinRequestActions(spaceId) {
  document.querySelectorAll('.join-request-card__btn').forEach(function (button) {
    button.addEventListener('click', function () {
      var card = button.closest('.join-request-card');
      if (!card) return;
      var requestId = card.dataset['requestId'];
      if (!requestId) return;
      var accept = button.dataset['action'] === 'accept';
      void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(spaceId, "/join-request/").concat(requestId), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          accept: accept
        })
      }).then(function (res) {
        if (res.ok) card.remove();
      });
    });
  });
}
// --- Priority ---
function renderPrioritySection(space) {
  var _a, _b;
  var sidebar = document.querySelector('.project-space-sidebar');
  if (!sidebar) return;
  var current = (_a = space.priority) !== null && _a !== void 0 ? _a : 0;
  var sectionEl = document.createElement('div');
  sectionEl.className = 'project-space-sidebar__priority-section';
  sectionEl.innerHTML = "\n    <h3 class=\"project-space-sidebar__section-title\">Priorit\xE4t (Startseite)</h3>\n    <div class=\"form-field\" style=\"margin-bottom: var(--space-3);\">\n      <label class=\"project-space-sidebar__label\" for=\"priority-select\">Position im Teaser</label>\n      <select id=\"priority-select\" class=\"input\">\n        <option value=\"0\"".concat(current === 0 ? ' selected' : '', ">Keine Priorit\xE4t</option>\n        <option value=\"1\"").concat(current === 1 ? ' selected' : '', ">Position 1</option>\n        <option value=\"2\"").concat(current === 2 ? ' selected' : '', ">Position 2</option>\n        <option value=\"3\"").concat(current === 3 ? ' selected' : '', ">Position 3</option>\n      </select>\n    </div>\n    <button class=\"button button--accent button--sm\" id=\"btn-save-priority\">Speichern</button>\n    <span id=\"priority-feedback\" class=\"project-space-sidebar__priority-feedback\"></span>");
  sidebar.appendChild(sectionEl);
  (_b = document.getElementById('btn-save-priority')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', function () {
    var _a;
    var select = document.getElementById('priority-select');
    var priority = parseInt((_a = select === null || select === void 0 ? void 0 : select.value) !== null && _a !== void 0 ? _a : '0', 10);
    var feedbackEl = document.getElementById('priority-feedback');
    void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(space.id, "/priority"), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        priority: priority
      })
    }).then(function (res) {
      if (res.ok) {
        space.priority = priority;
        if (feedbackEl) {
          feedbackEl.textContent = 'Gespeichert ✓';
          setTimeout(function () {
            feedbackEl.textContent = '';
          }, 3000);
        }
      } else {
        void handleApiError(res);
      }
    });
  });
}
// --- Main ---
document.addEventListener('DOMContentLoaded', function () {
  var spaceId = getSpaceId();
  if (!spaceId) return;
  void fetchSpace(spaceId).then(function (space) {
    renderHero(space);
    renderHeader(space);
    renderCollaboards(space);
    renderIdeaAttachments(space);
    renderUpdates(space);
    renderSidebar(space);
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
/******/ __webpack_require__.O(0, ["/themes/innovationsbox/js/vendor"], () => (__webpack_exec__("./resources/js/single_pages/projektraum.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);