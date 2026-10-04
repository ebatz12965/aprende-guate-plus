<?php

namespace App\Services\Learning;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;
use ZipArchive;

/**
 * Extrae una vista previa de texto de las evidencias para que el evaluador IA pueda leerlas.
 * Soporta TXT, CSV, PDF, DOCX, XLSX y PPTX; devuelve null si el formato no se puede leer.
 */
class EvidenceTextExtractor
{
    public const MAX_CHARACTERS = 12000;

    /** Límite de tamaño descomprimido por entrada ZIP para evitar archivos maliciosos (zip bombs). */
    private const MAX_ZIP_ENTRY_BYTES = 15 * 1024 * 1024;

    private const MAX_XLSX_ROWS = 200;

    public function extract(string $absolutePath, ?string $fileName = null, ?string $mime = null): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        try {
            $text = match ($this->formatOf($fileName ?? $absolutePath, $mime)) {
                'text' => (string) file_get_contents($absolutePath, false, null, 0, self::MAX_CHARACTERS * 4),
                'pdf' => (new PdfParser)->parseFile($absolutePath)->getText(),
                'docx' => $this->docx($absolutePath),
                'xlsx' => $this->xlsx($absolutePath),
                'pptx' => $this->pptx($absolutePath),
                default => null,
            };
        } catch (Throwable $exception) {
            Log::info('evidence.extraction_failed', ['file' => $fileName, 'reason' => $exception->getMessage()]);

            return null;
        }

        $text = $this->normalize($text);

        return $text === '' ? null : Str::limit($text, self::MAX_CHARACTERS, '…');
    }

    public function supports(?string $fileName, ?string $mime = null): bool
    {
        return $this->formatOf((string) $fileName, $mime) !== null;
    }

    private function formatOf(string $fileName, ?string $mime): ?string
    {
        $extension = Str::lower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mime = Str::lower((string) $mime);

        return match (true) {
            in_array($extension, ['txt', 'csv'], true), in_array($mime, ['text/plain', 'text/csv', 'application/csv'], true) => 'text',
            $extension === 'pdf' || $mime === 'application/pdf' => 'pdf',
            $extension === 'docx' || str_contains($mime, 'wordprocessingml') => 'docx',
            $extension === 'xlsx' || str_contains($mime, 'spreadsheetml') => 'xlsx',
            $extension === 'pptx' || str_contains($mime, 'presentationml') => 'pptx',
            default => null,
        };
    }

    private function docx(string $path): ?string
    {
        $xml = $this->zipEntries($path, fn (string $name) => $name === 'word/document.xml')['word/document.xml'] ?? null;

        return $xml === null ? null : $this->xmlToText($xml, ['</w:p>' => "\n", '<w:tab/>' => "\t", '<w:br/>' => "\n"]);
    }

    private function pptx(string $path): ?string
    {
        $slides = $this->zipEntries($path, fn (string $name) => (bool) preg_match('#^ppt/slides/slide\d+\.xml$#', $name));
        uksort($slides, 'strnatcmp');

        return collect($slides)->values()->map(fn (string $xml, int $index) => 'Diapositiva '.($index + 1).":\n".$this->xmlToText($xml, ['</a:p>' => "\n"]))->implode("\n\n");
    }

    /** Convierte las hojas a filas separadas por comas, resolviendo los textos compartidos. */
    private function xlsx(string $path): ?string
    {
        $entries = $this->zipEntries($path, fn (string $name) => $name === 'xl/sharedStrings.xml' || (bool) preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name));
        $shared = [];
        if (isset($entries['xl/sharedStrings.xml'])) {
            $strings = simplexml_load_string($entries['xl/sharedStrings.xml'], options: LIBXML_NONET);
            foreach ($strings?->si ?? [] as $item) {
                $shared[] = trim(strip_tags($item->asXML()));
            }
        }

        $sheets = array_filter($entries, fn ($name) => $name !== 'xl/sharedStrings.xml', ARRAY_FILTER_USE_KEY);
        uksort($sheets, 'strnatcmp');
        $output = [];
        foreach (array_values($sheets) as $index => $xml) {
            $sheet = simplexml_load_string($xml, options: LIBXML_NONET);
            $rows = [];
            foreach ($sheet?->sheetData?->row ?? [] as $row) {
                if (count($rows) >= self::MAX_XLSX_ROWS) {
                    break;
                }
                $cells = [];
                foreach ($row->c as $cell) {
                    $value = match (true) {
                        isset($cell->v) => (string) $cell->v,
                        isset($cell->is) => trim(strip_tags($cell->is->asXML())),
                        default => '',
                    };
                    $cells[] = (string) $cell['t'] === 's' ? ($shared[(int) $value] ?? '') : $value;
                }
                $rows[] = implode(',', $cells);
            }
            $output[] = 'Hoja '.($index + 1).":\n".implode("\n", $rows);
        }

        return implode("\n\n", $output);
    }

    /** @return array<string, string> Entradas del ZIP que cumplen el filtro, respetando el límite de tamaño. */
    private function zipEntries(string $path, callable $filter): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return [];
        }

        $entries = [];
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (! $stat || ! $filter($stat['name']) || $stat['size'] > self::MAX_ZIP_ENTRY_BYTES) {
                    continue;
                }
                $entries[$stat['name']] = (string) $zip->getFromIndex($i);
            }
        } finally {
            $zip->close();
        }

        return $entries;
    }

    private function xmlToText(string $xml, array $breaks): string
    {
        return html_entity_decode(strip_tags(strtr($xml, $breaks)), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function normalize(?string $text): string
    {
        $text = mb_convert_encoding((string) $text, 'UTF-8', 'UTF-8');
        $text = preg_replace("/[ \t]+/u", ' ', $text) ?? '';

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? '');
    }
}
