<?php
// sill-admin/guide.php — Guide d'utilisation du backend SILL SA
// Included from layout.php

$role = $_SESSION['admin_role'] ?? 'editor';
?>

<div class="guide">

    <!-- ═══════════════════════════════════════════════════════════
         HERO
         ═══════════════════════════════════════════════════════════ -->
    <div class="guide-hero">
        <div class="guide-hero-content">
            <h1>Guide d'utilisation</h1>
            <p class="guide-hero-sub">Tout ce qu'il faut savoir pour piloter le site <strong>sillsa.ch</strong></p>
        </div>
        <div class="guide-hero-meta">
            <span>Votre rôle : <strong class="role-<?= e($role) ?>"><?= $role === 'admin' ? 'Administrateur' : 'Éditeur' ?></strong></span>
            <span>Dernière mise à jour : mars 2026</span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         TABLE OF CONTENTS
         ═══════════════════════════════════════════════════════════ -->
    <nav class="guide-toc">
        <h2>Sommaire</h2>
        <ol>
            <li><a href="#guide-connexion">Se connecter</a></li>
            <li><a href="#guide-dashboard">Tableau de bord</a></li>
            <li><a href="#guide-kpi">KPIs — Chiffres clés</a></li>
            <li><a href="#guide-pages">Pages — Contenu éditorial</a></li>
            <li><a href="#guide-immeubles">Immeubles — Portefeuille</a></li>
            <li><a href="#guide-timeline">Actualités — Chronologie</a></li>
            <li><a href="#guide-publications">Publications — Rapports PDF</a></li>
            <li><a href="#guide-menu">Menu — Navigation</a></li>
            <li><a href="#guide-settings">Paramètres</a></li>
            <li><a href="#guide-roles">Rôles et permissions</a></li>
            <li><a href="#guide-architecture">Comment le site fonctionne</a></li>
            <li><a href="#guide-faq">Questions fréquentes</a></li>
        </ol>
    </nav>

    <!-- ═══════════════════════════════════════════════════════════
         1. CONNEXION
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-connexion">
        <div class="guide-section-header">
            <span class="guide-section-number">01</span>
            <h2>Se connecter</h2>
        </div>
        <div class="guide-section-body">
            <p>L'accès au backend se fait via <code>sillsa.ch/sill-admin/</code></p>

            <div class="guide-card guide-card--highlight">
                <h3>Connexion Microsoft (recommandé)</h3>
                <p>Cliquez sur <strong>« Se connecter avec Microsoft »</strong>. Votre compte professionnel <code>@sillsa.ch</code> vous identifie automatiquement. Votre rôle (Administrateur ou Éditeur) est déterminé par votre groupe Azure AD.</p>
            </div>

            <div class="guide-card">
                <h3>Connexion par mot de passe (fallback)</h3>
                <p>Un formulaire classique identifiant / mot de passe est disponible en dessous. Réservé au compte technique de maintenance.</p>
            </div>

            <div class="guide-tip">
                <strong>Sécurité :</strong> Votre session expire après 30 minutes d'inactivité. Les données non enregistrées seront perdues — pensez à sauvegarder régulièrement.
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         2. DASHBOARD
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-dashboard">
        <div class="guide-section-header">
            <span class="guide-section-number">02</span>
            <h2>Tableau de bord</h2>
        </div>
        <div class="guide-section-body">
            <p>La page d'accueil du backend affiche un résumé de l'état du site :</p>
            <ul>
                <li><strong>Nombre de pages</strong> publiées et brouillons</li>
                <li><strong>Nombre d'immeubles</strong> dans le portefeuille</li>
                <li><strong>Nombre de KPIs</strong> visibles sur la homepage</li>
                <li><strong>Événements timeline</strong></li>
            </ul>
            <p>C'est un tableau de bord en lecture seule — aucune action n'est nécessaire ici.</p>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         3. KPI
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-kpi">
        <div class="guide-section-header">
            <span class="guide-section-number">03</span>
            <h2>KPIs — Chiffres clés</h2>
        </div>
        <div class="guide-section-body">
            <p>Les KPIs apparaissent à deux endroits sur le site public :</p>

            <div class="guide-grid-2">
                <div class="guide-card">
                    <h3>Homepage — Bandeau chiffres clés</h3>
                    <p>Les 8 premiers KPIs visibles (catégorie <code>patrimoine</code>) s'affichent dans le bandeau animé de la page d'accueil. Les compteurs s'animent au scroll.</p>
                </div>
                <div class="guide-card">
                    <h3>Page Contexte économique</h3>
                    <p>Les KPIs des catégories <code>marche</code>, <code>energie</code> et <code>sill</code> alimentent la page d'indicateurs conjoncturels et de positionnement SILL SA.</p>
                </div>
            </div>

            <h3>Comment modifier un KPI</h3>
            <ol>
                <li>Cliquez sur <strong>Modifier</strong> à côté du KPI</li>
                <li>Modifiez la valeur numérique, le texte, l'unité ou le libellé</li>
                <li>Le <strong>toggle Visible</strong> contrôle l'affichage sur le site public</li>
                <li>Cliquez <strong>Enregistrer</strong></li>
            </ol>

            <div class="guide-tip">
                <strong>Attention :</strong> La homepage affiche au maximum 8 KPIs visibles. Si vous en activez plus de 8, seuls les 8 premiers (par ordre) seront affichés.
            </div>

            <div class="guide-fields">
                <h3>Champs disponibles</h3>
                <table>
                    <thead><tr><th>Champ</th><th>Exemple</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Label</strong></td><td>Logements et lots</td><td>Titre affiché sous le chiffre</td></tr>
                        <tr><td><strong>Valeur numérique</strong></td><td>834.00</td><td>Le chiffre animé (compteur)</td></tr>
                        <tr><td><strong>Valeur texte</strong></td><td>Stable depuis juin 2025</td><td>Texte affiché si pas de chiffre, ou en complément</td></tr>
                        <tr><td><strong>Unité</strong></td><td>m², %, M CHF, CHF/m²/an</td><td>Affiché après le chiffre</td></tr>
                        <tr><td><strong>Ordre</strong></td><td>1, 2, 3…</td><td>Position dans le bandeau</td></tr>
                        <tr><td><strong>Visible</strong></td><td>Oui / Non</td><td>Toggle d'affichage public</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         4. PAGES
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-pages">
        <div class="guide-section-header">
            <span class="guide-section-number">04</span>
            <h2>Pages — Contenu éditorial</h2>
        </div>
        <div class="guide-section-body">
            <p>Les pages regroupent le contenu éditorial du site : La Société, Environnement, Location, Bonnes pratiques, etc.</p>

            <h3>Éditeur visuel (TinyMCE)</h3>
            <p>Chaque page dispose d'un éditeur visuel riche. Vous pouvez :</p>
            <ul>
                <li><strong>Mettre en forme</strong> le texte (gras, italique, listes, titres)</li>
                <li><strong>Insérer des liens</strong> vers d'autres pages ou des documents</li>
                <li><strong>Ajouter des images</strong> en collant l'URL d'un fichier uploadé</li>
                <li><strong>Voir le code HTML</strong> en cliquant sur le bouton <code>&lt;/&gt;</code></li>
            </ul>

            <div class="guide-card guide-card--highlight">
                <h3>Champs importants</h3>
                <table>
                    <thead><tr><th>Champ</th><th>Impact sur le site</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Titre</strong></td><td>Titre affiché en haut de la page + balise <code>&lt;title&gt;</code></td></tr>
                        <tr><td><strong>Slug</strong></td><td>L'URL de la page. Ex : <code>la-societe</code> → sillsa.ch/la-societe</td></tr>
                        <tr><td><strong>Chapeau</strong></td><td>Texte d'introduction en grand, affiché sous le titre</td></tr>
                        <tr><td><strong>Contenu</strong></td><td>Corps de la page (éditeur visuel)</td></tr>
                        <tr><td><strong>Meta description</strong></td><td>Texte pour Google (référencement SEO)</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="guide-warning">
                <strong>Ne modifiez pas les slugs</strong> des pages existantes sans concertation. Changer un slug casse les liens existants et les favoris des visiteurs.
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         5. IMMEUBLES
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-immeubles">
        <div class="guide-section-header">
            <span class="guide-section-number">05</span>
            <h2>Immeubles — Portefeuille</h2>
        </div>
        <div class="guide-section-body">
            <p>Les immeubles constituent le cœur du site. Chaque immeuble a sa <strong>fiche détaillée</strong> et apparaît sur la <strong>carte interactive</strong> du portefeuille.</p>

            <div class="guide-grid-2">
                <div class="guide-card">
                    <h3>Fiche immeuble</h3>
                    <ul>
                        <li>Nom, adresse, quartier</li>
                        <li>Nombre de logements</li>
                        <li>Année de construction / rénovation</li>
                        <li>Label énergie (CECB, Minergie…)</li>
                        <li>Description</li>
                        <li>Galerie photos</li>
                    </ul>
                </div>
                <div class="guide-card">
                    <h3>Carte interactive</h3>
                    <ul>
                        <li>Position X/Y sur la carte SVG de Lausanne</li>
                        <li>Les points rouges sont cliquables</li>
                        <li>Le panneau latéral affiche un aperçu</li>
                        <li>Sur mobile, clic → navigation directe vers la fiche</li>
                    </ul>
                </div>
            </div>

            <h3>Galerie photos</h3>
            <p>Chaque immeuble peut avoir une <strong>image de couverture</strong> et une <strong>galerie</strong>. Les images sont stockées dans <code>/uploads/immeubles/{slug}/</code>.</p>
            <ul>
                <li><strong>Couverture</strong> : fichier nommé <code>cover.jpg</code> — visible dans la grille et la fiche</li>
                <li><strong>Galerie</strong> : fichiers numérotés <code>01-description.jpg</code>, <code>02-description.jpg</code>…</li>
                <li>Formats acceptés : JPG, PNG, WebP</li>
                <li>Taille recommandée : 1600×1000px minimum</li>
            </ul>

            <div class="guide-tip">
                <strong>Astuce :</strong> L'ordre d'affichage de la galerie est alphabétique. Utilisez le préfixe numérique (<code>01-</code>, <code>02-</code>…) pour contrôler l'ordre.
            </div>

            <div class="guide-card guide-card--highlight">
                <h3>Activer / désactiver un immeuble</h3>
                <p>Le toggle <strong>Actif</strong> contrôle si l'immeuble est visible sur le site public. Un immeuble inactif (<code>is_active = 0</code>) n'apparaît ni sur la carte, ni dans la grille, ni dans les KPIs. Utile pour préparer un immeuble avant son annonce officielle.</p>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         6. TIMELINE
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-timeline">
        <div class="guide-section-header">
            <span class="guide-section-number">06</span>
            <h2>Actualités — Chronologie</h2>
        </div>
        <div class="guide-section-body">
            <p>La timeline est affichée sur la page d'accueil. Elle retrace l'histoire de la SILL SA sous forme de frise chronologique interactive.</p>

            <h3>Créer un événement</h3>
            <ol>
                <li>Cliquez <strong>Nouveau</strong></li>
                <li>Remplissez le titre, la date, la catégorie et la description</li>
                <li>La description doit faire <strong>~180-200 caractères</strong> (ton institutionnel, pas de jargon)</li>
                <li>Vous pouvez associer une image existante</li>
            </ol>

            <div class="guide-fields">
                <h3>Catégories timeline</h3>
                <table>
                    <thead><tr><th>Catégorie</th><th>Usage</th></tr></thead>
                    <tbody>
                        <tr><td><strong>construction</strong></td><td>Nouvelle construction, livraison</td></tr>
                        <tr><td><strong>renovation</strong></td><td>Rénovation, transformation</td></tr>
                        <tr><td><strong>acquisition</strong></td><td>Achat, préemption</td></tr>
                        <tr><td><strong>institutionnel</strong></td><td>Gouvernance, décisions du Conseil</td></tr>
                        <tr><td><strong>energie</strong></td><td>Projets énergétiques, labels</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="guide-tip">
                <strong>Filtres :</strong> Sur le site public, les visiteurs peuvent filtrer les événements par catégorie. Choisissez la catégorie la plus pertinente pour chaque événement.
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         7. PUBLICATIONS
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-publications">
        <div class="guide-section-header">
            <span class="guide-section-number">07</span>
            <h2>Publications — Rapports PDF</h2>
        </div>
        <div class="guide-section-body">
            <p>La page Publications du site affiche les rapports annuels, documents officiels et autres PDF de la SILL SA.</p>

            <h3>Ajouter une publication</h3>
            <ol>
                <li>Cliquez <strong>Nouvelle publication</strong></li>
                <li>Remplissez le titre, l'année, le type</li>
                <li>Uploadez le <strong>fichier PDF</strong></li>
                <li>Uploadez une <strong>image de couverture</strong> (optionnel mais recommandé)</li>
                <li>Cliquez <strong>Créer</strong></li>
            </ol>

            <div class="guide-grid-2">
                <div class="guide-card">
                    <h3>Types de publications</h3>
                    <ul>
                        <li><strong>rapport_annuel</strong> — Rapport annuel</li>
                        <li><strong>rapport_activite</strong> — Rapport d'activité</li>
                        <li><strong>autre</strong> — Documents divers</li>
                    </ul>
                </div>
                <div class="guide-card">
                    <h3>Bonnes pratiques</h3>
                    <ul>
                        <li>Nommez le PDF clairement : <code>RA-2025-SILL-SA.pdf</code></li>
                        <li>Couverture : image de la 1ère page, ratio portrait</li>
                        <li>Taille PDF max recommandée : 20 Mo</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         8. MENU
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-menu">
        <div class="guide-section-header">
            <span class="guide-section-number">08</span>
            <h2>Menu — Navigation</h2>
        </div>
        <div class="guide-section-body">
            <p>Le menu de navigation du site public (header + footer) est entièrement géré depuis cette section.</p>

            <h3>Structure du menu</h3>
            <ul>
                <li><strong>Niveau 1</strong> — Éléments principaux visibles dans le header</li>
                <li><strong>Niveau 2</strong> — Sous-éléments (dropdown) rattachés à un parent</li>
            </ul>

            <h3>Champs</h3>
            <table>
                <thead><tr><th>Champ</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><strong>Libellé</strong></td><td>Texte affiché dans le menu</td></tr>
                    <tr><td><strong>Cible</strong></td><td>Le slug de la page (ex : <code>la-societe</code>) ou une URL externe</td></tr>
                    <tr><td><strong>Parent</strong></td><td>Aucun = niveau 1. Sélectionner un parent = sous-menu</td></tr>
                    <tr><td><strong>Ordre</strong></td><td>Position dans le menu (les flèches ▲▼ permettent de réordonner)</td></tr>
                    <tr><td><strong>Actif</strong></td><td>Si désactivé, l'élément disparaît du site public</td></tr>
                </tbody>
            </table>

            <div class="guide-warning">
                <strong>Impact immédiat :</strong> Toute modification du menu est visible instantanément sur le site public. Vérifiez le site après chaque changement.
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         9. SETTINGS
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-settings">
        <div class="guide-section-header">
            <span class="guide-section-number">09</span>
            <h2>Paramètres</h2>
        </div>
        <div class="guide-section-body">
            <p>Les paramètres sont des valeurs clé/valeur qui contrôlent des éléments transversaux du site :</p>
            <ul>
                <li><strong>tagline</strong> — Sous-titre du site</li>
                <li><strong>contact_email</strong> — Adresse e-mail affichée publiquement</li>
                <li><strong>contact_phone</strong> — Numéro de téléphone</li>
                <li><strong>contact_address</strong> — Adresse postale dans le footer</li>
                <li><strong>meta_description</strong> — Description pour les moteurs de recherche (SEO)</li>
            </ul>

            <div class="guide-tip">
                <strong>SEO :</strong> La meta description apparaît dans les résultats Google sous le titre du site. Elle doit faire 150-160 caractères et décrire clairement l'activité de la SILL SA.
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         10. ROLES
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-roles">
        <div class="guide-section-header">
            <span class="guide-section-number">10</span>
            <h2>Rôles et permissions</h2>
        </div>
        <div class="guide-section-body">
            <p>L'accès au backend est contrôlé par deux groupes Microsoft Azure AD :</p>

            <div class="guide-grid-2">
                <div class="guide-card guide-card--admin">
                    <h3>Administrateur</h3>
                    <p class="guide-card-group">Groupe : SILL-Backend-Admin</p>
                    <ul>
                        <li>Créer, modifier, supprimer du contenu</li>
                        <li>Gérer le menu de navigation</li>
                        <li>Modifier les paramètres du site</li>
                        <li>Accès complet à toutes les sections</li>
                    </ul>
                </div>
                <div class="guide-card guide-card--editor">
                    <h3>Éditeur</h3>
                    <p class="guide-card-group">Groupe : SILL-Backend-Editeur</p>
                    <ul>
                        <li>Créer et modifier du contenu</li>
                        <li>Gérer le menu de navigation</li>
                        <li>Modifier les paramètres du site</li>
                        <li><strong>Pas de suppression</strong> — Les boutons « Supprimer » ne sont pas visibles</li>
                    </ul>
                </div>
            </div>

            <div class="guide-tip">
                <strong>Besoin de supprimer ?</strong> Si vous êtes éditeur et devez supprimer un contenu, contactez un administrateur.
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         11. ARCHITECTURE
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-architecture">
        <div class="guide-section-header">
            <span class="guide-section-number">11</span>
            <h2>Comment le site fonctionne</h2>
        </div>
        <div class="guide-section-body">
            <p>Comprendre l'architecture vous aide à anticiper l'impact de vos modifications.</p>

            <div class="guide-architecture-map">
                <div class="guide-arch-item">
                    <h3>Page d'accueil</h3>
                    <p>Assemblée automatiquement à partir de :</p>
                    <ul>
                        <li>Image hero (hardcoded)</li>
                        <li><strong>KPIs</strong> visibles (bandeau animé)</li>
                        <li><strong>Timeline</strong> (frise chronologique filtrable)</li>
                        <li>Teaser contexte économique</li>
                    </ul>
                </div>
                <div class="guide-arch-item">
                    <h3>Pages éditoriales</h3>
                    <p>Chaque page est un template :</p>
                    <ul>
                        <li><strong>La Société</strong> — contenu BDD + carrousel immeubles</li>
                        <li><strong>Environnement</strong> — contenu BDD + graphique scatter</li>
                        <li><strong>Location</strong> — contenu BDD + sidebar contact</li>
                        <li><strong>Contexte</strong> — KPIs dynamiques + graphiques Chart.js</li>
                    </ul>
                </div>
                <div class="guide-arch-item">
                    <h3>Portefeuille</h3>
                    <ul>
                        <li><strong>Carte SVG</strong> interactive (desktop)</li>
                        <li><strong>Grille</strong> de cards (mobile)</li>
                        <li>Chaque immeuble a sa fiche détaillée</li>
                        <li>Les données viennent de la table <code>sill_immeubles</code></li>
                    </ul>
                </div>
                <div class="guide-arch-item">
                    <h3>Navigation</h3>
                    <ul>
                        <li>Header + footer générés depuis <code>sill_menu</code></li>
                        <li>Modifiez le menu ici → le site reflète instantanément</li>
                        <li>Le fil d'Ariane est automatique</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         12. FAQ
         ═══════════════════════════════════════════════════════════ -->
    <section class="guide-section" id="guide-faq">
        <div class="guide-section-header">
            <span class="guide-section-number">12</span>
            <h2>Questions fréquentes</h2>
        </div>
        <div class="guide-section-body">

            <div class="guide-faq-item">
                <h3>J'ai modifié un contenu mais je ne vois pas le changement sur le site</h3>
                <p>Le serveur met en cache les pages pour accélérer le chargement. Essayez <strong>Ctrl+Shift+R</strong> (rechargement forcé) dans votre navigateur. Les modifications sont toujours effectives côté serveur immédiatement.</p>
            </div>

            <div class="guide-faq-item">
                <h3>Comment ajouter un nouvel immeuble au portefeuille ?</h3>
                <p>Allez dans <strong>Immeubles → Nouveau</strong>. Remplissez tous les champs, uploadez les images, et positionnez-le sur la carte (coordonnées X/Y). Si vous n'êtes pas sûr de la position, contactez l'administrateur.</p>
            </div>

            <div class="guide-faq-item">
                <h3>Comment préparer un contenu sans le publier ?</h3>
                <p>Pour les immeubles, désactivez le toggle <strong>Actif</strong>. Pour les pages, utilisez le champ statut. Le contenu sera sauvegardé en base mais invisible sur le site public.</p>
            </div>

            <div class="guide-faq-item">
                <h3>Comment mettre à jour les chiffres de la page Contexte ?</h3>
                <p>Les indicateurs conjoncturels (marché, taux, énergie) se modifient dans <strong>KPIs</strong>. Les graphiques d'évolution (taux de référence, indice CRB, camembert loyers) sont codés dans le template — contactez l'administrateur pour les mettre à jour.</p>
            </div>

            <div class="guide-faq-item">
                <h3>Qui contacter en cas de problème technique ?</h3>
                <p>Contactez la direction de la SILL SA. Les problèmes techniques sont gérés en interne ou via le prestataire de développement.</p>
            </div>

            <div class="guide-faq-item">
                <h3>Puis-je casser le site en faisant une erreur ?</h3>
                <p>Non. Le backend protège contre les erreurs critiques. Au pire, un contenu affiché sera incorrect et pourra être corrigé. Les éditeurs n'ont pas accès à la suppression, ce qui limite les risques. En cas de doute, demandez avant d'agir.</p>
            </div>

        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         FOOTER
         ═══════════════════════════════════════════════════════════ -->
    <div class="guide-footer">
        <p>SILL SA — Société Immobilière Lausanne-Littoral<br>
        Backend développé en PHP vanilla — Design System Swiss Style<br>
        Guide rédigé en mars 2026</p>
    </div>

</div>
