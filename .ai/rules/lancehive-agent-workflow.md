# LanceHive Agent Workflow

**Applies to:** all implementation work; read before starting any task from `implementation-tasks.md`.

## Commands — always Sail

```bash
vendor/bin/sail artisan …
vendor/bin/sail artisan test --compact {test-path}
vendor/bin/sail bin pint --dirty --format agent
```

## Implement a task

1. Activate **`lancehive-build-task`** skill
2. Read **only** the target task section in `docs/multi-tenant/implementation-tasks.md` (+ linked acceptance-criteria section for Phases 2, 11, 15)
3. Activate **`lancehive-guardrails`** when task touches auth, policies, middleware, errors, or isolation
4. Implement → narrow Pest → Pint → mark `[x]` in Task checklist + refresh **Last verified** date

## Example prompts

```
Implement task 2.1 (TenantContext) using lancehive-build-task. Activate lancehive-guardrails.
```

```
Add ClientPolicy per task 2.6. Unit + feature tests. Update checklist.
```

## Do not

- Read all of `docs/` — one file from the index in `00-lancehive-core.mdc`
- Run full test suite unless asked
- Add dependencies without user approval
- Skip updating `implementation-tasks.md` checklist when a task is done

## Cursor skills map

| Skill | When |
|-------|------|
| `lancehive-build-task` | Ordered tasks from implementation-tasks.md |
| `lancehive-guardrails` | Security, errors, test matrix |
| `lancehive-conventions` | PHP style, naming, folders |
| `lancehive-architecture` | Module boundaries, tenancy model |
| `lancehive-testing` | Pest tests |
| `lancehive-api-docs` | Scramble / OpenAPI |
| `lancehive-api-contract` | `docs/api/` markdown contract |
