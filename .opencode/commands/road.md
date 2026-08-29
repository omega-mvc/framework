AUTONOMOUS ROADRUNNER COMPATIBILITY AUDIT:

You are given the raw `src/` directory of this project. Do not initialize a new project workspace; instead, perform an immediate, deep architectural analysis of the existing source files to determine if this application can run successfully as a worker under RoadRunner (PHP application server).

Execute the following tasks:
1. CODEBASE DISCOVERY: Recursively analyze all files in `src/` to map out state management, global/static state usage, database connections, dependency injection, lifecycle hooks, and potential memory leak vectors (e.g., static arrays accumulating data across requests).
2. ROADRUNNER COMPATIBILITY ASSESSMENT: Determine whether the architecture is inherently compatible with RoadRunner's persistent worker model, or if it violates core assumptions (e.g., uninitialized singletons, global state pollution, blocking calls).
3. ACTIONABLE REMEDIATION PLAN: If incompatibilities exist, list every precise modification, refactoring step, or structural adjustment required to make the codebase fully RoadRunner-compliant.
4. DELIVERABLE: Write the complete, comprehensive audit and remediation guide directly to a new file named `ROADRUNNER_COMPATIBILITY.md` at the root of the project. Do not omit technical details or code examples.
