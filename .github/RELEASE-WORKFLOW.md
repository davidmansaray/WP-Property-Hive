# Property Hive Release Workflow

This fork uses `staging` as its shared integration and staging-deployment
branch. The upstream production branch remains
`propertyhive/WP-Property-Hive:master`.

## Normal feature work

1. Create a local `feature/*`, `fix/*`, or `chore/*` branch.
2. Push it to the `davidmansaray` fork.
3. Open a pull request to the fork's `staging` branch:

   ```bash
   gh pr create \
     --repo davidmansaray/WP-Property-Hive \
     --base staging \
     --head "<branch>"
   ```

4. Assign the pull request to the intended version milestone.
5. Merge it after review and required checks pass.
6. The merge commit deploys automatically to the staging server.
7. Record the deployed SHA and move the pull request from `qa:ready` to
   `qa:passed` after testing.

If QA rejects work that is already on `staging`, revert its merge commit through
a new pull request. Do not force-push or reset `staging`.

## Release preparation

Only one active `release/*` branch may use the shared staging server at a time.

1. Confirm every intended pull request is in the release milestone and has
   passed staging QA.
2. Record the exact approved `staging` SHA.
3. Create the release branch from that SHA:

   ```bash
   git fetch davidmansaray staging
   git switch --create "release/<version>" "<approved-staging-sha>"
   git push --set-upstream davidmansaray "release/<version>"
   ```

4. Make only release-specific changes: version, stable tag, changelog,
   packaging metadata, or defects found during final QA.
5. Every release-branch push redeploys automatically. Reapprove the exact
   latest SHA after each change.
6. Open the final pull request to upstream `master`:

   ```bash
   gh pr create \
     --repo propertyhive/WP-Property-Hive \
     --base master \
     --head "davidmansaray:release/<version>"
   ```

The final pull request must list the release milestone, staging URL, exact
deployed SHA, QA evidence, migration notes, and rollback considerations.

## After upstream release

Synchronize the fork and staging line before accepting the next release:

```bash
git fetch origin master
git push davidmansaray "origin/master:master"
git switch staging
git merge --no-ff origin/master
git push davidmansaray staging
```

Verify the expected tag and release notes, close the milestone, and delete the
merged `release/<version>` branch.

## Hotfixes

Create `hotfix/*` from current upstream `master`, test it through the same
staging environment, and open the final pull request to upstream `master`.
After release, synchronize upstream `master` back into fork `master` and
`staging` exactly as above.

## Deployment behavior

`.github/workflows/deploy-propertyhive.yml` deploys only `staging` and
`release/**`. It:

- queues deployments to the single staging server in FIFO order;
- packages only files tracked by the exact triggering commit;
- excludes development-only paths;
- creates a timestamped backup under `/var/backups/propertyhive/`;
- synchronizes with delayed deletion;
- restores WordPress ownership and modes;
- lints the plugin and confirms it remains active;
- checks the HTTPS site root and a real published property;
- automatically restores the backup if post-sync verification fails.

The workflow's job summary is the deployment record. It contains the deployed
branch and SHA, rollback path, smoke-test URLs and statuses, and Actions run.
The host, user, path, and site URL are environment variables; only the SSH key
and known-hosts data are stored as environment secrets.

Use `workflow_dispatch` only to redeploy an allowed `staging` or `release/*`
commit. Selecting any other branch intentionally skips the deployment job.
