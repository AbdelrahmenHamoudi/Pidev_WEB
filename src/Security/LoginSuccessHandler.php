<?php

namespace App\Security;

use App\Service\ConnexionLogger;
use App\Service\TwoFactorService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private TwoFactorService $twoFactorService;
    private ConnexionLogger $connexionLogger;
    private RouterInterface $router;

    public function __construct(
        TwoFactorService $twoFactorService,
        ConnexionLogger $connexionLogger,
        RouterInterface $router
    ) {
        $this->twoFactorService = $twoFactorService;
        $this->connexionLogger = $connexionLogger;
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        // Logger la connexion reussie avec geolocalisation
        $this->connexionLogger->logSuccess($user);

        // Verifier si la 2FA est activee
        if ($this->twoFactorService->is2faEnabled($user)) {
            $session = $request->getSession();
            $session->set('2fa_verified', false);
            $session->set('2fa_user_id', $user->getId());

            if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                $session->set('2fa_target_path', '/admin');
            } else {
                $session->set('2fa_target_path', '/');
            }

            return new RedirectResponse($this->router->generate('app_2fa_verify'));
        }

        // Pas de 2FA : redirection normale
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse('/admin');
        }

        return new RedirectResponse('/');
    }
}