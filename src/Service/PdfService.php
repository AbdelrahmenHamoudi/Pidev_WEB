<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PdfService
{
    private $domPdf;

    public function __construct() {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $this->domPdf = new Dompdf($options);
    }

    public function generateBinaryPdf(string $html): string
    {
        $this->domPdf->loadHtml($html);
        $this->domPdf->render();
        return $this->domPdf->output();
    }

    public function showPdfFile(string $html, string $filename): Response
    {
        $this->domPdf->loadHtml($html);
        $this->domPdf->render();
        
        $canvas = $this->domPdf->getCanvas();
        $font = $this->domPdf->getFontMetrics()->get_font("helvetica", "bold");
        $canvas->page_text(520, 820, "Page {PAGE_NUM}/{PAGE_COUNT}", $font, 10, array(0,0,0));

        $output = $this->domPdf->output();

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }
}
