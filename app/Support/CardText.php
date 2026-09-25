<?php

namespace App\Support;

class CardText
{
    /**
     * Réduit proportionnellement la taille de police d'un champ de carte (nom,
     * prénom(s), classe...) quand le texte dépasse la largeur prévue, plutôt que
     * de le laisser tronqué par overflow:hidden — utile pour les élèves ayant
     * plusieurs prénoms ou un nom de famille composé.
     */
    public static function fitFontSize(?string $value, float $base, int $threshold, float $min): float
    {
        $len = mb_strlen((string) $value);

        if ($len <= $threshold) {
            return $base;
        }

        return max($min, round($base * $threshold / $len, 1));
    }
}
