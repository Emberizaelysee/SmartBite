# SmartBite — Application Web pour Restaurant

Préparé par : Nadine Jdid, Marianne Dagher, Elichaa Saleh Mhanna

---

## Présentation du projet

SmartBite est née d'un constat simple : beaucoup de restaurants offrent une excellente expérience en salle, mais peinent à exister en ligne. Pas de menu accessible facilement, des réservations qui se font encore par téléphone, des commandes perdues dans les échanges WhatsApp... SmartBite est là pour régler tout ça.

L'idée est de donner au restaurant une présence digitale complète — un endroit où les clients peuvent consulter le menu, réserver une table et passer commande, sans friction.
---

## Ce qu'on cherche à résoudre

Avant SmartBite, le restaurant faisait face à plusieurs problèmes concrets :

- Aucune vitrine en ligne pour présenter les plats
- Les clients avaient du mal à passer commande
- Des clients potentiels perdus faute de visibilité
- Une communication désorganisée avec la clientèle
- Sans gestion des commandes en temps réel
- Un suivi des réservations à la main
- Sans tableau de bord administrateur pour piloter l'ensemble

---

## Objectifs du frontend

Le frontend doit répondre à des besoins précis. Il ne s'agit pas juste de faire "un beau site", mais de créer une expérience fluide qui pousse naturellement le client à revenir :

- Présenter le menu de façon claire et appétissante
- Permettre à n'importe qui de parcourir les plats et gérer son panier sans avoir à créer un compte
- Faciliter la réservation d'une table en quelques clics
- Offrir un espace personnel où l'utilisateur retrouve ses commandes et réservations passées
- Intégrer un agent conversationnel pour le menu afin de guider l'utilisateur dans ses choix

---

## Technologies utilisées

Le frontend est développé en HTML, CSS et JavaScript avec Bootstraps.

|   Technologie   |   Rôle   |
|-----------------|----------|
|   HTML   |   Structure des pages et balisage sémantique   |
|   CSS   |   Mise en page, design, responsive design   |
|   JavaScript   |   Validation des formulaires, affichage dynamique du menu, gestion du panier, etc...   |

Le backend est développé en php et la base de donné se fait avec MySQL.

|   Technologie   |   Rôle   |
|-----------------|----------|
|   PHP   |   Logique serveur, traitement des formulaires, gestion des sessions   |
|   MySQL   |   Stockage et gestion des données (clients, plats, commandes, réservations)   |

---

## Pages de l'application

| Page | Description |
|------|-------------|
| `index.html` | Page d'accueil — présentation du restaurant avec menu complet avec panier et serveur virtuel IA  |
| `cart.html` | Panier pour regrouper les items à commander et avoir une prévisualisation de la commande |
| `reservation.html` | Formulaire de réservation de table |
| `review.html` | Consulter et laisser un avis sur un item du menu |
| `signin.html` et `signup.html` | Se connecter à son compte ou en crée un |
| `purchase.html` | Consulter la facture avant de 'valider' le paiment |
| `profile.html` | Page personnel — afin de consulter les commandes et le reservation passer ainsi que de modifier son compte  |
| `dashboard.html` | Page Administrateur — pour consulter et modifier la base de donnés |

---

## Fonctionnalités frontend

### Côté visiteur (sans connexion)

- Parcourir le menu et consulter les plats
- Ajouter ou retirer des plats du panier

### Côté utilisateur connecté

- Passer une commande depuis le menu
- Réserver une table
- Laisser un avis (commentaire et note)
- Consulter l'historique des commandes et des réservations

### Authentification

- Formulaire de connexion par e-mail et mot de passe
- Connexion via compte Google
- Formulaire d'inscription
- Page de modification du profil (nom d'utilisateur, mot de passe)
- Déconnexion et suppression du compte

### Serveur virtuel (IA) — pour le menu uniquement

Un agent conversationnel est intégré directement dans la page index. Il peut répondre aux questions sur les plats, détailler les ingrédients et proposer des recommandations selon les préférences de l'utilisateur.

---

## Les acteurs et leurs droits

|   Rôle   |    Ce qu'il peut faire    |
|----------|---------------------------|
| Visiteur | Consulter le site, voir le menu, gérer son panier |
| Utilisateur | Tout ce que fait le visiteur, plus : connexion, commandes, réservations, avis |
| Administrateur | Tout ce que fait l'utilisateur, plus : accès complet au tableau de bord et à la base de données |

---

## Authentification

La connexion standard repose sur une vérification e-mail / mot de passe avec `password_hash()` et `password_verify()` de PHP pour ne jamais stocker de mot de passe en clair.

La connexion Google utilise OAuth 2.0. Une fois l'utilisateur authentifié par Google, le backend vérifie si le compte existe déjà en base (via l'e-mail), le crée si nécessaire, puis ouvre une session PHP classique.

Les sessions sont gérées nativement avec `$_SESSION` et détruites proprement à la déconnexion.

---

## Structure des fichiers

```
SmartBite/
├── Frontend/
│   ├── index.html
│   ├── cart.html
│   ├── reservation.html
│   ├── review.html
│   ├── signup.html
│   ├── signin.html
│   ├── purchase.html
│   ├── profile.html
│   ├── dashboard.html
│   │
│   ├── css/
│   │   ├── style.css            
│   │   ├── index.css             
│   │   ├── cart.css 
│   │   ├── purchase.css 
│   │   ├── reservation.css 
│   │   ├── review.css 
│   │   ├── dashboard.css     
│   │   └── profile.css       
│   │
│   ├── js/
│   │   ├── script.js              
│   │   ├── review.js  
│   │   ├── dashboard.js              
│   │   ├── reservation.js       
│   │   └── profile.js          
│   │
│   └── img/
│       └── profile.jpg  
│         
├── Backend/
│   ├── api/
│   │   ├── signin.php            
│   │   ├── logout.php            
│   │   ├── delete_account.php
│   │   ├── fetch_all_order.php
│   │   ├── fetch_all_reservations.php 
│   │   ├── fetch_all_users.php 
│   │   ├── fetch_all_menu.php  
│   │   ├── manage_cart.php     
│   │   ├── fetch_all_reviews.php
│   │   ├── fetch_user_reservations.php
│   │   ├── fetch_user_orders.php
│   │   ├── get_profile.php
│   │   ├── place_order.php
│   │   ├── make_reservation.php
│   │   ├── reorder_order.php
│   │   ├── make_review.php
│   │   ├── dashboard_actions.php
│   │   ├── check_remember_me.php
│   │   ├── chatbot_proxy.php
│   │   ├── session_check.php
│   │   ├── update_profile.php
│   │   ├── uplode_profile_avatar.php
│   │   ├── signup.php
│   │   └── login_google
│   │
│   ├── config/
│   │   └── connection.php                
│   │
│   ├── models/
│   │   ├── DashboardModel.php 
│   │   └── ProfileModel.php
│   │
│   └── Database/
│       └── SmartBite.sql
│  
└── README.md

```

---
## Base de données

La base de données MySQL centralise toutes les données du restaurant. Voici le MCD :

![Model Conceptuel de Donné](./Frontend/img/MCD_SmartBite.png)

---

## Lancer le projet en local

# Lancer avec Live Server (VS Code) ou ouvrir index.html directement dans le navigateur pour visualiser le frontend

**Clonez le repertoire git:**

   ```bash
   git clone https://github.com/Emberizaelysee/SmartBite.git
   cd SmartBite
   ```

## Prerequis pour lancer le frontend et le backend
Pour faire marcher ce projet localement, il vous faut:
- Un serveur local comme **XAMPP**, **WAMP**, or **MAMP** (qui inclue Apache et MySQL).
- Un navigateur moderne.

## Instruction pour la mise en place du projet

1. **Clonez le repertoire git:**
   ```bash
   git clone https://github.com/Emberizaelysee/SmartBite.git
   cd SmartBite
   ```

2. **Aller dans le fichier du serveur local:**
   Metter le fichier `SmartBite` dans le fichier root:
   - Pour XAMPP: `htdocs/`
   - Pour WAMP: `www/`
   - Pour MAMP: `htdocs/`

3. **Mise en place de la base de donnes:**
   - Demarrer les composants Apache et MySQL depuis l'interface du serveur local.
   - Ouvrer phpMyAdmin (`http://localhost/phpmyadmin`).
   - Creer une dase de donne nommez `SmartBite`.
   - Improter le fichier SQL fournis dans `Backend/Database/SmartBite.sql` dans l'interface phpMyAdmin.

4. **Acceder a l'application:**
   Ouvrer le navigateur et naviger vers:

    `http://localhost/SmartBite/Frontend/index.html`



## Pour faire fonctionner le chatbot vous dever crée un fichier .env et y mettre dedans votre clés api gemini

```bash
DB_HOST="localhost"
DB_NAME="SmartBite"
DB_USER="root"
DB_PASS=""
GEMINI_API_KEY="VOTRE_CLE_API"
```