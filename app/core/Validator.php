<?php
/**
 * Validation et nettoyage de toutes les saisies utilisateur.
 */

declare(strict_types=1);

namespace Core;

final class Validator
{
    private array $data;
    private array $erreurs = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $champ, string $libelle): self
    {
        if (!isset($this->data[$champ]) || trim((string) $this->data[$champ]) === '') {
            $this->erreurs[$champ] = $libelle . ' est obligatoire.';
        }

        return $this;
    }

    public function email(string $champ, string $libelle): self
    {
        if (!isset($this->erreurs[$champ]) && !filter_var((string) ($this->data[$champ] ?? ''), FILTER_VALIDATE_EMAIL)) {
            $this->erreurs[$champ] = $libelle . ' n\'est pas une adresse e-mail valide.';
        }

        return $this;
    }

    public function telephone(string $champ, string $libelle, bool $optionnel = false): self
    {
        $valeur = preg_replace('/[\s\.\-]/', '', (string) ($this->data[$champ] ?? '')) ?? '';
        if ($optionnel && $valeur === '') {
            return $this;
        }
        if (!preg_match('/^\+?[0-9]{9,15}$/', $valeur)) {
            $this->erreurs[$champ] = $libelle . ' doit contenir entre 9 et 15 chiffres.';
        }

        return $this;
    }

    public function minLength(string $champ, int $longueur, string $libelle): self
    {
        if (!isset($this->erreurs[$champ]) && mb_strlen(trim((string) ($this->data[$champ] ?? ''))) < $longueur) {
            $this->erreurs[$champ] = $libelle . ' doit contenir au moins ' . $longueur . ' caractères.';
        }

        return $this;
    }

    public function maxLength(string $champ, int $longueur, string $libelle): self
    {
        if (mb_strlen((string) ($this->data[$champ] ?? '')) > $longueur) {
            $this->erreurs[$champ] = $libelle . ' ne doit pas dépasser ' . $longueur . ' caractères.';
        }

        return $this;
    }

    /** Mot de passe robuste : 8 caracteres, majuscule, minuscule, chiffre. */
    public function motDePasse(string $champ, string $libelle = 'Le mot de passe'): self
    {
        $valeur = (string) ($this->data[$champ] ?? '');
        if (mb_strlen($valeur) < 8) {
            $this->erreurs[$champ] = $libelle . ' doit contenir au moins 8 caractères.';
        } elseif (!preg_match('/[A-Z]/', $valeur) || !preg_match('/[a-z]/', $valeur) || !preg_match('/[0-9]/', $valeur)) {
            $this->erreurs[$champ] = $libelle . ' doit mêler majuscules, minuscules et chiffres.';
        }

        return $this;
    }

    public function identique(string $champ, string $champConfirmation, string $libelle): self
    {
        if (($this->data[$champ] ?? null) !== ($this->data[$champConfirmation] ?? null)) {
            $this->erreurs[$champConfirmation] = $libelle . ' ne correspond pas.';
        }

        return $this;
    }

    public function entierPositif(string $champ, string $libelle, int $min = 1, int $max = 100000): self
    {
        $brut = (string) ($this->data[$champ] ?? '');
        if (!preg_match('/^[0-9]+$/', $brut)) {
            $this->erreurs[$champ] = $libelle . ' doit être un nombre entier.';

            return $this;
        }
        $valeur = (int) $brut;
        if ($valeur < $min || $valeur > $max) {
            $this->erreurs[$champ] = $libelle . ' doit être compris entre ' . $min . ' et ' . $max . '.';
        }

        return $this;
    }

    public function dateFuture(string $champ, string $libelle, string $dateMin): self
    {
        $valeur = (string) ($this->data[$champ] ?? '');
        $ts     = strtotime($valeur);
        if ($ts === false) {
            $this->erreurs[$champ] = $libelle . ' est une date invalide.';

            return $this;
        }
        if (date('Y-m-d', $ts) < $dateMin) {
            $this->erreurs[$champ] = $libelle . ' doit être au plus tôt le ' . date('d/m/Y', strtotime($dateMin)) . '.';
        }

        return $this;
    }

    public function dansListe(string $champ, array $valeurs, string $libelle): self
    {
        if (!in_array($this->data[$champ] ?? null, $valeurs, true)) {
            $this->erreurs[$champ] = $libelle . ' est invalide.';
        }

        return $this;
    }

    /** Ajoute une erreur personnalisee sur un champ. */
    public function ajouterErreur(string $champ, string $message): self
    {
        $this->erreurs[$champ] = $message;

        return $this;
    }

    public function echoue(): bool
    {
        return $this->erreurs !== [];
    }

    public function erreurs(): array
    {
        return $this->erreurs;
    }

    public function premiereErreur(): ?string
    {
        return $this->erreurs === [] ? null : (string) reset($this->erreurs);
    }

    /** Valeur nettoyee d'un champ (trim + suppression des caracteres de controle). */
    public function valeur(string $champ, string $defaut = ''): string
    {
        $valeur = trim((string) ($this->data[$champ] ?? $defaut));

        return preg_replace('/[\x00-\x1F\x7F]/u', '', $valeur) ?? $defaut;
    }
}
