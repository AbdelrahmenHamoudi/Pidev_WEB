# Rapport de Tests Unitaires - Projet Symfony

## 1. Choix de l'entité
L'entité choisie pour ce workshop est **Hebergement**.

## 2. Règles métier identifiées
Nous avons défini trois règles métier critiques pour la validation d'un hébergement :
1. **Titre obligatoire** : Le titre de l'hébergement ne doit pas être vide.
2. **Prix positif** : Le prix par nuit doit être une valeur numérique supérieure à zéro.
3. **Capacité valide** : La capacité d'accueil doit être supérieure à zéro.

## 3. Service métier correspondant
Un service `HebergementManager` a été créé dans `src/Service/HebergementManager.php` pour centraliser cette logique de validation.

```php
// Extrait de HebergementManager.php
public function validate(Hebergement $hebergement): bool
{
    if (empty($hebergement->getTitre())) {
        throw new InvalidArgumentException('Le titre est obligatoire');
    }

    if ($hebergement->getPrixParNuitNumeric() <= 0) {
        throw new InvalidArgumentException('Le prix doit être supérieur à zéro');
    }

    if ($hebergement->getCapacite() <= 0) {
        throw new InvalidArgumentException('La capacité doit être supérieure à zéro');
    }

    return true;
}
```

## 4. Implémentation des tests unitaires
La classe de test `HebergementManagerTest` a été générée et implémentée dans `tests/Service/HebergementManagerTest.php`. Elle couvre les scénarios suivants :
- Validation d'un hébergement correct.
- Exception levée pour un titre manquant.
- Exception levée pour un prix invalide (<= 0).
- Exception levée pour une capacité invalide (<= 0).

## 5. Exécution des tests
L'exécution de la commande `php bin/phpunit` confirme que tous les tests passent avec succès.

### Résultat de la console :
```bash
PHPUnit 11.5.50 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\Users\IHEB\Desktop\symfony\Re7la_web hebergement\phpunit.dist.xml

....                                                                4 / 4 (100%)

Time: 00:01.238, Memory: 8.00 MB

OK (4 tests, 7 assertions)
```

## Conclusion
Les tests unitaires permettent de valider les règles métier de manière isolée et sécurisée. Le service `HebergementManager` est désormais prêt à être utilisé dans le reste de l'application pour garantir l'intégrité des données.
