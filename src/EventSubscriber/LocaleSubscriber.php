<?php

namespace App\EventSubscriber;

use App\Service\Hebergement\GeoDetectionService;
use App\Service\Hebergement\CurrencyService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class LocaleSubscriber implements EventSubscriberInterface
{
    // ✅ CORRECTION 2 : ajout du type string à la propriété
    private string $defaultLocale;

    public function __construct(
        private GeoDetectionService $geoService,
        private CurrencyService $currencyService,
        private Environment $twig,
        string $defaultLocale = 'fr'
    ) {
        $this->defaultLocale = $defaultLocale;
    }

    // ✅ CORRECTION 2 : ajout du type de retour void
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Default globals to prevent Twig errors
        $this->twig->addGlobal('current_currency', 'TND');
        $this->twig->addGlobal('current_currency_symbol', 'DT');

        if (!$request->hasPreviousSession()) {
            return;
        }

        $session = $request->getSession();
        $ip = $request->getClientIp();
        $prefs = null;

        // 1. Handle Locale
        $locale = $session->get('_locale');
        if (!$locale) {
            $prefs = $this->geoService->detectPreferences($ip ?? '');
            $locale = $prefs['locale'];
            $session->set('_locale', $locale);
        }
        $request->setLocale($locale);

        // 2. Handle Currency
        $currency = $session->get('_currency');
        if (!$currency) {
            if (!$prefs) {
                $prefs = $this->geoService->detectPreferences($ip ?? '');
            }
            $currency = $prefs['currency'];
            $session->set('_currency', $currency);
        }

        // 3. Inject into Twig Globals
        $this->twig->addGlobal('current_currency', $currency);
        $this->twig->addGlobal('current_currency_symbol', $this->currencyService->getSymbol($currency));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}