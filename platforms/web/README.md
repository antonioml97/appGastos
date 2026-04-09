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

## Regenerar enlaces

```powershell
powershell -ExecutionPolicy Bypass -File ..\..\scripts\setup-web-wrapper.ps1
```
