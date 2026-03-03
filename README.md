# Pastebin

A lightweight, self-hosted Pastebin application built with PHP and MySQL.
No build step required — just drop the files on any PHP-enabled web server.

## Features

- **Create, view, and delete** text/code pastes
- **Syntax highlighting** for 19 languages powered by [highlight.js](https://highlightjs.org/)
- **Anonymous comments** on every paste
- **Delete-token authentication** — session flash on creation, long-lived cookie fallback
- **Paginated paste listing** — browse all pastes with Prev/Next controls in the sidebar
- **Responsive UI** — mobile-first layout built with [Tailwind CSS](https://tailwindcss.com/)
- **Raw endpoint** — `?raw=SLUG` returns plain-text content (great for `curl`)
- **Zero dependencies** — no Composer, no npm; CDN assets only

## Directory Structure

```
pastebin/
├── config/
│   └── config.php          # DB credentials & application constants
├── src/
│   ├── db.php              # PDO factory + schema auto-creation
│   ├── helpers.php         # Slug/token generation, pagination helpers, language list
│   └── actions.php         # POST request handlers (create, delete, add_comment, raw)
├── views/
│   ├── layout_head.php     # HTML <head> + sticky top navbar
│   ├── layout_foot.php     # Footer + global JavaScript
│   ├── home.php            # Create-paste form & error/success notices
│   ├── paste_view.php      # View paste, comments, and delete form
│   └── sidebar.php         # Paginated recent-pastes sidebar
├── index.php               # Application entry point (routing + data fetching)
├── LICENSE
└── README.md
```

## Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/druvx13/pastebin.git
   cd pastebin
   ```

2. **Configure the database** — edit `config/config.php`:

   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'pastebin_app');
   ```

3. **Deploy** the directory to a PHP-enabled web server with the project root
   as the document root (or set up a virtual host pointing to it).

4. **Visit the site** — the database and tables are created automatically on
   the first request.

## Requirements

| Requirement | Minimum version |
|---|---|
| PHP | 7.4 |
| MySQL / MariaDB | MySQL 5.7 / MariaDB 10.3 |
| PHP extensions | `pdo`, `pdo_mysql`, `mbstring` |

## Configuration Reference

All configuration lives in `config/config.php`:

| Constant | Default | Description |
|---|---|---|
| `DB_HOST` | `127.0.0.1` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_USER` | `root` | MySQL username |
| `DB_PASS` | `password` | MySQL password (**change before deploying**) |
| `DB_NAME` | `pastebin_app` | Database name (auto-created if absent) |
| `PASTES_PER_PAGE` | `15` | Pastes shown per page in the sidebar |
| `COOKIE_LIFETIME` | `2592000` | Cookie lifetime in seconds (default 30 days) |
| `COMMENT_MAX_LENGTH` | `2000` | Maximum comment body length (characters) |
| `COMMENT_NAME_MAX` | `100` | Maximum commenter name length (characters) |

## Usage

| Task | How |
|---|---|
| **Create a paste** | Fill in the form on the homepage and click **Create Paste** |
| **View a paste** | Click any entry in the sidebar, or navigate to `/?view=SLUG` |
| **Copy content** | Click the **Copy** button on the paste page |
| **Raw content** | Click **Raw** or visit `/?raw=SLUG` (returns `text/plain`) |
| **Delete a paste** | Use the delete form at the bottom of the paste page |
| **Browse past pastes** | Use the **← Prev** / **Next →** controls in the sidebar |
| **Comment on a paste** | Use the comment form on any paste page |

## Security Notes

- **Change the default database credentials** in `config/config.php` before any
  public deployment.
- **Enable HTTPS** in production to protect tokens and content in transit.
- Delete tokens use [`hash_equals()`](https://www.php.net/hash_equals) to
  prevent timing-attack leakage.
- All user-supplied content is escaped with `htmlspecialchars()` at render time.
- Cookies set by the application use `SameSite=Lax` in the JavaScript layer;
  consider setting the `Secure` flag on the PHP `setcookie()` calls when
  serving over HTTPS.

## License

[MIT](LICENSE)
