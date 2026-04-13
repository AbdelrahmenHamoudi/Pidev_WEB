<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TwoFactorListener
{
    private TokenStorageInterface $tokenStorage;
    private RouterInterface $router;

    // Routes accessibles meme sans 2FA verifiee
    private const ALLOWED_ROUTES = [
        'app_2fa_verify',
        'app_2fa_disable',
        'app_login',
        'app_logout',
        'app_register',
        'app_verify_email',
        'app_resend_verification',
        'app_forgot_password',
        'app_reset_password',
    ];

    public function __construct(TokenStorageInterface $tokenStorage, RouterInterface $router)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Laisser passer les routes autorisees
        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        // Laisser passer les assets et le profiler
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/_') || str_starts_with($path, '/css') || str_starts_with($path, '/js') || str_starts_with($path, '/images') || str_starts_with($path, '/assets') || str_starts_with($path, '/uploads')) {
            return;
        }

        // Verifier si un utilisateur est connecte
        $token = $this->tokenStorage->getToken();
        if (!$token || !is_object($token->getUser())) {
            return;
        }

        $session = $request->getSession();

        // Si la 2FA est en attente et pas encore verifiee
        if ($session->has('2fa_verified') && $session->get('2fa_verified') === false) {
            $event->setResponse(
                new RedirectResponse($this->router->generate('app_2fa_verify'))
            );
        }
    }
}