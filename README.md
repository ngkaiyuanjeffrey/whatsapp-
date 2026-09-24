# WhatsApp Bot Control Panel

Consent-based internal messaging platform using PHP, MySQL, and a Windows Python worker. The worker never connects directly to MySQL; it uses bearer-authenticated PHP API endpoints.

## Layout

- `database/schema.sql`: initial MySQL schema
- `web/`: PHP web and API surface
- `worker/`: Python worker boundary and configuration
- `docs/`: architecture and delivery limitations

## Local setup

1. Create a MySQL database and import `database/schema.sql`.
2. Copy `web/config.example.php` to `web/config.local.php` and set local values. If the file does not exist yet, the app will try to create it from the example on first run.
3. Configure Apache's document root to the project root or `web/` directory, depending on your local setup.
4. Install worker dependencies with `python -m pip install -r worker/requirements.txt`.
5. Copy `worker/config.example.ini` to `worker/config.ini`; never commit the token.
6. Link WhatsApp Web manually in the persistent worker browser profile.

This starter does not claim exactly-once delivery. Browser automation has an at-least-once execution model with an unavoidable ambiguous crash window.
