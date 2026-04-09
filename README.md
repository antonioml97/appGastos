# AppGastos

Repositorio organizado con una base compartida y tres plataformas:

- `shared`: codigo comun de la aplicacion
- `platforms/web`: version web Laravel
- `platforms/android`: version mobile con NativePHP Mobile
- `platforms/desktop`: version escritorio con NativePHP Desktop

## Como esta organizado

### `shared`

Aqui vive la logica comun de la app:

- controladores
- modelos
- rutas
- vistas
- componentes Vue
- migraciones
- tests

Si cambias funcionalidad de la aplicacion, normalmente debes hacerlo en `shared`.

### `platforms/web`

Wrapper de la plataforma web. Tiene su propio entorno, dependencias y base de datos, pero reutiliza el codigo comun de `shared`.

### `platforms/android`

Wrapper de la plataforma mobile. Usa `nativephp/mobile` y mantiene su propia configuracion nativa dentro de `nativephp/`.

### `platforms/desktop`

Wrapper de la plataforma escritorio. Usa `nativephp/desktop` para abrir la app como ejecutable y generar la version `.exe`.

## Requisitos generales

Antes de abrir cualquier plataforma necesitas tener instalado:

- PHP 8.3 o superior
- Composer
- Node.js
- NPM

Ademas:

- para Android: Android Studio o toolchain Android configurado
- para Desktop: entorno compatible con NativePHP Desktop en Windows

## Regla rapida

- cambias la app: `shared`
- abres la web: `platforms/web`
- abres mobile: `platforms/android`
- abres desktop: `platforms/desktop`

## Instalacion y apertura por plataforma

### Web

Carpeta:

- `C:\Users\genericoRS\Desktop\AppGastos\platforms\web`

#### Instalar

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\web
composer install
copy .env.example .env
php artisan key:generate
if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite | Out-Null }
php artisan migrate
npm install
```

Tambien puedes usar el script rapido:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\web
composer run setup
```

#### Abrir

Para abrir la version web en desarrollo:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\web
composer run dev
```

Eso levanta Laravel, cola, logs y Vite.

Si solo quieres el frontend:

```powershell
npm run dev
```

### Android / Mobile

Carpeta:

- `C:\Users\genericoRS\Desktop\AppGastos\platforms\android`

#### Instalar

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\android
composer install
copy .env.example .env
php artisan key:generate
if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite | Out-Null }
php artisan migrate
npm install
```

Tambien puedes usar:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\android
composer run setup
```

#### Abrir

Para abrir la app mobile en desarrollo:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\android
php artisan native:run android
```

Comandos utiles:

```powershell
php artisan native:version
php artisan native:package android
php native run android
```

### Desktop

Carpeta:

- `C:\Users\genericoRS\Desktop\AppGastos\platforms\desktop`

#### Instalar

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\desktop
composer install
copy .env.example .env
php artisan key:generate
if (!(Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite | Out-Null }
php artisan migrate
npm install
php artisan native:install
```

Tambien puedes usar el setup del wrapper:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\desktop
composer run setup
```

#### Abrir

Para abrir la app de escritorio en desarrollo:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\desktop
php artisan native:run
```

Si quieres abrir el flujo con Vite en paralelo:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\desktop
composer run native:dev
```

Para generar build de escritorio:

```powershell
php artisan native:build win x64
php artisan native:publish win x64
```

## Regenerar wrappers

Si los enlaces a `shared` se rompen o quieres reconstruir una plataforma:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-web-wrapper.ps1
powershell -ExecutionPolicy Bypass -File scripts/setup-android-wrapper.ps1
powershell -ExecutionPolicy Bypass -File scripts/setup-desktop-wrapper.ps1
```

## Punto de partida recomendado

1. Cambia la funcionalidad en `shared`.
2. Instala la plataforma que quieras usar dentro de su carpeta en `platforms`.
3. Abrela con el comando correspondiente de esa misma carpeta.
