// Build / deploy info, surfaced on the Settings page so admins can
// confirm at a glance which version is actually running.
//
// Everything here is read from the local git checkout once, at
// process startup, and cached — because the systemd service is
// restarted after each deploy, so a fresh Node process always reads
// fresh values. If the folder isn't a git checkout (or `git` isn't
// installed) every field is null and the view shows a gentle "not
// available" message instead of crashing.
//
//   commit / commitShort   the current commit SHA
//   branch                 e.g. "main"
//   commitDate             when that commit was made (ISO)
//   subject                first line of the commit message
//   dirty                  true if there are uncommitted local edits
//   deployedAt             mtime of .git/HEAD, which advances on
//                          every checkout / pull / reset — i.e.
//                          "when this server last took an update"

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const REPO_ROOT = path.join(__dirname, '..');

function git(cmd) {
    try {
        return execSync(cmd, {
            cwd: REPO_ROOT,
            stdio: ['ignore', 'pipe', 'ignore'],
            timeout: 3000,
        }).toString().trim();
    } catch (e) {
        return '';
    }
}

let cached = null;

function loadBuildInfo() {
    if (cached) return cached;

    const commit = git('git rev-parse HEAD');
    const commitShort = git('git rev-parse --short HEAD');
    const branch = git('git rev-parse --abbrev-ref HEAD');
    const commitDate = git('git log -1 --format=%cI');
    const subject = git('git log -1 --format=%s');
    const status = git('git status --porcelain');

    let deployedAt = '';
    try {
        const headPath = path.join(REPO_ROOT, '.git', 'HEAD');
        deployedAt = fs.statSync(headPath).mtime.toISOString();
    } catch (e) { /* not a git checkout */ }

    cached = {
        commit:       commit || null,
        commitShort:  commitShort || null,
        branch:       branch || null,
        commitDate:   commitDate || null,
        subject:      subject || null,
        dirty:        status !== '',
        deployedAt:   deployedAt || null,
        available:    !!(commit || deployedAt),
    };
    return cached;
}

module.exports = { loadBuildInfo };
