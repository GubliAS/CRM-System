<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExporter
{
    /**
     * @param  list<array{key: string, label: string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    public function download(string $format, string $reportName, array $columns, array $rows): StreamedResponse
    {
        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $reportName) ?: 'report';

        return match (strtolower($format)) {
            'csv' => $this->csv($safe, $columns, $rows),
            'excel', 'xlsx', 'xls' => $this->excel($safe, $columns, $rows),
            'pdf' => $this->pdf($safe, $reportName, $columns, $rows),
            default => abort(422, 'Unsupported export format.'),
        };
    }

    /**
     * @param  list<array{key: string, label: string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    private function csv(string $safeName, array $columns, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($columns, $rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_map(fn (array $col) => $col['label'], $columns));

            foreach ($rows as $row) {
                $line = [];
                foreach ($columns as $col) {
                    $line[] = $this->scalar($row[$col['key']] ?? '');
                }
                fputcsv($out, $line);
            }

            fclose($out);
        }, $safeName.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * SpreadsheetML XML that Excel opens without a composer dependency.
     *
     * @param  list<array{key: string, label: string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    private function excel(string $safeName, array $columns, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($columns, $rows): void {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
            echo 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            echo '<Worksheet ss:Name="Report"><Table>';

            echo '<Row>';
            foreach ($columns as $col) {
                echo '<Cell><Data ss:Type="String">'.$this->xml((string) $col['label']).'</Data></Cell>';
            }
            echo '</Row>';

            foreach ($rows as $row) {
                echo '<Row>';
                foreach ($columns as $col) {
                    $value = $this->scalar($row[$col['key']] ?? '');
                    $type = is_numeric($value) ? 'Number' : 'String';
                    echo '<Cell><Data ss:Type="'.$type.'">'.$this->xml((string) $value).'</Data></Cell>';
                }
                echo '</Row>';
            }

            echo '</Table></Worksheet></Workbook>';
        }, $safeName.'.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * Minimal text PDF (no external package).
     *
     * @param  list<array{key: string, label: string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    private function pdf(string $safeName, string $title, array $columns, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($title, $columns, $rows): void {
            $lines = [$title, str_repeat('-', min(80, max(20, strlen($title))))];
            $header = implode(' | ', array_map(fn (array $col) => $col['label'], $columns));
            $lines[] = $header;
            $lines[] = str_repeat('-', min(120, strlen($header)));

            foreach ($rows as $row) {
                $parts = [];
                foreach ($columns as $col) {
                    $parts[] = $this->scalar($row[$col['key']] ?? '');
                }
                $lines[] = implode(' | ', $parts);
            }

            echo $this->buildPdf($lines);
        }, $safeName.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @param  list<string>  $lines
     */
    private function buildPdf(array $lines): string
    {
        $content = "BT /F1 10 Tf 40 780 Td 14 TL\n";
        $yLines = 0;

        foreach ($lines as $line) {
            $safe = $this->pdfEscape($this->truncate($line, 110));
            if ($yLines > 50) {
                // Keep a single page for Stage 10; truncate remaining.
                $content .= '('.$this->pdfEscape('…').") '\n";
                break;
            }
            $content .= "({$safe}) '\n";
            $yLines++;
        }

        $content .= 'ET';

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
        $objects[] = '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref
0 '.(count($objects) + 1).'
';
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer
<< /Size '.(count($objects) + 1).' /Root 1 0 R >>
startxref
'.$xref.'
%%EOF';

        return $pdf;
    }

    private function scalar(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value) ?: '';
        }

        return (string) $value;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }

    private function truncate(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max - 1).'…';
    }
}
