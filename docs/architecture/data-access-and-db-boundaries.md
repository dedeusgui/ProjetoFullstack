# Data Access and DB Boundaries

## Current Data Access Modes

- Repository classes in `app/Repository/*`
- Service-layer direct MySQLi queries (present in some services, e.g., completion flows)
- Stored procedures and views defined in `sql/doitly_unified.sql`

## Practical Boundaries (Current)

- Prefer repositories for reusable CRUD/query logic
- Allow service-level DB access when orchestration + transactions/procedures are tightly coupled
- Document exceptions and high-impact data access decisions in ADRs when patterns change

## Testing Implications

- Action/integration tests require real MySQL/MariaDB behavior (procedures, views, constraints)
- Test schema reset uses the unified SQL dump for parity with runtime expectations

