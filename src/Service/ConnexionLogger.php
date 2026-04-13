<?php

namespace App\Service;

use App\Entity\Connexion_logs;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ConnexionLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private GeoLocationService $geoService,
    ) {}

    public function logSuccess(Users $user): void
    {
        $this->save($user, true, null);
    }

    public function logFailure(?Users $user, string $reason): void
    {
        $this->save($user, false, $reason);
    }

    private function save(?Users $user, bool $success, ?string $failureReason): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $ip = $request?->getClientIp() ?? '0.0.0.0';
        $userAgent = $request?->headers->get('User-Agent') ?? '';

        // Si IP locale, recuperer l'IP publique reelle
        $geoIp = $ip;
        if ($this->isLocalIp($ip)) {
            $publicIp = $this->getPublicIp();
            if ($publicIp) {
                $geoIp = $publicIp;
            }
        }

        // Geolocaliser l'IP
        $location = $this->geoService->getLocationFromIp($geoIp);

        // Parser le device info
        $deviceInfo = $this->parseDeviceInfo($userAgent);

        $log = new Connexion_logs();
        $log->setUser_id($user);
        $log->setSuccess($success);
        $log->setFailure_reason($failureReason);
        $log->setIp_address($ip);
        $log->setDevice_info($deviceInfo);
        $log->setCountry($location['country']);
        $log->setCity($location['city']);
        $log->setLatitude($location['latitude']);
        $log->setLongitude($location['longitude']);
        $log->setLogin_time(new \DateTimeImmutable());

        $this->em->persist($log);
        $this->em->flush();
    }

    public function logLogout(Users $user): void
    {
        $repo = $this->em->getRepository(Connexion_logs::class);
        $last = $repo->findOneBy(
            ['user_id' => $user, 'success' => true],
            ['login_time' => 'DESC']
        );

        if ($last && $last->getLogout_time() === null) {
            $last->setLogout_time(new \DateTimeImmutable());
            $this->em->flush();
        }
    }

    /**
     * Recuperer l'IP publique reelle via une API externe
     */
    private function getPublicIp(): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'method' => 'GET',
                ],
            ]);

            $ip = @file_get_contents('https://api.ipify.org', false, $context);

            if ($ip !== false && filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                return trim($ip);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verifier si c'est une IP locale
     */
    private function isLocalIp(string $ip): bool
    {
        $localIps = ['127.0.0.1', '::1', '0.0.0.0', 'localhost'];

        if (in_array($ip, $localIps, true)) {
            return true;
        }

        if (
            str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '172.16.') ||
            str_starts_with($ip, '172.17.') ||
            str_starts_with($ip, '172.18.') ||
            str_starts_with($ip, '172.19.') ||
            str_starts_with($ip, '172.2') ||
            str_starts_with($ip, '172.30.') ||
            str_starts_with($ip, '172.31.')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Extraire les infos device depuis le User-Agent
     */
    private function parseDeviceInfo(string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Inconnu';
        }

        $browser = 'Navigateur inconnu';
        $os = 'OS inconnu';

        if (str_contains($userAgent, 'Edg/')) {
            $browser = 'Microsoft Edge';
        } elseif (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera')) {
            $browser = 'Opera';
        } elseif (str_contains($userAgent, 'Chrome/')) {
            $browser = 'Google Chrome';
        } elseif (str_contains($userAgent, 'Firefox/')) {
            $browser = 'Mozilla Firefox';
        } elseif (str_contains($userAgent, 'Safari/') && !str_contains($userAgent, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident/')) {
            $browser = 'Internet Explorer';
        }

        if (str_contains($userAgent, 'Windows NT 10')) {
            $os = 'Windows 10/11';
        } elseif (str_contains($userAgent, 'Windows NT')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac OS X')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            if (str_contains($userAgent, 'Android')) {
                $os = 'Android';
            } else {
                $os = 'Linux';
            }
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $os = 'iOS';
        }

        return $browser . ' / ' . $os;
    }
}