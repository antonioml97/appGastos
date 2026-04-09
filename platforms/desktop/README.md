# AppGastos Desktop Wrapper

Este wrapper usa `nativephp/desktop` y comparte el codigo principal de la app con `shared`.

## Que es compartido

- `shared/app`
- `shared/resources`
- `shared/routes`
- `shared/tests`
- la mayor parte de `shared/config`
- `shared/bootstrap/app.php`
- `shared/public`
- migraciones, factories y seeders de `shared/database`
- `shared/artisan`, `shared/package.json`, `shared/vite.config.js`, `shared/phpunit.xml`

## Que es propio del wrapper Desktop

- `composer.json`
- `.env`
- `vendor`
- `nativephp`
- `storage`
- `bootstrap/cache`
- `database/database.sqlite`
- `config/nativephp.php` cuando se publique para Desktop

## Regenerar enlaces compartidos

```powershell
powershell -ExecutionPolicy Bypass -File ..\..\scripts\setup-desktop-wrapper.ps1
```

## Preparar dependencias Desktop

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan native:install
```
