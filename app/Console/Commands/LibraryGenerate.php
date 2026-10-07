<?php

namespace App\Console\Commands;

use App\Jobs\GenerateLibraryWalkthroughJob;
use App\Models\LibraryTopic;
use App\Services\Library\LibraryContentService;
use Illuminate\Console\Command;
use Throwable;

class LibraryGenerate extends Command
{
    protected $signature = 'library:generate {slugs?* : Temas a generar (slug o parte del slug); vacío = todos los pendientes}
        {--only=all : lesson, practice, walkthrough o all}
        {--limit=0 : Máximo de temas a procesar}
        {--force-walkthrough : Vuelve a crear el extracto animado aunque ya exista}
        {--sync : Ejecuta aquí mismo, sin la cola}';

    protected $description = 'Genera una sola vez el contenido de la biblioteca de Office (consume tokens de IA y, si hay clave, caracteres de ElevenLabs).';

    public function handle(LibraryContentService $library): int
    {
        $query = LibraryTopic::query()->orderBy('level_order')->orderBy('tool')->orderBy('position');
        if ($slugs = $this->argument('slugs')) {
            $query->where(fn ($q) => collect($slugs)->each(fn ($slug) => $q->orWhere('slug', 'like', '%'.$slug.'%')));
        }
        $topics = $query->get();
        if ((int) $this->option('limit') > 0) {
            $topics = $topics->take((int) $this->option('limit'));
        }
        $only = (string) $this->option('only');

        foreach ($topics as $topic) {
            $this->line("· {$topic->slug}");
            try {
                if (in_array($only, ['all', 'lesson'], true) && ! $topic->lesson) {
                    $library->lesson($topic, queueWalkthrough: false);
                    $this->line('  lección lista');
                }
                if (in_array($only, ['all', 'practice'], true) && ! $topic->practice) {
                    $library->practice($topic);
                    $this->line('  práctica lista');
                }
                if (in_array($only, ['all', 'walkthrough'], true) && $topic->fresh()->lesson && (! $topic->fresh()->hasWalkthrough() || $this->option('force-walkthrough'))) {
                    $job = new GenerateLibraryWalkthroughJob($topic->id, (bool) $this->option('force-walkthrough'));
                    $this->option('sync') ? dispatch_sync($job) : dispatch($job->onQueue('ai'));
                    $this->line($this->option('sync') ? '  extracto animado: '.($topic->fresh()->hasWalkthrough() ? 'listo' : 'no se pudo generar') : '  extracto animado en cola');
                }
            } catch (Throwable $exception) {
                $this->error('  '.$exception->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
