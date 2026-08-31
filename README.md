# SignaDocs

Aplicación de gestión y firma de documentos con chat de inteligencia artificial integrado. Permite subir, resumir y firmar documentos, mantener conversaciones con IA y gestionar usuarios con roles de administrador.

## Requisitos previos

- PHP 8.3 o superior
- [Composer](https://getcomposer.org/)
- Node.js 18+ y npm
- SQLite (configuración por defecto)

## Instalación

Clona el repositorio y ejecuta el script de instalación:

```bash
git clone <url-del-repositorio>
cd docs-tp
composer setup
```

Este comando ejecuta automáticamente:
1. Instala dependencias de PHP y Node.js
2. Copia `.env.example` a `.env` (si no existe)
3. Genera la clave de la aplicación
4. Ejecuta las migraciones de la base de datos
5. Compila los assets del frontend

## Variables de entorno

Edita el archivo `.env` con tu configuración. Las variables más importantes:

| Variable | Descripción |
|---|---|
| `GEMINI_API_KEY` | Clave de API de Google Gemini (requerida para resúmenes de documentos con IA) |
| `DOCUMENT_SUMMARY_MODEL` | Modelo de IA para resúmenes (por defecto: `gemini-2.0-flash`) |
| `APP_URL` | URL base de la aplicación (por defecto: `http://localhost:8000`) |
| `DB_CONNECTION` | Conexión de base de datos (por defecto: `sqlite`) |

## Ejecución

Para iniciar el servidor de desarrollo completo (artisan, vite, queue worker y pail):

```bash
composer dev
```

La estará disponible en `http://localhost:8000`.

## Desarrollo

### Comandos de PHP

```bash
composer lint              # Formatear código PHP con Pint
composer lint:check        # Verificar estilo de código
composer types:check       # Análisis estático con PHPStan (nivel 7)
composer test              # Ejecutar lint + análisis estático + tests PHPUnit
```

### Comandos de Frontend

```bash
npm run dev                # Servidor de desarrollo Vite
npm run build              # Compilar para producción
npm run lint:check         # Verificar código JavaScript/TypeScript
npm run format:check       # Verificar formato con Prettier
npm run types:check        # Verificar tipos TypeScript
```

### Tests

```bash
php artisan test --compact                    # Ejecutar todos los tests
php artisan test --compact tests/Feature/     # Tests de un archivo específico
php artisan test --compact --filter=nombre    # Filtrar por nombre de test
```

## Funcionalidades

- **Autenticación**: Login, registro, recuperación de contraseña, passkeys y autenticación de dos factores (2FA)
- **Gestión de documentos**: Subida, descarga, firma digital y resúmenes con IA (Google Gemini)
- **Chat con IA**: Conversaciones asistidas por inteligencia artificial
- **Panel de administración**: Creación de usuarios y documentos con roles
- **Diseño responsivo**: Interfaz con Tailwind CSS v4 y componentes Radix UI

## Estructura del proyecto

```
├── app/                 # Lógica de la aplicación (Controllers, Models, Services)
├── bootstrap/           # Bootstrapping de Laravel
├── config/              # Archivos de configuración
├── database/            # Migrations, factories y seeders
├── docs/                # Documentación adicional
├── public/              # Assets públicos (entry point)
├── resources/           # Frontend (React, CSS, Blade views)
│   ├── js/              # Componentes React y páginas Inertia
│   └── views/           # Plantillas Blade
├── routes/              # Definición de rutas
├── storage/             # Archivos subidos, logs, cache
└── tests/               # Tests PHPUnit
```

## Tecnologías

- **Backend**: Laravel 13, PHP 8.3, Laravel Fortify (auth), Laravel Wayfinder (rutas tipadas)
- **Frontend**: React 19, Inertia.js v3, Tailwind CSS v4, Vite 8
- **IA**: Prism PHP, Google Gemini
- **Herramientas**: PHPStan (análisis estático), Pint (formato), PHPUnit (tests)

## Licencia

MIT
