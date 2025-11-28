### Agent Prompt

**Hello,**

You are taking over a critical task to debug a recurring server crash in a PHP-based documentation viewer. A previous agent attempted a fix that proved to be incorrect, so a fresh perspective is required.

**The Problem:**

The application crashes when rendering specific long, link-heavy Markdown files. The PHP server process hangs, consumes all available resources, and eventually dies. The crash is reproducible in the web server environment but not via simple command-line execution, pointing towards a resource limit issue (memory or execution time) specific to the web server's configuration.

A URL that reliably triggers the crash is: `http://46.224.60.113:81/apps/docs/index.php?doc=epow/technical-explanation.md`

The relevant code is in `web/apps/docs/index.php`, specifically the `ParsedownExt` class which extends a third-party `Parsedown` library.

**The Failed Theory and Attempted Fix:**

A previous agent theorized that the crash was caused by a performance bottleneck in the `ParsedownExt::inlineLink` method. This method is executed for every hyperlink in the document. Inside this method, the expensive, I/O-heavy `realpath()` function was being called repeatedly in a loop to resolve the documentation's base directory path.

The attempted fix was to cache the result of this `realpath()` call. The result was calculated once in the class constructor and stored in a property, eliminating the repeated I/O calls.

**CRITICAL: This fix did NOT work.** After deploying the change, the server exhibits the exact same hanging behavior on the same Markdown files. The theory was wrong.

**Your Task:**

Your task is to find the true root cause of the crash and implement a permanent solution. The previous fix has been reverted. You must investigate alternative causes.

**Recommended Next Steps and Alternative Theories:**

1.  **Isolate the Hang with Logging:** The most crucial next step is to pinpoint exactly where the code is hanging. Modify `web/apps/docs/index.php` to add detailed logging. For example:
    *   Log entry and exit of the `inlineLink` method.
    *   Log before and after the `parent::inlineLink($Excerpt)` call.
    *   Log before and after the remaining `realpath($file)` call.
    *   By examining the logs after triggering the crash, you can determine which line is the culprit.

2.  **Investigate the Parent `Parsedown` Library:** The bug may not be in our custom `ParsedownExt` code, but in the base `Parsedown` class (in `Parsedown.php`). The call to `parent::inlineLink($Excerpt)` could be the source of the hang. You may need to read the code of this third-party library to understand its implementation.

3.  **Analyze the Problematic Content:** The content of the Markdown file that causes the crash (`epow/technical-explanation.md`) is a key piece of evidence. You will need to find a way to read this file's contents to look for unusual patterns, such as strange link formats or structures that might trigger a rare edge case or a catastrophic backtracking issue in a regular expression within the parser.

4.  **Re-examine `inlineLink` for Other Issues:** While the `realpath` theory was wrong, the problem is still highly correlated with link processing. Scrutinize the other operations within the `inlineLink` method. Could the `preg_match` call be inefficient on certain link types?

**Important Housekeeping Rules:**

*   **Increment Version Number:** On every new commit you make, you **must** increment the `VERSION` constant defined at the top of `web/apps/docs/index.php`. This is crucial for tracking changes.
*   **Implement a Failsafe Timeout:** As a critical part of your solution, you must implement a failsafe mechanism. The entire documentation rendering process should not exceed a maximum execution time (e.g., 15 seconds). If it does, the script must terminate gracefully and display a user-friendly error message, rather than allowing the server to hang indefinitely. You can use PHP's `set_time_limit()` function for this.

Your goal is to form a new, evidence-based theory and implement a fix that permanently resolves the server crash while also making the viewer more resilient.
