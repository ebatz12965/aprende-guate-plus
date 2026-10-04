# Orquestación de IA

## Propósito

La aplicación usa una puerta de enlace única (`App\AI\AiGateway`) para cualquier llamada a un proveedor. Las capas web, diagnósticos, rutas, lecciones y prácticas no envían solicitudes HTTP a OpenAI directamente.

## Proveedor actual

Solo OpenAI está habilitado. La credencial se toma de `OPENAI_API_KEY` o, por compatibilidad, de `AI_API_KEY` en `.env`. No se persiste ni se muestra una clave en la base de datos.

Los proveedores de video y voz no se han activado: Veo y Sora requieren sus propios contratos, credenciales, límites de coste y mecanismos de trabajo asíncrono. El agente `tutorial_script` genera guiones, no video.

## Agentes registrados

`config/ai.php` define catorce agentes. Ocho están en uso: perfilador profesional (`career_profiler`), diseñador de rutas (`learning_path`), clasificador de contenido, generador de teoría, generador de ejercicios, evaluador de evidencias y prácticas (`evaluator`), tutor y generador de cuestionarios de mentoría. Los otros seis (currículo, guion tutorial, archivos, entrevista, evaluación de entrevista y empleabilidad) están marcados como `planned`: no tienen un módulo que los invoque y no pueden activarse.

### Configuración desde administración

En `Administración > Orquestador IA` cada agente en uso puede activarse o desactivarse y recibir un modelo o un máximo de tokens distintos. `App\AI\AgentCatalog` combina `config/ai.php` con los cambios guardados en la tabla `ai_agents`, donde cada columna vacía significa "usar el valor de config/ai.php". Un agente desactivado no llama al proveedor y la plataforma usa su contenido de respaldo. "Restablecer" elimina los cambios del agente.

## Datos y trazabilidad

- `ai_requests` guarda metadatos, hashes, estado, duración y tokens, no prompts ni respuestas completas.
- `ai_token_usages` registra proveedor y agente, por lo que el panel puede atribuir consumo.
- `ai_content_versions` conserva la primera versión publicada de cada lección y práctica.
- Las rutas guardan una versión de su arquitectura y relaciones secuenciales entre fases.
- Las habilidades se modelan como categorías, habilidades, dependencias, nivel del usuario y evidencias. Al entregar una práctica se crea una evidencia; cuando el evaluador la califica, la confianza de la habilidad pasa a ser el promedio de sus puntajes evaluados.

## Operación local

Después de iniciar MySQL en Laragon, ejecuta `php artisan migrate`. Los agentes no necesitan seeder: sin cambios guardados se usa `config/ai.php`.

El panel se encuentra en `Administración > Orquestador IA`.

## Pruebas detalladas

Toda prueba nueva debe incluir un caso realista con organización, personas, periodo, decisión que se debe tomar y datos ficticios consistentes. También debe declarar objetivos, entregables, requisitos, rúbrica ponderada y, cuando aplique, recursos descargables. Para hojas de cálculo, el recurso debe incluir CSV y una guía de fórmulas o cálculos sugeridos.