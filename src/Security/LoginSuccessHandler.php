<?php

namespace App\Security;

use App\Service\TwoFactorService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private TwoFactorService $twoFactorService;
    private RouterInterface $router;

    public function __construct(TwoFactorService $twoFactorService, RouterInterface $router)
    {
        $this->twoFactorService = $twoFactorService;
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        // Verifier si la 2FA est activee
        if ($this->twoFactorService->is2faEnabled($user)) {
            // Stocker en session que la 2FA est en attente
            $session = $request->getSession();
            $session->set('2fa_verified', false);
            $session->set('2fa_user_id', $user->getId());

            // Stocker la destination finale
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