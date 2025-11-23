# Task: AI Task Planning: Docs Improve

## Session State

- **Current Branch**: docs-improve-planning-1
- **PR**: `(Fill in with the URL of the pull request)`

---

## Protocol: Session Resumption

If you are a new agent instance resuming this task, you must not proceed with the task list. Your first actions are to audit the state of the branch and report to the user:

1.  **Audit the Checklist**: For every item in the Task Checklist, you must use `read_file`, `ls`, and other tools to verify whether the work described has actually been completed.
2.  **Summarize for User**: Create a summary of your findings for the user. For example:
    *   "I have audited the `task.md`. I can confirm that tasks 1.1 and 1.2 are complete. Task 1.3 is marked as complete, but I have found that the file it was supposed to create is missing. Task 2.0 is not yet started."
3.  **Request Instructions**: After providing the summary, you must explicitly ask the user for instructions on how to proceed and then wait for a response. For example:
    *   "How should I proceed? Should I re-do task 1.3, or should I proceed to task 2.0?"

---

## Protocol: Immutable Branch

**The branch for this session is IMMUTABLE.** All work must be added as new "commits" to this branch.

---

## Protocol: Core Workflow

- **Task Checklist**: All work must be broken down into a numbered checklist.
- **Atomic Commits**: Each numbered item is a single, logical change and must be its own "commit".
- **Keep `task.md` Updated**: This file must be updated with every "commit".
- **Provisional Completion**: You may check off tasks, but the user is the final arbiter.
- **Neutral Commit Language**: Avoid words like "final" in "commit" messages.

---

## Protocol: User Interaction

There are two ways to interact with the agent:

1.  **Chat (Default)**: Provide instructions and feedback through conversation.
2.  **`task.md` Override**: You can directly edit this `task.md` file and commit the changes. When you do, you **must** notify the agent. The agent is required to immediately stop its current work, pull the latest changes, re-read this file, and follow the new instructions. This is the primary method for providing detailed, asynchronous instructions.

---

## Protocol: Session Completion

This procedure begins **only** when the user explicitly states the session is complete.

1.  **Standard Procedure**:
    *   Delete this `task.md` file.
    *   "Commit" the deletion with the message `docs: Remove task.md session file`.
2.  **Squash Option (User-Requested)**:
    *   If the user requests a "squash", you must first read the contents of this `task.md` file.
    *   Delete this `task.md` file.
    *   "Commit" the deletion, but use the content of the deleted file as the basis for a detailed, multi-line commit message that summarizes the entire session's work.

---

## Task Checklist
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
- [x] 3. docs/readme as short intro and toc linking to ALL doc files

- [ ] 4. new docs/resources - list of important web sites, nodes, community sites etc

- [ ] 5. update root project readme to link to docs dir.  update all breadcrumbs to use root of project as 1st crumb.  PHPCoin > docs > foo

- [ ] 6. security fix web/apps/docs - we use _GET to build path. but never trust user input.  must secure against path transversal attacks, etc
