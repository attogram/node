# AI Task Planning: Docs Improve

This file will be used by AI agent and user to coordinate the documentation improvement task.

## Instructions

- All further work will be on this branch.
- user will either chat with agent, or user will update `TASK.md` and tell agent to refresh, read, and act.

## How to Work

1. Always keep this task file updated.
2. Always commit early and commit often. Keep actions atomic, and always immediately commit and push changes.
3. After a commit, perform validation steps (like tests or code reviews). If corrections are needed, make them in a new, separate commit. Do not wait for a review before pushing the initial change.

## tasks
- [x] 1. Standardize README.md as index file
  - [x] 1.1 Rename `docs/index.md` to `docs/README.md`.
  - [x] 1.2 Update `web/apps/docs/index.php` to use `README.md` as the default file instead of `index.md`.
  - [x] 1.3 Create `README.md` index files for each subdirectory in the `docs/` folder.
    - [x] 1.3.1 `docs/api/README.md`
    - [x] 1.3.2 `docs/dapps/README.md`
    - [x] 1.3.3 `docs/getting-started/README.md`
    - [x] 1.3.4 `docs/introduction/README.md`
    - [x] 1.3.5 `docs/masternodes/README.md`
    - [x] 1.3.6 `docs/mining/README.md`
    - [x] 1.3.7 `docs/smart-contracts/README.md`
    - [x] 1.3.8 `docs/staking/README.md`
    - [x] 1.3.9 `docs/wallet/README.md`
  - [x] 1.4 The content of each new `README.md` will be a markdown-formatted list of the files within its directory.
- [x] 2. Create header and navigation onto every doc file
  - [x] 2.1 Add a two-part header to every `.md` file in the `docs/` directory.
  - [x] 2.2 The header will consist of a clickable breadcrumb trail and a main navigation bar.
  - [x] 2.3 Example for `docs/mining/how-to-mine.md`:
    ```markdown
    [Docs Home](../../README.md) > [Mining](../README.md) > How to Mine

    ---
    [Introduction](../introduction/README.md) | [Getting Started](../getting-started/README.md) | [Mining](../mining/README.md) | [Staking](../staking/README.md) | [Wallet](../wallet/README.md) | [Masternodes](../masternodes/README.md) | [Smart Contracts](../smart-contracts/README.md) | [dApps](../dapps/README.md) | [API](../api/README.md)
    ---
    ```
- [ ] 3. docs/readme as short intro and toc linking to ALL doc files
