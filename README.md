# Kinetodesk.

Kinetodesk. is a full-stack business management web application built with a Laravel backend and a React + Vite frontend. It provides a clean dashboard experience for managing operational data such as customers, employees, categories, and related business records.

## Live Website

Live Demo: **[Click here](https://kinetodesk.inamullahmd.com/)**

## Tech Stack

### Frontend

- React
- Vite
- TypeScript
- Axios
- React Router
- Tailwind CSS

### Backend

- Laravel
- PHP
- MySQL
- Laravel Migrations
- Laravel Seeders
- REST API

### Deployment

- GitHub Actions
- SSH-based deployment
- Automated frontend build
- Automated backend deployment

## Project Structure

```txt
kinetodesk/
├── client/   # React Vite frontend
├── server/   # Laravel backend
└── .github/
    └── workflows/
        └── deploy.yml
```

## Features

- Responsive dashboard interface
- REST API powered by Laravel
- MySQL database integration
- Customer management
- Employee management
- Category management
- Database migrations and seeders
- Separate frontend and backend architecture
- Production-ready deployment workflow

## Local Setup

### 1. Clone the repository

```bash
git clone <repository-url>
cd <repository-folder>
```

### 2. Set up the Laravel backend

```bash
cd server
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

By default, the Laravel API will run locally at:

```txt
http://127.0.0.1:8000
```

### 3. Set up the React frontend

Open a new terminal:

```bash
cd client
npm install
npm run dev
```

By default, the frontend will run locally at:

```txt
http://localhost:5173
```

### 4. Configure frontend environment variables

Create a `.env` file inside the `client` folder:

```env
VITE_API_URL=http://127.0.0.1:8000
```

For production, set `VITE_API_URL` to the deployed backend API base URL.

## Environment Variables

### Frontend

The frontend uses the following variable:

```env
VITE_API_URL=
```

Example for local development:

```env
VITE_API_URL=http://127.0.0.1:8000
```

### Backend

The Laravel backend uses a standard `.env` configuration file.

Common backend variables include:

```env
APP_NAME=Kinetodesk
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Do not commit production `.env` files to the repository.

## API Client Configuration

The frontend should read the API base URL from the Vite environment variable:

```ts
import axios from 'axios'

const apiBaseUrl = import.meta.env.VITE_API_URL || ''

const apiClient = axios.create({
  baseURL: `${apiBaseUrl}/api`,
  headers: {
    Accept: 'application/json',
  },
})

export default apiClient
```

## Useful Commands

### Run backend locally

```bash
cd server
php artisan serve
```

### Run frontend locally

```bash
cd client
npm run dev
```

### Run migrations

```bash
cd server
php artisan migrate
```

### Run seeders

```bash
cd server
php artisan db:seed
```

### Reset and seed database

```bash
cd server
php artisan migrate:fresh --seed
```

### Build frontend

```bash
cd client
npm run build
```

### Clear Laravel cache

```bash
cd server
php artisan optimize:clear
```

## Frontend SPA Routing

For production environments that serve the React app as a single-page application, unknown frontend routes should fall back to `index.html`.

Create this file:

```txt
client/public/.htaccess
```

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d

  RewriteRule ^ index.html [L]
</IfModule>
```

Vite will copy this file into the production build output.

## Production Notes

- The frontend and backend are deployed separately.
- The frontend build is generated from the `client` directory.
- The Laravel backend is deployed from the `server` directory.
- The production Laravel `.env` file should be created directly on the server.
- The `storage/` directory and production `.env` file should not be overwritten during deployment.
- Seeders should be run manually unless the project is using a disposable demo database.
- Sensitive server paths, domains, credentials, and private keys should never be committed to the repository.

## License

This project is for portfolio and demonstration purposes.
