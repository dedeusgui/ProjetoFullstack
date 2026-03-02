<?php

declare(strict_types=1);

const EXIT_OK = 0;
const EXIT_PRECONDITION_FAILED = 1;
const EXIT_LAUNCH_FAILED = 2;

if (PHP_OS_FAMILY !== 'Windows') {
    fwrite(STDERR, "composer docs is currently Windows-only. Use this command on Windows.\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$repoRoot = realpath(__DIR__ . '/..');
if ($repoRoot === false) {
    fwrite(STDERR, "Unable to resolve repository root.\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$options = getopt('', ['commit::', 'title::']);
$requestedCommit = is_string($options['commit'] ?? null) && trim($options['commit']) !== ''
    ? trim($options['commit'])
    : 'HEAD';
$runTitle = is_string($options['title'] ?? null) ? trim($options['title']) : '';
$runTitleSlug = slugify($runTitle);

$templatePath = $repoRoot . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'automation' . DIRECTORY_SEPARATOR . 'docs-agent-prompt.md';
if (!is_file($templatePath)) {
    fwrite(STDERR, "Prompt template not found: {$templatePath}\n");
    exit(EXIT_PRECONDITION_FAILED);
}

assertCommandWorks('git --version', $repoRoot, 'git is required for composer docs.');

$headCheck = runCommand('git rev-parse --verify HEAD', $repoRoot);
if ($headCheck['exitCode'] !== 0) {
    fwrite(STDERR, "No commits found. Create at least one commit before running composer docs.\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$commitResult = runCommand('git rev-parse --verify ' . shellQuote($requestedCommit . '^{commit}'), $repoRoot);
if ($commitResult['exitCode'] !== 0) {
    fwrite(STDERR, "Commit not found: {$requestedCommit}\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$resolvedCommit = firstLine($commitResult['stdout']);
if ($resolvedCommit === '') {
    fwrite(STDERR, "Unable to resolve commit hash for {$requestedCommit}.\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$parentCheck = runCommand('git rev-parse --verify ' . shellQuote($resolvedCommit . '^'), $repoRoot);
$commitRange = $resolvedCommit;
if ($parentCheck['exitCode'] === 0) {
    $parentCommit = firstLine($parentCheck['stdout']);
    if ($parentCommit !== '') {
        $commitRange = $parentCommit . '..' . $resolvedCommit;
    }
}

$changedFiles = resolveChangedFiles($repoRoot, $resolvedCommit, $commitRange);

$codexCommandPath = resolveCodexCommandPath($repoRoot);
if ($codexCommandPath === null) {
    fwrite(STDERR, "codex.cmd not found. Install Codex CLI or add it to PATH.\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$runsRoot = $repoRoot . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'automation' . DIRECTORY_SEPARATOR . 'runs';
if (!is_dir($runsRoot) && !mkdir($runsRoot, 0777, true) && !is_dir($runsRoot)) {
    fwrite(STDERR, "Unable to create runs directory: {$runsRoot}\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$runId = date('Ymd-His') . '-' . substr($resolvedCommit, 0, 8);
if ($runTitleSlug !== '') {
    $runId .= '-' . $runTitleSlug;
}

$runDirectory = $runsRoot . DIRECTORY_SEPARATOR . $runId;
$suffix = 1;
while (is_dir($runDirectory)) {
    $runDirectory = $runsRoot . DIRECTORY_SEPARATOR . $runId . '-' . $suffix;
    $suffix++;
}

if (!mkdir($runDirectory, 0777, true) && !is_dir($runDirectory)) {
    fwrite(STDERR, "Unable to create run directory: {$runDirectory}\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$promptRuntimePath = $runDirectory . DIRECTORY_SEPARATOR . 'prompt.runtime.md';
$runnerScriptPath = $runDirectory . DIRECTORY_SEPARATOR . 'run-docs-agent.ps1';
$agentOutputPath = $runDirectory . DIRECTORY_SEPARATOR . 'agent.output.txt';
$agentConsoleLogPath = $runDirectory . DIRECTORY_SEPARATOR . 'agent.console.log';
$launchLogPath = $runDirectory . DIRECTORY_SEPARATOR . 'launch.log';
$metaPath = $runDirectory . DIRECTORY_SEPARATOR . 'meta.json';

$templateContent = file_get_contents($templatePath);
if ($templateContent === false) {
    fwrite(STDERR, "Unable to read prompt template: {$templatePath}\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$promptContent = buildRuntimePrompt(
    $templateContent,
    $repoRoot,
    $runDirectory,
    $resolvedCommit,
    $commitRange,
    $changedFiles
);

if (file_put_contents($promptRuntimePath, $promptContent) === false) {
    fwrite(STDERR, "Unable to write runtime prompt: {$promptRuntimePath}\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$runnerScript = buildRunnerScript(
    $repoRoot,
    $codexCommandPath,
    $promptRuntimePath,
    $agentOutputPath,
    $agentConsoleLogPath
);

if (file_put_contents($runnerScriptPath, $runnerScript) === false) {
    fwrite(STDERR, "Unable to write runner script: {$runnerScriptPath}\n");
    exit(EXIT_PRECONDITION_FAILED);
}

$launchCommand = 'cmd /c start "" powershell.exe -NoExit -ExecutionPolicy Bypass -File ' . windowsQuote($runnerScriptPath);
$launchExitCode = launchDetached($launchCommand, $repoRoot);

$meta = [
    'run_id' => basename($runDirectory),
    'created_at' => date(DATE_ATOM),
    'repo_root' => $repoRoot,
    'commit' => $resolvedCommit,
    'commit_range' => $commitRange,
    'changed_files' => $changedFiles,
    'paths' => [
        'prompt_template' => $templatePath,
        'prompt_runtime' => $promptRuntimePath,
        'runner_script' => $runnerScriptPath,
        'agent_output' => $agentOutputPath,
        'agent_console_log' => $agentConsoleLogPath,
        'launch_log' => $launchLogPath,
    ],
    'launch_command' => $launchCommand,
    'launch_exit_code' => $launchExitCode,
];

file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

$launchLogLines = [
    'created_at=' . date(DATE_ATOM),
    'run_id=' . basename($runDirectory),
    'repo_root=' . $repoRoot,
    'commit=' . $resolvedCommit,
    'commit_range=' . $commitRange,
    'codex_cmd=' . $codexCommandPath,
    'prompt_runtime=' . $promptRuntimePath,
    'runner_script=' . $runnerScriptPath,
    'agent_output=' . $agentOutputPath,
    'agent_console_log=' . $agentConsoleLogPath,
    'launch_command=' . $launchCommand,
    'launch_exit_code=' . $launchExitCode,
];

file_put_contents($launchLogPath, implode(PHP_EOL, $launchLogLines) . PHP_EOL);

if ($launchExitCode !== 0) {
    fwrite(STDERR, "Failed to launch detached docs agent. See {$launchLogPath}\n");
    exit(EXIT_LAUNCH_FAILED);
}

fwrite(STDOUT, "Docs agent launched in a new terminal window.\n");
fwrite(STDOUT, "Run directory: {$runDirectory}\n");
fwrite(STDOUT, "Prompt: {$promptRuntimePath}\n");
fwrite(STDOUT, "Console log: {$agentConsoleLogPath}\n");
fwrite(STDOUT, "Final message output: {$agentOutputPath}\n");

exit(EXIT_OK);

/**
 * @return array{exitCode:int,stdout:string,stderr:string}
 */
function runCommand(string $command, string $cwd): array
{
    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);
    if (!is_resource($process)) {
        return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'Unable to spawn process'];
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'exitCode' => is_int($exitCode) ? $exitCode : 1,
        'stdout' => trim((string) $stdout),
        'stderr' => trim((string) $stderr),
    ];
}

function launchDetached(string $command, string $cwd): int
{
    $descriptorSpec = [
        0 => ['file', 'NUL', 'r'],
        1 => ['file', 'NUL', 'w'],
        2 => ['file', 'NUL', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);
    if (!is_resource($process)) {
        return 1;
    }

    return proc_close($process);
}

function assertCommandWorks(string $command, string $cwd, string $failureMessage): void
{
    $result = runCommand($command, $cwd);
    if ($result['exitCode'] !== 0) {
        fwrite(STDERR, $failureMessage . PHP_EOL);
        if ($result['stderr'] !== '') {
            fwrite(STDERR, $result['stderr'] . PHP_EOL);
        }
        exit(EXIT_PRECONDITION_FAILED);
    }
}

function resolveCodexCommandPath(string $cwd): ?string
{
    $whereResult = runCommand('where codex.cmd', $cwd);
    if ($whereResult['exitCode'] === 0 && $whereResult['stdout'] !== '') {
        foreach (preg_split('/\R/', $whereResult['stdout']) ?: [] as $line) {
            $candidate = trim($line);
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }
    }

    $appData = getenv('APPDATA');
    if (is_string($appData) && $appData !== '') {
        $fallback = $appData . DIRECTORY_SEPARATOR . 'npm' . DIRECTORY_SEPARATOR . 'codex.cmd';
        if (is_file($fallback)) {
            return $fallback;
        }
    }

    return null;
}

/**
 * @return string[]
 */
function resolveChangedFiles(string $repoRoot, string $resolvedCommit, string $commitRange): array
{
    if (str_contains($commitRange, '..')) {
        $result = runCommand(
            'git diff --name-only --diff-filter=ACMR ' . shellQuote($commitRange),
            $repoRoot
        );
    } else {
        $result = runCommand(
            'git show --pretty="" --name-only ' . shellQuote($resolvedCommit),
            $repoRoot
        );
    }

    $files = [];
    if ($result['exitCode'] === 0 && $result['stdout'] !== '') {
        foreach (preg_split('/\R/', $result['stdout']) ?: [] as $line) {
            $file = trim($line);
            if ($file !== '') {
                $files[] = str_replace('\\', '/', $file);
            }
        }
    }

    return array_values(array_unique($files));
}

/**
 * @param string[] $changedFiles
 */
function buildRuntimePrompt(
    string $templateContent,
    string $repoRoot,
    string $runDirectory,
    string $resolvedCommit,
    string $commitRange,
    array $changedFiles
): string {
    $lines = [];
    $lines[] = rtrim($templateContent);
    $lines[] = '';
    $lines[] = '## Runtime Context';
    $lines[] = '- Generated at: `' . date(DATE_ATOM) . '`';
    $lines[] = '- Repository root: `' . str_replace('\\', '/', $repoRoot) . '`';
    $lines[] = '- Commit under review: `' . $resolvedCommit . '`';
    $lines[] = '- Commit range to inspect: `' . $commitRange . '`';
    $lines[] = '- Run artifacts directory: `' . str_replace('\\', '/', $runDirectory) . '`';
    $lines[] = '';
    $lines[] = '## Changed Files';

    if ($changedFiles === []) {
        $lines[] = '- (No changed files detected)';
    } else {
        foreach ($changedFiles as $filePath) {
            $lines[] = '- `' . $filePath . '`';
        }
    }

    return implode(PHP_EOL, $lines) . PHP_EOL;
}

function buildRunnerScript(
    string $repoRoot,
    string $codexCommandPath,
    string $promptRuntimePath,
    string $agentOutputPath,
    string $agentConsoleLogPath
): string {
    $repoRootEscaped = powershellSingleQuoted($repoRoot);
    $codexEscaped = powershellSingleQuoted($codexCommandPath);
    $promptEscaped = powershellSingleQuoted($promptRuntimePath);
    $outputEscaped = powershellSingleQuoted($agentOutputPath);
    $consoleEscaped = powershellSingleQuoted($agentConsoleLogPath);

    return <<<PS1
\$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath '{$repoRootEscaped}'
Get-Content -LiteralPath '{$promptEscaped}' -Raw | & '{$codexEscaped}' exec --sandbox workspace-write -C '{$repoRootEscaped}' -o '{$outputEscaped}' - *>&1 | Tee-Object -FilePath '{$consoleEscaped}'
\$exitCode = \$LASTEXITCODE
"codex-exit-code=\$exitCode" | Tee-Object -FilePath '{$consoleEscaped}' -Append
exit \$exitCode
PS1;
}

function firstLine(string $value): string
{
    $lines = preg_split('/\R/', trim($value));
    return $lines[0] ?? '';
}

function slugify(string $value): string
{
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function shellQuote(string $value): string
{
    return escapeshellarg($value);
}

function windowsQuote(string $value): string
{
    return '"' . str_replace('"', '""', $value) . '"';
}

function powershellSingleQuoted(string $value): string
{
    return str_replace("'", "''", $value);
}
