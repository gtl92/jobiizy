# Jobiizy — Journal des interventions IA

Ce fichier sert de mémoire durable des interventions réalisées avec Codex, ChatGPT ou un autre agent IA.

Il ne remplace pas l'historique Git : il apporte le contexte fonctionnel et opérationnel permettant de comprendre pourquoi une modification a été faite et sur quels environnements elle a été appliquée.

---

## Format à utiliser

### AAAA-MM-JJ — Titre court

**Objectif**

Description du problème ou de la demande.

**Environnement(s)**

- LW : oui/non
- HS : oui/non
- HP : oui/non

**Fichiers modifiés**

- `chemin/fichier`

**Modifications**

- changement 1
- changement 2

**Tests / vérifications**

- test ou vérification effectuée

**Déploiement**

- LW : état
- HS : état
- HP : état

**À suivre**

- éventuelles actions restantes

---

## 2026-08-25 — Mise en place de la mémoire technique du projet

**Objectif**

Éviter que chaque nouvelle conversation ChatGPT/Codex dépende de l'historique des chats pour retrouver les environnements, règles de travail et décisions précédentes.

**Environnement(s)**

Cette intervention concerne la documentation du dépôt Git et ne constitue pas un déploiement WordPress sur LW, HS ou HP.

**Fichiers ajoutés**

- `AGENTS.md`
- `docs/ENVIRONMENTS.md`
- `docs/CURRENT-STATE.md`
- `docs/CHANGELOG-AI.md`

**Modifications**

- définition officielle des environnements LW, HS et HP ;
- ajout des chemins locaux/CloudMounter correspondants ;
- ajout des règles de travail pour Codex ;
- ajout d'une mémoire synthétique de l'état de Jobiizy ;
- mise en place du présent journal d'interventions ;
- obligation pour les futures interventions significatives de mettre à jour la documentation de traçabilité.

**Tests / vérifications**

- dépôt GitHub `gtl92/jobiizy` vérifié accessible ;
- branche de travail/documentation : `refacto/cleanup` ;
- nomenclature confirmée : LW / HS / HP.

**Déploiement**

- LW : aucun déploiement WordPress
- HS : aucun déploiement
- HP : aucun déploiement

**À suivre**

- enrichir progressivement ce journal avec les interventions techniques futures ;
- lorsqu'une ancienne modification importante est recherchée, utiliser Git et les fichiers des environnements pour reconstruire son historique, puis documenter le résultat ici si utile.
