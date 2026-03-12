# SILL Admin — Guide d'utilisation

## Connexion

- URL : `https://26.sillsa.ch/sill-admin/`
- Saisir identifiant et mot de passe
- Protection anti brute-force : 5 tentatives max en 15 minutes
- Session expirée = redirection automatique vers la page de login

---

## Navigation

La barre horizontale en haut donne accès à toutes les sections :

| Section | Description |
|---|---|
| **Tableau de bord** | Vue d'ensemble : nombre de KPIs, pages, publications et éléments de menu actifs |
| **KPIs** | Gestion de la visibilité des chiffres clés affichés sur la homepage |
| **Pages** | Création, modification et désactivation des pages du site |
| **Publications** | Gestion des publications PDF (rapports annuels, etc.) |
| **Paramètres** | Réglages du site (tagline, coordonnées, meta, etc.) |
| **Menu** | Gestion des rubriques de navigation et leur ordre d'affichage |

Le nom d'utilisateur connecté apparaît en haut à droite, avec le bouton **Déconnexion**.

---

## Tableau de bord

Affiche les compteurs principaux :
- Nombre total de KPIs et ceux visibles sur la homepage
- Nombre de pages actives
- Nombre de publications actives
- Nombre d'éléments de menu actifs

Aucune action n'est requise ici, c'est un résumé de l'état du site.

---

## KPIs (chiffres clés)

Les valeurs des KPIs sont **importées depuis Excel** et ne sont pas modifiables depuis l'interface.

**Seule action possible** : activer ou désactiver la visibilité d'un KPI sur la homepage en cliquant sur le **toggle** (interrupteur) dans la colonne "Visible".

- Toggle vert = visible sur la homepage
- Toggle gris = masqué

Les colonnes affichées :
- **Label** : nom du KPI (ex: "Immeubles", "Surface SUP totale")
- **Valeur** : valeur numérique ou texte (readonly)
- **Unité** : unité de mesure (m², M CHF, %, etc.)
- **Visible** : toggle on/off

---

## Pages

### Liste des pages
Affiche toutes les pages du site avec leur titre, slug (URL), statut actif/inactif, et les actions disponibles.

### Créer une page
1. Cliquer **Nouvelle page**
2. Remplir le **Titre** (obligatoire)
3. Le **Slug** (partie de l'URL) est généré automatiquement si laissé vide
4. Saisir le **Contenu** via l'éditeur visuel (WYSIWYG)
5. Optionnel : renseigner le **Meta title** et la **Meta description** (SEO)
6. Cocher **Page active** pour la rendre visible
7. Cliquer **Enregistrer**

### Modifier une page
1. Cliquer **Modifier** sur la ligne de la page
2. Modifier les champs souhaités
3. L'éditeur de contenu permet de formater le texte sans écrire de HTML :
   - **Gras**, *Italique*, Souligné
   - Listes à puces et numérotées
   - Liens hypertexte
   - Insertion d'images et de tableaux
   - Choix du format (Paragraphe, Titre 2, Titre 3, etc.)
   - Bouton **Source** pour basculer en mode HTML brut si besoin
   - Bouton **Agrandir** pour éditer en plein écran
4. Cliquer **Enregistrer**

### Désactiver une page
- Cliquer **Désactiver** (la page n'est pas supprimée, juste masquée du site)

---

## Publications

### Liste
Affiche toutes les publications avec leur titre, année, type et statut.

### Créer une publication
1. Cliquer **Nouvelle publication**
2. Remplir le titre, l'année, le type (rapport annuel, communiqué, etc.)
3. Uploader le fichier PDF (10 Mo maximum)
4. Cliquer **Enregistrer**

### Modifier / Désactiver
- Même principe que pour les pages

---

## Paramètres

Affiche tous les paramètres du site sous forme clé/valeur :
- Les clés sont fixes (définies dans la base de données)
- Les valeurs sont modifiables librement
- Les textes longs s'affichent dans un champ multi-lignes

Cliquer **Enregistrer les paramètres** pour sauvegarder toutes les modifications.

---

## Menu

Gestion des rubriques de la navigation principale du site :
- **Ajouter** une rubrique
- **Modifier** le libellé, le lien, le parent (pour sous-menus)
- **Réordonner** les rubriques (champ sort_order)
- **Activer / Désactiver** une rubrique

---

## Notes techniques

- Le back-office est accessible uniquement via `/sill-admin/`
- Les mots de passe sont chiffrés (bcrypt)
- Chaque formulaire est protégé contre les attaques CSRF
- Les sessions expirent automatiquement après inactivité
- Les fichiers uploadés sont validés par type MIME
