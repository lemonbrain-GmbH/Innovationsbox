# Security Known Issues

This document tracks npm and Composer security advisories that cannot be remediated immediately due to upstream dependency constraints. Each entry includes a risk assessment and the conditions required to re-evaluate.

**Last reviewed:** 2026-06-02
**Next scheduled review:** 2026-09-01 (quarterly)

---

## npm Vulnerabilities

All advisories below are triaged as acceptable risk because they are:
- locked behind upstream packages (`@concretecms/bedrock`, `laravel-mix`) that cannot be upgraded in isolation, or
- confined to dev/build-time tooling with no production runtime exposure.

| Advisory ID | Package | Severity (npm) | Root Cause / Chain | Risk Assessment | Mitigation / Remediation path |
|---|---|---|---|---|---|
| GHSA-vh95-rmgr-6w4m | `minimist` | Critical | `@concretecms/bedrock` → `selectize` → `sifter` → `optimist` → `minimist <=0.2.3` | **Build-time only.** `optimist`/`minimist` is called by `sifter` during asset compilation, never in a browser or server HTTP context. Prototype Pollution requires attacker-controlled input to `minimist`; build input is developer-controlled source files. | Upgrade blocked by bedrock pinning selectize. Revisit when `@concretecms/bedrock` ships a selectize update or is replaced. |
| GHSA-xvch-5gv4-984h | `minimist` | Critical | Same chain as above | Same as above | Same as above |
| GHSA-q58r-hwc8-rm9j | `bootstrap` | Moderate | `@concretecms/bedrock` → `pnotify` → `@pnotify/bootstrap3` → `bootstrap 3.x` | **Not exploitable in custom code.** Bootstrap 3 Popover/Tooltip JS is not called anywhere in `inno_frontend`. Our Tooltip atom (`components/atoms/tooltip/`) is a custom CSS-only implementation with no Bootstrap JS dependency. pnotify uses Bootstrap 3 for its own notifications, which are CMS-controlled and rendered in the admin panel only. | Upgrade blocked by `pnotify@5` pinning bootstrap 3. Track `@concretecms/bedrock` for pnotify upgrade. |
| GHSA-vxmc-5x29-h64v | `bootstrap` | Moderate | Same chain as above (data-* attribute XSS) | Same as above. No `data-toggle`, `data-content`, or `data-original-title` attributes are used in custom code. | Same as above |
| GHSA-wh5w-82f3-wrxh | `ckeditor4` | Moderate | `@concretecms/bedrock` → `ckeditor4` (XSS in AJAX sample) | **CMS upstream, not in custom code.** CKEditor4 is the CMS's rich-text editor. The vulnerable AJAX sample endpoint is part of CKEditor's demo files, which are not deployed in a production Concrete CMS instance. Custom packages (`inno_*`) do not import or instantiate CKEditor. | Track ConcreteCMS upstream for CKEditor5 migration. No action in custom packages required. |
| GHSA-mw2c-vx6j-mg76 | `ckeditor4` | Moderate | `@concretecms/bedrock` → `ckeditor4` (XSS in preview feature) | **CMS upstream.** Preview feature XSS requires CKEditor preview to be enabled; default CMS config disables it. Custom packages do not configure CKEditor. | Same as above |
| GHSA-fq6h-4g8v-qqvm | `ckeditor4` | Moderate | `@concretecms/bedrock` → `ckeditor4` (CDATA detection XSS) | **CMS upstream.** Exploitable only within the CMS editor context, accessible to authenticated editors/admins only. | Same as above |
| GHSA-6v96-m24v-f58j | `ckeditor4` | Moderate | `@concretecms/bedrock` → `ckeditor4` (domain takeover linked XSS) | **CMS upstream, low risk.** Requires attacker control of a CDN sub-domain. ConcreteCMS assesses this as low risk for self-hosted instances. | Same as above |
| GHSA-7r32-vfj5-c2jv | `ckeditor4` | Moderate | `@concretecms/bedrock` → `ckeditor4` (GeSHi Code Snippet plugin XSS) | **CMS upstream.** Code Snippet plugin is only active if explicitly enabled by a CMS admin. Not enabled in default project configuration. | Same as above |
| GHSA-848j-6mx2-7j84 | `elliptic` | Low | `laravel-mix` → `node-libs-browser` → `crypto-browserify` → `elliptic` | **Build-tool only, no fix available.** `elliptic` is used by `crypto-browserify` to polyfill Node crypto for webpack bundling. It runs only during `npm run production`/`development` builds, never in a browser or server HTTP path. The weak cryptographic primitive concern applies to signing operations; no signing is performed in our build pipeline. | No upstream fix available. Monitor for `laravel-mix` v7 or alternative bundler migration (Vite). |
| GHSA-4v9v-hfq4-rm2v | `webpack-dev-server` | Moderate | `laravel-mix` → `webpack-dev-server` (source code theft via malicious website) | **Dev-time only.** webpack-dev-server is never started in CI or production (`npm run production` is used). The attack requires a developer to visit a malicious site while `npm run watch` is running. Mitigated by: (1) dev server bound to localhost, (2) firewall rules on dev machines, (3) CI does not run dev-server. | Blocked by `laravel-mix@6` pinning an older `webpack-dev-server`. Revisit on laravel-mix upgrade or Vite migration. |
| GHSA-9jgg-88mc-972h | `webpack-dev-server` | Moderate | Same chain (non-Chromium browser variant) | Same as above | Same as above |
| GHSA-79cf-xcqc-c78w | `webpack-dev-server` | Moderate | Same chain (cross-origin source exposure on non-HTTPS origins) | **Dev-time only.** Additionally mitigated by developers using HTTPS-capable local proxy (`BrowserSync` with `http://project.local`). Not exploitable on production (dev-server not started). | Same as above |
| GHSA-w5hq-g745-h8pq | `uuid` | Moderate | (a) `@storybook/addon-actions` → `uuid <11.1.1`; (b) `webpack-dev-server` → `sockjs` → `uuid` | **Dev/build-time only, no fix available.** Buffer bounds check issue in `uuid` v3/v5/v6 when a caller provides a `buf` argument. Storybook and webpack-dev-server are never part of the production runtime. Neither path is reachable from the internet. | Storybook path: awaiting `@storybook/addon-actions` upgrade in a future Storybook major. webpack path: same as webpack-dev-server entries above. |
| GHSA-5j4c-8p2g-v4jx | `vue` | Low | `@concretecms/bedrock` → `vue 2.x` (ReDoS in `parseHTML`) | **CMS upstream, authenticated context only.** Vue 2 is used by Concrete CMS's admin interface. The `parseHTML` function is called on admin-provided HTML. ReDoS requires a specially crafted string to cause backtracking; the CMS admin panel is only accessible to authenticated users with editor role or higher. Vue 2 is EOL. Custom `inno_frontend` code does not import Vue. | Track `@concretecms/bedrock` for Vue 3 migration. No action in custom packages required. |

---

## Composer / PHP Vulnerabilities

The following Symfony and Twig advisories affect transitive dependencies pulled in by `concrete5/core`. They cannot be updated independently of the CMS core.

| CVE | Package | Severity | Title | Risk Assessment | Mitigation / Remediation path |
|---|---|---|---|---|---|
| CVE-2026-45067 | `symfony/mime` | High | Email Header / SMTP Command Injection via CRLF in `Symfony\Component\Mime\Address` | **CMS upstream.** `symfony/mime` is used by Concrete CMS's mailer. Exploitation requires attacker-controlled email addresses to be passed unsanitised to `Address`. The notification package (`inno_notification`) constructs `Address` objects from CMS user records, which are validated on registration. Any CRLF in an email address would be rejected by Concrete CMS's own input validation before reaching symfony/mime. | Upgrade blocked by Concrete CMS version lock. Track ConcreteCMS 9.x releases for Symfony update. |
| CVE-2026-45070 | `symfony/mime` | Medium | Email Header Injection via Non-Token Characters in Mime Parameter Names | **CMS upstream.** Same context as above. Parameter names in MIME parts are set programmatically by the CMS, not from user input in the custom packages. | Same as above |
| CVE-2026-48784 | `symfony/routing` | Medium | UrlGenerator Dot-Segment Encoding generates off-route URLs | **CMS upstream.** Affects URL generation for chained `../` segments. The `inno_*` packages use Concrete CMS's routing via `$this->app->make('url')` — they do not construct URLs with raw dot-segments from user input. Impact limited to edge-case URL generation in CMS admin. | Track ConcreteCMS core update. |
| CVE-2024-50342 | `symfony/http-client` | Low | `NoPrivateNetworkHttpClient` allows internal address/port enumeration | **CMS upstream, low risk.** `symfony/http-client` is a CMS dependency; the `NoPrivateNetworkHttpClient` guard is only relevant if the CMS is configured to proxy HTTP requests to user-supplied URLs. Default project configuration does not enable such proxying. | Track ConcreteCMS core update. |
| CVE-2026-45304 | `symfony/yaml` | Low | YAML Parser exponential memory allocation via recursive anchors | **CMS upstream.** YAML parsing in Concrete CMS is used for config files, not for user-supplied input in the custom packages. | Track ConcreteCMS core update. |
| CVE-2026-45305 | `symfony/yaml` | Low | YAML Parser ReDoS via catastrophic backtracking | **CMS upstream.** Same context as above. | Same as above |
| CVE-2026-45133 | `symfony/yaml` | Low | YAML Parser stack exhaustion via unbounded recursion | **CMS upstream.** Same context as above. | Same as above |
| CVE-2026-48808 | `twig/twig` | Medium | Sandbox property allowlist bypass via `column` filter | **CMS upstream.** Twig sandbox is used by Concrete CMS for theme/block rendering. The `column` filter bypass requires a template author with sandbox access to craft a malicious template. Block templates in `inno_frontend` are non-sandboxed PHP partials; they do not use Twig. | Track `twig/twig` ^3.27 release and ConcreteCMS update. |
| CVE-2026-48805 | `twig/twig` | Medium | Sandbox state regression in deprecated internal wrappers | **CMS upstream.** Same context as above. | Same as above |
| CVE-2026-46636 | `twig/twig` | Medium | Sandbox filter/tag/function allowlist bypass on state change between renders | **CMS upstream.** Same context as above. | Same as above |
| CVE-2026-48806 | `twig/twig` | Medium | Sandbox `__toString()` bypass via dynamic mapping keys | **CMS upstream.** Same context as above. | Same as above |
| CVE-2026-48807 | `twig/twig` | Medium | Sandbox `__toString()` bypass via `Traversable` in operators | **CMS upstream.** Same context as above. | Same as above |

---

## Review Schedule

| Trigger | Action |
|---|---|
| `@concretecms/bedrock` release | Re-run `npm audit`; remove triaged entries that are now fixed |
| `laravel-mix` upgrade or Vite migration | Re-evaluate all `webpack-dev-server` and `elliptic` entries |
| ConcreteCMS 9.x patch release | Re-run `composer audit`; check Symfony and Twig versions |
| Quarterly (next: 2026-09-01) | Full re-triage of this document |
| Any new `critical` advisory in `npm audit` | Immediate review; do not add to `.nsprc` without written justification |
