# Laravel Platform Reference

Production-style Laravel reference application for a small multi-tenant workspace and project API.

This is an original portfolio project using synthetic data and domain language. It is not copied from any employer system and makes no production-scale or uptime claims.

## Problem

SaaS APIs need more than CRUD endpoints. They must keep tenant boundaries explicit, separate authentication from authorisation, validate input, and make database constraints match the domain model.

## Solution

The first milestone models a workspace as a tenant boundary. Users belong to workspaces with a role (`owner`, `admin`, or `member`). Only workspace owners/admins with the `projects:create` token ability can create a project. The controller resolves the workspace from the route, checks membership, validates the request, and persists through a scoped relationship.

```mermaid
flowchart LR
    Client --> Token[Sanctum token endpoint]
    Client --> API[POST /api/v1/workspaces/{workspace}/projects]
    API --> Auth[Authentication + token ability]
    Auth --> RBAC[Workspace membership + role]
    RBAC --> Request[Form request validation]
    Request --> Eloquent[Workspace-scoped Eloquent relation]
    Eloquent --> MySQL[(MySQL / SQLite for tests)]
```

## Key Features

- Laravel 13 application with Composer lockfile.
- Sanctum personal access tokens with a `projects:create` ability.
- Workspace membership and role checks for tenant-scoped authorisation.
- Request validation for project names and bounded project keys.
- Foreign keys, composite membership primary key, and workspace/key uniqueness constraint.
- Feature tests covering allowed access, role denial, tenant isolation, and validation.
- Dockerfile and Compose services for the app, MySQL, and Redis local infrastructure.

The current milestone does not claim that Redis queues, background workers, or a production deployment are wired into the application; those are deliberate next steps.

## API Design

### Issue a token

```http
POST /api/v1/tokens
Content-Type: application/json

{
  "email": "user@example.test",
  "password": "local-password",
  "device_name": "local-client"
}
```

### Create a project

```http
POST /api/v1/workspaces/{workspace}/projects
Authorization: Bearer <sanctum-token>
Content-Type: application/json

{
  "name": "Billing API",
  "key": "bill"
}
```

The route returns `201 Created` for an authorised owner/admin. A non-member or regular member receives `403 Forbidden`; malformed input receives Laravel's validation response.

## Database Design

- `workspaces` owns tenant-level projects.
- `workspace_user` stores membership and role with a composite primary key.
- `projects` carries both `workspace_id` and `created_by` foreign keys.
- `unique(workspace_id, key)` prevents duplicate project keys inside one tenant while allowing the same key in another tenant.

The test suite uses SQLite for speed. The Compose definition provides MySQL for local integration checks; SQLite success is not treated as proof of every MySQL-specific behaviour.

## Security

Implemented in this milestone:

- Sanctum token authentication.
- Ability check for project creation.
- Workspace membership and role authorisation.
- Request validation and database constraints.
- `.env` ignored; `.env.example` contains no credentials.

Not claimed yet: rate limiting policy, audit event persistence, Redis queues, secret-manager integration, and production hardening. Those belong in subsequent milestones.

## Testing

```bash
php artisan test
vendor/bin/pint --test
```

The feature suite currently covers six tests and the repository is kept green before publication.

## Running Locally

### SQLite quick start

```bash
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
php artisan test
```

### Docker services

```bash
docker compose up --build
```

The Compose file starts the PHP app, MySQL, and Redis with local-only example credentials. Run migrations inside the app container before using the database-backed API.

## Engineering Decisions

- **Tenant boundary in the URL and query:** makes the scope visible and reviewable.
- **Policy inputs are explicit:** token ability and workspace role are separate checks, avoiding a single overloaded permission flag.
- **Database constraints mirror domain rules:** application validation improves feedback, while unique keys and foreign keys protect persisted state.
- **Framework conventions over abstraction theatre:** Eloquent relationships and Form Requests are used where they improve clarity; a service layer will be added when workflow complexity justifies it.

## Future Improvements

- Add a dedicated policy/service layer and audit events.
- Add Redis-backed jobs for asynchronous project imports.
- Add OpenAPI documentation and request correlation IDs.
- Add rate limiting and structured operational logging.
- Add MySQL integration tests in CI and publish a scoped CI workflow.
- Add a small frontend only after the API and security boundaries remain stable.

## License

MIT. See [LICENSE](LICENSE).
