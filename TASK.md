# AI Task Planning: Docs Improve

This file will be used by AI agent and user to coordinate the documentation improvement task.

## Instructions

- All further work will be on this branch.
- user will either chat with agent, or user will update `TASK.md` and tell agent to refresh, read, and act.

## How to Work

1. Always keep this task file updated.
2. Always commit early and commit often. Keep actions atomic, and always immediately commit and push changes. Never wait to push. Ex: do not wait for subagent review before pushing.

## tasks
- [ ] **Standardize README.md as index file**
  - [ ] Rename `docs/index.md` to `docs/README.md`.
  - [ ] Update `web/apps/docs/index.php` to use `README.md` as the default file instead of `index.md`.
  - [ ] Create `README.md` index files for each subdirectory in the `docs/` folder.
    - [ ] `docs/api/README.md`
    - [ ] `docs/dapps/README.md`
    - [ ] `docs/getting-started/README.md`
    - [ ] `docs/introduction/README.md`
    - [ ] `docs/masternodes/README.md`
    - [ ] `docs/mining/README.md`
    - [ ] `docs/smart-contracts/README.md`
    - [ ] `docs/staking/README.md`
    - [ ] `docs/wallet/README.md`
  - [ ] The content of each new `README.md` will be a markdown-formatted list of the files within its directory.
