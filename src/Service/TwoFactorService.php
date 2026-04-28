<?php

namespace App\Service;

use App\Entity\User_2fa;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class TwoFactorService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Recuperer le User_2fa actif d'un utilisateur
     */
    public function getUser2fa(Users $user): ?User_2fa
    {
        return $this->em->getRepository(User_2fa::class)->findOneBy([
            'user_id' => $user,
            'enabled' => true,
        ]);
    }

    /**
     * Verifier si la 2FA est activee pour un utilisateur
     */
    public function is2faEnabled(Users $user): bool
    {
        return $this->getUser2fa($user) !== null;
    }

    /**
     * Generer un secret TOTP et creer l'entree en DB (pas encore activee)
     */
    public function setup2fa(Users $user): User_2fa
    {
        // Supprimer les anciens setups non actives
        $existing = $this->em->getRepository(User_2fa::class)->findBy([
            'user_id' => $user,
            'enabled' => false,
        ]);

        foreach ($existing as $old) {
            $this->em->remove($old);
        }

        $totp = TOTP::generate();
        $secret = $totp->getSecret();

        $user2fa = new User_2fa();
        $user2fa->setUser_id($user);
        $user2fa->setSecret_key($secret);
        $user2fa->setEnabled(false);
        $user2fa->setCreated_at(new \DateTimeImmutable());
        $user2fa->setBackup_codes('[]');

        $this->em->persist($user2fa);
        $this->em->flush();

        return $user2fa;
    }

    /**
     * Generer le QR Code en Data URI
     */
    public function getQrCodeDataUri(Users $user, string $secret): string
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($user->getE_mail());
        $totp->setIssuer('RE7LA');
        $totp->setPeriod(30);
        $totp->setDigits(6);

        $provisioningUri = $totp->getProvisioningUri();

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($provisioningUri)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(280)
            ->margin(10)
            ->build();

        return $result->getDataUri();
    }

    /**
     * Verifier un code TOTP
     */
    public function verifyCode(string $secret, string $code): bool
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setPeriod(30);
        $totp->setDigits(6);

        return $totp->verify($code, null, 1); // window=1 pour tolerance
    }

    /**
     * Activer la 2FA apres verification du premier code
     */
    public function activate2fa(User_2fa $user2fa): array
    {
        $user2fa->setEnabled(true);

        // Generer 8 codes de secours
        $backupCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $backupCodes[] = strtoupper(bin2hex(random_bytes(4))); // ex: "A1B2C3D4"
        }

        $user2fa->setBackupCodesArray($backupCodes);

        $this->em->flush();

        return $backupCodes;
    }

    /**
     * Desactiver la 2FA
     */
    public function disable2fa(Users $user): void
    {
        $entries = $this->em->getRepository(User_2fa::class)->findBy([
            'user_id' => $user,
        ]);

        foreach ($entries as $entry) {
            $this->em->remove($entry);
        }

        $this->em->flush();
    }

    /**
     * Verifier un code de secours (backup)
     */
    public function verifyBackupCode(User_2fa $user2fa, string $code): bool
    {
        return $user2fa->useBackupCode(strtoupper(trim($code)));
    }
}