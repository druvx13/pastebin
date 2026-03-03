# Pastebin

A lightweight, self-hosted Pastebin built with PHP and MySQL. No frameworks or Composer dependencies required.

## Features

- **Create / View / Delete** pastes with syntax highlighting
- **Anonymous comments** on individual paste pages
- **Syntax highlighting** via [highlight.js](https://highlightjs.org/) (CDN)
- **Responsive UI** with [Tailwind CSS](https://tailwindcss.com/) (CDN)
- **MySQL auto-setup** — database and tables are created automatically on first run
- **One-time delete token** shown after paste creation (session flash) with cookie fallback for the same browser
- **Raw endpoint** (`?raw=SLUG`) returns paste content as plain text

## Directory Structure

```
pastebin/
├── index.php            # Entry point — session start + requires below files
├── src/
│   ├── config.php       # Database credentials and app constants
│   ├── db.php           # PDO connection; auto-creates DB and tables
│   ├── helpers.php      # Helper functions, language list, $basePath
│   └── handlers.php     # POST/GET request handlers; sets view variables
├── views/
│   ├── layout.php       # HTML shell: head, header, main grid, footer, global JS
│   ├── paste.php        # View-paste section: code, comments, delete form
│   ├── create.php       # Create-paste form
│   └── sidebar.php      # Recent pastes sidebar
├── LICENSE
└── README.md
```

## Requirements

- PHP 7.4 or later (PHP 8.x recommended)
- MySQL 5.7+ or MariaDB 10.3+
- A web server (Apache, Nginx, Caddy, …) with PHP support

## Installation

1. **Clone or download** the repository into your web server's document root (or a subdirectory).

2. **Edit `src/config.php`** and set your database credentials:

   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'pastebin_app');
   ```

3. **Visit the page** in your browser. The database and tables are created automatically on the first request.

## Configuration

All tuneable constants live in **`src/config.php`**:

| Constant | Default | Description |
|---|---|---|
| `DB_HOST` | `127.0.0.1` | MySQL host |
| `DB_PORT` | `3306` | MySQL port |
| `DB_USER` | `root` | MySQL username |
| `DB_PASS` | `password` | MySQL password |
| `DB_NAME` | `pastebin_app` | Database name (auto-created) |
| `TABLE_PASTES` | `pastes` | Pastes table name |
| `TABLE_COMMENTS` | `comments` | Comments table name |
| `SLUG_LENGTH_BYTES` | `5` | Bytes used to generate paste slug (slug = hex, so length × 2 chars) |
| `DELETE_TOKEN_BYTES` | `12` | Bytes used to generate the delete token |
| `RECENT_COUNT` | `20` | Number of recent pastes shown in the sidebar |
| `COOKIE_LIFETIME` | `2592000` | Cookie lifetime in seconds (default: 30 days) |
| `COMMENT_MAX_LENGTH` | `2000` | Maximum comment length in characters |
| `COMMENT_NAME_MAX` | `100` | Maximum commenter name length in characters |

## Usage

### Creating a paste

1. Open the homepage.
2. Optionally enter a **title** and select a **language** for syntax highlighting.
3. Paste your content and click **Create Paste**.
4. A **delete token** is displayed once — copy and store it if you want to delete the paste from a different browser.

### Viewing a paste

Paste URLs follow the pattern `/?view=<slug>`. The sidebar lists the 20 most recent pastes.

### Raw content

Append `?raw=<slug>` to fetch the paste content as plain text, useful for scripts:

```
curl https://your-host/?raw=<slug>
```

### Deleting a paste

On the paste page, enter the delete token in the **Delete** form and submit. If you are on the same browser that created the paste, the token is pre-filled from a cookie.

### Comments

Each paste page has an anonymous comment form. A name is optional; if omitted, the comment is shown as *Anonymous*.

## Production Checklist

- [ ] Replace the default DB credentials in `src/config.php`.
- [ ] Serve the application over **HTTPS**.
- [ ] Build Tailwind CSS locally instead of using the CDN Play script.
- [ ] Set `secure` and `httponly` flags on cookies (`setcookie` calls in `src/handlers.php`).
- [ ] Consider adding rate limiting to the comment and create endpoints.
- [ ] Back up your MySQL database regularly.

## License

See [LICENSE](LICENSE).
