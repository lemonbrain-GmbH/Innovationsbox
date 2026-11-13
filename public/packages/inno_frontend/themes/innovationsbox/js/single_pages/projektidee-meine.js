"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/themes/innovationsbox/js/single_pages/projektidee-meine"],{

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

/***/ "./resources/js/single_pages/projektidee-meine.ts"
/*!********************************************************!*\
  !*** ./resources/js/single_pages/projektidee-meine.ts ***!
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
  var target = container !== null && container !== void 0 ? container : document.getElementById('ideas-container');
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
function statusBadge(status) {
  var _a;
  var map = {
    submitted: {
      label: 'Eingereicht',
      cls: 'badge--neutral'
    },
    under_review: {
      label: 'In Prüfung',
      cls: 'badge--info'
    },
    feedback_requested: {
      label: 'Rückmeldung angefordert',
      cls: 'badge--warning'
    },
    approved: {
      label: 'Genehmigt',
      cls: 'badge--success'
    },
    rejected: {
      label: 'Abgelehnt',
      cls: 'badge--error'
    }
  };
  var s = (_a = map[status]) !== null && _a !== void 0 ? _a : {
    label: status,
    cls: 'badge--neutral'
  };
  return "<span class=\"badge ".concat(s.cls, "\">").concat((0, escape_html_1.escapeHtml)(s.label), "</span>");
}
var loadedIdeas = [];
function fetchIdeas() {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee2() {
    var res, json;
    return _regenerator().w(function (_context2) {
      while (1) switch (_context2.n) {
        case 0:
          _context2.n = 1;
          return fetch("".concat(API_BASE, "/mine"));
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
function renderIdeaStatusInfo(idea) {
  if (idea.status === 'rejected' && idea.rejectionReason) {
    return "<div class=\"idea-card__rejection\"><strong>Ablehnungsgrund:</strong> ".concat((0, escape_html_1.escapeHtml)(idea.rejectionReason), "</div>");
  }
  if (idea.status === 'feedback_requested' && idea.feedbackNotes) {
    var deadline = idea.feedbackDeadline ? "<br><strong>Frist:</strong> ".concat(formatDateCH(idea.feedbackDeadline)) : '';
    return "<div class=\"idea-card__feedback\"><strong>R\xFCckmeldung:</strong> ".concat((0, escape_html_1.escapeHtml)(idea.feedbackNotes)).concat(deadline, "</div>");
  }
  if (idea.status === 'approved' && idea.projectSpaceId) {
    return "<a href=\"/projektraum/view/".concat((0, escape_html_1.escapeHtml)(idea.projectSpaceId), "\" class=\"button button--muted button--sm\">Zur Innovation \u2192</a>");
  }
  return '';
}
function renderIdeaActions(idea) {
  if (idea.status !== 'submitted' && idea.status !== 'feedback_requested') return '';
  var editBtn = "<button type=\"button\" class=\"button button--muted button--sm\" data-edit-idea=\"".concat((0, escape_html_1.escapeHtml)(idea.id), "\" data-title=\"").concat((0, escape_html_1.escapeHtml)(idea.title), "\" data-description=\"").concat((0, escape_html_1.escapeHtml)(idea.description), "\" data-category=\"").concat((0, escape_html_1.escapeHtml)(idea.category), "\">Bearbeiten</button>");
  var withdrawBtn = idea.status === 'submitted' ? "<button type=\"button\" class=\"button button--danger button--sm\" data-withdraw-idea=\"".concat((0, escape_html_1.escapeHtml)(idea.id), "\" data-title=\"").concat((0, escape_html_1.escapeHtml)(idea.title), "\">Zur\xFCckziehen</button>") : '';
  return "<div class=\"idea-card__actions\">".concat(editBtn).concat(withdrawBtn, "</div>");
}
function renderIdeaCard(idea) {
  return "\n    <div class=\"idea-card\" data-id=\"".concat((0, escape_html_1.escapeHtml)(idea.id), "\">\n      <div class=\"idea-card__header\">\n        <h3 class=\"idea-card__title\">").concat((0, escape_html_1.escapeHtml)(idea.title), "</h3>\n        <div class=\"idea-card__badges\">\n          ").concat(statusBadge(idea.status), "\n        </div>\n      </div>\n      <div class=\"idea-card__description\">").concat((0, escape_html_1.sanitizeHtml)(idea.description), "</div>\n      <div class=\"idea-card__meta\">\n        <span class=\"idea-card__date\">").concat(formatDateCH(idea.createdAt), "</span>\n        ").concat(idea.attachments.length > 0 ? "<span class=\"idea-card__attachments\">\uD83D\uDCCE ".concat(String(idea.attachments.length), " Datei").concat(idea.attachments.length > 1 ? 'en' : '', "</span>") : '', "\n      </div>\n      ").concat(idea.attachments.length > 0 ? "<div class=\"idea-card__files\">".concat(idea.attachments.map(function (a) {
    return "<a href=\"".concat((0, escape_html_1.escapeHtml)(a.url), "\" class=\"idea-card__file\" target=\"_blank\" rel=\"noopener\">").concat(a.isImage ? "\uD83D\uDDBC" : "\uD83D\uDCC4", " ").concat((0, escape_html_1.escapeHtml)(a.filename), "</a>");
  }).join(''), "</div>") : '', "\n      ").concat(renderIdeaStatusInfo(idea), "\n      ").concat(renderIdeaActions(idea), "\n    </div>");
}
function renderIdeas(ideas) {
  loadedIdeas = ideas;
  var container = document.getElementById('ideas-container');
  if (!container) return;
  if (ideas.length === 0) {
    container.innerHTML = "\n      <div class=\"projektidee-meine-page__empty\">\n        <p>Du hast noch keine Projektideen eingereicht.</p>\n        <a href=\"/projektidee/einreichen\" class=\"button button--accent button--lg\">Idee einreichen</a>\n      </div>";
    return;
  }
  container.innerHTML = ideas.map(renderIdeaCard).join('');
  initEditButtons();
  initWithdrawButtons();
}
var editEditorInstance = null;
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
function buildEditFormData(title, description, category, filesToRemove, newFilesInput, headerFile, removeHeader) {
  var formData = new FormData();
  formData.append('title', title);
  formData.append('description', description);
  formData.append('category', category);
  filesToRemove.forEach(function (attId) {
    formData.append('removeFiles[]', attId);
  });
  var newFiles = newFilesInput === null || newFilesInput === void 0 ? void 0 : newFilesInput.files;
  if (newFiles) {
    for (var i = 0; i < newFiles.length; i++) {
      formData.append('files[]', newFiles[i]);
    }
  }
  if (headerFile) {
    formData.append('headerImage', headerFile);
  }
  if (removeHeader) {
    formData.append('removeHeaderImage', '1');
  }
  return formData;
}
function submitEditIdea(ideaId, titleInput, categorySelect, newFilesInput, filesToRemove, headerFile, removeHeader, modal) {
  var _a;
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee3() {
    var newTitle, newCategory, newDescription, formData, res, msg;
    return _regenerator().w(function (_context3) {
      while (1) switch (_context3.n) {
        case 0:
          newTitle = titleInput.value.trim();
          newCategory = categorySelect.value;
          newDescription = (_a = editEditorInstance === null || editEditorInstance === void 0 ? void 0 : editEditorInstance.getData()) !== null && _a !== void 0 ? _a : '';
          if (!(!newTitle || !newCategory || !newDescription)) {
            _context3.n = 1;
            break;
          }
          return _context3.a(2);
        case 1:
          formData = buildEditFormData(newTitle, newDescription, newCategory, filesToRemove, newFilesInput, headerFile, removeHeader);
          _context3.n = 2;
          return (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(ideaId), {
            method: 'POST',
            body: formData
          });
        case 2:
          res = _context3.v;
          if (!res.ok) {
            _context3.n = 3;
            break;
          }
          window.location.reload();
          _context3.n = 5;
          break;
        case 3:
          _context3.n = 4;
          return extractErrorMessage(res);
        case 4:
          msg = _context3.v;
          showInlineError(msg, modal);
        case 5:
          return _context3.a(2);
      }
    }, _callee3);
  }));
}
function initEditButtons() {
  var modal = document.getElementById('edit-idea-modal');
  var titleInput = document.getElementById('edit-idea-title');
  var categorySelect = document.getElementById('edit-idea-category');
  var submitBtn = document.getElementById('edit-idea-submit');
  var existingFilesContainer = document.getElementById('edit-existing-files');
  var newFilesInput = document.getElementById('edit-new-files');
  if (!modal || !titleInput || !categorySelect || !submitBtn) return;
  modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  var filesToRemove = [];
  var editHeaderImageFile = null;
  var removeHeaderImage = false;
  var editHeaderPreview = document.getElementById('edit-header-image-preview');
  var editHeaderPreviewImg = document.getElementById('edit-header-image-preview-img');
  var editHeaderDropzone = document.getElementById('edit-header-image-dropzone');
  var editHeaderInput = document.getElementById('edit-header-image-input');
  var editHeaderRemoveBtn = document.getElementById('edit-header-image-remove');
  if (editHeaderDropzone && editHeaderInput) {
    editHeaderDropzone.addEventListener('click', function () {
      editHeaderInput.click();
    });
    editHeaderDropzone.addEventListener('dragover', function (e) {
      e.preventDefault();
      editHeaderDropzone.classList.add('header-image-upload__dropzone--active');
    });
    editHeaderDropzone.addEventListener('dragleave', function () {
      editHeaderDropzone.classList.remove('header-image-upload__dropzone--active');
    });
    editHeaderDropzone.addEventListener('drop', function (e) {
      var _a;
      e.preventDefault();
      editHeaderDropzone.classList.remove('header-image-upload__dropzone--active');
      var file = e instanceof DragEvent ? (_a = e.dataTransfer) === null || _a === void 0 ? void 0 : _a.files[0] : undefined;
      if (file) setEditHeaderImage(file);
    });
    editHeaderInput.addEventListener('change', function () {
      var _a;
      var file = (_a = editHeaderInput.files) === null || _a === void 0 ? void 0 : _a[0];
      if (file) setEditHeaderImage(file);
    });
  }
  if (editHeaderRemoveBtn) {
    editHeaderRemoveBtn.addEventListener('click', function () {
      editHeaderImageFile = null;
      removeHeaderImage = true;
      if (editHeaderPreview) editHeaderPreview.hidden = true;
      if (editHeaderDropzone) editHeaderDropzone.hidden = false;
    });
  }
  function setEditHeaderImage(file) {
    var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (allowedTypes.indexOf(file.type) === -1) {
      showInlineError('Titelbild muss JPEG, PNG, GIF oder WebP sein.', modal);
      return;
    }
    if (file.size > 10000000) {
      showInlineError('Titelbild darf maximal 10 MB gross sein.', modal);
      return;
    }
    editHeaderImageFile = file;
    removeHeaderImage = false;
    if (editHeaderPreviewImg && editHeaderPreview && editHeaderDropzone) {
      editHeaderPreviewImg.src = URL.createObjectURL(file);
      editHeaderPreview.hidden = false;
      editHeaderDropzone.hidden = true;
    }
  }
  document.querySelectorAll('[data-edit-idea]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a;
      var ideaId = (_a = btn.dataset['editIdea']) !== null && _a !== void 0 ? _a : '';
      var idea = loadedIdeas.find(function (i) {
        return i.id === ideaId;
      });
      if (!idea) return;
      filesToRemove = [];
      editHeaderImageFile = null;
      removeHeaderImage = false;
      titleInput.value = idea.title;
      categorySelect.value = idea.category;
      if (newFilesInput) newFilesInput.value = '';
      // Show existing header image or dropzone
      if (editHeaderPreview && editHeaderPreviewImg && editHeaderDropzone) {
        if (idea.headerImageUrl) {
          editHeaderPreviewImg.src = idea.headerImageUrl;
          editHeaderPreview.hidden = false;
          editHeaderDropzone.hidden = true;
        } else {
          editHeaderPreviewImg.src = '';
          editHeaderPreview.hidden = true;
          editHeaderDropzone.hidden = false;
        }
      }
      // Render existing attachments with remove buttons
      if (existingFilesContainer) {
        if (idea.attachments.length === 0) {
          existingFilesContainer.innerHTML = '<p style="color: var(--color-text-muted); font-size: var(--font-size-sm, 0.875rem);">Keine Anhänge</p>';
        } else {
          existingFilesContainer.innerHTML = idea.attachments.map(function (a) {
            return "<div class=\"edit-file-item\" data-att-id=\"".concat((0, escape_html_1.escapeHtml)(a.id), "\">\n              <span>").concat(a.isImage ? "\uD83D\uDDBC" : "\uD83D\uDCC4", " ").concat((0, escape_html_1.escapeHtml)(a.filename), "</span>\n              <button type=\"button\" class=\"edit-file-item__remove\" title=\"Entfernen\">\xD7</button>\n            </div>");
          }).join('');
          existingFilesContainer.querySelectorAll('.edit-file-item__remove').forEach(function (removeBtn) {
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
      }
      openDialogWithBackdrop(modal);
      setTimeout(function () {
        var ckeditor = getCKEditor();
        if (ckeditor && !editEditorInstance) {
          editEditorInstance = ckeditor.replace('edit-idea-description', {
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
            height: 150
          });
        }
        if (editEditorInstance) {
          editEditorInstance.setData(idea.description);
        }
      }, 100);
      var freshSubmit = submitBtn.cloneNode(true);
      submitBtn.replaceWith(freshSubmit);
      freshSubmit.addEventListener('click', function () {
        void submitEditIdea(ideaId, titleInput, categorySelect, newFilesInput, filesToRemove, editHeaderImageFile, removeHeaderImage, modal);
      });
    });
  });
}
function initWithdrawButtons() {
  var modal = document.getElementById('withdraw-modal');
  var nameEl = document.getElementById('withdraw-idea-name');
  var confirmBtn = document.getElementById('withdraw-confirm');
  if (!modal || !confirmBtn) return;
  modal.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      modal.close();
    });
  });
  document.querySelectorAll('[data-withdraw-idea]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var _a, _b;
      var ideaId = (_a = btn.dataset['withdrawIdea']) !== null && _a !== void 0 ? _a : '';
      var title = (_b = btn.dataset['title']) !== null && _b !== void 0 ? _b : '';
      if (nameEl) nameEl.textContent = "\xAB".concat(title, "\xBB");
      var freshConfirm = confirmBtn.cloneNode(true);
      confirmBtn.replaceWith(freshConfirm);
      freshConfirm.addEventListener('click', function () {
        void (0, api_client_1.apiFetch)("".concat(API_BASE, "/").concat(ideaId), {
          method: 'DELETE'
        }).then(function (res) {
          if (res.ok) window.location.reload();else void extractErrorMessage(res).then(function (msg) {
            showInlineError(msg);
          });
        });
      });
      modal.showModal();
    });
  });
}
function loadCategories() {
  return __awaiter(this, void 0, void 0, /*#__PURE__*/_regenerator().m(function _callee4() {
    var select, res, json, _t2;
    return _regenerator().w(function (_context4) {
      while (1) switch (_context4.p = _context4.n) {
        case 0:
          select = document.getElementById('edit-idea-category');
          if (select) {
            _context4.n = 1;
            break;
          }
          return _context4.a(2);
        case 1:
          _context4.p = 1;
          _context4.n = 2;
          return fetch(CATEGORY_API);
        case 2:
          res = _context4.v;
          _context4.n = 3;
          return res.json();
        case 3:
          json = _context4.v;
          json.data.forEach(function (cat) {
            var option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            select.appendChild(option);
          });
          _context4.n = 5;
          break;
        case 4:
          _context4.p = 4;
          _t2 = _context4.v;
        case 5:
          return _context4.a(2);
      }
    }, _callee4, null, [[1, 4]]);
  }));
}
document.addEventListener('DOMContentLoaded', function () {
  if (!document.getElementById('ideas-container')) return;
  void loadCategories();
  void fetchIdeas().then(renderIdeas)["catch"](function () {
    showInlineError('Fehler beim Laden der Ideen.');
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
/******/ __webpack_require__.O(0, ["/themes/innovationsbox/js/vendor"], () => (__webpack_exec__("./resources/js/single_pages/projektidee-meine.ts")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);