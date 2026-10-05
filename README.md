# Aprende Guate

Plataforma de aprendizaje adaptativo con IA. Cada estudiante responde un diagnóstico, recibe una ruta personalizada por fases y temas, genera lecciones y prácticas bajo demanda, resuelve retos evaluados por IA y puede solicitar mentorías con personas instructoras.

## Stack

- **Backend:** Laravel 13 (PHP 8.3+), colas en base de datos, scheduler.
- **Frontend:** Blade, Tailwind CSS 3, Alpine.js y Vite.
- **Autenticación:** Laravel Breeze, verificación de correo, verificación en dos pasos (TOTP) e inicio de sesión con Google (Socialite).
- **Roles:** `spatie/laravel-permission` con los roles `Admin`, `Instructor` y `Student`.
- **IA:** proveedor compatible con la API de OpenAI mediante `App\AI\AiGateway` (ver [docs/ai-orchestration.md](docs/ai-orchestration.md)).

## Requisitos

- PHP 8.3 o superior con las extensiones habituales de Laravel.
- Composer y Node.js 20 o superior.
- MySQL (por ejemplo, con Laragon) o SQLite.

## Instalación local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configura la base de datos en `.env` (para Laragon: `DB_CONNECTION=mysql`) y luego:

```bash
php artisan migrate --seed
php artisan storage:link
```

El seeder crea los roles y tres usuarios de prueba con la contraseña `password`: `admin@example.com`, `instructor@example.com` y `student@example.com`. **No ejecutes los seeders en producción.**

## Ejecución

La aplicación necesita cuatro procesos:

| Proceso | Comando |
|---|---|
| Servidor web | `php artisan serve` |
| Assets (desarrollo) | `npm run dev` |
| Worker de colas | `php artisan queue:work database --queue=ai,default --tries=1 --timeout=120` |
| Scheduler (recordatorios de mentoría) | `php artisan schedule:work` |

En Windows, `iniciar-local-admin.bat` abre los cuatro en ventanas separadas. **El worker es obligatorio:** la generación de rutas, lecciones, prácticas y retos, y la evaluación de evidencias, se procesan en la cola `ai`. Después de cambiar código, reinicia el worker (`php artisan queue:restart`).

## Inteligencia artificial

Variables principales en `.env`:

| Variable | Uso |
|---|---|
| `OPENAI_API_KEY` / `AI_API_KEY` | Credencial del proveedor. Sin ella, la plataforma usa contenido de respaldo. |
| `AI_BASE_URL` | URL base compatible con la API de OpenAI. |
| `AI_MODEL` | Modelo por defecto (`gpt-4o-mini`). |
| `AI_CONNECT_TIMEOUT`, `AI_GENERATION_TIMEOUT`, `AI_RETRY_TIMES` | Tiempos de espera y reintentos HTTP. |
| `DB_QUEUE_RETRY_AFTER` | Debe superar el timeout de los jobs de IA (ver `AiGateway::jobTimeout`). |

Desde **Administración → Configuración IA** se ajustan, sin tocar `.env`:

- los límites de consultas al tutor y de generación de contenido (por minuto y por día);
- el presupuesto total de tokens: al alcanzarlo, el gateway deja de llamar al proveedor;
- la longitud del diagnóstico.

En **Administración → Orquestador IA** se activa o desactiva cada agente y se ajusta su modelo o límite de tokens.

## Estructura

```
app/AI/                  Gateway, catálogo de agentes, guardas de salida y proveedores
app/Services/Learning/   Servicios de IA por responsabilidad: diagnóstico, arquitectura
                         de rutas, contenido por tema y retos (generación y evaluación)
app/Services/            Tutor IA, perfilador profesional, guía, 2FA y correo
app/Jobs/                Generación y evaluación con IA en segundo plano (cola "ai")
app/Http/Controllers/    Learning/ (estudiante), mentor y Admin/
app/Policies/            Autorización por propiedad de cada recurso
config/ai.php            Proveedores, agentes y límites de tokens
config/office_curriculum.php  Ruta predefinida para metas de ofimática
```

La aplicación tiene tres espacios de trabajo según el rol: **aprendizaje** (todas las personas), **mentor** (`Instructor`) y **administración** (`Admin`). Se alternan desde el menú de perfil.

## Pruebas

```bash
php artisan test
```

Las pruebas usan SQLite en memoria y nunca llaman a un proveedor de IA real: `phpunit.xml` define credenciales falsas y `Tests\TestCase` bloquea cualquier petición HTTP no simulada.
