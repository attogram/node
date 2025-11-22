# AI Task Planning: Docs Improve

This file will be used by AI agent and user to coordinate the documentation improvement task.

## Instructions

- All further work will be on this branch.
- user will either chat with agent, or user will update `TASK.md` and tell agent to refresh, read, and act.

## How to Work

1. Always keep this task file updated.
2. Always commit early and commit often. Keep actions atomic, and always immediately commit and push changes. Never wait to push. Ex: do not wait for subagent review before pushing.

## tasks
1. [ ] **Standardize README.md as index file**
   1. [ ] Rename `docs/index.md` to `docs/README.md`.
   2. [ ] Update `web/apps/docs/index.php` to use `README.md` as the default file instead of `index.md`.
   3. [ ] Create `README.md` index files for each subdirectory in the `docs/` folder.
      1. [ ] `docs/api/README.md`
      2. [ ] `docs/dapps/README.md`
      3. [ ] `docs/getting-started/README.md`
      4. [ ] `docs/introduction/README.md`
      5. [ ] `docs/masternodes/README.md`
      6. [ ] `docs/mining/README.md`
      7. [ ] `docs/smart-contracts/README.md`
      8. [ ] `docs/staking/README.md`
      9. [ ] `docs/wallet/README.md`
   4. [ ] The content of each new `README.md` will be a markdown-formatted list of the files within its directory.
2. [ ] **Create header and navigation onto every doc file**
