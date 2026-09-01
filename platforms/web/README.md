# AppGastos Web Wrapper

Este wrapper usa Laravel web tradicional y comparte el codigo principal con `shared`.

## Compartido

- `shared/app`
- `shared/resources`
- `shared/routes`
- `shared/tests`
- la mayor parte de `shared/config`
- `shared/bootstrap`
- `shared/public`
- `shared/database/factories`
- `shared/database/migrations`
- `shared/database/seeders`

## Propio de Web

- `composer.json`
- `.env`
- `vendor`
- `storage`
- `bootstrap/cache`
- `database/database.sqlite`
- `node_modules`

## Produccion

La web se ejecuta en Vercel mediante `api/server.php` y almacena los datos en Neon PostgreSQL. Las variables secretas se configuran en Vercel; no se deben copiar a este directorio ni versionar.

URL publica: `https://appgastos-lilac.vercel.app`

Comprobacion previa al despliegue:

```powershell
php artisan test
npm run build
vercel deploy
```

## Regenerar enlaces

```powershell
powershell -ExecutionPolicy Bypass -File ..\..\scripts\setup-web-wrapper.ps1
```
