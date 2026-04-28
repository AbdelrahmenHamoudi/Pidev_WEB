<?php

namespace App\EventListener;

use App\Service\RecaptchaValidator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RecaptchaRequestListener
{
    private RecaptchaValidator $recaptcha;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(RecaptchaValidator $recaptcha, UrlGeneratorInterface $urlGenerator)
    {
        $this->recaptcha = $recaptcha;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->isMethod('POST')) {
            return;
        }

        $route = $request->attributes->get('_route');

        // Vérifier reCAPTCHA pour le LOGIN uniquement
        // check_path dans security.yaml pointe vers app_login
        if ($route === 'app_login') {
            $token = $request->request->get('g-recaptcha-response');
            $ip = $request->getClientIp();

            if (!$this->recaptcha->verify($token, $ip)) {
                $request->getSession()->getFlashBag()->add(
                    'error',
                    'Veuillez confirmer que vous n\'êtes pas un robot.'
                );
                $event->setResponse(
                    new RedirectResponse($this->urlGenerator->generate('app_login'))
                );
            }
        }
    }
}