# AppGastos

AppGastos es una aplicacion de control de gastos personales construida sobre una base compartida y preparada para ejecutarse en tres plataformas:

- Web
- Android / mobile
- Desktop / Windows

La idea central del proyecto es sencilla: la aplicacion real vive en `shared`, y cada plataforma tiene su propio wrapper dentro de `platforms`.

## Vista rapida

| Area | Estado actual |
| --- | --- |
| Arquitectura | `shared + platforms` |
| Frontend | Vue 3 + Vite + Tailwind CSS 4 |
| Backend | Laravel 12 + PHP 8.3 |
| Base de datos | SQLite por plataforma |
| Mobile | NativePHP Mobile 3 |
| Desktop | NativePHP Desktop 2.1 |

## Que incluye ahora mismo

El proyecto ya incorpora estas funcionalidades:

- panel principal
- gestion de categorias
- gastos mensuales
- ingresos mensuales
- resumen mensual
- resumen anual
- cuentas normales
- cuentas de ahorro
- retiradas desde ahorro
- gastos fijos mensuales
- ingresos fijos mensuales
- omision de movimientos fijos en un mes concreto
- exportacion a Excel
- importacion desde Excel generado por la propia app
- categorias base sincronizadas desde `shared/config.json`
- mensajes de validacion legibles para usuario final

## Arquitectura del repositorio

```text
AppGastos/
|- shared/
|- platforms/
|  |- web/
|  |- android/
|  |- desktop/
|- scripts/
|- README.md
```

### `shared`

Aqui vive la aplicacion real:

- controladores
- modelos
- rutas
- servicios de dominio
- componentes Vue
- vistas Blade
- migraciones
- configuracion compartida
- tests

Si vas a tocar logica, comportamiento, validaciones, pantallas o estructura funcional, lo normal es trabajar en `shared`.

### `platforms/web`

Wrapper de la version web.

Tiene su propio:

- `.env`
- `vendor`
- `node_modules`
- `storage`
- `database/database.sqlite`

### `platforms/android`

Wrapper mobile basado en NativePHP Mobile.

Incluye:

- entorno Laravel propio
- proyecto Android nativo en `nativephp/android`
- SQLite propia
- bundle Laravel empaquetado dentro del APK

### `platforms/desktop`

Wrapper de escritorio basado en NativePHP Desktop.

Incluye:

- entorno Laravel propio
- runtime desktop
- SQLite propia
- build para Windows `.exe`

## Regla de trabajo recomendada

Usa esta guia rapida:

1. Implementa la funcionalidad en `shared`.
2. Pruebala primero en `platforms/web`.
3. Migra la plataforma que vayas a ejecutar.
4. Valida despues en `android` o `desktop` si el cambio tambien les afecta.

## Funcionalidades principales

### Inicio

- acceso rapido a las secciones clave
- panel principal de navegacion

### Categorias

- crear categoria
- editar categoria
- borrar categoria
- selector de color
- selector de icono
- categorias base por defecto desde configuracion compartida

### Gastos e ingresos mensuales

- alta, edicion y borrado de gastos
- alta, edicion y borrado de ingresos
- mensajes de error entendibles
- resumen del mes
- balance del mes
- desglose por categoria

### Resumen anual

- resumen por ano
- comparativa mensual
- distribucion por categoria

### Cuentas

- cuentas normales
- cuentas de ahorro
- retiradas desde ahorro
- persistencia mensual del saldo de cuenta normal

La cuenta normal ya no depende solo de un saldo global: el proyecto guarda el comportamiento mensual para que cada mes pueda apoyarse en el cierre del anterior y aplicar bien ingresos y gastos del mes actual.

### Movimientos fijos mensuales

- crear gastos fijos
- crear ingresos fijos
- editar movimientos fijos
- activar o desactivar movimientos fijos
- generar automaticamente los movimientos del mes
- omitir un movimiento fijo solo en un mes concreto si se borra desde el mes

### Configuracion compartida

- categorias base cargadas desde `shared/config.json`
- sincronizacion reutilizable entre plataformas

### Excel

- exportacion de datos
- importacion desde archivos exportados por la propia app

## Base de datos

Cada wrapper usa su propia base SQLite.

Eso significa que las migraciones deben ejecutarse por separado en:

- `platforms/web`
- `platforms/android`
- `platforms/desktop`

Entre las migraciones actuales ya existen tablas o relaciones para:

- categorias
- gastos
- ingresos
- cuentas
- movimientos fijos
- excepciones mensuales de movimientos fijos
- saldos mensuales de cuentas normales

## Requisitos

Necesitas como base:

- PHP 8.3 o superior
- Composer
- Node.js
- NPM

Ademas:

- Android Studio o toolchain Android para la version mobile
- entorno compatible con NativePHP Desktop para la version Windows

## Instalacion por plataforma

### Web

Directorio de trabajo:

`C:\Users\genericoRS\Desktop\AppGastos\platforms\web`

Instalacion:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\web
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
```

Abrir en desarrollo:

```powershell
php artisan serve
```

En otra terminal:

```powershell
npm run dev
```

Build:

```powershell
npm run build
```

### Android / Mobile

Directorio de trabajo:

`C:\Users\genericoRS\Desktop\AppGastos\platforms\android`

Instalacion:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\android
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
```

Abrir en desarrollo:

```powershell
php artisan native:run android --build=debug
```

### APK debug actualizado

Si quieres sacar un APK debug para instalarlo manualmente:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\android
php artisan optimize:clear
npm run build
php artisan native:run android --build=debug
cd nativephp\android
.\gradlew.bat assembleDebug
```

APK generado:

`C:\Users\genericoRS\Desktop\AppGastos\platforms\android\nativephp\android\app\build\outputs\apk\debug\app-debug.apk`

Importante:

- `.\gradlew.bat assembleDebug` por si solo no siempre basta
- primero hay que regenerar el bundle Laravel que entra en Android
- la forma segura es ejecutar antes `php artisan native:run android --build=debug`

### Release Android firmada

Si algun dia quieres paquete firmado para distribucion, `php artisan native:package android` exige configuracion de firma:

- `ANDROID_KEYSTORE_FILE`
- `ANDROID_KEYSTORE_PASSWORD`
- `ANDROID_KEY_ALIAS`
- `ANDROID_KEY_PASSWORD`

### Desktop

Directorio de trabajo:

`C:\Users\genericoRS\Desktop\AppGastos\platforms\desktop`

Instalacion:

```powershell
cd C:\Users\genericoRS\Desktop\AppGastos\platforms\desktop
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
php artisan native:install
```

Abrir en desarrollo:

```powershell
php artisan native:run
```

Build y publicacion:

```powershell
php artisan native:build win x64
php artisan native:publish win x64
```

## Scripts de soporte

Si necesitas reconstruir wrappers o reestablecer enlaces con `shared`:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/setup-web-wrapper.ps1
powershell -ExecutionPolicy Bypass -File scripts/setup-android-wrapper.ps1
powershell -ExecutionPolicy Bypass -File scripts/setup-desktop-wrapper.ps1
```

Estos scripts sirven para volver a enlazar recursos compartidos como:

- codigo base
- configuracion comun
- `config.json`

## Archivos importantes

- [README.md](C:\Users\genericoRS\Desktop\AppGastos\README.md)
- [shared](C:\Users\genericoRS\Desktop\AppGastos\shared)
- [shared/config.json](C:\Users\genericoRS\Desktop\AppGastos\shared\config.json)
- [shared/routes/web.php](C:\Users\genericoRS\Desktop\AppGastos\shared\routes\web.php)
- [shared/app/Http/Controllers](C:\Users\genericoRS\Desktop\AppGastos\shared\app\Http\Controllers)
- [shared/app/Models](C:\Users\genericoRS\Desktop\AppGastos\shared\app\Models)
- [shared/resources/js/components](C:\Users\genericoRS\Desktop\AppGastos\shared\resources\js\components)
- [platforms/web](C:\Users\genericoRS\Desktop\AppGastos\platforms\web)
- [platforms/android](C:\Users\genericoRS\Desktop\AppGastos\platforms\android)
- [platforms/desktop](C:\Users\genericoRS\Desktop\AppGastos\platforms\desktop)
- [scripts](C:\Users\genericoRS\Desktop\AppGastos\scripts)

## Versiones

### Version 1.0

Base inicial del proyecto con:

- categorias
- gastos
- ingresos
- resumen mensual
- estructura original de una sola aplicacion

### Version 2.0

Salto a arquitectura por plataformas:

- `shared + platforms`
- version web
- version mobile
- version desktop
- resumen anual
- mejoras de interfaz
- validaciones mas claras

### Estado funcional actual sobre 2.x

Sobre esa base, el repositorio ya suma tambien:

- exportacion e importacion Excel
- cuentas normales y de ahorro
- movimientos fijos mensuales
- excepciones mensuales para movimientos fijos borrados desde el mes
- categorias base compartidas
- persistencia mensual del saldo de cuenta normal
- mejoras de arranque en mobile

## Git y releases

Si quieres que una version quede visible en GitHub, el flujo normal es:

1. hacer commit
2. crear tag
3. subir la rama
4. subir el tag

Ejemplo:

```powershell
git add .
git commit -m "release: v2.0.0"
git tag -a v2.0.0 -m "AppGastos 2.0"
git push origin main
git push origin v2.0.0
```

Convencion util:

- `2.0.1` para correcciones
- `2.1.0` para nuevas funcionalidades compatibles
- `3.0.0` para cambios grandes o incompatibles

## Nota para Windows

`composer run dev` puede fallar por dependencias como `Pail`, ya que `pcntl` no esta disponible en Windows.

Si pasa eso, usa:

```powershell
php artisan serve
npm run dev
```

## Resumen practico

Si quieres quedarte con una sola idea del proyecto, es esta:

- se desarrolla en `shared`
- cada plataforma corre con su propio entorno y su propia SQLite
- primero se prueba en web
- luego se valida en mobile o desktop
- en Android hay que regenerar bien el bundle antes de sacar APK si quieres ver cambios reales
