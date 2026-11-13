# API Documentation

The Innovationsbox API is documented using the [OpenAPI 3.0](https://www.openapis.org/) specification, generated from PHP 8 attributes on controllers via [zircote/swagger-php](https://github.com/zircote/swagger-php).

## Swagger UI (Interactive Docs)

Available at `/api/docs` when running locally.

**Controlled by config:** `api.docs_enabled` (default: `true` in dev, set to `false` in production).

The "Authorize" button uses Concrete CMS session cookies — log into the CMS first in the same browser, then "Try it out" sends requests with your session automatically.

## Spec Files

- `docs/api/openapi.yaml` — YAML format (committed)
- `docs/api/openapi.json` — JSON format (committed)

Import either file into Postman, Insomnia, or Bruno.

## Regenerating the Spec

```bash
composer openapi:generate
```

This scans all `#[OA\...]` attributes in `public/packages/inno_*/src/` and generates both YAML and JSON files. Always commit the result — CI checks for staleness.

## Adding a New Endpoint

1. Add `#[OA\Get]` / `#[OA\Post]` / etc. attributes to your controller method
2. Add `#[OA\Response]` for every possible status code
3. Reference reusable schemas from `inno_shared/src/Infrastructure/OpenApi/Schema/`
4. Run `composer openapi:generate` and commit the updated spec

## Adding a New Schema

1. Create a class in `public/packages/inno_shared/src/Infrastructure/OpenApi/Schema/`
2. Add `#[OA\Schema]` with properties
3. Reference it from controllers via `ref: '#/components/schemas/YourSchema'`
4. Regenerate the spec

## Authentication

### Current: Session/Cookie

The API uses Concrete CMS session authentication:

1. Log in via the CMS web interface (`/login`)
2. The browser stores a `CONCRETE5` session cookie
3. All API requests include this cookie automatically

```bash
# Example with curl
curl -c cookies.txt -X POST http://localhost:8080/login/authenticate/concrete \
  -d "uName=admin&uPassword=your_password"

curl -b cookies.txt http://localhost:8080/api/project-idea
```

### Future: OAuth 2.0 Bearer Tokens

Concrete CMS 9.4 supports OAuth 2.0 natively at `/oauth/2.0/`. This can be added as a second auth method on the custom API endpoints in the future, enabling external integrators and programmatic access.

## Linting

```bash
npm run lint:api
```

Uses [Spectral](https://stoplight.io/open-source/spectral) with rules defined in `.spectral.yaml`.

## CI Pipeline

1. **Spec linting** — `npm run lint:api` (Stage 1)
2. **Staleness check** — regenerate spec, diff against committed version, fail if stale (Stage 2)
