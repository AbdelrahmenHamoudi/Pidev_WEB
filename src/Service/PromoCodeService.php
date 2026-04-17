<?php

namespace App\Service;

use App\Entity\PromoCode;
use App\Repository\PromoCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PromoCodeService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(
        private PromoCodeRepository $promoCodeRepo,
        private EntityManagerInterface $em,
        HttpClientInterface $httpClient,
        string $qrCoderApiKey = ''  // Inject via services.yaml
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $qrCoderApiKey;
    }

    // ════════════════════════════════════════════════════════════════════════
    // CODE GENERATION
    // ════════════════════════════════════════════════════════════════════════

    private function generateUniqueCode(): string
    {
        $hex = strtoupper(bin2hex(random_bytes(4)));
        return 'RE7LA-' . substr($hex, 0, 4) . '-' . substr($hex, 4, 4);
    }

    private function buildQrContent(string $code, int $promotionId): string
    {
        return json_encode([
            'code'         => $code,
            'promotion_id' => $promotionId,
            'app'          => 'RE7LA',
        ], JSON_UNESCAPED_SLASHES);
    }

    // ════════════════════════════════════════════════════════════════════════
    // QR CODE GENERATION via QRCoder.co.uk API
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Generate QR code image URL using QRCoder.co.uk API
     * Free tier: 100 requests/day with API key
     * 
     * @param string $content The content to encode in QR
     * @return string URL to QR code image
     */
    public function generateQrImageUrl(string $content): string
    {
        // If no API key configured, use fallback (GoQR.me - no key needed)
        if (empty($this->apiKey)) {
            return $this->generateFallbackQrUrl($content);
        }

        try {
            $response = $this->httpClient->request('GET', 'https://www.qrcoder.co.uk/api/v4/', [
                'query' => [
                    'key' => $this->apiKey,
                    'text' => $content,
                    'size' => '250',  // Size in pixels
                    'format' => 'png'
                ],
                'timeout' => 10
            ]);

            // QRCoder returns the image directly, we return the URL
            // For download/storage, we'd need to save the image
            // But for display, we use the API URL
            return 'https://www.qrcoder.co.uk/api/v4/?key=' . urlencode($this->apiKey) 
                   . '&text=' . urlencode($content) 
                   . '&size=250&format=png';

        } catch (\Exception $e) {
            // Fallback if API fails
            return $this->generateFallbackQrUrl($content);
        }
    }

    /**
     * Download QR code image and save to local storage
     * Returns the local path to the saved image
     */
    public function downloadAndSaveQr(string $content, int $promotionId): string
    {
        $uploadDir = 'uploads/qr_codes/';
        $filename = 'qr_promo_' . $promotionId . '_' . uniqid() . '.png';
        $fullPath = $uploadDir . $filename;

        // Ensure directory exists
        $targetDir = $this->getParameter('kernel.project_dir') . '/public/' . $uploadDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        try {
            if (!empty($this->apiKey)) {
                // Use QRCoder API
                $response = $this->httpClient->request('GET', 'https://www.qrcoder.co.uk/api/v4/', [
                    'query' => [
                        'key' => $this->apiKey,
                        'text' => $content,
                        'size' => '250',
                        'format' => 'png'
                    ],
                    'timeout' => 10
                ]);

                $imageData = $response->getContent();
            } else {
                // Use GoQR.me fallback
                $response = $this->httpClient->request('GET', 'https://api.qrserver.com/v1/create-qr-code/', [
                    'query' => [
                        'size' => '250x250',
                        'data' => $content,
                        'format' => 'png'
                    ],
                    'timeout' => 10
                ]);

                $imageData = $response->getContent();
            }

            file_put_contents($targetDir . $filename, $imageData);
            return $fullPath;

        } catch (\Exception $e) {
            // Return empty string if failed
            return '';
        }
    }

    private function generateFallbackQrUrl(string $content): string
    {
        // GoQR.me - completely free, no key needed
        return 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' 
               . urlencode($content);
    }

    private function getParameter(string $name): mixed
    {
        // Access parameter bag if available, otherwise return empty
        return '';
    }

    // ════════════════════════════════════════════════════════════════════════
    // PROMO CODE CRUD
    // ════════════════════════════════════════════════════════════════════════

    public function createPromoCode(int $promotionId, bool $downloadImage = false): PromoCode
    {
        // Check if active code already exists
        $existing = $this->promoCodeRepo->findActiveCode($promotionId);
        if ($existing) {
            return $existing;
        }

        $code = $this->generateUniqueCode();
        $qrContent = $this->buildQrContent($code, $promotionId);

        $promoCode = new PromoCode();
        $promoCode->setPromotionId($promotionId);
        $promoCode->setCode($code);
        $promoCode->setQrContent($qrContent);

        // Optionally download and save QR image locally
        if ($downloadImage) {
            $localPath = $this->downloadAndSaveQr($qrContent, $promotionId);
            // Could store path in entity if we add a field
        }

        $this->em->persist($promoCode);
        $this->em->flush();

        return $promoCode;
    }

    public function validateCode(string $code, int $promotionId): array
    {
        $promoCode = $this->promoCodeRepo->findOneBy([
            'code'        => strtoupper(trim($code)),
            'promotionId' => $promotionId,
        ]);

        if (!$promoCode) {
            return [
                'valid' => false, 
                'message' => '❌ Code invalide ou ne correspond pas à cette promotion.'
            ];
        }

        if ($promoCode->isUsed()) {
            return [
                'valid' => false, 
                'message' => '❌ Ce code a déjà été utilisé.'
            ];
        }

        return [
            'valid'   => true,
            'message' => '✅ Code validé ! Vous pouvez réserver.',
            'codeId'  => $promoCode->getId(),
            'code'    => $promoCode->getCode()
        ];
    }

    public function markCodeAsUsed(string $code, int $userId): bool
    {
        $promoCode = $this->promoCodeRepo->findOneBy([
            'code' => strtoupper(trim($code))
        ]);

        if (!$promoCode || $promoCode->isUsed()) {
            return false;
        }

        $promoCode->setUsed(true);
        $promoCode->setUsedBy($userId);
        $promoCode->setUsedAt(new \DateTime());
        $this->em->flush();

        return true;
    }

    public function getActiveCode(int $promotionId): ?PromoCode
    {
        return $this->promoCodeRepo->findActiveCode($promotionId);
    }

    /**
     * Get QR image URL for a promotion's active code
     */
    public function getQrImageUrlForPromotion(int $promotionId): ?string
    {
        $code = $this->getActiveCode($promotionId);
        if (!$code) {
            return null;
        }

        return $this->generateQrImageUrl($code->getQrContent());
    }
}