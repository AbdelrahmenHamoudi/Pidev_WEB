<?php

namespace App\Service;

use App\Entity\Users;

class UserManager
{
    /**
     * Valide les données d'un utilisateur selon les règles métier.
     * @throws \InvalidArgumentException si une règle est violée
     */
    public function validate(Users $user): bool
    {
        // Règle 1 : Le nom est obligatoire
        if (empty(trim($user->getNom() ?? ''))) {
            throw new \InvalidArgumentException('Le nom est obligatoire.');
        }

        // Règle 2 : Le prénom est obligatoire
        if (empty(trim($user->getPrenom() ?? ''))) {
            throw new \InvalidArgumentException('Le prénom est obligatoire.');
        }

        // Règle 3 : L'email doit être valide
        if (!filter_var($user->getE_mail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'adresse email est invalide.');
        }

        // Règle 4 : Le numéro de téléphone doit contenir exactement 8 chiffres
        if (!preg_match('/^\d{8}$/', $user->getNum_tel() ?? '')) {
            throw new \InvalidArgumentException('Le numéro de téléphone doit contenir exactement 8 chiffres.');
        }

        // Règle 5 : Le mot de passe doit avoir au moins 8 caractères
        if (strlen($user->getMot_de_pass() ?? '') < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        // Règle 6 : Le rôle doit être valide (déjà géré dans l'entité, on vérifie ici aussi)
        if (!in_array($user->getRole(), ['user', 'admin'], true)) {
            throw new \InvalidArgumentException('Le rôle doit être "user" ou "admin".');
        }

        // Règle 7 : Le statut doit être valide
        if (!in_array($user->getStatus(), ['Banned', 'Unbanned'], true)) {
            throw new \InvalidArgumentException('Le statut doit être "Banned" ou "Unbanned".');
        }

        return true;
    }
}