# Aprende Guate

Plataforma de aprendizaje adaptativo con IA. Cada estudiante responde un diagnóstico, recibe una ruta personalizada por fases y temas, genera lecciones y prácticas bajo demanda, resuelve retos evaluados por IA y puede solicitar mentorías con personas instructoras.

## Stack

- **Backend:** Laravel 13 (PHP 8.3+), colas en base de datos, scheduler.
- **Frontend:** Blade, Tailwind CSS 3, Alpine.js y Vite.
- **Autenticación:** Laravel Breeze, verificación de correo, verificación en dos pasos (TOTP) e inicio de sesión con Google (Socialite).
- **Roles y permisos:** `spatie/laravel-permission`. Cada cuenta puede tener varios roles: `Student` (base de todas), `Instructor` (mentor), `Admin` (super administrador) y roles personalizados con permisos delegados.
- **Bitácora:** `spatie/laravel-activitylog` registra quién hizo cada cambio administrativo.
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
| Worker de colas | `php artisan queue:work database --queue=ai,default --tries=1` (cada trabajo define su propio límite de tiempo; reinícialo después de actualizar el código) |
| Datos de demostración del tablero de IA | `php artisan ai:demo-data --days=21`, solo en una base cuyo nombre contenga «demo» (por ejemplo `DB_DATABASE=proyecto_demo`). Con `--live` sigue registrando solicitudes cada pocos segundos para mostrar el tablero en vivo. |
| Scheduler (recordatorios de mentoría) | `php artisan schedule:work` |

En Windows, el script `iniciar-local-admin.bat` (fuera del repositorio, en la carpeta de documentos del proyecto) abre los cuatro en ventanas separadas. **El worker es obligatorio:** la generación de rutas, lecciones, prácticas y retos, y la evaluación de evidencias, se procesan en la cola `ai`. Después de cambiar código, reinicia el worker (`php artisan queue:restart`).

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

La aplicación tiene tres espacios de trabajo según el rol: **aprendizaje** (todas las personas), **mentor** (`Instructor`) y **administración**. Se alternan desde el menú de perfil.

### Administración y permisos

- El rol `Admin` es el **super administrador**: tiene todos los permisos del panel.
- El rol **Administrador** (creado de fábrica) está un nivel debajo: empieza con todos los permisos delegables y el super administrador decide cuáles conserva. En **Administración → Roles** también puede crear otros roles personalizados (por ejemplo, "Soporte") con permisos delegables: usuarios, suspensiones, asignar roles, solicitudes de mentor, avisos, tablero y consumo de IA, y bitácora. El catálogo está en `app/Support/AdminAccess.php`.
- Nunca se delegan: eliminar cuentas, gestionar roles, límites y presupuesto de IA, orquestador y correo saliente.
- Quien asigna roles solo puede dar roles cuyos permisos también tenga. Un administrador delegado no puede modificar cuentas con permisos de administración, y siempre queda al menos un super administrador activo.
- Las cuentas se **suspenden** (temporal o indefinidamente, con motivo) en lugar de eliminarse. Al suspender una cuenta:
  - se cierra su sesión;
  - no puede entrar, tampoco con Google;
  - deja de recibir avisos;
  - se oculta su perfil público;
  - se cancelan sus mentorías abiertas.
- Con `ADMIN_REQUIRE_TWO_FACTOR=true` se exige verificación en dos pasos para entrar al panel. En producción se exige por defecto.
- Recuperación desde el servidor: `php artisan admin:super correo@dominio.com` asigna el rol de super administrador y levanta la suspensión de esa cuenta; con `--quitar` se lo retira.

## Pruebas

```bash
php artisan test
```

Las pruebas usan SQLite en memoria y nunca llaman a un proveedor de IA real: `phpunit.xml` define credenciales falsas y `Tests\TestCase` bloquea cualquier petición HTTP no simulada.
