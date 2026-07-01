# Zalo Mini App Game Builder

## Project Structure

- `src/`: Zalo Mini App frontend runtime
- `backend/`: Laravel backend for platform APIs and future management surfaces
- `docker-compose.yml`: local backend infrastructure with PostgreSQL
- `openspec/`: change proposals, design notes, specs, and tasks

## Backend Development

1. Install backend dependencies:
   ```bash
   cd backend
   composer install
   npm install
   npm run build
   ```
2. Start the local backend infrastructure from the project root:
   ```bash
   docker compose up -d --build
   ```
3. Run migrations and seed the baseline platform users:
   ```bash
   docker compose exec app php artisan migrate --seed
   ```
4. Open the Laravel backend at `http://localhost:8080`.

### Default local identities

- Platform admin: `admin@example.com`
- Workspace owner: `owner@example.com`
- Shared local password: `password`

### Database defaults

- Database: `game_builder`
- Username: `game_builder`
- Password: `secret`
- Host port: `5433`

## Mini App Frontend Development

### Using Zalo Mini App Extension

1. Install [Visual Studio Code](https://code.visualstudio.com/download) and [Zalo Mini App Extension](https://mini.zalo.me/docs/dev-tools).
2. In the **Home** tab, complete **Config App ID** and **Install Dependencies**.
3. Navigate to the **Run** tab, select the suitable launcher, and click **Start**.

### Using Zalo Mini App CLI

1. [Install Node JS](https://nodejs.org/en/download/).
2. [Install Zalo Mini App CLI](https://mini.zalo.me/docs/dev-tools/cli/intro/).
3. Move to the mini app root folder:
   ```bash
   cd /path/to/app-quay-may-man
   ```
   Do not run `zmp` commands inside `backend/`, because that folder is the Laravel API, not the Zalo Mini App project.
4. **Install dependencies**:
   ```bash
   npm install
   ```
5. **Start** the dev server:
   ```bash
   zmp start
   ```
6. **Open** `localhost:3000` in your browser.

## Deployment

1. **Create** a mini program. For instructions on how to create a mini program, please refer to the [Coffee Shop Tutorial](https://mini.zalo.me/tutorial/coffee-shop/step-1/).
2. **Build** the frontend assets from the mini app root:
   ```bash
   npm run build
   ```
   This generates the `www/` folder and updates `www/app-config.json` with the built CSS and JS assets required by ZMP deploy.
3. **Deploy** your mini program to Zalo using the mini app ID created.

   - **Using Zalo Mini App Extension**: navigate to the **Deploy** panel > **Login** > **Deploy**.
   - **Using Zalo Mini App CLI**:
     ```bash
     zmp login
     zmp deploy
     ```
     When ZMP CLI asks for the dist folder, use `www` from the project root.

4. Open the mini app in Zalo by scanning the QR code.

## Resources

- [Zalo Mini App Official Website](https://mini.zalo.me/)
- [ZaUI Documentation](https://mini.zalo.me/documents/zaui/)
- [ZMP SDK Documentation](https://mini.zalo.me/documents/api/)
- [Laravel Documentation](https://laravel.com/docs)
- [DevTools Documentation](https://mini.zalo.me/docs/dev-tools/)
- [Ready-made Mini App Templates](https://mini.zalo.me/zaui-templates)
- [Community Support](https://mini.zalo.me/community)
