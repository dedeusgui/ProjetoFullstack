# Docs Automation Runbook

This runbook explains how to launch the detached documentation agent using Composer.

## Command

- `composer docs`

Optional arguments:

- `composer docs -- --commit=<sha-or-ref>`
- `composer docs -- --title="<run-label>"`

Defaults:

- commit/ref: `HEAD`
- diff scope: `HEAD^..HEAD` when parent commit exists, otherwise `HEAD`

## What Happens

1. `scripts/docs_agent_launcher.php` validates prerequisites (`git`, `codex.cmd`, commit availability).
2. A run directory is created under `docs/automation/runs/<timestamp>-<commit>[-title]/`.
3. The launcher writes:
   - `prompt.runtime.md`
   - `run-docs-agent.ps1`
   - `meta.json`
   - `launch.log`
4. A new PowerShell window is opened and runs a separate Codex agent with the runtime prompt.
5. The current terminal is released immediately so feature work can continue.

## Run Artifacts

Each run folder contains:

- `prompt.runtime.md`: exact prompt sent to the docs agent
- `run-docs-agent.ps1`: generated command wrapper used by the detached terminal
- `agent.console.log`: detached agent stdout/stderr stream
- `agent.output.txt`: final agent message (`codex exec -o` output)
- `launch.log`: launcher summary and process launch details
- `meta.json`: structured run metadata (commit, range, paths, launch command, exit code)

## Failure Behavior

`composer docs` fails fast when preflight checks or launch fail:

- missing `git`
- missing `codex.cmd`
- missing prompt template (`docs/automation/docs-agent-prompt.md`)
- unresolved target commit
- inability to create run artifacts or spawn detached process

## Troubleshooting

### `codex.cmd not found`

- Ensure Codex CLI is installed and available in `PATH`, or at `%APPDATA%\\npm\\codex.cmd`.

### PowerShell script policy errors

- The launcher starts the detached shell with `-ExecutionPolicy Bypass`.
- If local policy still blocks execution, run with an account/policy that allows process-scoped bypass.

### Detached agent cannot reach OpenAI API

- Check internet access and Codex authentication status.
- Inspect `agent.console.log` for connection/auth errors.

### Prompt or output files missing

- Inspect `launch.log` and `meta.json` in the run folder for creation/launch failures.

## Limitations

- Current launcher is Windows-only.
- In non-interactive/headless terminals, the new window may not fully execute even if launcher exit code is `0`.
- The launcher guarantees detached process startup, not documentation correctness.
- Documentation changes still require normal review before merge.
