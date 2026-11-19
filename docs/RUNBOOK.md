# RUNBOOK — Kilakodon Hub

Procédures courantes pour l'équipe. Toutes se font depuis l'administration (`/admin`), sauf mention contraire. Les commandes `php artisan …` se lancent à la racine du projet.

> Phase actuelle : développement local. Les procédures de production (sauvegardes, supervision, déploiement) seront ajoutées au lot 7.

---

## 1. Activer ou désactiver l'assistant IA

Le site fonctionne sans l'assistant : le couper ne casse rien, le bouton disparaît simplement.

**Couper ou rallumer (cas normal)**
1. Réglages → **Paramètres** → section **Assistant IA**.
2. Basculer **Assistant activé**, puis enregistrer. Le changement est journalisé (Réglages → **Journal**).

**Couper en urgence (l'administration est inaccessible)**
1. Dans le fichier `.env` du serveur : `AI_ENABLED=false`.
2. `php artisan config:clear`.

**Le budget du jour est atteint**
- L'assistant se coupe tout seul à 100 % du budget quotidien (0,50 $, reprise le lendemain) ou du budget mensuel (1 $, reprise le mois suivant). Une alerte e-mail part à 80 % de chacun.
- Pour suivre la consommation : Assistant → **Usage**.
- Pour changer les budgets : Réglages → **Paramètres** → Assistant IA → budget quotidien et budget mensuel. Toute hausse est une décision de la direction (D-023, D-026).

**La clé API**
- Elle se trouve uniquement dans `.env` (`ANTHROPIC_API_KEY`), jamais en base, dans l'administration ou dans Git.
- `AI_DRIVER=fake` utilise des réponses de test, sans appel ni coût.

## 2. Relancer un envoi d'e-mail

Les e-mails passent par une file vidée chaque minute par le planificateur (`php artisan schedule:work` en local).

1. Réglages → **Envois** ; filtrer par statut **Échec** ou **Abandonné**.
2. Lire l'erreur affichée (adresse invalide, serveur SMTP indisponible…).
3. Corriger la cause si besoin, par exemple les paramètres `MAIL_*` dans `.env`.
4. Cliquer **Renvoyer** sur la ligne concernée, puis confirmer.

Si rien ne part du tout : vérifier que le planificateur tourne et que `MAIL_MAILER` n'est pas resté sur `log`.

## 3. Traiter une demande de droits (accès, correction, suppression, opposition)

Les demandes arrivent par le formulaire **« Gérer mes données »** (`/mes-donnees`, lien dans le pied de page). Chaque nouvelle demande déclenche un e-mail aux administrateurs. Une demande reçue par téléphone ou WhatsApp se saisit aussi dans l'administration.

1. Réglages → **Demandes de droits** → ouvrir la demande.
2. **Vérifier l'identité** sous 24 h (délai affiché au demandeur, D-026) : rappeler la personne sur le numéro ou l'adresse que nous avions déjà. Ne jamais traiter une demande sur la seule foi du formulaire. Ne pas recopier de pièce d'identité dans les notes.
3. Cliquer **Identité vérifiée**.
4. Dans **Contact concerné**, rechercher le contact par nom, téléphone ou e-mail, puis enregistrer.
5. Selon la demande :
   - **Accès** : cliquer **Exporter les données (JSON)**, puis transmettre le fichier à la personne par un canal sûr. L'export est journalisé.
   - **Correction** : corriger la fiche dans Commercial → **Contacts**.
   - **Suppression** : cliquer **Anonymiser le contact**, puis taper `ANONYMISER` pour confirmer. **C'est irréversible.** Les nom, téléphones, e-mail, messages, notes, conversations et e-mails liés sont effacés ; les chiffres (étapes, dates, sources) restent.
   - **Opposition** (ne plus être contacté) : cliquer **Retirer le consentement marketing**. Le retrait est enregistré ; anonymiser en plus si la personne le demande.
6. Cliquer **Marquer traitée** et décrire ce qui a été fait. Si la demande ne peut pas aboutir (identité impossible à vérifier, aucune donnée trouvée), cliquer **Rejeter** avec le motif.
7. Prévenir la personne du résultat.

Purges automatiques (planifiées, rien à faire) :

| Données | Délai | Fréquence |
|---|---|---|
| Diagnostics anonymes non convertis | 30 jours | chaque jour à 02:00 |
| Conversations de l'assistant non liées à un lead | 90 jours | chaque jour à 02:15 |
| Espaces du Démo Lab | 24 heures | toutes les heures |

Durées validées par la direction (D-026).

## 4. Restaurer un contenu

Chaque modification d'un contenu (page, solution, secteur, FAQ, article, réalisation) crée une révision.

1. Ouvrir le contenu dans **Contenus**, puis l'onglet **Révisions**.
2. **Voir** la révision voulue, puis cliquer **Restaurer**.
3. La restauration crée une nouvelle révision et figure dans le journal (Réglages → **Journal**). Vérifier le statut (brouillon ou publié) après la restauration.

Un contenu validé doit aussi être réexporté dans `database/seeders/content/` (décision D-020), sinon une réinstallation ramènerait l'ancienne version.

## 5. Ajouter une règle de diagnostic

Une version **active** du questionnaire ne se modifie pas : les sessions passées gardent leur version.

1. Diagnostic → **Questionnaires** → sur la version active, cliquer **Dupliquer en nouvelle version**.
2. Ouvrir la nouvelle version (brouillon) → onglet **Règles** → **Créer**.
3. Renseigner :
   - **Clé** (identifiant court) et **Nom** ;
   - **Priorité** (plus petit = évalué en premier) ;
   - **Conditions** et **Effets** ;
   - **Explication « Pourquoi ? »** : texte montré au prospect.
4. Tester : faire un diagnostic complet sur la version brouillon, en local, avec des réponses qui déclenchent la règle.
5. Cliquer **Activer**. Les nouveaux diagnostics utilisent cette version, et la version précédente est retirée.
6. Reporter la règle dans `docs/DIAGNOSTIC_SPEC.md` et ajouter un test dans `tests/` (le moteur est couvert par des tests obligatoires).

## 6. Mesure d'audience et tableau de bord

- La bannière cookies propose **Accepter** ou **Refuser**. Sans accord, aucun identifiant visiteur n'est posé et les visites restent comptées de façon agrégée.
- Le visiteur revoit son choix sur la page **Cookies** (section « Vos choix »).
- Le **tableau de bord direction** (Pilotage → Tableau de bord direction, réservé aux administrateurs) recalcule ses chiffres à chaque affichage. Rien à maintenir.

## 7. Limites de débit et réseaux mobiles

Les formulaires publics sont limités par adresse IP (contact et rendez-vous : 5 par heure ; demandes de droits : 5 par heure ; mesure : 120 événements par minute). Derrière les opérateurs mobiles, de nombreux clients partagent la même IP publique : un prospect peut être bloqué à tort. Il suffit alors de lui proposer WhatsApp ; si le cas se répète, ajuster les limites dans `app/Providers/AppServiceProvider.php`.

## 8. Commandes utiles

```bash
php artisan kh:make-admin             # créer un administrateur
php artisan kh:mfa-reset              # réinitialiser la double authentification d'un compte
php artisan kh:content-audit          # lister les [À VALIDER] restants
php artisan kh:ai-eval --driver=fake  # vérifier l'assistant sans appel réel
php artisan kh:conversations-purge    # lancer la purge des conversations à la main
composer kh:check                     # Pint + PHPStan + tests (avant chaque livraison)
```
