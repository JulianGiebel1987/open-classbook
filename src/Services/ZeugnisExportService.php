<?php

namespace OpenClassbook\Services;

use OpenClassbook\Models\ZeugnisImage;

class ZeugnisExportService
{
    private ZeugnisPlaceholderService $placeholderService;

    public function __construct()
    {
        $this->placeholderService = new ZeugnisPlaceholderService();
    }

    /**
     * Output a single certificate as a PDF download.
     * Caller must call exit after this.
     */
    public function exportSingle(array $template, array $instance, array $student, array $class = []): void
    {
        $tokens = ZeugnisPlaceholderService::getStudentTokens($student, $class);
        $canvas = json_decode($template['template_canvas'], true);
        $fieldValues = json_decode($instance['field_values'] ?? '{}', true) ?: [];

        $pdf = $this->createPdf($template);
        $this->renderTemplateToPdf($pdf, $canvas, $fieldValues, $tokens);

        $lastName = $student['lastname'] ?? $student['last_name'] ?? 'schueler';
        $firstName = $student['firstname'] ?? $student['first_name'] ?? '';
        $filename = 'zeugnis_' . $this->sanitizeFilename($lastName . '_' . $firstName) . '_' . date('Y-m-d') . '.pdf';

        Logger::audit(
            'export_zeugnis_pdf',
            $_SESSION['user_id'] ?? null,
            'zeugnis_instance',
            (int) $instance['id'],
            'Exported certificate for student ' . $instance['id']
        );

        $pdf->Output($filename, 'D');
    }

    /**
     * Output a ZIP archive containing one PDF per instance.
     * Caller must call exit after this.
     */
    public function exportBatch(array $instances): void
    {
        $tmpFiles = [];

        foreach ($instances as $instance) {
            $template = ['template_canvas' => $instance['template_canvas'], 'page_orientation' => $instance['page_orientation'], 'page_format' => $instance['page_format']];
            $student = [
                'firstname'   => $instance['student_first_name'],
                'lastname'    => $instance['student_last_name'],
                'birthday'    => $instance['student_birthday'] ?? null,
                'class_name'  => $instance['class_name'] ?? '',
                'school_year' => $instance['school_year'] ?? '',
            ];
            $tokens = ZeugnisPlaceholderService::getStudentTokens($student);
            $canvas = json_decode($template['template_canvas'], true);
            $fieldValues = json_decode($instance['field_values'] ?? '{}', true) ?: [];

            $pdf = $this->createPdf($template);
            $this->renderTemplateToPdf($pdf, $canvas, $fieldValues, $tokens);

            $pdfContent = $pdf->Output('', 'S');
            $tmpPath = tempnam(sys_get_temp_dir(), 'zeugnis_');
            file_put_contents($tmpPath, $pdfContent);

            $lastName  = $instance['student_last_name']  ?? 'schueler';
            $firstName = $instance['student_first_name'] ?? '';
            $zipName = 'zeugnis_' . $this->sanitizeFilename($lastName . '_' . $firstName) . '.pdf';

            $tmpFiles[$zipName] = $tmpPath;

            Logger::audit(
                'export_zeugnis_pdf',
                $_SESSION['user_id'] ?? null,
                'zeugnis_instance',
                (int) $instance['id'],
                'Batch export certificate for student ' . $instance['id']
            );
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'zeugnis_batch_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);

        foreach ($tmpFiles as $zipName => $tmpPath) {
            $zip->addFile($tmpPath, $zipName);
        }
        $zip->close();

        // Clean up temp PDF files
        foreach ($tmpFiles as $tmpPath) {
            @unlink($tmpPath);
        }

        $zipFilename = 'zeugnisse_' . date('Y-m-d') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        @unlink($zipPath);
    }

    private function createPdf(array $template): \TCPDF
    {
        $orientation = $template['page_orientation'] ?? 'P';
        $format = $template['page_format'] ?? 'A4';

        $pdf = new \TCPDF($orientation, 'mm', $format, true, 'UTF-8', false);
        $pdf->SetCreator('Open-Classbook');
        $pdf->SetAuthor('Open-Classbook');
        $pdf->SetTitle('Zeugnis');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        return $pdf;
    }

    /**
     * Render all pages of the canvas onto the PDF.
     *
     * @param array $canvas      Decoded template_canvas JSON
     * @param array $fieldValues Map of element_id => value
     * @param array $tokens      Placeholder token => resolved value
     */
    public function renderTemplateToPdf(\TCPDF $pdf, array $canvas, array $fieldValues, array $tokens): void
    {
        $pages = $canvas['pages'] ?? [];

        foreach ($pages as $page) {
            $pdf->AddPage();
            $elements = $page['elements'] ?? [];

            foreach ($elements as $el) {
                $this->renderElement($pdf, $el, $fieldValues, $tokens);
            }
        }
    }

    private function renderElement(\TCPDF $pdf, array $el, array $fieldValues, array $tokens): void
    {
        $x = (float) ($el['x'] ?? 0);
        $y = (float) ($el['y'] ?? 0);
        $w = (float) ($el['width'] ?? 50);
        $h = (float) ($el['height'] ?? 10);

        switch ($el['type'] ?? '') {
            case 'text_static':
                $this->renderStaticText($pdf, $el, $x, $y, $w, $h, $tokens);
                break;

            case 'text_free':
            case 'date':
                $value = (string) ($fieldValues[$el['id']] ?? $el['placeholder'] ?? '');
                $this->renderFreeText($pdf, $el, $x, $y, $w, $h, $value);
                break;

            case 'placeholder':
                $content = ZeugnisPlaceholderService::resolvePlaceholders($el['content'] ?? '', $tokens);
                $this->renderStaticText($pdf, array_merge($el, ['content' => $content]), $x, $y, $w, $h, $tokens);
                break;

            case 'grade':
                $value = (string) ($fieldValues[$el['id']] ?? '');
                $this->renderGrade($pdf, $el, $x, $y, $w, $h, $value);
                break;

            case 'checkbox':
                $checked = (bool) ($fieldValues[$el['id']] ?? false);
                $this->renderCheckbox($pdf, $el, $x, $y, $w, $h, $checked);
                break;

            case 'signature':
                $this->renderSignatureBox($pdf, $el, $x, $y, $w, $h);
                break;

            case 'divider':
                $color = $this->parseHexColor($el['color'] ?? '#000000');
                $pdf->SetDrawColor($color[0], $color[1], $color[2]);
                $lineWidth = (float) ($el['lineWidth'] ?? 0.5);
                $pdf->SetLineWidth($lineWidth);
                $pdf->Line($x, $y, $x + $w, $y);
                break;

            case 'image':
                $this->renderImage($pdf, $el, $x, $y, $w, $h);
                break;

            case 'table':
                $this->renderTable($pdf, $el, $x, $y, $w, $h, $fieldValues, $tokens);
                break;
        }
    }

    private function renderStaticText(\TCPDF $pdf, array $el, float $x, float $y, float $w, float $h, array $tokens): void
    {
        $this->applyTextStyle($pdf, $el);
        $content = ZeugnisPlaceholderService::resolvePlaceholders($el['content'] ?? '', $tokens);
        $align = $el['align'] ?? 'L';
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($w, $h, $content, 0, $align, false, 1, $x, $y);
    }

    private function renderFreeText(\TCPDF $pdf, array $el, float $x, float $y, float $w, float $h, string $value): void
    {
        $this->applyTextStyle($pdf, $el);

        if (!empty($el['border'])) {
            $border = 1;
        } else {
            $border = 0;
        }

        $align = $el['align'] ?? 'L';
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($w, $h, $value, $border, $align, false, 1, $x, $y);
    }

    private function renderGrade(\TCPDF $pdf, array $el, float $x, float $y, float $w, float $h, string $value): void
    {
        $this->applyTextStyle($pdf, array_merge(['fontStyle' => 'B', 'align' => 'C'], $el));
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($w, $h, $value, 1, 'C', false, 1, $x, $y);
    }

    private function renderCheckbox(\TCPDF $pdf, array $el, float $x, float $y, float $w, float $h, bool $checked): void
    {
        $boxSize = min($h, 5);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect($x, $y + ($h - $boxSize) / 2, $boxSize, $boxSize);

        if ($checked) {
            $pdf->SetFont('zapfdingbats', '', $boxSize * 1.8);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($x - 0.5, $y + ($h - $boxSize) / 2 - 0.5);
            $pdf->Cell($boxSize + 1, $boxSize + 1, chr(52), 0, 0, 'C');
        }

        // Label rechts neben der Checkbox
        $label = $el['label'] ?? '';
        if ($label !== '') {
            $this->applyTextStyle($pdf, $el);
            $pdf->SetXY($x + $boxSize + 2, $y);
            $pdf->MultiCell($w - $boxSize - 2, $h, $label, 0, 'L');
        }
    }

    private function renderSignatureBox(\TCPDF $pdf, array $el, float $x, float $y, float $w, float $h): void
    {
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);
        $pdf->SetLineStyle(['dash' => '3,2']);
        $pdf->Rect($x, $y, $w, $h);
        $pdf->SetLineStyle(['dash' => 0]);

        // Signature line near bottom
        $lineY = $y + $h - 6;
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Line($x + 5, $lineY, $x + $w - 5, $lineY);

        $label = $el['label'] ?? 'Unterschrift';
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY($x, $lineY + 1);
        $pdf->Cell($w, 4, $label, 0, 0, 'C');
    }

    private function renderImage(\TCPDF $pdf, array $el, float $x, float $y, float $w, float $h): void
    {
        $src = $el['src'] ?? '';
        if (empty($src)) {
            return;
        }

        // src is stored as image ID reference: 'zeugnis-img:{id}'
        if (str_starts_with($src, 'zeugnis-img:')) {
            $imageId = (int) substr($src, strlen('zeugnis-img:'));
            $image = ZeugnisImage::findById($imageId);
            if (!$image) {
                return;
            }
            $filePath = ZeugnisImage::storagePath($image['stored_name']);
        } else {
            $filePath = $src;
        }

        if (!file_exists($filePath)) {
            return;
        }

        $pdf->Image($filePath, $x, $y, $w, $h, '', '', '', true);
    }

    private function renderTable(\TCPDF $pdf, array $el, float $x, float $y, float $w, float $h, array $fieldValues, array $tokens): void
    {
        $columns = $el['tableColumns'] ?? [];
        if (empty($columns)) {
            return;
        }

        $rows = $el['tableRows'] ?? 3;
        $rowHeight = $h / max($rows + 1, 2); // +1 for header
        $colCount = count($columns);
        $colWidth = $w / $colCount;

        // Header row
        $pdf->SetFont(
            $el['fontFamily'] ?? 'helvetica',
            'B',
            (float) ($el['fontSize'] ?? 9)
        );
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetTextColor(0, 0, 0);
        $curX = $x;
        foreach ($columns as $col) {
            $pdf->SetXY($curX, $y);
            $pdf->MultiCell($colWidth, $rowHeight, $col['label'] ?? '', 1, 'C', true, 0, $curX, $y);
            $curX += $colWidth;
        }
        $pdf->Ln($rowHeight);

        // Data rows
        $pdf->SetFont(
            $el['fontFamily'] ?? 'helvetica',
            '',
            (float) ($el['fontSize'] ?? 9)
        );
        $pdf->SetFillColor(255, 255, 255);

        for ($r = 0; $r < $rows; $r++) {
            $curX = $x;
            $rowY = $y + ($r + 1) * $rowHeight;
            foreach ($columns as $colIdx => $col) {
                $fieldKey = ($el['id'] ?? 'table') . '_r' . $r . '_c' . $colIdx;
                $value = (string) ($fieldValues[$fieldKey] ?? '');
                $pdf->SetXY($curX, $rowY);
                $pdf->MultiCell($colWidth, $rowHeight, $value, 1, 'L', false, 0, $curX, $rowY);
                $curX += $colWidth;
            }
            $pdf->Ln($rowHeight);
        }
    }

    private function applyTextStyle(\TCPDF $pdf, array $el): void
    {
        $font = $el['fontFamily'] ?? 'helvetica';
        $style = $el['fontStyle'] ?? '';
        $size = (float) ($el['fontSize'] ?? 11);
        $color = $this->parseHexColor($el['color'] ?? '#000000');

        $pdf->SetFont($font, $style, $size);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
    }

    /**
     * @return int[] [r, g, b]
     */
    private function parseHexColor(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function sanitizeFilename(string $name): string
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);
        return preg_replace('/[^a-z0-9_-]/', '_', $name);
    }
}
