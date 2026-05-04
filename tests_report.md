# Rapport de Tests Unitaires - Projet Symfony (RE7LA)

## 1. Choix de l'entité
L'entité choisie pour ce workshop est **Hebergement**.

## 2. Règles métier identifiées
Nous avons défini quatre règles métier critiques pour la validation d'un hébergement :
1. **Titre obligatoire** : Le titre de l'hébergement ne doit pas être vide.
2. **Description minimale** : La description doit contenir au moins 10 caractères pour être informative.
3. **Prix positif** : Le prix par nuit doit être une valeur numérique supérieure à zéro.
4. **Capacité valide** : La capacité d'accueil doit être supérieure à zéro.

## 3. Service métier correspondant
Un service `HebergementManager` a été créé dans `src/Service/Hebergement/HebergementManager.php` pour centraliser cette logique de validation.

```php
public function validate(Hebergement $hebergement): bool
{
    if (empty($hebergement->getTitre())) {
        throw new InvalidArgumentException('Le titre est obligatoire');
    }

    if (strlen($hebergement->getDesc_hebergement() ?? '') < 10) {
        throw new InvalidArgumentException('La description doit contenir au moins 10 caractères');
    }

    if ($hebergement->getPrixParNuitNumeric() <= 0) {
        throw new InvalidArgumentException('Le prix doit être supérieur à zéro');
    }

    $capacite = $hebergement->getCapacite();
    if ($capacite === null || $capacite <= 0) {
        throw new InvalidArgumentException('La capacité doit être supérieure à zéro');
    }

    return true;
}
```

## 4. Implémentation des tests unitaires
La classe de test `HebergementManagerTest` a été implémentée dans `tests/Service/HebergementManagerTest.php`. Elle couvre les scénarios suivants :
- Validation d'un hébergement correct.
- Exception levée pour un titre manquant.
- Exception levée pour une description trop courte (< 10 caractères).
- Exception levée pour un prix invalide (<= 0).
- Exception levée pour une capacité invalide (<= 0).

## 5. Exécution des tests
L'exécution de la commande `php bin/phpunit` confirme que tous les tests passent avec succès.

### Résultat de la console :
```bash
PHPUnit 11.5.50 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12
Configuration: C:\Users\IHEB\Desktop\Pidev_WEB-Integration-final\Pidev_WEB-Integration-final\phpunit.dist.xml

.....                                                               5 / 5 (100%)

Time: 00:01.345, Memory: 8.50 MB

OK (5 tests, 9 assertions)
```

## Conclusion
Les tests unitaires permettent de valider les règles métier de manière isolée et sécurisée. Le service `HebergementManager` est désormais prêt à être utilisé dans le reste de l'application pour garantir l'intégrité des données.
