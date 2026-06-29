# Zalo Mini App

## Project Structure

- `src/`: Zalo Mini App frontend
- `backend/`: Laravel admin and API backend
- `docker-compose.yml`: local backend infrastructure with PostgreSQL

## Development

### Backend Admin/API

1. Install PHP dependencies for the backend:
   ```bash
   cd backend
   composer install
   npm install
   npm run build
   ```
2. Return to the project root and start backend infrastructure:
   ```bash
   docker compose up -d --build
   ```
3. Run Laravel migrations inside the PHP container:
   ```bash
   docker compose exec app php artisan migrate
   ```
4. Open the admin backend at `http://localhost:8080`.
   PostgreSQL is exposed on host port `5433` to avoid conflicts with any local database already using `5432`.

### Mini App Frontend

### Using Zalo Mini App Extension

1. Install [Visual Studio Code](https://code.visualstudio.com/download) and [Zalo Mini App Extension](https://mini.zalo.me/docs/dev-tools).
1. In the **Home** tab, process **Config App ID** and **Install Dependencies**.
1. Navigate to the **Run** tab, select the suitable launcher, and click **Start**.

### Using Zalo Mini App CLI

1. [Install Node JS](https://nodejs.org/en/download/).
1. [Install Zalo Mini App CLI](https://mini.zalo.me/docs/dev-tools/cli/intro/).
1. **Install dependencies**:
   ```bash
   npm install
   ```
1. **Start** the dev server:
   ```bash
   zmp start
   ```
1. **Open** `localhost:3000` in your browser.

## Deployment

1. **Create** a mini program. For instructions on how to create a mini program, please refer to the [Coffee Shop Tutorial](https://mini.zalo.me/tutorial/coffee-shop/step-1/)

1. **Deploy** your mini program to Zalo using the mini app ID created.

   - **Using Zalo Mini App Extension**: navigate to the **Deploy** panel > **Login** > **Deploy**.
   - **Using Zalo Mini App CLI**:
     ```bash
     zmp login
     zmp deploy
     ```

1. Open the mini app in Zalo by scanning the QR code.

## Resources

- [Zalo Mini App Official Website](https://mini.zalo.me/)
- [ZaUI Documentation](https://mini.zalo.me/documents/zaui/)
- [ZMP SDK Documentation](https://mini.zalo.me/documents/api/)
- [Laravel Documentation](https://laravel.com/docs)
- [DevTools Documentation](https://mini.zalo.me/docs/dev-tools/)
- [Ready-made Mini App Templates](https://mini.zalo.me/zaui-templates)
- [Community Support](https://mini.zalo.me/community)
# zalo-mini-app
