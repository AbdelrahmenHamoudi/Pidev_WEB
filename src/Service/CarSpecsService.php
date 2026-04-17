<?php

namespace App\Service;

class CarSpecsService
{
    private array $brandPresets = [
        'mercedes' => ['puissance' => 250, 'vitesse' => 240, 'accel' => 6.5, 'conso' => 7.5],
        'bmw' => ['puissance' => 260, 'vitesse' => 250, 'accel' => 5.9, 'conso' => 8.0],
        'audi' => ['puissance' => 245, 'vitesse' => 245, 'accel' => 6.2, 'conso' => 7.8],
        'tesla' => ['puissance' => 450, 'vitesse' => 230, 'accel' => 3.5, 'conso' => 0.0],
        'ferrari' => ['puissance' => 700, 'vitesse' => 340, 'accel' => 2.9, 'conso' => 15.0],
        'porsche' => ['puissance' => 400, 'vitesse' => 290, 'accel' => 4.2, 'conso' => 10.5],
        'toyota' => ['puissance' => 130, 'vitesse' => 180, 'accel' => 10.5, 'conso' => 5.2],
        'renault' => ['puissance' => 110, 'vitesse' => 175, 'accel' => 11.2, 'conso' => 5.5],
        'peugeot' => ['puissance' => 120, 'vitesse' => 185, 'accel' => 10.0, 'conso' => 5.4],
        'volkswagen' => ['puissance' => 150, 'vitesse' => 210, 'accel' => 8.5, 'conso' => 6.0],
    ];

    public function suggestSpecs(string $marque, string $modele): array
    {
        $marque = strtolower($marque);
        $modele = strtolower($modele);
        
        // Base specs from brand
        $base = $this->brandPresets[$marque] ?? ['puissance' => 140, 'vitesse' => 190, 'accel' => 9.0, 'conso' => 6.5];

        // Specific model adjustments
        $multiplier = 1.0;
        if (str_contains($modele, 'amg') || str_contains($modele, ' m ') || str_contains($modele, 'rs')) {
            $multiplier = 1.8;
        } elseif (str_contains($modele, 'sport') || str_contains($modele, 'gt')) {
            $multiplier = 1.4;
        } elseif (str_contains($modele, 'suv') || str_contains($modele, 'cross')) {
            $multiplier = 0.9;
        }

        return [
            'puissance' => (int)($base['puissance'] * $multiplier),
            'vitesse_max' => (int)($base['vitesse'] * ($multiplier > 1 ? 1.2 : 1.0)),
            'acceleration' => round($base['accel'] / $multiplier, 1),
            'consommation' => round($base['conso'] * ($multiplier > 1.2 ? 1.5 : 1.0), 1),
            'boite_vitesse' => ($multiplier > 1.3 || $marque === 'tesla') ? 'Automatique' : 'Manuelle'
        ];
    }
}
