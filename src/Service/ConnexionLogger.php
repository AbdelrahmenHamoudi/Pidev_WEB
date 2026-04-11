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

        $log = new Connexion_logs();
        $log->setUser_id($user);
        $log->setSuccess($success);
        $log->setFailure_reason($failureReason);
        $log->setIp_address($request?->getClientIp() ?? '0.0.0.0');
        $log->setDevice_info($request?->headers->get('User-Agent') ?? '');

        $this->em->persist($log);
        $this->em->flush();
    }

    public function logLogout(Users $user): void
    {
        // Marquer le dernier log de connexion réussie comme logout
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
}