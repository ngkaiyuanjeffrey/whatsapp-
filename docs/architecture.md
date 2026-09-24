# Architecture

The browser-facing PHP application authenticates operators with sessions and CSRF tokens. PHP owns authorization, PDO access, campaign/job state, audit events, and media access.

The Windows worker authenticates to `/api/worker.php` with `Authorization: Bearer <token>`. It claims jobs, sends through its persistent Playwright browser, and reports results. It never receives database credentials.

## Delivery semantics

Jobs are at-least-once. If WhatsApp accepts a message and the worker crashes before reporting success, the server cannot know whether delivery occurred. Stale-job recovery must therefore be observable and documented as potentially duplicating an external send.
