<?php

namespace Tests\Unit;

use App\Services\Learning\EvidenceTextExtractor;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class EvidenceTextExtractorTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        array_map('unlink', array_filter($this->files, 'is_file'));
        parent::tearDown();
    }

    private function zipFile(string $extension, array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'evidence').'.'.$extension;
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $this->files[] = $path;
    }

    private function file(string $extension, string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'evidence').'.'.$extension;
        file_put_contents($path, $content);

        return $this->files[] = $path;
    }

    public function test_reads_docx_paragraphs(): void
    {
        $path = $this->zipFile('docx', ['word/document.xml' => '<w:document xmlns:w="w"><w:body><w:p><w:r><w:t>Informe de ventas</w:t></w:r></w:p><w:p><w:r><w:t>Total: Q 5,400 &amp; crecimiento</w:t></w:r></w:p></w:body></w:document>']);

        $text = (new EvidenceTextExtractor)->extract($path, 'informe.docx');

        $this->assertStringContainsString("Informe de ventas\nTotal: Q 5,400 & crecimiento", $text);
    }

    public function test_reads_xlsx_rows_with_shared_strings(): void
    {
        $path = $this->zipFile('xlsx', [
            'xl/sharedStrings.xml' => '<sst><si><t>Vendedor</t></si><si><t>Total</t></si><si><t>Ana</t></si></sst>',
            'xl/worksheets/sheet1.xml' => '<worksheet><sheetData><row><c t="s"><v>0</v></c><c t="s"><v>1</v></c></row><row><c t="s"><v>2</v></c><c><v>450</v></c></row></sheetData></worksheet>',
        ]);

        $text = (new EvidenceTextExtractor)->extract($path, 'datos.xlsx');

        $this->assertStringContainsString("Vendedor,Total\nAna,450", $text);
    }

    public function test_reads_pptx_slides_in_order(): void
    {
        $path = $this->zipFile('pptx', [
            'ppt/slides/slide2.xml' => '<p:sld><a:p><a:r><a:t>Conclusiones</a:t></a:r></a:p></p:sld>',
            'ppt/slides/slide1.xml' => '<p:sld><a:p><a:r><a:t>Portada</a:t></a:r></a:p></p:sld>',
        ]);

        $text = (new EvidenceTextExtractor)->extract($path, 'presentacion.pptx');

        $this->assertLessThan(strpos($text, 'Conclusiones'), strpos($text, 'Portada'));
    }

    public function test_reads_pdf_text(): void
    {
        $stream = 'BT /F1 12 Tf 72 712 Td (Evidencia en PDF) Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

        $text = (new EvidenceTextExtractor)->extract($this->file('pdf', $pdf), 'evidencia.pdf');

        $this->assertStringContainsString('Evidencia en PDF', (string) $text);
    }

    public function test_plain_text_is_truncated_and_unsupported_formats_return_null(): void
    {
        $extractor = new EvidenceTextExtractor;

        $text = $extractor->extract($this->file('txt', str_repeat('a', EvidenceTextExtractor::MAX_CHARACTERS + 500)), 'notas.txt');
        $this->assertLessThanOrEqual(EvidenceTextExtractor::MAX_CHARACTERS + 1, mb_strlen($text));

        $this->assertNull($extractor->extract($this->file('png', 'binario'), 'captura.png', 'image/png'));
        $this->assertNull($extractor->extract($this->file('docx', 'no es un zip'), 'roto.docx'));
    }
}
