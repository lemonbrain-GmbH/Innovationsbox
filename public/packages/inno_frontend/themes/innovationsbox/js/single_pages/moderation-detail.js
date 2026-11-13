"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/themes/innovationsbox/js/single_pages/moderation-detail"],{

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

/***/ "./resources/js/single_pages/moderation-detail.ts"
/*!********************************************************!*\
  !*** ./resources/js/single_pages/moderation-detail.ts ***!
  \********************************************************/
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
var API_BASE = '/api/moderation';
var CATEGORY_API = '/api/moderation/category';
var ERROR_ICON = '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>';
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
function formatDateCH(isoDate) {
  try {
    return new Date(isoDate).toLocaleDateString('de-CH', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  } catch (_a) {
    return isoDate;
  }
}
function formatFileSize(bytes) {
  if (bytes < 1000) return "".concat(String(bytes), " B");
  if (bytes < 1000000) return "".concat((bytes / 1000).toFixed(1), " KB");
  return "".concat((bytes / 1000000).toFixed(1), " MB");
}
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
  var target = container !== null && container !== void 0 ? container : document.getElementById('review-container');
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
  alert.scrollIntoView({
    behavior: 'smooth',
    block: 'nearest'
  });
}
function getReviewId() {
  var _a;
  var params = new URLSearchParams(window.location.search);
  return (_a = params.get('id')) !== null && _a !== void 0 ? _a : '';
}
function fetchReview(id) {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee2() {
    var res, json;
    return _regenerator().w(function (_context2) {
      while (1) switch (_context2.n) {
        case 0:
          _context2.n = 1;
          return fetch("".concat(API_BASE, "/").concat(encodeURIComponent(id)));
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
var approveEditorInstance = null;
function loadCategories(select, current) {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee3() {
    var res, json, _t2;
    return _regenerator().w(function (_context3) {
      while (1) switch (_context3.p = _context3.n) {
        case 0:
          _context3.p = 0;
          _context3.n = 1;
          return fetch(CATEGORY_API);
        case 1:
          res = _context3.v;
          _context3.n = 2;
          return res.json();
        case 2:
          json = _context3.v;
          json.data.forEach(function (cat) {
            var option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            if (cat.id === current) option.selected = true;
            select.appendChild(option);
          });
          _context3.n = 4;
          break;
        case 3:
          _context3.p = 3;
          _t2 = _context3.v;
        case 4:
          return _context3.a(2);
      }
    }, _callee3, null, [[0, 3]]);
  }));
}
function getInputValue(id) {
  var _a;
  var el = document.getElementById(id);
  return (_a = el === null || el === void 0 ? void 0 : el.value.trim()) !== null && _a !== void 0 ? _a : '';
}
function addIfChanged(body, key, value, original) {
  if (value && value !== original) {
    body[key] = value;
  }
}
function buildApproveBody(idea) {
  var _a;
  var description = (_a = approveEditorInstance === null || approveEditorInstance === void 0 ? void 0 : approveEditorInstance.getData()) !== null && _a !== void 0 ? _a : '';
  var body = {};
  addIfChanged(body, 'title', getInputValue('approve-title'), idea.title);
  addIfChanged(body, 'description', description, idea.description);
  addIfChanged(body, 'category', getInputValue('approve-category'), idea.category);
  return body;
}
function handleApprove(review, idea) {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee4() {
    var body, res, msg;
    return _regenerator().w(function (_context4) {
      while (1) switch (_context4.n) {
        case 0:
          body = buildApproveBody(idea);
          _context4.n = 1;
          return (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(encodeURIComponent(review.id), "/approve"), {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
          });
        case 1:
          res = _context4.v;
          if (!res.ok) {
            _context4.n = 2;
            break;
          }
          window.location.href = '/moderation';
          _context4.n = 4;
          break;
        case 2:
          _context4.n = 3;
          return extractErrorMessage(res);
        case 3:
          msg = _context4.v;
          showInlineError(msg);
        case 4:
          return _context4.a(2);
      }
    }, _callee4);
  }));
}
function handleFeedbackRequest(review) {
  var _a, _b;
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee5() {
    var notesEl, deadlineEl, notes, body, deadline, res, msg;
    return _regenerator().w(function (_context5) {
      while (1) switch (_context5.n) {
        case 0:
          notesEl = document.getElementById('feedback-notes');
          deadlineEl = document.getElementById('feedback-deadline');
          notes = (_a = notesEl === null || notesEl === void 0 ? void 0 : notesEl.value.trim()) !== null && _a !== void 0 ? _a : '';
          if (notes) {
            _context5.n = 1;
            break;
          }
          showInlineError('Bitte gib eine Rückmeldung ein.');
          return _context5.a(2);
        case 1:
          body = {
            notes: notes
          };
          deadline = (_b = deadlineEl === null || deadlineEl === void 0 ? void 0 : deadlineEl.value.trim()) !== null && _b !== void 0 ? _b : '';
          if (deadline) {
            body['deadline'] = deadline;
          }
          _context5.n = 2;
          return (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(encodeURIComponent(review.id), "/feedback"), {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
          });
        case 2:
          res = _context5.v;
          if (!res.ok) {
            _context5.n = 3;
            break;
          }
          window.location.href = '/moderation';
          _context5.n = 5;
          break;
        case 3:
          _context5.n = 4;
          return extractErrorMessage(res);
        case 4:
          msg = _context5.v;
          showInlineError(msg);
        case 5:
          return _context5.a(2);
      }
    }, _callee5);
  }));
}
function handleReject(review, rejectModal) {
  var _a;
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee6() {
    var reasonEl, reason, res, msg;
    return _regenerator().w(function (_context6) {
      while (1) switch (_context6.n) {
        case 0:
          reasonEl = document.getElementById('reject-reason');
          reason = (_a = reasonEl === null || reasonEl === void 0 ? void 0 : reasonEl.value.trim()) !== null && _a !== void 0 ? _a : '';
          if (reason) {
            _context6.n = 1;
            break;
          }
          showInlineError('Bitte gib einen Ablehnungsgrund an.', rejectModal);
          return _context6.a(2);
        case 1:
          _context6.n = 2;
          return (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(encodeURIComponent(review.id), "/reject"), {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              notes: reason
            })
          });
        case 2:
          res = _context6.v;
          if (!res.ok) {
            _context6.n = 3;
            break;
          }
          window.location.href = '/moderation';
          _context6.n = 5;
          break;
        case 3:
          _context6.n = 4;
          return extractErrorMessage(res);
        case 4:
          msg = _context6.v;
          showInlineError(msg, rejectModal);
        case 5:
          return _context6.a(2);
      }
    }, _callee6);
  }));
}
function initFileManagement(review, idea) {
  var _a;
  var filesToRemove = [];
  document.querySelectorAll('.moderation-detail-page__attachment-remove').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      var _a;
      e.preventDefault();
      var row = btn.closest('.moderation-detail-page__attachment-row');
      var attId = (_a = row.dataset['attId']) !== null && _a !== void 0 ? _a : '';
      filesToRemove.push(attId);
      row.style.opacity = '0.3';
      row.style.textDecoration = 'line-through';
      btn.style.display = 'none';
    });
  });
  (_a = document.getElementById('btn-save-files')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
    var newFilesInput = document.getElementById('mod-new-files');
    if (filesToRemove.length === 0 && (!(newFilesInput === null || newFilesInput === void 0 ? void 0 : newFilesInput.files) || newFilesInput.files.length === 0)) {
      return;
    }
    var formData = new FormData();
    formData.append('title', idea.title);
    formData.append('description', idea.description);
    formData.append('category', idea.category);
    filesToRemove.forEach(function (attId) {
      formData.append('removeFiles[]', attId);
    });
    if (newFilesInput === null || newFilesInput === void 0 ? void 0 : newFilesInput.files) {
      for (var i = 0; i < newFilesInput.files.length; i++) {
        formData.append('files[]', newFilesInput.files[i]);
      }
    }
    void (0, api_client_1.apiFetch)("/api/project-idea/".concat(encodeURIComponent(review.ideaId)), {
      method: 'POST',
      body: formData
    }).then(function (res) {
      if (res.ok) {
        window.location.reload();
      } else {
        void extractErrorMessage(res).then(function (msg) {
          showInlineError(msg);
        });
      }
    });
  });
}
function renderReview(review) {
  var _a, _b, _c, _d;
  var container = document.getElementById('review-container');
  if (!container) return;
  var idea = review.idea;
  if (!idea) {
    container.innerHTML = '<p>Idee nicht gefunden.</p>';
    return;
  }
  var attachmentsHtml = "<div class=\"moderation-detail-page__attachments\">\n      <h3>Anh\xE4nge</h3>\n      <div class=\"moderation-detail-page__attachment-list\" id=\"mod-attachment-list\">\n        ".concat(idea.attachments.length > 0 ? idea.attachments.map(function (a) {
    return "<div class=\"moderation-detail-page__attachment-row\" data-att-id=\"".concat((0, escape_html_1.escapeHtml)(a.id), "\">\n                <a href=\"").concat((0, escape_html_1.escapeHtml)(a.url), "\" class=\"moderation-detail-page__attachment\" target=\"_blank\" rel=\"noopener\">\n                  <span>").concat(a.isImage ? "\uD83D\uDDBC" : "\uD83D\uDCC4", " ").concat((0, escape_html_1.escapeHtml)(a.filename), "</span>\n                  <span class=\"moderation-detail-page__attachment-size\">").concat(formatFileSize(a.size), "</span>\n                </a>\n                <button type=\"button\" class=\"moderation-detail-page__attachment-remove\" title=\"Entfernen\">\xD7</button>\n              </div>");
  }).join('') : '<p style="color: var(--color-text-muted);">Keine Anhänge</p>', "\n      </div>\n      <div class=\"form-field\" style=\"margin-top: var(--space-3);\">\n        <label class=\"label\" for=\"mod-new-files\">Neue Anh\xE4nge hinzuf\xFCgen</label>\n        <input type=\"file\" id=\"mod-new-files\" multiple accept=\"image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text\">\n      </div>\n      <button type=\"button\" class=\"button button--muted button--sm\" id=\"btn-save-files\" style=\"margin-top: var(--space-2);\">Anh\xE4nge speichern</button>\n    </div>");
  container.innerHTML = "\n    <section class=\"moderation-detail-page__idea\">\n      <h2>".concat((0, escape_html_1.escapeHtml)(idea.title), "</h2>\n      <div class=\"moderation-detail-page__meta\">\n        <span>Eingereicht von: <strong>").concat((0, escape_html_1.escapeHtml)(idea.submitterName), "</strong></span>\n        <span>Datum: ").concat(formatDateCH(idea.createdAt), "</span>\n      </div>\n      <div class=\"moderation-detail-page__description\">").concat((0, escape_html_1.sanitizeHtml)(idea.description), "</div>\n      ").concat(attachmentsHtml, "\n    </section>\n\n    <section class=\"moderation-detail-page__decision\">\n      <h3>Entscheidung</h3>\n\n      <div class=\"moderation-detail-page__approve-form\">\n        <h4>Genehmigen</h4>\n        <p style=\"color: var(--color-text-muted); font-size: var(--font-size-sm, 0.875rem);\">Du kannst Titel, Beschreibung und Kategorie vor der Genehmigung anpassen.</p>\n        <div class=\"form-field\" style=\"margin-bottom: var(--space-3);\">\n          <label class=\"label\" for=\"approve-title\">Titel</label>\n          <input type=\"text\" id=\"approve-title\" class=\"input\" value=\"").concat((0, escape_html_1.escapeHtml)(idea.title), "\">\n        </div>\n        <div class=\"form-field\" style=\"margin-bottom: var(--space-3);\">\n          <label class=\"label\" for=\"approve-category\">Kategorie</label>\n          <select id=\"approve-category\" class=\"input\"></select>\n        </div>\n        <div class=\"form-field\" style=\"margin-bottom: var(--space-3);\">\n          <label class=\"label\" for=\"approve-description\">Beschreibung</label>\n          <textarea id=\"approve-description\" class=\"input\" rows=\"6\">").concat((0, escape_html_1.escapeHtml)(idea.description), "</textarea>\n        </div>\n        <button type=\"button\" class=\"button button--accent button--lg\" id=\"btn-approve\">Genehmigen</button>\n      </div>\n\n      <hr style=\"margin: var(--space-6) 0;\">\n\n      <div class=\"moderation-detail-page__feedback-form\">\n        <h4>R\xFCckmeldung anfordern</h4>\n        <p style=\"color: var(--color-text-muted); font-size: var(--font-size-sm, 0.875rem);\">Fordere \xC4nderungen oder weitere Informationen vom Einreicher an.</p>\n        <div class=\"form-field\" style=\"margin-bottom: var(--space-3);\">\n          <label class=\"label\" for=\"feedback-notes\">R\xFCckmeldung (erforderlich)</label>\n          <textarea id=\"feedback-notes\" class=\"input\" rows=\"3\" placeholder=\"Was soll ge\xE4ndert oder erg\xE4nzt werden?\"></textarea>\n        </div>\n        <div class=\"form-field\" style=\"margin-bottom: var(--space-3);\">\n          <label class=\"label\" for=\"feedback-deadline\">Frist (optional)</label>\n          <input type=\"date\" id=\"feedback-deadline\" class=\"input\">\n        </div>\n        <button type=\"button\" class=\"button button--warning button--lg\" id=\"btn-feedback\">R\xFCckmeldung senden</button>\n      </div>\n\n      <hr style=\"margin: var(--space-6) 0;\">\n\n      <div class=\"moderation-detail-page__reject-form\">\n        <h4>Ablehnen</h4>\n        <button type=\"button\" class=\"button button--danger button--lg\" id=\"btn-reject\">Ablehnen...</button>\n      </div>\n    </section>\n  ");
  // Load categories into dropdown
  var catSelect = document.getElementById('approve-category');
  if (catSelect) void loadCategories(catSelect, idea.category);
  // Init CKEditor for description
  setTimeout(function () {
    var ckeditor = getCKEditor();
    if (ckeditor && !approveEditorInstance) {
      approveEditorInstance = ckeditor.replace('approve-description', {
        toolbar: [{
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'Underline']
        }, {
          name: 'paragraph',
          items: ['NumberedList', 'BulletedList']
        }, {
          name: 'links',
          items: ['Link', 'Unlink']
        }],
        contentsCss: getCkeContentsCss(),
        removePlugins: 'elementspath',
        resize_enabled: false,
        versionCheck: false,
        height: 200
      });
    }
  }, 100);
  // Approve handler
  (_a = document.getElementById('btn-approve')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
    void handleApprove(review, idea);
  });
  // Feedback request handler
  (_b = document.getElementById('btn-feedback')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', function () {
    void handleFeedbackRequest(review);
  });
  // Reject handler — opens modal
  var rejectModal = document.getElementById('reject-modal');
  (_c = document.getElementById('btn-reject')) === null || _c === void 0 ? void 0 : _c.addEventListener('click', function () {
    rejectModal === null || rejectModal === void 0 ? void 0 : rejectModal.showModal();
  });
  rejectModal === null || rejectModal === void 0 ? void 0 : rejectModal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      rejectModal.close();
    });
  });
  (_d = document.getElementById('reject-confirm')) === null || _d === void 0 ? void 0 : _d.addEventListener('click', function () {
    void handleReject(review, rejectModal);
  });
  initFileManagement(review, idea);
}
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('review-container')) return;
  var reviewId = getReviewId();
  if (!reviewId) {
    var container = document.getElementById('review-container');
    if (container) container.innerHTML = '<p>Kein Review ausgewählt.</p>';
    return;
  }
  void fetchReview(reviewId).then(renderReview)["catch"](function () {
    showInlineError('Fehler beim Laden des Reviews.');
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
/******/ __webpack_require__.O(0, ["/themes/innovationsbox/js/vendor"], () => (__webpack_exec__("./resources/js/single_pages/moderation-detail.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);