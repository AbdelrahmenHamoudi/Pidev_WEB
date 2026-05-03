<?php

namespace App\Service;

use App\Entity\Admin_action_logs;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AdminActionLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
    ) {}

    public function log(
        Users $admin,
        string $actionType,
        string $targetType,
        int $targetId,
        string $targetDescription,
        string $details = ''
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request?->getClientIp() ?? '0.0.0.0';

        $log = new Admin_action_logs();
        $log->setAdmin_id($admin);
        $log->setAdmin_name($admin->getPrenom() . ' ' . $admin->getNom());
        $log->setAction_type($actionType);
        $log->setTarget_type($targetType);
        $log->setTarget_id($targetId);
        $log->setTarget_description($targetDescription);
        $log->setDetails($details);
        $log->setIp_address($ip);

        $this->em->persist($log);
        $this->em->flush();
    }
}