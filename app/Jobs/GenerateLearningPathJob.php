<?php

namespace App\Jobs;

use App\AI\AiGateway;
use App\Models\DiagnosticAttempt;
use App\Models\LearningPath;
use App\Models\LearningPathVersion;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\StudentProfile;
use App\Models\UserSkill;
use App\Services\Learning\DiagnosticScorer;
use App\Services\Learning\GoalAnalysisService;
use App\Services\Learning\LearningPathArchitect;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class GenerateLearningPathJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public int $attemptId)
    {
        // Arquitectura de la ruta: borrador, reintento y revisión.
        $this->timeout = AiGateway::callTimeout((int) config('ai.content_timeout')) * 3 + 20;
    }

    public function handle(LearningPathArchitect $architect, GoalAnalysisService $analyst, DiagnosticScorer $scorer): void
    {
        $attempt = DiagnosticAttempt::find($this->attemptId);

        if (! $attempt || $attempt->status !== 'generating') {
            return;
        }

        try {
            $analysis = $attempt->analysis ?: $analyst->fallback($attempt->primary_answer);
            // El nivel lo calcula el cuestionario, no la IA; los intentos anteriores a este cambio se califican aquí.
            $assessment = data_get($attempt->skill_results, 'assessment') ?? $scorer->score($attempt->questions ?? [], $attempt->answers ?? [], $analysis);
            $architecture = $architect->design($attempt->primary_answer, $analysis, $assessment, $attempt->user_id);
            $level = $assessment['level'] ?? 'Inicial';
            $careerProfile = [
                'target_role' => $analysis['target_role'] ?? $attempt->primary_answer,
                'current_level' => $level,
                'skill_gaps' => collect($assessment['skills'] ?? [])->where('level', '!=', 'Avanzado')->sortBy('score')->pluck('name')->values()->all(),
                'summary' => data_get($architecture, 'summary'),
            ];

            DB::transaction(function () use ($attempt, $architecture, $careerProfile, $level, $analysis, $assessment) {
                StudentProfile::updateOrCreate(
                    ['user_id' => $attempt->user_id],
                    ['professional_goal' => $attempt->primary_answer, 'current_level' => $level],
                );

                LearningPath::where('user_id', $attempt->user_id)->where('status', 'active')->update(['status' => 'replaced']);

                $path = LearningPath::create([
                    'user_id' => $attempt->user_id,
                    'title' => data_get($architecture, 'title', 'Ruta personalizada'),
                    'goal' => $attempt->primary_answer,
                    'estimated_level' => $level,
                    'estimated_hours' => data_get($architecture, 'total_hours'),
                    'generation_version' => 'pipeline-v2',
                    'status' => 'active',
                ]);

                LearningPathVersion::create(['learning_path_id' => $path->id, 'diagnostic_attempt_id' => $attempt->id, 'version' => 1, 'architecture' => ['analysis' => $analysis, 'assessment' => $assessment, 'career_profile' => $careerProfile, 'roadmap' => $architecture], 'generator' => 'learning_path']);

                $previousItemId = null;
                foreach (data_get($architecture, 'modules', []) as $position => $module) {
                    $area = data_get($module, 'skill_type', 'general');
                    $category = SkillCategory::firstOrCreate(['slug' => Str::slug($area)], ['name' => Str::headline($area)]);
                    $item = $path->items()->create([
                        'title' => data_get($module, 'title', 'Módulo de aprendizaje'),
                        'area' => $area,
                        'description' => data_get($module, 'description'),
                        'content' => ['generation' => 'architecture', 'skills' => array_filter([data_get($module, 'skill')])],
                        // Cada fase trae su propia pregunta; la genérica solo queda para las rutas de respaldo.
                        'quiz' => $this->validQuiz(data_get($module, 'quiz')) ?? ['question' => '¿Qué conviene hacer antes de avanzar?', 'options' => ['Revisar los temas del módulo', 'Saltar al siguiente módulo', 'Ignorar las dudas'], 'answer' => 0],
                        'position' => $position + 1,
                    ]);

                    if ($previousItemId) {
                        DB::table('learning_path_dependencies')->insert(['learning_path_id' => $path->id, 'learning_path_item_id' => $item->id, 'depends_on_item_id' => $previousItemId, 'created_at' => now(), 'updated_at' => now()]);
                    }
                    $previousItemId = $item->id;

                    foreach (data_get($module, 'topics', []) as $topicPosition => $topic) {
                        $skill = Skill::firstOrCreate(['slug' => Str::slug($area).'.'.Str::slug(data_get($topic, 'title', 'tema'))], ['skill_category_id' => $category->id, 'name' => data_get($topic, 'title', 'Tema'), 'description' => data_get($topic, 'learning_objective')]);
                        UserSkill::firstOrCreate(['user_id' => $attempt->user_id, 'skill_id' => $skill->id], ['level' => 0, 'confidence' => 0]);
                        $item->topics()->create([
                            'title' => data_get($topic, 'title', 'Tema'),
                            'learning_objective' => data_get($topic, 'learning_objective', 'Comprender y aplicar el tema.'),
                            'practice_type' => data_get($topic, 'practice_type', 'case_study'),
                            'position' => $topicPosition + 1,
                            'metadata' => ['skill_slug' => $skill->slug],
                        ]);
                    }
                }

                $attempt->update([
                    'level' => $level,
                    'score' => $assessment['score'] ?? 0,
                    'skill_results' => ['assessment' => $assessment, 'summary' => data_get($architecture, 'summary'), 'career_profile' => $careerProfile],
                    'status' => 'completed',
                    'generation_error' => null,
                ]);
            });
        } catch (Throwable $exception) {
            $attempt->update([
                'status' => 'failed',
                'generation_error' => 'No se pudo generar tu ruta. Puedes reintentar sin volver a responder el cuestionario.',
            ]);

            throw $exception;
        }
    }

    private function validQuiz(mixed $quiz): ?array
    {
        return is_array($quiz) && filled($quiz['question'] ?? null) && count($quiz['options'] ?? []) === 3 && in_array($quiz['answer'] ?? null, [0, 1, 2], true)
            ? ['question' => $quiz['question'], 'options' => array_values($quiz['options']), 'answer' => $quiz['answer']]
            : null;
    }

    public function failed(?Throwable $exception): void
    {
        DiagnosticAttempt::whereKey($this->attemptId)->update([
            'status' => 'failed',
            'generation_error' => 'No se pudo generar tu ruta. Puedes reintentar sin volver a responder el cuestionario.',
        ]);
    }
}
