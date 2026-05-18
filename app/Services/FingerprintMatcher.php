<?php

namespace App\Services;

/**
 * Comparateur de minuties FMR_ISO (ISO/IEC 19794-2:2005).
 *
 * Le SDK Thales produit des templates FMR_ISO par doigt segmenté.
 * Deux captures du même doigt produisent des templates légèrement différents
 * (décalage de quelques pixels, minuties manquantes ou ajoutées selon la pose).
 * Une comparaison exacte (hash SHA-256) ne détecte donc jamais les doublons
 * inter-sessions. Ce service implémente un matching basé sur les minuties :
 * on compte le nombre de minuties correspondantes entre deux templates,
 * normalisé par la taille du plus grand des deux ensembles.
 *
 * Algorithme : matching glouton sans alignement rigide.
 *   Pour chaque minutie A, on cherche la minutie B la plus proche telle que :
 *   - Le type (terminaison / bifurcation) est identique
 *   - La distance euclidienne ≤ DIST_TOLERANCE pixels
 *   - La différence d'angle ≤ ANGLE_TOLERANCE (sur 256 unités = 360°)
 *   Score = minuties_correspondantes / max(|A|, |B|)
 *
 * Limites : sans alignement préalable, le score est sensible aux translations
 * et rotations importantes. Pour une véritable comparaison biométrique AFIS,
 * une licence DactyMatch Thales est nécessaire. Ce matcher convient à la
 * détection de doublons évidents (même personne re-enregistrée le même jour
 * ou peu après, même position de main).
 */
class FingerprintMatcher
{
    /** Tolérance spatiale en pixels */
    private const DIST_TOLERANCE = 20;

    /** Tolérance angulaire (sur 256 unités = 360°, soit ~28°) */
    private const ANGLE_TOLERANCE = 20;

    /**
     * Seuil de similarité : fraction minimale de minuties correspondantes
     * pour considérer deux templates comme appartenant au même doigt.
     * 0.35 = 35 %  →  seuil intermédiaire (bon compromis FAR/FRR)
     */
    public const MATCH_THRESHOLD = 0.35;

    // ─────────────────────────────────────────────────────────────────────────
    // API publique
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Compare deux templates FMR_ISO (base64).
     * Retourne un score de similarité entre 0.0 (aucun rapport) et 1.0 (identique).
     */
    public static function matchScore(string $templateB64A, string $templateB64B): float
    {
        $mA = self::parseMinutiae($templateB64A);
        $mB = self::parseMinutiae($templateB64B);

        if (empty($mA) || empty($mB)) {
            return 0.0;
        }

        $distSqMax = self::DIST_TOLERANCE ** 2;
        $matched   = 0;

        foreach ($mA as $a) {
            foreach ($mB as $b) {
                // Types différents → impossible (terminaison ≠ bifurcation)
                if ($a['t'] !== $b['t']) {
                    continue;
                }

                // Distance euclidienne² (évite sqrt pour la perf)
                $dx = $a['x'] - $b['x'];
                $dy = $a['y'] - $b['y'];
                if ($dx * $dx + $dy * $dy > $distSqMax) {
                    continue;
                }

                // Angle circulant (0-255 → 0-360°)
                $da = abs($a['a'] - $b['a']);
                if ($da > 128) {
                    $da = 256 - $da; // gestion du wrap-around
                }
                if ($da > self::ANGLE_TOLERANCE) {
                    continue;
                }

                $matched++;
                break; // chaque minutie de A ne peut matcher qu'une minutie de B
            }
        }

        return $matched / max(count($mA), count($mB));
    }

    /**
     * Vérifie si l'un des templates dans $newTemplates correspond biométriquement
     * à l'un des templates dans $storedTemplates.
     * Retourne le score le plus élevé trouvé (0.0 si aucun match).
     * Sort immédiatement dès que le seuil est atteint (optimisation 1:N).
     *
     * @param string[] $newTemplates    Tableau de base64 FMR_ISO (nouvellement capturés)
     * @param string[] $storedTemplates Tableau de base64 FMR_ISO (stockés en DB)
     * @param float    $threshold       Seuil (défaut : MATCH_THRESHOLD)
     */
    public static function bestScore(
        array  $newTemplates,
        array  $storedTemplates,
        float  $threshold = self::MATCH_THRESHOLD
    ): float {
        $best = 0.0;
        foreach ($newTemplates as $newT) {
            foreach ($storedTemplates as $storedT) {
                $score = self::matchScore($newT, $storedT);
                if ($score > $best) {
                    $best = $score;
                }
                if ($best >= $threshold) {
                    return $best; // sortie anticipée
                }
            }
        }
        return $best;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Parsing FMR_ISO (ISO/IEC 19794-2:2005)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Extrait les minuties d'un template FMR_ISO encodé en base64.
     * Retourne un tableau de ['t' => type, 'x' => int, 'y' => int, 'a' => int].
     * Retourne [] en cas d'échec de parsing.
     *
     * Structure binaire :
     *   Header (30 octets) :
     *     [0-3]  "FMR\x00"         magic
     *     [4-7]  " 20\x00" ou " 20" version
     *     [8-11] total_length       uint32 big-endian
     *     [12-13] CBEFF product type  uint16
     *     [14-15] CBEFF product owner uint16
     *     [16-17] équipement compliance uint16
     *     [18-19] équipement ID         uint16
     *     [20-21] largeur image en px   uint16
     *     [22-23] hauteur image en px   uint16
     *     [24-25] résolution X px/cm    uint16
     *     [26-27] résolution Y px/cm    uint16
     *     [28]    nb vues               uint8
     *     [29]    réservé               uint8
     *
     *   Par vue doigt :
     *     [0]  position doigt        uint8
     *     [1]  n° vue | impression    uint8
     *     [2]  qualité                uint8
     *     [3]  nb minuties            uint8
     *     [4..4+nb*6-1] minuties (6 octets chacune)
     *     [4+nb*6]   longueur données étendue  uint16
     *     [4+nb*6+2..] données étendues
     *
     *   Par minutie (6 octets) :
     *     [0-1]  type(2b) | X(14b)   uint16 big-endian
     *     [2-3]  rés(2b)  | Y(14b)   uint16 big-endian
     *     [4]    angle (0-255 = 0-360°)
     *     [5]    qualité
     */
    private static function parseMinutiae(string $base64): array
    {
        $bin = base64_decode($base64, true);
        if ($bin === false || strlen($bin) < 30) {
            return [];
        }

        // Vérification du magic FMR\x00
        if (substr($bin, 0, 4) !== "FMR\x00") {
            return [];
        }

        $len      = strlen($bin);
        $numViews = ord($bin[28]);
        if ($numViews === 0) {
            return [];
        }

        $offset   = 30; // premier octet après le header
        $minutiae = [];

        for ($v = 0; $v < $numViews; $v++) {
            if ($offset + 4 > $len) {
                break;
            }

            // Octets 0-2 : ignorés (position, vue/impression, qualité)
            $numMin  = ord($bin[$offset + 3]);
            $offset += 4;

            for ($m = 0; $m < $numMin; $m++) {
                if ($offset + 6 > $len) {
                    break 2; // sortie des deux boucles si données incomplètes
                }

                // type(2b) | X(14b)  — uint16 big-endian
                $typeX = (ord($bin[$offset]) << 8) | ord($bin[$offset + 1]);
                // rés(2b) | Y(14b)  — uint16 big-endian
                $resY  = (ord($bin[$offset + 2]) << 8) | ord($bin[$offset + 3]);
                // Angle 0-255
                $angle = ord($bin[$offset + 4]);
                $offset += 6;

                $type = ($typeX >> 14) & 0x03;
                $x    = $typeX & 0x3FFF;
                $y    = $resY  & 0x3FFF;

                // Type 0 = "autre" (non classé) — exclu du matching
                if ($type === 0) {
                    continue;
                }

                $minutiae[] = ['t' => $type, 'x' => $x, 'y' => $y, 'a' => $angle];
            }

            // Bloc de données étendues : uint16 longueur + données
            if ($offset + 2 <= $len) {
                $extLen  = (ord($bin[$offset]) << 8) | ord($bin[$offset + 1]);
                $offset += 2 + $extLen;
            }
        }

        return $minutiae;
    }
}
