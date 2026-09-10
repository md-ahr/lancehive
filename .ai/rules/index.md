# LanceHive Rules Index

Path-scoped rules for AI agents and teammates. **Read every file whose globs match the path(s) you are editing** before writing code. Also run `grep -rin 'keyword' .ai/rules` when a path match alone might miss cross-cutting guidance.

In Cursor, `.cursor/rules/*.mdc` auto-apply for the same paths — treat both as binding.

| Glob | File |
|------|------|
| `app/Features/**` | [lancehive-architecture.md](./lancehive-architecture.md) |
| `app/Core/**` | [lancehive-architecture.md](./lancehive-architecture.md) |
| `routes/**`, `config/api.php`, `config/scramble.php` | [lancehive-api.md](./lancehive-api.md) |
| `app/Features/**/Http/**` | [lancehive-api.md](./lancehive-api.md) |
| `tests/**` | [lancehive-testing.md](./lancehive-testing.md) |
| `docs/multi-tenant/implementation-tasks.md`, build tasks | [lancehive-agent-workflow.md](./lancehive-agent-workflow.md) |
| `*` (always read before any task) | [lancehive-agent-workflow.md](./lancehive-agent-workflow.md) |

## Quick entry

| Need | Start here |
|------|------------|
| Implement a task | `lancehive-build-task` skill → task block in `implementation-tasks.md` |
| Auth, policies, errors, tests | `lancehive-guardrails` skill |
| API contract | `docs/api/README.md` |
| Human dev guide | `docs/development/README.md` |
