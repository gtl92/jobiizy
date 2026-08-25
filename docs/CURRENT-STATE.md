# Jobiizy — État courant du projet

> Ce fichier est une mémoire technique synthétique. Il doit être mis à jour lorsqu'une intervention modifie significativement l'architecture, les parcours ou l'état d'avancement du projet.

## Positionnement produit

Jobiizy évolue d'une plateforme principalement présentée comme « l'emploi francophone en Israël » vers une plateforme consacrée plus largement à **l'emploi en Israël**.

Le français et la communauté francophone restent une composante importante de l'identité et de la valeur ajoutée de Jobiizy, mais les opportunités peuvent être en français, anglais, hébreu ou dans un environnement multilingue.

Le positionnement en cours de travail est également de ne pas réduire Jobiizy à un job board classique : la plateforme doit pouvoir centraliser, relayer et structurer des opportunités issues notamment de communautés, tout en permettant progressivement les offres directes d'employeurs.

## Architecture technique connue

- WordPress
- thème Cariera avec thème enfant
- WP Job Manager et extensions associées
- plugin spécifique `jobiizy-customizations`
- import et structuration d'offres provenant notamment de WhatsApp

Dans le dépôt Git actuel, les deux ensembles principaux sont :

- `cariera-child-staging/`
- `jobiizy-customizations/`

## Environnements

Voir `ENVIRONMENTS.md`.

- LW : développement LocalWP
- HS : staging Hostinger
- HP : production Hostinger

## Principes techniques déjà établis

- Développer/tester d'abord sur LW.
- Conserver autant que possible les mécanismes Cariera / WP Job Manager existants.
- Utiliser le thème enfant et le plugin de customisation pour les adaptations.
- Éviter les modifications directes non contrôlées sur HP.
- Vérifier les variantes desktop/mobile et visiteur/candidat/employeur pour les évolutions de parcours concernées.

## Refonte UX / fonctionnelle — état de référence

Éléments déjà identifiés dans les travaux précédents :

- homepage largement refondue mais encore à finaliser ;
- coexistence de plusieurs systèmes de header/menu/footer à rationaliser ;
- parcours connexion/inscription et acquisition candidat/employeur à harmoniser ;
- plusieurs implémentations/listes d'offres à consolider ;
- fiche d'offre et candidatures interne/externe déjà largement retravaillées ;
- dashboards candidat et employeur encore à restructurer ;
- responsive et design system à consolider ;
- pipeline d'import WhatsApp V2 largement avancé avec workflow de revue avant publication.

## Priorités structurelles connues

1. Stabiliser les routes/pages officielles.
2. Unifier header, footer, menus et avatar.
3. Consolider le design system.
4. Nettoyer les pages et mécanismes historiques devenus inutiles.
5. Stabiliser les parcours candidat et employeur.
6. Poursuivre le repositionnement de Jobiizy comme plateforme d'accès au marché de l'emploi en Israël et de structuration d'opportunités.

## Discipline de mise à jour

Ce fichier ne remplace pas l'analyse du code réel.

Avant de prendre une décision technique :

1. vérifier le code actuel ;
2. consulter Git ;
3. consulter `CHANGELOG-AI.md` ;
4. comparer les environnements concernés si nécessaire.

Ne jamais considérer ce résumé comme une preuve qu'une fonctionnalité est actuellement déployée sur HS ou HP.
