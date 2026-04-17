<?php

namespace App\Controller\Hebergement;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route('/change-locale/{locale}', name: 'app_change_locale')]
    public function changeLocale(string $locale, Request $request): Response
    {
        $supported = ['fr', 'en', 'ar'];
        if (!in_array($locale, $supported)) {
            $locale = 'fr';
        }

        $request->getSession()->set('_locale', $locale);
        
        // Add cookie for JS
        $response = $this->getRedirectResponse($request);
        $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('USER_LOCALE', $locale, time() + (3600 * 24 * 30), '/'));
        
        return $response;
    }

    #[Route('/change-currency/{currency}', name: 'app_change_currency')]
    public function changeCurrency(string $currency, Request $request): Response
    {
        $supported = ['TND', 'EUR', 'USD', 'GBP'];
        if (!in_array($currency, $supported)) {
            $currency = 'TND';
        }

        $request->getSession()->set('_currency', $currency);
        
        $response = $this->getRedirectResponse($request);
        // Add cookie for JS manager
        $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('USER_CURRENCY', $currency, time() + (3600 * 24 * 30), '/'));
        
        return $response;
    }

    private function getRedirectResponse(Request $request): Response
    {
        $referer = $request->headers->get('referer');
        if (!$referer) {
            return $this->redirectToRoute('app_home');
        }

        return $this->redirect($referer);
    }
}
