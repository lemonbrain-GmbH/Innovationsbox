"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/themes/innovationsbox/js/single_pages/projektidee-einreichen"],{

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

/***/ "./resources/js/single_pages/projektidee-einreichen.ts"
/*!*************************************************************!*\
  !*** ./resources/js/single_pages/projektidee-einreichen.ts ***!
  \*************************************************************/
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
var API_BASE = '/api/project-idea';
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
  var target = container !== null && container !== void 0 ? container : document.getElementById('form-error-container');
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
var editorInstance = null;
var selectedFiles = [];
var MAX_FILES = 5;
var MAX_FILE_SIZE = 10000000;
var headerImageFile = null;
function initCKEditor() {
  var ckeditor = getCKEditor();
  if (!ckeditor || editorInstance) return;
  editorInstance = ckeditor.replace('idea-description', {
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
function loadCategories() {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee2() {
    var select, res, json, _t2;
    return _regenerator().w(function (_context2) {
      while (1) switch (_context2.p = _context2.n) {
        case 0:
          select = document.getElementById('idea-category');
          if (select) {
            _context2.n = 1;
            break;
          }
          return _context2.a(2);
        case 1:
          _context2.p = 1;
          _context2.n = 2;
          return fetch(CATEGORY_API);
        case 2:
          res = _context2.v;
          _context2.n = 3;
          return res.json();
        case 3:
          json = _context2.v;
          json.data.forEach(function (cat) {
            var option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            select.appendChild(option);
          });
          _context2.n = 5;
          break;
        case 4:
          _context2.p = 4;
          _t2 = _context2.v;
        case 5:
          return _context2.a(2);
      }
    }, _callee2, null, [[1, 4]]);
  }));
}
function initFileUpload() {
  var zone = document.getElementById('file-upload-zone');
  var input = document.getElementById('idea-files');
  var list = document.getElementById('file-list');
  if (!zone || !input || !list) return;
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
      addFiles(Array.from(e.dataTransfer.files));
    }
  });
  input.addEventListener('change', function () {
    if (input.files) {
      addFiles(Array.from(input.files));
    }
    input.value = '';
  });
}
function addFiles(files) {
  var _iterator = _createForOfIteratorHelper(files),
    _step;
  try {
    for (_iterator.s(); !(_step = _iterator.n()).done;) {
      var file = _step.value;
      if (selectedFiles.length >= MAX_FILES) {
        showInlineError("Maximal ".concat(String(MAX_FILES), " Dateien erlaubt."));
        break;
      }
      if (file.size > MAX_FILE_SIZE) {
        showInlineError("Die Datei \xAB".concat(file.name, "\xBB ist zu gross (max. 10 MB)."));
        continue;
      }
      selectedFiles.push(file);
    }
  } catch (err) {
    _iterator.e(err);
  } finally {
    _iterator.f();
  }
  renderFileList();
}
function renderFileList() {
  var list = document.getElementById('file-list');
  if (!list) return;
  if (selectedFiles.length === 0) {
    list.innerHTML = '';
    return;
  }
  list.innerHTML = selectedFiles.map(function (file, idx) {
    return "\n    <div class=\"file-upload-zone__item\">\n      <span class=\"file-upload-zone__name\">".concat((0, escape_html_1.escapeHtml)(file.name), "</span>\n      <span class=\"file-upload-zone__size\">").concat((file.size / 1000000).toFixed(1), " MB</span>\n      <button type=\"button\" class=\"file-upload-zone__remove\" data-idx=\"").concat(String(idx), "\" title=\"Entfernen\">\xD7</button>\n    </div>");
  }).join('');
  list.querySelectorAll('.file-upload-zone__remove').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a;
      var idx = parseInt((_a = btn.dataset['idx']) !== null && _a !== void 0 ? _a : '0', 10);
      selectedFiles.splice(idx, 1);
      renderFileList();
    });
  });
}
function validateHeaderImage(file) {
  var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (allowedTypes.indexOf(file.type) === -1) {
    return Promise.resolve('Nur JPEG, PNG, GIF oder WebP erlaubt.');
  }
  if (file.size > 10 * 1024 * 1024) {
    return Promise.resolve('Maximale Dateigrösse: 10 MB.');
  }
  return new Promise(function (resolve) {
    var img = new Image();
    img.onload = function () {
      resolve(img.naturalWidth < 800 ? 'Mindestbreite: 800 Pixel.' : null);
      URL.revokeObjectURL(img.src);
    };
    img.onerror = function () {
      resolve('Bild konnte nicht gelesen werden.');
    };
    img.src = URL.createObjectURL(file);
  });
}
function showHeaderImagePreview(file) {
  var preview = document.getElementById('header-image-preview');
  var previewImg = document.getElementById('header-image-preview-img');
  var dropzone = document.getElementById('header-image-dropzone');
  if (!preview || !previewImg || !dropzone) return;
  previewImg.src = URL.createObjectURL(file);
  preview.hidden = false;
  dropzone.hidden = true;
}
function clearHeaderImagePreview() {
  var preview = document.getElementById('header-image-preview');
  var previewImg = document.getElementById('header-image-preview-img');
  var dropzone = document.getElementById('header-image-dropzone');
  if (!preview || !previewImg || !dropzone) return;
  if (previewImg.src.startsWith('blob:')) {
    URL.revokeObjectURL(previewImg.src);
  }
  previewImg.src = '';
  preview.hidden = true;
  dropzone.hidden = false;
  headerImageFile = null;
}
function handleHeaderImageFile(file) {
  void validateHeaderImage(file).then(function (error) {
    if (error) {
      showInlineError(error);
      return;
    }
    headerImageFile = file;
    showHeaderImagePreview(file);
  });
}
function initHeaderImageUpload() {
  var dropzone = document.getElementById('header-image-dropzone');
  var input = document.getElementById('header-image-input');
  var removeBtn = document.getElementById('header-image-remove');
  if (!dropzone || !input || !removeBtn) return;
  dropzone.addEventListener('click', function () {
    input.click();
  });
  dropzone.addEventListener('dragover', function (e) {
    e.preventDefault();
    dropzone.classList.add('header-image-upload__dropzone--active');
  });
  dropzone.addEventListener('dragleave', function () {
    dropzone.classList.remove('header-image-upload__dropzone--active');
  });
  dropzone.addEventListener('drop', function (e) {
    var _a;
    e.preventDefault();
    dropzone.classList.remove('header-image-upload__dropzone--active');
    var file = e instanceof DragEvent ? (_a = e.dataTransfer) === null || _a === void 0 ? void 0 : _a.files[0] : undefined;
    if (file) {
      handleHeaderImageFile(file);
    }
  });
  input.addEventListener('change', function () {
    var _a;
    var file = (_a = input.files) === null || _a === void 0 ? void 0 : _a[0];
    if (file) {
      handleHeaderImageFile(file);
    }
    input.value = '';
  });
  removeBtn.addEventListener('click', function () {
    clearHeaderImagePreview();
  });
}
function handleResponse(res, submitBtn) {
  if (res.ok) {
    window.location.href = '/projektidee/meine';
  } else {
    void extractErrorMessage(res).then(function (msg) {
      showInlineError(msg);
    });
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Idee einreichen';
    }
  }
}
function submitIdea(title, description, category, submitBtn) {
  if (selectedFiles.length > 0 || headerImageFile !== null) {
    var formData = new FormData();
    formData.append('title', title);
    formData.append('description', description);
    formData.append('category', category);
    selectedFiles.forEach(function (file) {
      formData.append('files[]', file);
    });
    if (headerImageFile !== null) {
      formData.append('headerImage', headerImageFile);
    }
    void (0, api_client_1.apiFetch)(API_BASE, {
      method: 'POST',
      body: formData
    }).then(function (res) {
      handleResponse(res, submitBtn);
    });
  } else {
    void (0, api_client_1.apiFetch)(API_BASE, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        title: title,
        description: description,
        category: category
      })
    }).then(function (res) {
      handleResponse(res, submitBtn);
    });
  }
}
function initForm() {
  var form = document.getElementById('idea-form');
  if (!form) return;
  form.addEventListener('submit', function (e) {
    var _a;
    e.preventDefault();
    var titleInput = document.getElementById('idea-title');
    var categorySelect = document.getElementById('idea-category');
    var title = titleInput.value.trim();
    var category = categorySelect.value;
    var description = (_a = editorInstance === null || editorInstance === void 0 ? void 0 : editorInstance.getData()) !== null && _a !== void 0 ? _a : '';
    if (!title || !category || !description) {
      showInlineError("Bitte f\xFClle alle Pflichtfelder aus.");
      return;
    }
    var submitBtn = document.getElementById('idea-submit');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Wird eingereicht...';
    }
    submitIdea(title, description, category, submitBtn);
  });
}
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('idea-form')) return;
  void loadCategories();
  initCKEditor();
  initHeaderImageUpload();
  initFileUpload();
  initForm();
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
/******/ __webpack_require__.O(0, ["/themes/innovationsbox/js/vendor"], () => (__webpack_exec__("./resources/js/single_pages/projektidee-einreichen.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);