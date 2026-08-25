# Jobiizy — Consignes pour Codex et les agents IA

Ce dépôt est la source de référence technique pour le projet Jobiizy.

## À lire avant toute intervention

Toujours commencer par lire :

1. `AGENTS.md`
2. `docs/ENVIRONMENTS.md`
3. `docs/CURRENT-STATE.md`
4. `docs/CHANGELOG-AI.md`

Ne pas supposer qu'une conversation précédente est disponible ou complète.

## Environnements officiels

Les trois sigles officiels sont :

- **LW** : environnement LocalWP sur le Mac de Gilles, utilisé comme environnement local de développement.
- **HS** : environnement de staging Hostinger.
- **HP** : environnement de production Hostinger.

Les chemins exacts sont documentés dans `docs/ENVIRONMENTS.md`.

## Règles de travail

- Par défaut, analyser et modifier d'abord **LW**.
- Ne pas modifier **HS** ou **HP** sans demande explicite de l'utilisateur.
- **HP est la production** : aucune modification directe ou déploiement ne doit être réalisé sans validation explicite.
- Conserver les mécanismes natifs Cariera / WP Job Manager lorsqu'ils existent déjà, sauf décision explicite de refonte.
- Préférer les surcharges dans le thème enfant et le plugin `jobiizy-customizations` plutôt que la modification du thème ou des plugins parents.
- Avant une modification importante, identifier les fichiers réellement concernés et vérifier qu'il n'existe pas déjà une implémentation concurrente ou historique.
- Après une modification, vérifier autant que possible les impacts desktop/mobile et les états visiteur/candidat/employeur lorsque le sujet est lié à l'interface ou aux parcours.
- Ne pas inventer l'état d'un environnement. Si un état doit être vérifié sur LW, HS ou HP, l'examiner directement avant de conclure.

## Traçabilité obligatoire

Après toute intervention significative, mettre à jour `docs/CHANGELOG-AI.md` avec au minimum :

- date ;
- objectif ;
- environnement concerné ;
- fichiers modifiés ;
- résumé des changements ;
- tests effectués ;
- statut de report éventuel vers HS / HP ;
- points restant à traiter.

Si l'état global du projet change, mettre également à jour `docs/CURRENT-STATE.md`.

## Git

Git doit être utilisé comme source de vérité pour retrouver les modifications passées :

- `git log` pour l'historique ;
- `git diff` pour les différences ;
- `git blame` pour retrouver l'origine d'une ligne ;
- historique des commits et branches pour identifier ce qui a été fait par une session précédente.

Lorsqu'une question porte sur « ce qui avait été modifié auparavant », rechercher d'abord dans Git et dans `docs/CHANGELOG-AI.md` avant de demander à l'utilisateur de redonner le contexte.

## Sécurité des informations

Ne jamais ajouter de mot de passe, secret, token, clé API ou identifiant sensible dans ces fichiers ou dans Git.
