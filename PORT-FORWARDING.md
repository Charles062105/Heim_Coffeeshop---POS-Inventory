# Using VS Code Port Forwarding

This guide explains how to open the coffee shop system from a browser when the
application is running in a VS Code remote environment, such as a Dev
Container, SSH host, or Codespace. Port forwarding makes a remote development
server available through a browser URL; it does not deploy the application.

## 1. Start the application

Open the project folder in VS Code and use the integrated terminal.

1. Make sure PHP dependencies are installed with `composer install`.
2. Make sure the project `.env` is configured and its database is reachable.
3. Build the frontend assets so the app does not depend on the Vite dev server:

   ```powershell
   npm.cmd install
   npm.cmd run build
   ```

   If dependencies are already installed, skip the install command.
4. Start Laravel on port `8000`:

   ```powershell
   php artisan serve --host=0.0.0.0 --port=8000
   ```

   Keep this terminal running. In a remote container or host, binding to
   `0.0.0.0` lets VS Code reach the development server through its forwarding
   tunnel.

## 2. Forward port 8000 in VS Code

1. Open the **Ports** view:
   - Select **View > Terminal**, then open the **Ports** tab; or
   - Open the Command Palette (`Ctrl+Shift+P`) and run **Ports: Focus on Ports View**.
2. If port `8000` is not already listed, select **Forward a Port** and enter
   `8000`.
3. Wait for the forwarded port to show as active.
4. Select **Open in Browser** next to port `8000`, or copy its **Forwarded
   Address** into a browser.
5. Sign in using an existing application account.

The forwarded address may differ between sessions. Use the address shown by
VS Code rather than assuming it will always be the same.

## 3. Choose who can access the forwarded port

Keep the port **Private** unless another person specifically needs access.
Private forwarding is limited to your VS Code account/session. If collaboration
is required, use VS Code's port visibility control to share access only with
the intended people, when that option is available in your environment.

Do not make this development POS publicly accessible for real transactions.
Port forwarding is not production hosting: it does not provide production
hardening, availability, or protection for test data and credentials. Never
use real customer, payment, or business data in a shared development instance.

## 4. Local business deployment / safe production settings

This project is intended to run as a local business-machine deployment, not as a
publicly hosted web app. Use the local host URL that matches the machine and keep
all access private unless a client-specific LAN deployment has been explicitly
approved.

When adjusting environment values, preserve the existing `.env` and keep a backup
before changing anything. After editing `.env`, always clear and rebuild the
Laravel config cache so the runtime reflects the new values:

```powershell
php artisan config:clear
php artisan config:cache
```

For the intended operational deployment, ensure:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` matches the local application URL
- production assets are built with `npm.cmd run build`
- no debugging or stack traces are exposed to end users

Do not expose the application on a public network without additional security
hardening, firewall rules, TLS, and an explicit client approval for that
deployment architecture.

## 5. Stop forwarding

## 4. Stop forwarding

When finished, stop `php artisan serve` with `Ctrl+C`. In the **Ports** view,
select **Stop Forwarding Port** for port `8000` if it remains listed.

## Troubleshooting

- **Port 8000 is not listed:** Start the Laravel server first, then use
  **Forward a Port** and enter `8000` manually.
- **The forwarded page does not load:** Check that the server terminal still
  shows Laravel listening on port `8000`. In a remote environment, use the
  `--host=0.0.0.0` option shown above.
- **The page loads without styling or scripts:** Re-run `npm.cmd run build`,
  then refresh the forwarded page. The normal production build outputs
  `public/build` assets and does not require forwarding Vite's port.
- **The browser shows a database or application error:** Port forwarding only
  exposes the web server. Check `.env`, database availability, and Laravel's
  application logs; do not add database ports to the public forwarding list.
- **You are running the project directly on your Windows computer:** Open
  `http://127.0.0.1:8000` locally after starting `php artisan serve`. VS Code
  remote port forwarding is intended for servers running in a remote VS Code
  environment.

For deployment, package the system as an application, create separate accounts
for each branch, keep branch reports and stock separate, and provide combined
sales totals across branches.
