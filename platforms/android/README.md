# AppGastos Android Wrapper

Este wrapper usa `nativephp/mobile` y comparte el codigo principal de la app con `shared`.

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

## Propio de Android

- `composer.json`
- `.env`
- `vendor`
- `nativephp`
- `storage`
- `bootstrap/cache`
- `config/nativephp.php`
- `database/database.sqlite`
- `node_modules`

## Regenerar enlaces

```powershell
powershell -ExecutionPolicy Bypass -File ..\..\scripts\setup-android-wrapper.ps1
```
