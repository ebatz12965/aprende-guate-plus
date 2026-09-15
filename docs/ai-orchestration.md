# Orquestación de IA

## Propósito

La aplicación usa una puerta de enlace única (`App\AI\AiGateway`) para cualquier llamada a un proveedor. Las capas web, diagnósticos, rutas, lecciones y prácticas no envían solicitudes HTTP a OpenAI directamente.

## Proveedor actual

Solo OpenAI está habilitado. La credencial se toma de `OPENAI_API_KEY` o, por compatibilidad, de `AI_API_KEY` en `.env`. No se persiste ni se muestra una clave en la base de datos.

Los proveedores de video y voz no se han activado: Veo y Sora requieren sus propios contratos, credenciales, límites de coste y mecanismos de trabajo asíncrono. El agente `tutorial_script` genera guiones, no video.

## Agentes registrados

El catálogo central registra doce roles: perfil profesional, ruta, currículo, teoría, guion tutorial, ejercicios, archivos, evaluación, tutor, entrevista, evaluación de entrevista y empleabilidad. Los tres últimos están desactivados hasta que existan sus módulos de producto y rúbricas; no pueden invocarse accidentalmente.

## Datos y trazabilidad

- `ai_requests` guarda metadatos, hashes, estado, duración y tokens, no prompts ni respuestas completas.
- `ai_token_usages` registra proveedor y agente, por lo que el panel puede atribuir consumo.
- `ai_content_versions` conserva la primera versión publicada de cada lección y práctica.
- Las rutas guardan una versión de su arquitectura y relaciones secuenciales entre fases.
- Las habilidades se modelan como categorías, habilidades, dependencias, nivel del usuario y evidencias. Al entregar una práctica se crea una evidencia y se actualiza la habilidad asociada.

## Operación local

Después de iniciar MySQL en Laragon, ejecuta:

```powershell
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" artisan migrate
& "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe" artisan db:seed --class=AiOrchestratorSeeder
```

El panel se encuentra en `Administración > Orquestador IA`.
## Pruebas detalladas

Toda prueba nueva debe incluir un caso realista con organización, personas, periodo, decisión que se debe tomar y datos ficticios consistentes. También debe declarar objetivos, entregables, requisitos, rúbrica ponderada y, cuando aplique, recursos descargables. Para hojas de cálculo, el recurso debe incluir CSV y una guía de fórmulas o cálculos sugeridos.