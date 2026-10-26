---
name: kh-design-system
description: Construire ou modifier une interface publique ou un composant Blade de Kilakodon Hub selon le design system (tokens bleu/rouge, Fraunces/Inter, cartes, artefacts, animations légères). À charger avant toute vue.
---

# Appliquer le design system Kilakodon

Référence complète : `docs/UX_UI_DESIGN.md`. Résumé opérationnel :

## Avant d'écrire une vue
1. Vérifier si un composant `x-kh.*` existe déjà (`resources/views/components/kh/`). Le réutiliser ; sinon, le créer **générique** et l'ajouter à `/_styleguide`.
2. Identifier la structure de la page dans UX §5 et suivre l'ordre des sections.

## Règles visuelles
- Fond `canvas`, bandes alternées `fog`. Cartes `mist`, rayon 24, **sans ombre au repos**.
- Bouton primaire : pilule `kh-blue-700`, toujours avec un bouton `ghost` à côté quand il y a deux actions.
- H1/H2 : `font-display` (Fraunces) graisse 400. Au maximum **un** fragment `<em>` par titre, rendu en italique `kh-red-600`, et uniquement sur les titres clés.
- Le rouge est rare : < 3 % de la surface, une seule `card-accent` rouge par page.
- Tout chiffre dans un artefact porte l'étiquette « Démo ».
- Aucune couleur hexadécimale dans les vues : uniquement les tokens Tailwind (`bg-kh-blue-700`, `text-slate`, …).
- Icônes Lucide via `x-kh.icon`.

## Animations
- Uniquement `transform` et `opacity`. Apparitions : `data-reveal` + `style="--i:n"` (6 éléments maximum en cascade).
- Survol des cartes : `-translate-y-1` + `shadow-hover` ; boutons : flèche qui glisse.
- Respect de `prefers-reduced-motion` ; le contenu doit rester visible si le JS ne charge pas (`.js [data-reveal]`).
- Pas de bibliothèque d'animation. Les animations continues (artefacts du hero) se mettent en pause hors écran.

## Accessibilité (à vérifier à chaque vue)
- Labels sur tous les champs, erreurs liées (`aria-describedby`), focus visible, cibles ≥ 44 px, hiérarchie de titres correcte, `alt` pertinents (vide pour le décor), sphère de points en `aria-hidden`.

## Performance
- Images avec `width`/`height`, AVIF/WebP, `loading="lazy"` hors hero. Pas de Livewire sur les pages de contenu. Le widget de l'assistant est chargé à l'ouverture.

## Vérification
Contrôler la page à 360 px, 768 px et 1280 px ; lancer Lighthouse sur les pages clés en fin de lot.
