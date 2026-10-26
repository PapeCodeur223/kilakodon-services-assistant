# UX / UI DESIGN — Kilakodon Hub

> Objectif : garder l'identité de kilakodon-services.com (logo, bleu et rouge) et lui donner une tenue d'**agence premium** : sobre, éditoriale, rapide.
> Inspiration de style : référence « Steep » (fournie) — serif d'affichage aérien sur fond blanc, cartes douces, boutons pilule, fragments de produit qui « flottent ». **On reprend la structure et la retenue, pas la palette** : le noir/pêche de Steep est remplacé par le **bleu et le rouge du logo**.

---

## 1. Direction artistique

**« Éditorial confiant, ancré à Bamako. »**
- 90 % de surfaces neutres (blanc, gris très légèrement bleuté). Le **bleu Kilakodon** porte la structure et l'action ; le **rouge Kilakodon** est une ponctuation rare (un mot en italique dans un titre, un indicateur, une carte d'accent par page).
- Les titres utilisent un serif à graisse 400, grand et calme : l'autorité sans crier.
- Les visuels sont des **fragments de produit** (brief, alerte de stock, mini-graphique, champ de l'assistant) posés autour des titres, jamais des photos de stock (poignées de main, open spaces).
- Motif signature : la **sphère de points** du logo (trame en demi-teinte), réutilisée en décor discret.
- Le site doit paraître *rapide et précis*, comme les logiciels que vous vendez.

## 2. Logo

- Fichier source : `resources/brand/logo-kilakodon.png` (512×512, fond transparent). **Ne pas redessiner ni recolorer le logo.**
- À produire en lot 0 : version recadrée horizontale (sphère + texte) pour le header, en WebP et PNG @1x/@2x ; favicon et `apple-touch-icon` à partir de la sphère seule ; version blanche **uniquement** si la direction la fournit (sinon, sur fond bleu foncé, placer le logo dans une pastille blanche arrondie).
- Hauteur dans le header : 36 px mobile, 44 px desktop. Zone de protection : la hauteur d'un point de la sphère, tout autour.

## 3. Tokens

### 3.1 Couleurs
Valeurs mesurées sur le logo : bleu `#282A89`, rouge `#F91C0C`.

| Token | Valeur | Rôle | Contraste sur blanc |
|---|---|---|---|
| `--color-kh-blue-950` | `#12134A` | Fond de la bande CTA finale, pied de page | 17,9 |
| `--color-kh-blue-900` | `#1C1E66` | Survol des boutons primaires, titres sur fond clair bleuté | 14,7 |
| `--color-kh-blue-700` | `#282A89` | **Couleur de marque** : bouton primaire, liens actifs, icônes | 11,8 |
| `--color-kh-blue-500` | `#4A4FC0` | Focus ring, graphiques | ≈ 6,6 |
| `--color-kh-blue-200` | `#C9CBEE` | Bordures accentuées, points du motif | — |
| `--color-kh-blue-100` | `#E3E4F6` | Puces, fonds de tags | — |
| `--color-kh-blue-50` | `#F1F2FB` | Cartes d'accent bleues, surbrillance | — |
| `--color-kh-red-500` | `#F91C0C` | **Rouge du logo** : décor uniquement, ou texte ≥ 24 px | 4,0 (grand texte seulement) |
| `--color-kh-red-600` | `#D4170A` | Rouge **fonctionnel** : mot en italique des titres, badges, erreurs | 5,35 ✔ AA |
| `--color-kh-red-900` | `#6B0E07` | Texte sur carte d'accent rouge | — |
| `--color-kh-red-50` | `#FEEDEB` | Carte d'accent rouge (**une seule par page**) | — |
| `--color-ink` | `#17191C` | Texte principal | 17,6 |
| `--color-slate` | `#64687A` | Texte secondaire, légendes | 5,5 ✔ AA |
| `--color-smoke` | `#9A9DAA` | Placeholders, désactivé (jamais pour du texte utile) | — |
| `--color-line` | `#E6E7EE` | Filets, bordures d'inputs | — |
| `--color-canvas` | `#FFFFFF` | Fond principal | — |
| `--color-fog` | `#FAFAFC` | Bandes alternées | — |
| `--color-mist` | `#F2F3F7` | Cartes neutres, fonds d'inputs | — |
| `--color-success-600` | `#147A4B` | Succès | ✔ |
| `--color-warning-600` | `#9A5B00` | Avertissement | ✔ |
| `--color-whatsapp` | `#25D366` | **Uniquement** l'icône WhatsApp (jamais un fond de bouton plein) | — |

**Règle de dosage :** sur une page, le rouge occupe < 3 % de la surface visible.

### 3.2 Typographie
| Rôle | Police | Remarques |
|---|---|---|
| Affichage (H1, H2, grands chiffres) | **Fraunces** variable, graisse **400**, `font-variation-settings: "SOFT" 50, "WONK" 0, "opsz" 72` | Italique pour le mot d'accent. Ne jamais dépasser 500 |
| Texte et interface | **Inter** variable, graisses 400 / 450 / 500 / 600 (600 seulement pour les libellés de boutons ≤ 15 px et les données) | `font-feature-settings: "cv11", "ss01"` |

Échelle fluide (mobile → desktop) :
| Token | Taille | Interlignage | Interlettrage | Usage |
|---|---|---|---|---|
| `--text-display` | `clamp(2.75rem, 1.6rem + 4.6vw, 5.5rem)` (44 → 88 px) | 1.08 | -0.025em | H1 de l'accueil |
| `--text-h1` | `clamp(2.25rem, 1.6rem + 2.6vw, 4rem)` (36 → 64 px) | 1.12 | -0.018em | H1 des pages internes |
| `--text-h2` | `clamp(1.875rem, 1.4rem + 1.9vw, 2.75rem)` (30 → 44 px) | 1.18 | -0.015em | Titres de section |
| `--text-h3` | `1.5rem` → `1.625rem` | 1.25 | -0.009em | Inter 500 — titres de cartes |
| `--text-lead` | `1.125rem` → `1.25rem` | 1.5 | 0 | Chapeaux |
| `--text-body` | `1.0625rem` (17 px) | 1.6 | 0 | Corps |
| `--text-small` | `0.9375rem` (15 px) | 1.5 | 0 | Légendes |
| `--text-tag` | `0.8125rem` (13 px), Inter 500, MAJUSCULES, `letter-spacing: 0.08em` | 1.2 | | Sur-titres (eyebrows) |

Polices auto-hébergées (`@fontsource-variable/fraunces` normal + italique, `@fontsource-variable/inter`), sous-ensemble latin + latin-ext, `font-display: swap`. Préchargement de Fraunces normale uniquement.

### 3.3 Espacements, rayons, ombres, mise en page
- Base 4 px. Échelle : 4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96, 128.
- Sections : `padding-block: clamp(64px, 8vw, 128px)`. Conteneur : `max-width: 1200px`, gouttière 20 px mobile et 32 px desktop. Contenu texte long : `max-width: 68ch`.
- Rayons : boutons `9999px` ; cartes `24px` ; petites cartes et inputs `16px` ; artefacts flottants `20px` ; images `12px`.
- Ombres :
  - `--shadow-artifact: 0 0 0 1px rgb(40 42 137 / .06), 0 20px 25px -5px rgb(18 19 74 / .10), 0 8px 10px -6px rgb(18 19 74 / .08)` — artefacts flottants **uniquement**.
  - `--shadow-pop: 0 0 0 1px rgb(40 42 137 / .06), 0 8px 40px rgb(18 19 74 / .12)` — modales, panneau assistant.
  - `--shadow-hover: 0 0 0 1px rgb(40 42 137 / .08), 0 12px 32px -8px rgb(18 19 74 / .16)` — état survol des cartes.
  - Les cartes au repos n'ont **pas** d'ombre.
- Points de rupture : `sm 640`, `md 768`, `lg 1024`, `xl 1280`. Conception **mobile d'abord** (360 px de référence).

### 3.4 Tailwind v4 (`resources/css/app.css`)
```css
@import "tailwindcss";
@import "@fontsource-variable/inter";
@import "@fontsource-variable/fraunces";
@import "@fontsource-variable/fraunces/wght-italic.css";

@theme {
  --color-kh-blue-950: #12134A; --color-kh-blue-900: #1C1E66; --color-kh-blue-700: #282A89;
  --color-kh-blue-500: #4A4FC0; --color-kh-blue-200: #C9CBEE; --color-kh-blue-100: #E3E4F6; --color-kh-blue-50: #F1F2FB;
  --color-kh-red-500: #F91C0C; --color-kh-red-600: #D4170A; --color-kh-red-900: #6B0E07; --color-kh-red-50: #FEEDEB;
  --color-ink: #17191C; --color-slate: #64687A; --color-smoke: #9A9DAA; --color-line: #E6E7EE;
  --color-canvas: #FFFFFF; --color-fog: #FAFAFC; --color-mist: #F2F3F7;
  --color-success-600: #147A4B; --color-warning-600: #9A5B00; --color-whatsapp: #25D366;

  --font-display: "Fraunces Variable", ui-serif, Georgia, serif;
  --font-sans: "Inter Variable", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;

  --radius-card: 24px; --radius-artifact: 20px; --radius-input: 16px; --radius-image: 12px;
  --shadow-artifact: 0 0 0 1px rgb(40 42 137 / .06), 0 20px 25px -5px rgb(18 19 74 / .10), 0 8px 10px -6px rgb(18 19 74 / .08);
  --shadow-pop: 0 0 0 1px rgb(40 42 137 / .06), 0 8px 40px rgb(18 19 74 / .12);
  --shadow-hover: 0 0 0 1px rgb(40 42 137 / .08), 0 12px 32px -8px rgb(18 19 74 / .16);

  --ease-out-soft: cubic-bezier(.2, .7, .2, 1);
}
```
Les composants Blade utilisent ces tokens ; **aucune couleur hexadécimale en dur** dans les vues.

## 4. Composants (Blade `x-kh.*`)

| Composant | Spécification |
|---|---|
| `x-kh.button` variant `primary` | Pilule, fond `kh-blue-700`, texte blanc, Inter 500 16 px, `padding: 12px 22px` (min-height 48 px). Survol : fond `kh-blue-900` + flèche `→` qui glisse de 4 px. Focus : anneau 3 px `kh-blue-500` décalé de 2 px |
| variant `ghost` | Pilule, transparent, bordure 1 px `kh-blue-700`, texte `kh-blue-700`. Survol : fond `kh-blue-50` |
| variant `whatsapp` | Comme `ghost`, avec l'icône WhatsApp en `--color-whatsapp` à gauche. Libellé : « Discuter sur WhatsApp ». `href` construit par `WhatsAppLink::for($context, $text)` |
| `x-kh.link-arrow` | Texte `ink`, suffixe `→`, soulignement qui se déploie de gauche à droite au survol (pas au repos) |
| `x-kh.eyebrow` | Sur-titre en MAJUSCULES `kh-blue-700`, précédé d'un point rouge de 6 px (`kh-red-500`) — rappel du logo |
| `x-kh.card` | Fond `mist`, rayon 24, padding 28/24, sans ombre. Si lien : survol `translateY(-4px)` + `--shadow-hover` + fond `canvas` |
| `x-kh.card-accent` tone `blue` | Fond `kh-blue-50`, texte `kh-blue-900` |
| `x-kh.card-accent` tone `red` | Fond `kh-red-50`, texte `kh-red-900` — **une par page maximum** |
| `x-kh.artifact` | Fond blanc, rayon 20, `--shadow-artifact`, padding 16/20. Contient des mini-interfaces. **Toujours** une étiquette discrète « Démo » quand il affiche des chiffres |
| `x-kh.section` | Gère le fond (`canvas` / `fog`), le padding vertical, l'eyebrow, le titre H2 serif et le chapeau |
| `x-kh.halftone` | SVG de la sphère de points (voir §6), props : `size`, `tone`, `animate` |
| `x-kh.faq` | `<details>` natifs stylés (fonctionnent sans JS), icône `+` qui tourne à 45° à l'ouverture |
| `x-kh.stepper` | Barre de progression du diagnostic : 4 segments, segment actif `kh-blue-700`, terminé `kh-blue-200`, libellé « Étape 2 sur 4 · Votre façon de travailler » |
| `x-kh.choice` | Option de questionnaire en carte pleine largeur (min-height 56 px), radio ou case masquée, état sélectionné : bordure 2 px `kh-blue-700` + fond `kh-blue-50` + coche |
| `x-kh.input` / `select` / `textarea` | Fond blanc, bordure `line`, rayon 16, hauteur 52 px, label au-dessus (jamais de placeholder seul), erreur sous le champ en `kh-red-600` avec icône |
| `x-kh.reco-card` | Carte de recommandation : titre du module, poids sous forme de 1 à 3 points pleins, bouton « Pourquoi ? » qui déplie la liste des réponses déclencheuses |
| `x-kh.badge` | Pilule 13 px : `neutral` (mist/slate), `blue`, `red`, `success`, `warning` |
| `x-kh.empty-state` | Petite sphère de points, titre, texte et action |
| `x-kh.toast` | Bas centre sur mobile, bas droite sur desktop, disparition après 5 s, `aria-live="polite"` |

### 4.1 Header
- Hauteur 72 px. Logo à gauche ; navigation centrale : **Solutions ▾** (méga-menu à 6 entrées avec icône et une ligne de description), **Secteurs ▾**, **Démo Lab**, **Réalisations**, **Méthode**, **Ressources** ; à droite : `link` « WhatsApp » avec icône + bouton `primary` « Diagnostic gratuit ».
- Au repos : fond transparent sur `canvas`. Après 8 px de défilement : fond `rgb(255 255 255 / .85)` + `backdrop-filter: blur(12px)` + filet bas `line` (transition 200 ms).
- Mobile : logo + bouton « Diagnostic » compact + burger. Le menu plein écran glisse depuis le haut, avec de grandes entrées en Fraunces 28 px et les boutons WhatsApp et Diagnostic en bas.

### 4.2 Pied de page
Fond `kh-blue-950`, texte `#C9CBEE`, titres blancs. Colonnes : Solutions, Secteurs, Entreprise (Méthode, Réalisations, À propos, Ressources, Contact), Légal (Mentions, Confidentialité, Cookies, « Gérer mes données »). Bloc coordonnées : Bamako, téléphones, e-mail, horaires (depuis `settings`). Une sphère de points en `kh-blue-900` déborde en bas à droite. Mention : « © 2026 Kilakodon Services ». **Aucun lien vers `#`** : un réseau social sans URL réelle n'est pas affiché.

### 4.3 Assistant (widget)
- **Lanceur** : pilule en bas à droite (mobile : 16 px des bords, au-dessus de la zone de geste), fond `kh-blue-700`, icône étincelle + « Une question ? ». **Jamais d'ouverture automatique**, jamais de son, pas de pastille de notification factice.
- **Panneau** : 400×640 px desktop (bas droite, `--shadow-pop`, rayon 24) ; plein écran sur mobile. Il comprend :
  - un en-tête avec le nom « Assistant Kilakodon », un badge « IA » et un bouton fermer ;
  - une ligne de mention : « Réponses générées par IA à partir de nos pages. Un conseiller peut prendre le relais. » ;
  - à l'état vide, 3 suggestions : « Quel logiciel pour ma boutique ? », « Comment se passe un projet avec vous ? », « Combien de temps pour un ERP ? ».
- **Messages** : visiteur à droite (fond `kh-blue-700`, texte blanc, rayon 20 avec coin bas droit 6) ; assistant à gauche (fond `mist`). Sous chaque réponse : les **sources** en puces-liens (« Stocks & caisse → ») et 👍/👎.
- **Composeur** (inspiré du « AI composer » de Steep) : champ blanc, rayon 16, placeholder « Posez votre question… », bouton d'envoi rond de 40 px `kh-blue-700` avec flèche blanche. Compteur de caractères visible au-delà de 800.
- **Carte de confirmation d'action** : `x-kh.card-accent` bleue, titre « Voici ce que nous allons transmettre », liste des champs modifiables, deux boutons « Confirmer l'envoi » (primary) et « Annuler » (ghost). Après exécution : état succès avec la référence, ou état erreur explicite.
- **État indisponible** : sphère de points grisée, « L'assistant est momentanément indisponible. Vous pouvez faire le diagnostic ou nous écrire directement. », suivi des boutons Diagnostic et WhatsApp.
- Indicateur de saisie : 3 points qui pulsent en `kh-blue-200` → `kh-blue-700`.

## 5. Pages

### 5.1 Accueil
```
[Header]
HERO (canvas, min-height ~88vh desktop)
  eyebrow  • LOGICIELS DE GESTION SUR MESURE — BAMAKO
  H1 display  Pilotez votre entreprise avec des outils *faits pour elle*.   (*…* = Fraunces italique, kh-red-600)
  lead  (CONTENT.md)
  [Faire mon diagnostic gratuit]  [Essayer le Démo Lab]          micro : « 3 minutes · sans inscription · résultat immédiat »
  Artefacts flottants autour du titre (desktop), en carrousel horizontal doux sous les CTA (mobile) :
    ① Brief KLK-2026-0042 (haut gauche)  ② Alerte stock « Riz 25 kg — 4 sacs » (droite)
    ③ Mini-courbe des ventes « Démo » (bas gauche)  ④ Composeur assistant (bas centre)
  Sphère de points kh-blue-100 en fond, à droite, partiellement hors cadre
BANDE « RÉALITÉS LOCALES » (fog) — 4 items avec icône : Hors-ligne · Mobile Money · FCFA & fiscalité locale · Support à Bamako
« VOUS VOUS RECONNAISSEZ ? » (canvas) — 4 cartes problème → solution (Stock, Caisse, Suivi clients, Rapports)
SOLUTIONS (fog) — grille 3×2 de x-kh.card avec icône, titre, tagline, link-arrow
DIAGNOSTIC (canvas) — 2 colonnes : à gauche, 3 étapes numérotées en Fraunces ; à droite, un artefact de restitution (niveau de maturité + 3 recommandations avec « Pourquoi ? »)
DÉMO LAB (fog) — capture animée légère (WebP/MP4 muet en boucle ≤ 600 Ko, poster statique) + CTA
MÉTHODE (canvas) — 4 étapes en ligne horizontale reliée par un filet qui se dessine au scroll
RÉALISATIONS (fog) — masquée si aucune réalisation autorisée
POURQUOI KILAKODON (canvas) — 1 carte accent ROUGE + 3 cartes neutres
FAQ (fog) — 6 questions
BANDE CTA FINALE (kh-blue-950) — H2 serif blanc, 2 boutons (primary inversé : fond blanc, texte kh-blue-700 ; whatsapp ghost blanc), sphère de points kh-blue-900
[Footer]
```

### 5.2 Page solution (`/solutions/{slug}`)
Hero 2 colonnes (texte et artefact propre à la solution) → « Le problème » → « Pour qui » → « Ce que vous obtenez » (grille de fonctionnalités) → « Comment on travaille » (process) → « Ce que ce n'est pas » (limites, carte accent bleue) → délai indicatif et prix (« Sur devis après diagnostic » si vide) → FAQ propre → bande CTA « Diagnostic pré-rempli pour cette solution ». Fil d'Ariane en haut. JSON-LD `Service` + `FAQPage`.

### 5.3 Page secteur (`/secteurs/{slug}`)
Hero avec le secteur → « Vos défis » (pain points) → « Ce que ça change » (outcomes) → solutions recommandées (cartes) → démo associée → réalisation du secteur si autorisée → CTA « Diagnostic {secteur} » (`?secteur=code`).

### 5.4 Diagnostic
- Mise en page sobre et focalisée : header réduit (logo + « Quitter » + bouton enregistrer), aucun footer, largeur max 720 px.
- Une **étape = un écran**, avec 2 à 4 questions par écran. Le stepper est collant en haut. Boutons bas : « Retour » (ghost) et « Continuer » (primary, désactivé tant qu'il manque une réponse requise). Sur mobile, ces boutons sont collés en bas avec `safe-area-inset`.
- Transition entre étapes : fondu + glissement de 12 px (250 ms). Le focus va sur le titre de la nouvelle étape.
- Lien discret « Enregistrer et reprendre plus tard » : il affiche le lien de reprise et propose de l'envoyer à soi-même sur WhatsApp.
- **Écran de restitution** :
  - en tête, « Votre diagnostic » avec le niveau de maturité sous forme de 4 points sur une ligne (le niveau atteint en `kh-blue-700`) et son libellé en Fraunces ;
  - les sections « Vos 3 priorités », « Ce que nous recommandons » (`reco-card` avec « Pourquoi ? ») et « À clarifier ensemble » ;
  - le résumé rédigé, marqué « rédigé automatiquement » ;
  - un bloc de conversion collant : « Recevoir mon brief » (primary) et « Discuter sur WhatsApp ».
- **Formulaire de coordonnées** (feuille modale) : nom, téléphone/WhatsApp (préfixe +223 par défaut), e-mail (facultatif), entreprise, puis 2 cases de consentement séparées (traitement : obligatoire ; actualités : facultative, décochée), avec un lien vers la confidentialité.

### 5.5 Brief (`/brief/{token}`)
- Présentation « document » : fond `fog`, feuille blanche centrée (max 820 px, rayon 24, `--shadow-artifact`), en-tête avec le logo, la référence et la date.
- 3 blocs clairement titrés :
  - « Ce que vous nous avez dit » (modifiable) ;
  - « Ce que nous recommandons » (lecture seule, avec « Pourquoi ? ») ;
  - « Résumé » (modifiable, avec la mention de sa source).
- Actions : « Valider mon brief » → état validé (badge `success`, horodatage), puis « Télécharger le PDF », « Continuer sur WhatsApp » et « Demander un rendez-vous ».
- Mention de bas de page : « Document de travail non contractuel. Lien privé valable jusqu'au {date}. »

### 5.6 Démo Lab
- `/demo-lab` : 3 cartes — **Boutique** (badge « Interactif »), **Gestion scolaire** et **Boulangerie** (badge « Démo guidée »).
- `/demo-lab/boutique` : interface d'application dans un cadre de « fenêtre » (barre supérieure avec 3 points). Bandeau permanent : « Démo — données fictives, réinitialisées toutes les 24 h ». Onglets : Caisse · Stock · Ventes · Tableau de bord.
  - Caisse : grille de produits tactile, panier, choix du paiement (Espèces / Mobile Money / Crédit), « Encaisser ».
  - Une alerte de stock apparaît en toast rouge discret quand un seuil est franchi.
  - La facture s'ouvre en vue imprimable avec filigrane.
- Un panneau « Guide » (3 missions : « Faites une vente », « Déclenchez une alerte de stock », « Imprimez une facture ») avec coches — gamification légère.
- Après 2 missions, une carte apparaît : « Vous voulez ça pour votre entreprise ? » → diagnostic pré-rempli ou WhatsApp.

### 5.7 Réalisations, Contact, RDV, Ressources, Légales
- **Réalisations** : grille de cartes avec image, secteur (badge) et descripteur client. La page détail suit la structure Contexte → Défi → Intervention → Livrables → Résultats (uniquement si vérifiés) → Témoignage (si autorisé).
- **Contact** : 2 colonnes — formulaire court (nom, téléphone, e-mail facultatif, sujet, message, consentements) et bloc coordonnées et horaires avec gros bouton WhatsApp.
- **RDV** : motif, format, 1 à 3 créneaux (date + matin/après-midi/soir), coordonnées. Message de succès : « Demande reçue — un conseiller vous confirme le créneau » (**jamais « RDV confirmé »**).
- **Ressources** : liste éditoriale (Fraunces pour les titres), page article de 68 caractères de large, avec temps de lecture et bloc CTA en fin d'article.
- **Légales** : gabarit texte simple et sommaire ancré.

### 5.8 Admin (Filament)
- Thème : couleur primaire = palette bleue ci-dessus, `danger` = rouge 600, police Inter, logo dans la barre latérale. Mode sombre désactivé au MVP.
- **Tableau de bord** : widgets Entonnoir (30 jours), Leads à traiter, RDV demandés, Coût IA du jour et du mois, Retours IA négatifs à revoir.
- Navigation par groupes : **Commercial** (Leads [vue Kanban par étape + liste], Contacts, Entreprises, RDV, Briefs) · **Contenus** (Solutions, Secteurs, Réalisations, Articles, FAQ, Pages) · **Assistant** (Base de connaissances, Conversations, Retours, Usage) · **Diagnostic** (Modèles, Questions, Règles, Sessions) · **Réglages** (Paramètres, Utilisateurs, Journal, Demandes RGPD, Envois).

## 6. Motif « sphère de points »

Généré une seule fois en SVG par `x-kh.halftone` (calcul côté serveur, mis en cache) :
- Grille de 11 colonnes × 11 lignes dans un disque. Le rayon de chaque point décroît de gauche à droite : `r = rMax * (1 - (x / width) ^ 1.6)`, avec un léger renflement vertical au centre — comme sur le logo. Les points dont le rayon est inférieur à 0,6 px sont omis.
- Couleur par défaut `kh-blue-100` (décor) ; variantes `kh-blue-200` et `kh-blue-900` (sur fond foncé).
- `aria-hidden="true"`, `focusable="false"`.
- Animation optionnelle (`animate`) : chaque point passe de `scale(0)` à `scale(1)` avec un délai proportionnel à sa distance au coin gauche (`--d`), 600 ms au total, **une seule fois**, déclenchée à l'entrée dans l'écran.

## 7. Animations — sobres et légères

**Principes :** elles ne doivent ni alourdir ni gêner. On anime **uniquement `transform` et `opacity`**. Pas de bibliothèque d'animation (pas de GSAP, AOS ni Lottie), pas de défilement détourné (scroll-jacking), pas de parallaxe sur mobile. Tout est désactivé sous `prefers-reduced-motion: reduce`. Budget JS total pour les animations : **< 2 Ko gz**.

| Animation | Détail |
|---|---|
| **Apparition au scroll** | `[data-reveal]` : `opacity 0 → 1`, `translateY(16px) → 0`, 560 ms `--ease-out-soft`. Décalage en cascade via `style="--i: n"` → `transition-delay: calc(var(--i) * 70ms)` (maximum 6 éléments). Déclenchée **une fois** par IntersectionObserver (`threshold: .15`, `rootMargin: 0 0 -10% 0`) |
| **Titre du hero** | Le mot en italique rouge apparaît 200 ms après le reste du titre, avec un léger `translateY(8px)` (CSS uniquement, au chargement) |
| **Artefacts du hero** | Entrée en cascade (fondu + `scale(.96 → 1)`), puis flottement continu `translateY(0 ↔ -6px)` sur 6 à 8 s, en `ease-in-out`, avec des durées décalées. Mis en **pause hors écran** (`animation-play-state` piloté par l'observer) |
| **Sphère de points** | Assemblage unique au premier affichage (§6) |
| **Ligne de la méthode** | Le filet reliant les 4 étapes se dessine (`scaleX(0 → 1)`, origine gauche, 900 ms) quand la section entre dans l'écran ; les étapes apparaissent en cascade |
| **Survol des cartes** | `translateY(-4px)` + `--shadow-hover` + fond `canvas`, 200 ms. L'icône de la carte se décale de 2 px |
| **Survol des boutons** | Changement de fond 150 ms ; la flèche `→` glisse de 4 px |
| **Liens** | Soulignement `scaleX(0 → 1)` depuis la gauche, 200 ms |
| **Header** | Transition fond/flou/filet 200 ms au franchissement de 8 px |
| **Diagnostic** | Transition d'étape 250 ms (fondu + 12 px) ; remplissage du stepper 300 ms ; à la restitution, les 4 points de maturité s'allument un à un (80 ms d'écart) |
| **Assistant** | Panneau : `translateY(16px) + opacity` 220 ms ; points de saisie qui pulsent |
| **Démo Lab** | Ligne ajoutée au panier : `scale(.98 → 1)` ; compteur de stock qui « tic » (changement de couleur 300 ms) |

Implémentation de référence (`resources/js/reveal.js`) :
```js
const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
export function initReveal() {
  const els = document.querySelectorAll('[data-reveal]');
  if (mq.matches || !('IntersectionObserver' in window)) { els.forEach(e => e.classList.add('is-in')); return; }
  const io = new IntersectionObserver((entries) => {
    entries.forEach(({ isIntersecting, target }) => {
      if (isIntersecting) { target.classList.add('is-in'); io.unobserve(target); }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });
  els.forEach(e => io.observe(e));
}
```
```css
@media (prefers-reduced-motion: no-preference) {
  [data-reveal] { opacity: 0; transform: translateY(16px);
    transition: opacity .56s var(--ease-out-soft), transform .56s var(--ease-out-soft);
    transition-delay: calc(var(--i, 0) * 70ms); }
  [data-reveal].is-in { opacity: 1; transform: none; }
}
```
**Important :** le contenu doit rester visible si le JS ne se charge pas. Pour cela, on ajoute la classe `js` sur `<html>` au tout début du `<head>`, et on préfixe la règle d'état initial par `.js [data-reveal]`.

## 8. Iconographie et images
- Icônes : **Lucide** en SVG inline via un composant Blade (`x-kh.icon name="package"`), trait de 1,75 px, 20 ou 24 px.
- Images : captures réelles des logiciels Kilakodon (anonymisées), photos réelles de l'équipe et des locaux à Bamako si disponibles [À VALIDER]. **Interdit** : photos de stock génériques, faux logos clients, avatars générés présentés comme des clients.
- Les mockups d'écran sont présentés dans des artefacts (§4) plutôt que dans des cadres d'appareil lourds.

## 9. Accessibilité (WCAG 2.2 AA visé)
- Contrastes vérifiés (§3.1) : le rouge 500 n'est jamais utilisé pour du texte < 24 px.
- Navigation complète au clavier, lien d'évitement « Aller au contenu », focus toujours visible (anneau `kh-blue-500`).
- Zoom autorisé : **ne jamais mettre `user-scalable=no`** (défaut de l'ancien site).
- Cibles tactiles ≥ 44×44 px. Les formulaires ont des labels explicites et des erreurs liées par `aria-describedby`.
- Diagnostic : `fieldset` + `legend` par question ; annonce du changement d'étape (`aria-live`).
- Assistant : `role="dialog"`, `aria-modal` sur mobile, piège à focus et `Esc` pour fermer ; nouveaux messages annoncés en `aria-live="polite"`.
- L'information ne dépend jamais uniquement de la couleur (icônes et texte sur les badges et les erreurs).

## 10. À faire / À éviter
**À faire**
- Titres H1 et H2 en Fraunces 400, avec un seul mot ou une seule expression en italique rouge par titre au maximum, et pas sur tous les titres (accueil, solutions, CTA finale).
- Un bouton primaire bleu associé à un bouton ghost sur la même ligne.
- Des bandes alternées `canvas` / `fog` pour le rythme.
- L'étiquette « Démo » sur tout chiffre affiché dans un artefact.

**À éviter**
- Les dégradés arc-en-ciel, le glassmorphism lourd, les ombres sur les cartes au repos.
- Le rouge en fond de grande surface.
- Les compteurs animés de « chiffres clés » non vérifiés.
- Les carrousels automatiques, les pop-ups d'entrée et l'ouverture automatique du chat.
- Les animations sur `width`, `height`, `top`, `left` ou `box-shadow` en boucle.
