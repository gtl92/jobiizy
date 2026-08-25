# Jobiizy — Environnements

Ce document définit les noms et chemins officiels des environnements Jobiizy.

## LW — LocalWP

- **Type** : développement local
- **Hébergement** : LocalWP sur le Mac de Gilles
- **Racine WordPress** : `/Users/gtl92/Local Sites/staging-jobiizy/app/public`
- **Usage** : environnement de développement et de test principal

## HS — Hostinger Staging

- **Type** : staging
- **Hébergement** : Hostinger
- **Accès local via CloudMounter** : `/Users/gtl92/Library/CloudStorage/CloudMounter-GTLHOSTINGER/domains/jobiizy.com/public_html/jobiizy-staging`
- **Usage** : validation sur l'hébergement avant passage en production

## HP — Hostinger Production

- **Type** : production
- **Hébergement** : Hostinger
- **Accès local via CloudMounter** : `/Users/gtl92/Library/CloudStorage/CloudMounter-GTLHOSTINGER/domains/jobiizy.com/public_html`
- **Usage** : site de production

## Nomenclature obligatoire

Toujours utiliser les sigles suivants :

- `LW` = LocalWP
- `HS` = Hostinger staging
- `HP` = Hostinger production

Ne pas utiliser `HW` pour désigner la production.

## Règles de déploiement

1. Le développement se fait par défaut sur **LW**.
2. Le passage vers **HS** doit être volontaire et identifiable.
3. Le passage vers **HP** nécessite une validation explicite de l'utilisateur.
4. Une présence d'un fichier sur LW ne signifie pas automatiquement qu'il est présent sur HS ou HP.
5. Lors d'une comparaison entre environnements, vérifier réellement les fichiers concernés avant d'affirmer qu'une modification est déployée.
6. Documenter les reports/déploiements significatifs dans `CHANGELOG-AI.md`.

## Informations sensibles

Les mots de passe, tokens, clés API et autres secrets ne doivent jamais être ajoutés à ce document ni au dépôt Git.
