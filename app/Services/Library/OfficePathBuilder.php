<?php

namespace App\Services\Library;

use App\Models\LearningPath;
use App\Models\LearningPathVersion;
use App\Models\LibraryTopic;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\UserSkill;
use App\Support\Library\OfficeSyllabus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Arma una ruta de Office con temas de la biblioteca. Quien empieza de cero recibe todo el temario; quien ya sabe
 * lo básico (por ejemplo, de intermedio a experto) recibe solo los niveles que le faltan. Ningún tema se genera aquí:
 * cada uno toma su contenido de la biblioteca la primera vez que alguien lo abre.
 */
class OfficePathBuilder
{
    /** @param  list<string>  $tools */
    public function build(User $user, string $from = 'principiante', string $to = 'experto', array $tools = []): LearningPath
    {
        if (! isset(OfficeSyllabus::LEVELS[$from], OfficeSyllabus::LEVELS[$to]) || OfficeSyllabus::LEVELS[$from]['order'] > OfficeSyllabus::LEVELS[$to]['order']) {
            throw new InvalidArgumentException('Niveles no válidos.');
        }
        $tools = $tools ?: array_keys(OfficeSyllabus::TOOLS);
        $levels = OfficeSyllabus::levelsBetween($from, $to);
        $topics = LibraryTopic::where('course', OfficeSyllabus::COURSE)->whereIn('tool', $tools)->whereIn('level', $levels)
            ->orderBy('level_order')->orderBy('position')->get()->groupBy(fn (LibraryTopic $topic) => $topic->level.'.'.$topic->tool);
        if ($topics->isEmpty()) {
            throw new InvalidArgumentException('La biblioteca de Office está vacía: ejecuta php artisan library:sync.');
        }

        $toolNames = collect($tools)->map(fn ($tool) => OfficeSyllabus::TOOLS[$tool] ?? $tool)->implode(', ');
        $range = OfficeSyllabus::LEVELS[$from]['label'].' a '.OfficeSyllabus::LEVELS[$to]['label'];
        $goal = "Dominar Microsoft Office ({$toolNames}) de nivel {$range}";

        return DB::transaction(function () use ($user, $topics, $levels, $tools, $toolNames, $range, $goal, $from) {
            StudentProfile::updateOrCreate(['user_id' => $user->id], ['professional_goal' => $goal, 'current_level' => OfficeSyllabus::LEVELS[$from]['label']]);
            LearningPath::where('user_id', $user->id)->where('status', 'active')->update(['status' => 'replaced']);
            $path = LearningPath::create(['user_id' => $user->id, 'title' => "Microsoft Office: {$range}", 'goal' => $goal, 'estimated_level' => OfficeSyllabus::LEVELS[$from]['label'],
                'estimated_hours' => $topics->flatten()->count() * 2, 'generation_version' => 'library-office-v1', 'status' => 'active']);
            LearningPathVersion::create(['learning_path_id' => $path->id, 'version' => 1, 'generator' => 'library',
                'architecture' => ['library' => OfficeSyllabus::COURSE, 'levels' => $levels, 'tools' => $tools, 'summary' => "Ruta de {$toolNames}, de nivel {$range}, con contenido de la biblioteca compartida."]]);

            $category = SkillCategory::firstOrCreate(['slug' => 'ofimatica'], ['name' => 'Ofimática']);
            $previous = null;
            $position = 0;
            // Por nivel y, dentro del nivel, por herramienta: primero lo básico de todas, luego lo intermedio…
            foreach ($levels as $level) {
                foreach ($tools as $tool) {
                    $group = $topics->get("{$level}.{$tool}");
                    if (! $group) {
                        continue;
                    }
                    $label = OfficeSyllabus::TOOLS[$tool].' · '.OfficeSyllabus::LEVELS[$level]['label'];
                    $item = $path->items()->create(['title' => $label, 'area' => 'ofimatica', 'position' => ++$position,
                        'description' => $group->pluck('title')->implode(' · '), 'content' => ['generation' => 'library', 'skills' => [$label]], 'quiz' => OfficeSyllabus::quiz($tool, $level)]);
                    if ($previous) {
                        DB::table('learning_path_dependencies')->insert(['learning_path_id' => $path->id, 'learning_path_item_id' => $item->id, 'depends_on_item_id' => $previous, 'created_at' => now(), 'updated_at' => now()]);
                    }
                    $previous = $item->id;

                    foreach ($group->values() as $index => $libraryTopic) {
                        $skill = Skill::firstOrCreate(['slug' => $libraryTopic->slug], ['skill_category_id' => $category->id, 'name' => $libraryTopic->title, 'description' => $libraryTopic->learning_objective]);
                        UserSkill::firstOrCreate(['user_id' => $user->id, 'skill_id' => $skill->id], ['level' => 0, 'confidence' => 0]);
                        $item->topics()->create(['title' => $libraryTopic->title, 'learning_objective' => $libraryTopic->learning_objective, 'practice_type' => $libraryTopic->practice_type,
                            'position' => $index + 1, 'metadata' => ['skill_slug' => $skill->slug, 'library_topic_id' => $libraryTopic->id]]);
                    }
                }
            }

            return $path;
        });
    }
}
