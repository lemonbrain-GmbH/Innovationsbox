# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| main    | Yes       |
| staging | Yes       |
| feature branches | Development only |

## Reporting a Vulnerability

If you discover a security vulnerability in the Innovationsbox project, please report it responsibly.

**Do NOT open a public issue.** Instead:

1. Email: **security@lemonbrain.ch**
2. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

## Response Timeline

- **Acknowledgment:** Within 2 business days
- **Assessment:** Within 5 business days
- **Fix/Mitigation:** Depends on severity
  - Critical: within 24 hours
  - High: within 1 week
  - Medium/Low: next release cycle

## Security Measures

### Automated security checks (CI pipeline)

- **Dependency scanning:** `npm audit` and `composer audit` run on every PR; high/critical CVEs fail the build
- **Static analysis (SAST):** Semgrep scans PHP and TypeScript for vulnerability patterns (`npm run audit:sast`)
- **Secret detection:** `detect-secrets` pre-commit hook prevents accidental credential commits
- **License compliance:** `npm run license:check` rejects GPL-3.0/AGPL-3.0 dependencies

### Application-level controls

- **CSRF protection on all API endpoints:** `CsrfValidationTrait` validates the `X-CSRF-Token` header on every state-changing request (POST/PUT/DELETE/PATCH). The frontend `apiFetch()` utility attaches this token automatically.
- **Security response headers:** `SecurityHeadersMiddleware` adds `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, and (on HTTPS) `Strict-Transport-Security` to all API responses.
- **Input validation:** `InputValidator` validates and trims all API request fields before they reach use-case handlers. Invalid input returns HTTP 422.
- **Frontend XSS prevention:** User-supplied HTML rendered in the browser is sanitised with DOMPurify using a restricted allow-list (`resources/js/utils/escape-html.ts`).

### Known issues and accepted risks

Security issues that have been assessed and accepted (or are awaiting remediation) are tracked in [SECURITY-KNOWN-ISSUES.md](SECURITY-KNOWN-ISSUES.md).

## Scope

This policy applies to the Innovationsbox application code in this repository.
It does not cover the Concrete CMS core or third-party packages — report those
to their respective maintainers.
