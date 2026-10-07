<?php

namespace App\Console\Commands;

use App\Services\Library\LibraryContentService;
use Illuminate\Console\Command;

class LibrarySync extends Command
{
    protected $signature = 'library:sync';

    protected $description = 'Crea o actualiza los temas de la biblioteca de Office a partir del temario (no genera contenido ni consume tokens).';

    public function handle(LibraryContentService $library): int
    {
        $this->info('Temas en la biblioteca: '.$library->sync());

        return self::SUCCESS;
    }
}
