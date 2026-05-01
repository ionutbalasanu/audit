<?php
declare(strict_types=1);

final class SimplePdfDocument
{
    private array $pages = [];
    private int $currentPage = -1;
    private float $pageWidth;
    private float $pageHeight;

    public function __construct(float $pageWidth = 595.28, float $pageHeight = 841.89)
    {
        $this->pageWidth = $pageWidth;
        $this->pageHeight = $pageHeight;
    }

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->currentPage = count($this->pages) - 1;
    }

    public function addText(float $x, float $topY, string $text, float $fontSize = 12.0, bool $bold = false, array $rgb = [0, 0, 0]): void
    {
        if ($text === '') {
            return;
        }

        $this->ensurePage();
        $y = $this->pageHeight - $topY;
        [$r, $g, $b] = $this->normalizeColor($rgb);
        $font = $bold ? 'F2' : 'F1';

        $this->pages[$this->currentPage] .= sprintf(
            "BT /%s %.2F Tf %.4F %.4F %.4F rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
            $font,
            $fontSize,
            $r,
            $g,
            $b,
            $x,
            $y,
            self::escapeText($text)
        );
    }

    public function addLine(float $x1, float $topY1, float $x2, float $topY2, float $width = 1.0, array $rgb = [0, 0, 0]): void
    {
        $this->ensurePage();
        [$r, $g, $b] = $this->normalizeColor($rgb);
        $y1 = $this->pageHeight - $topY1;
        $y2 = $this->pageHeight - $topY2;

        $this->pages[$this->currentPage] .= sprintf(
            "%.4F %.4F %.4F RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            $r,
            $g,
            $b,
            $width,
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    public function addFilledRect(float $x, float $topY, float $width, float $height, array $rgb): void
    {
        $this->ensurePage();
        [$r, $g, $b] = $this->normalizeColor($rgb);
        $y = $this->pageHeight - $topY - $height;

        $this->pages[$this->currentPage] .= sprintf(
            "%.4F %.4F %.4F rg %.2F %.2F %.2F %.2F re f\n",
            $r,
            $g,
            $b,
            $x,
            $y,
            $width,
            $height
        );
    }

    public function render(): string
    {
        if ($this->pages === []) {
            $this->addPage();
        }

        // Custom encoding: WinAnsi + Romanian characters in unused slots
        // ă=0xA2, Ă=0xA3, ș/ş=0xA6, Ș/Ş=0xA7, ț/ţ=0xBE, Ț/Ţ=0xBF
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding 5 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding 5 0 R >>',
            5 => '<< /Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [162 /abreve 163 /Abreve 166 /scedilla 167 /Scedilla 190 /tcedilla 191 /Tcedilla] >>',
        ];

        $kids = [];
        $nextObject = 6;

        foreach ($this->pages as $stream) {
            $pageObject = $nextObject++;
            $contentObject = $nextObject++;
            $kids[] = $pageObject . ' 0 R';

            $objects[$pageObject] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                $this->pageWidth,
                $this->pageHeight,
                $contentObject
            );

            $objects[$contentObject] = sprintf(
                "<< /Length %d >>\nstream\n%sendstream",
                strlen($stream),
                $stream
            );
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 " . $size . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($index = 1; $index < $size; $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . $size . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function ensurePage(): void
    {
        if ($this->currentPage === -1) {
            $this->addPage();
        }
    }

    private function normalizeColor(array $rgb): array
    {
        $r = isset($rgb[0]) ? max(0.0, min(1.0, (float)$rgb[0])) : 0.0;
        $g = isset($rgb[1]) ? max(0.0, min(1.0, (float)$rgb[1])) : 0.0;
        $b = isset($rgb[2]) ? max(0.0, min(1.0, (float)$rgb[2])) : 0.0;
        return [$r, $g, $b];
    }

    /**
     * Convert UTF-8 text to the custom single-byte encoding used by this PDF,
     * then escape for PDF string literal syntax.
     *
     * Encoding map (custom slots added via /Differences):
     *   ă (U+0103) → 0xA2  |  Ă (U+0102) → 0xA3
     *   ș (U+0219) → 0xA6  |  Ș (U+0218) → 0xA7  (also ş/Ş cedilla variants)
     *   ț (U+021B) → 0xBE  |  Ț (U+021A) → 0xBF  (also ţ/Ţ cedilla variants)
     *   â (U+00E2) → 0xE2  |  Â (U+00C2) → 0xC2  (already in WinAnsi)
     *   î (U+00EE) → 0xEE  |  Î (U+00CE) → 0xCE  (already in WinAnsi)
     */
    private static function escapeText(string $text): string
    {
        // Map UTF-8 byte sequences to their single-byte encoding equivalents
        $map = [
            "\xC4\x83" => "\xA2", // ă
            "\xC4\x82" => "\xA3", // Ă
            "\xC3\xA2" => "\xE2", // â
            "\xC3\x82" => "\xC2", // Â
            "\xC3\xAE" => "\xEE", // î
            "\xC3\x8E" => "\xCE", // Î
            "\xC8\x99" => "\xA6", // ș (comma below)
            "\xC8\x98" => "\xA7", // Ș (comma below)
            "\xC5\x9F" => "\xA6", // ş (cedilla)
            "\xC5\x9E" => "\xA7", // Ş (cedilla)
            "\xC8\x9B" => "\xBE", // ț (comma below)
            "\xC8\x9A" => "\xBF", // Ț (comma below)
            "\xC5\xA3" => "\xBE", // ţ (cedilla)
            "\xC5\xA2" => "\xBF", // Ţ (cedilla)
            "\xE2\x80\x93" => '-',   // en dash
            "\xE2\x80\x94" => '-',   // em dash
            "\xE2\x80\x9C" => '"',   // left double quote
            "\xE2\x80\x9D" => '"',   // right double quote
            "\xE2\x80\x98" => "'",   // left single quote
            "\xE2\x80\x99" => "'",   // right single quote
            "\xE2\x80\xA6" => '...', // ellipsis
        ];

        $text = strtr($text, $map);

        // Strip any remaining multi-byte UTF-8 sequences (unsupported characters)
        // After strtr, remaining multi-byte sequences are unrecognized — replace with '?'
        $text = preg_replace('/[\xC2-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF4][\x80-\xBF]{3}/S', '?', $text) ?? $text;

        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\(', '\)', '', ' '],
            $text
        );
    }
}
