<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Api2Pdf Service — External REST API for PDF generation.
 *
 * How to get your API key:
 *   1. Go to https://portal.api2pdf.com/register
 *   2. Sign up for a free account (free starting credit included)
 *   3. Copy your API key from the dashboard
 *   4. Set it in your .env file: API2PDF_KEY=your_real_key_here
 *
 * Documentation: https://www.api2pdf.com/documentation/v2/
 * Free tier: Starting credit for developers, extremely affordable beyond that.
 */
class Api2PdfService
{
    private const API_BASE = 'https://v2.api2pdf.com';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $api2pdfKey,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Convert raw HTML to a PDF via the Api2Pdf Chrome engine.
     *
     * @param string $html       The full HTML content to convert
     * @param string $filename   Desired filename for the PDF
     * @param bool   $inline     If true, display inline; if false, force download
     * @return array{success: bool, pdf_url: string|null, error: string|null}
     */
    public function htmlToPdf(string $html, string $filename = 'document.pdf', bool $inline = false): array
    {
        try {
            $response = $this->httpClient->request('POST', self::API_BASE . '/chrome/pdf/html', [
                'headers' => [
                    'Authorization' => $this->api2pdfKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'html'     => $html,
                    'fileName' => $filename,
                    'options'  => [
                        'delay'           => 0,
                        'landscape'       => false,
                        'printBackground' => true,
                    ],
                    'inline' => $inline,
                ],
            ]);

            $data = $response->toArray();

            if (!empty($data['FileUrl'])) {
                return [
                    'success' => true,
                    'pdf_url' => $data['FileUrl'],
                    'error'   => null,
                ];
            }

            return [
                'success' => false,
                'pdf_url' => null,
                'error'   => $data['Error'] ?? 'Unknown error from Api2Pdf',
            ];

        } catch (\Exception $e) {
            $this->logger?->error('Api2Pdf error: ' . $e->getMessage());
            return [
                'success' => false,
                'pdf_url' => null,
                'error'   => 'Api2Pdf service error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Download the PDF content from the Api2Pdf URL and return raw bytes.
     *
     * @param string $pdfUrl The URL returned by Api2Pdf
     * @return string|null   Raw PDF bytes, or null on failure
     */
    public function downloadPdf(string $pdfUrl): ?string
    {
        try {
            $response = $this->httpClient->request('GET', $pdfUrl);
            return $response->getContent();
        } catch (\Exception $e) {
            $this->logger?->error('Failed to download PDF: ' . $e->getMessage());
            return null;
        }
    }
}
