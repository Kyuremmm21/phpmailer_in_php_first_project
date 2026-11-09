# Project structure

- `pages/` contains the user-facing PHP pages.
- `actions/` contains form handlers, downloads, and other PHP endpoints.
- `includes/` contains shared PHP helpers.
- `config/` contains the database connection.
- `assets/` contains the shared stylesheet.
- `uploads/`, `PHPMailer/`, and `vendor/` contain uploaded files and dependencies.

The root `.htaccess` routes existing PHP page URLs to their new folders and serves the stylesheet at its existing URL. Apache `mod_rewrite` and `AllowOverride` must be enabled.
