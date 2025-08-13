# Single-File Pastebin (Refactored)

This project is a simple, self-hosted pastebin application, originally written as a single PHP file. This version has been refactored into a more structured and maintainable format, separating concerns and making it easier to extend.

## Features

- **Create, View, and Delete Pastes**: Core pastebin functionality.
- **Syntax Highlighting**: Automatic language detection and highlighting using `highlight.js`.
- **Anonymous Comments**: Users can leave comments on pastes.
- **Self-Hosted**: Run it on your own PHP-enabled web server.
- **Automatic Database Setup**: The application automatically creates the necessary database and tables on the first run.
- **Delete Tokens**: Pastes can be deleted using a token provided at creation time (or via a cookie for the same browser session).

## Tech Stack

- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: Tailwind CSS (via CDN), highlight.js (via CDN)

## Project Structure

The project has been organized into the following structure:

```
.
├── config/
│   ├── database.example.php  // Example configuration
│   └── database.php          // Your local configuration (ignored by Git)
├── public/
│   ├── css/
│   │   └── style.css         // Custom styles
│   ├── js/
│   │   └── main.js           // Custom JavaScript
│   └── index.php             // Front Controller (all requests go here)
├── src/
│   ├── Database.php          // Handles DB connection and initialization
│   ├── actions.php           // Handles all POST form submissions
│   └── helpers.php           // Helper functions
├── templates/
│   ├── partials/             // Reusable template partials (header, footer, etc.)
│   ├── home.php              // Template for the homepage
│   ├── layout.php            // Main layout/skeleton for all pages
│   └── view_paste.php        // Template for viewing a single paste
├── .gitignore
├── LICENSE
└── README.md
```

## Installation

1.  **Clone the Repository**:
    ```bash
    git clone <repository-url>
    cd <repository-directory>
    ```

2.  **Configure Database**:
    -   Rename the `config/database.example.php` file to `config/database.php`.
    -   Open `config/database.php` and edit the following lines with your MySQL database credentials:
        ```php
        define('DB_HOST', '127.0.0.1');
        define('DB_USER', 'your_db_user');
        define('DB_PASS', 'your_db_password');
        define('DB_NAME', 'pastebin_app');
        ```
    - The application will attempt to create the database (`pastebin_app` by default) if it does not exist. Your database user needs `CREATE DATABASE` privileges for this to work.

3.  **Configure Web Server**:
    -   Point your web server's document root to the `public/` directory. This is important for security, as it prevents direct access to your source files, configuration, and templates.

    -   **For Apache**: Your virtual host configuration might look something like this:
        ```apache
        <VirtualHost *:80>
            ServerName your-domain.com
            DocumentRoot /path/to/your/project/public

            <Directory /path/to/your/project/public>
                AllowOverride All
                Require all granted
            </Directory>
        </VirtualHost>
        ```
        You may also need to enable `mod_rewrite` if you decide to implement URL rewriting later.

    -   **For Nginx**: Your server block might look like this:
        ```nginx
        server {
            listen 80;
            server_name your-domain.com;
            root /path/to/your/project/public;
            index index.php;

            location / {
                try_files $uri $uri/ /index.php?$query_string;
            }

            location ~ \.php$ {
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/var/run/php/php8.x-fpm.sock; // Adjust to your PHP-FPM version
            }
        }
        ```

4.  **Visit the Application**:
    -   Open your web browser and navigate to your domain. The application should be running, and the database and tables will be created automatically.

## License

This project is licensed under the terms of the [LICENSE](LICENSE) file.
