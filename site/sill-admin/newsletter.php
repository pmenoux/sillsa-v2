<?php
// newsletter.php — Gestion Newsletter Infomaniak
// SILL SA v2 — PHP 8.2 vanilla
// Included from layout.php inside admin-main div.

// ---------------------------------------------------------------------------
// Bootstrap: load config + API
// ---------------------------------------------------------------------------

if (!file_exists(__DIR__ . '/newsletter-config.php')) {
    echo '<div class="flash flash-error">Fichier <code>newsletter-config.php</code> manquant. Copiez le modèle et renseignez le token API Infomaniak.</div>';
    return;
}

require_once __DIR__ . '/newsletter-config.php';
require_once __DIR__ . '/newsletter-api.php';

$nl = new InfomaniakNewsletter(NEWSLETTER_API_TOKEN, NEWSLETTER_DOMAIN_ID);

// ---------------------------------------------------------------------------
// Tab routing
// ---------------------------------------------------------------------------

$tab = preg_replace('/[^a-z]/', '', $_GET['tab'] ?? 'dashboard');
$validTabs = ['dashboard', 'subscribers', 'groups', 'campaigns', 'stats'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'dashboard';
}

// ---------------------------------------------------------------------------
// Tab navigation
// ---------------------------------------------------------------------------
?>
<div class="page-header">
    <h1>Newsletter</h1>
</div>

<div class="nl-tabs">
    <a href="?page=newsletter&tab=dashboard" class="nl-tab <?= $tab === 'dashboard' ? 'nl-tab--active' : '' ?>">Tableau de bord</a>
    <a href="?page=newsletter&tab=subscribers" class="nl-tab <?= $tab === 'subscribers' ? 'nl-tab--active' : '' ?>">Abonnés</a>
    <a href="?page=newsletter&tab=groups" class="nl-tab <?= $tab === 'groups' ? 'nl-tab--active' : '' ?>">Groupes</a>
    <a href="?page=newsletter&tab=campaigns" class="nl-tab <?= $tab === 'campaigns' ? 'nl-tab--active' : '' ?>">Campagnes</a>
    <a href="?page=newsletter&tab=stats" class="nl-tab <?= $tab === 'stats' ? 'nl-tab--active' : '' ?>">Statistiques</a>
</div>

<?php

// =========================================================================
//  TAB: DASHBOARD
// =========================================================================

if ($tab === 'dashboard'):
    $statusResp   = $nl->countStatus();
    $dashResp     = $nl->dashboard();
    $creditsResp  = $nl->getCredits();

    $status  = InfomaniakNewsletter::data($statusResp) ?? [];
    $dash    = InfomaniakNewsletter::data($dashResp) ?? [];
    $credits = InfomaniakNewsletter::data($creditsResp) ?? [];

    $totalActive   = $status['active'] ?? 0;
    $totalBounced  = $status['bounced'] ?? 0;
    $totalUnsub    = $status['unsubscribed'] ?? 0;
    $totalSent     = $dash['send_total'] ?? 0;
?>

<div class="stats-grid" style="margin-top:20px">
    <div class="stat-card">
        <div class="stat-value"><?= number_format($totalActive, 0, ',', '\'') ?></div>
        <div class="stat-label">Abonnés actifs</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($totalBounced, 0, ',', '\'') ?></div>
        <div class="stat-label">Bounced</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($totalUnsub, 0, ',', '\'') ?></div>
        <div class="stat-label">Désinscrits</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= number_format($totalSent, 0, ',', '\'') ?></div>
        <div class="stat-label">Emails envoyés (total)</div>
    </div>
</div>

<?php
    // Recent campaigns
    $campResp = $nl->dashboardCampaigns();
    $recentCampaigns = InfomaniakNewsletter::data($campResp) ?? [];
?>

<h2 style="font-size:16px; font-weight:600; margin:24px 0 12px">Dernières campagnes</h2>

<?php if (empty($recentCampaigns)): ?>
    <p class="admin-empty">Aucune campagne récente.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Objet</th>
                <th>Statut</th>
                <th>Expéditeur</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recentCampaigns as $c): ?>
            <tr>
                <td><strong><?= e($c['subject'] ?? '—') ?></strong></td>
                <td><?= nlStatusBadge($c['status'] ?? '') ?></td>
                <td><?= e($c['email_from_name'] ?? '') ?></td>
                <td class="cell-readonly"><?= $c['started_at'] ? date('d.m.Y H:i', $c['started_at']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php
// =========================================================================
//  TAB: SUBSCRIBERS
// =========================================================================

elseif ($tab === 'subscribers'):

    // --- POST handlers ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrfCheck();

        // CREATE subscriber
        if ($action === 'create') {
            $email     = trim($_POST['email'] ?? '');
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname  = trim($_POST['lastname'] ?? '');
            $groups    = array_map('intval', $_POST['groups'] ?? []);

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Adresse email invalide.');
            } else {
                $fields = [];
                if ($firstname) $fields['firstname'] = $firstname;
                if ($lastname)  $fields['lastname']  = $lastname;

                $resp = $nl->createSubscriber($email, $fields, $groups);
                if (InfomaniakNewsletter::data($resp) !== null) {
                    flash('success', 'Abonné ajouté : ' . $email);
                } else {
                    flash('error', 'Erreur API : ' . InfomaniakNewsletter::error($resp));
                }
            }
            header('Location: ?page=newsletter&tab=subscribers');
            exit;
        }

        // DELETE subscriber
        if ($action === 'delete') {
            if (!canDelete()) {
                flash('error', 'Suppression réservée aux administrateurs.');
            } else {
                $subId = (int) ($_POST['subscriber_id'] ?? 0);
                if ($subId > 0) {
                    $resp = $nl->deleteSubscriber($subId);
                    if (($resp['result'] ?? '') === 'success') {
                        flash('success', 'Abonné supprimé.');
                    } else {
                        flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                    }
                }
            }
            header('Location: ?page=newsletter&tab=subscribers');
            exit;
        }

        // UPDATE subscriber
        if ($action === 'edit') {
            $subId     = (int) ($_POST['subscriber_id'] ?? 0);
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname  = trim($_POST['lastname'] ?? '');

            if ($subId > 0) {
                $data = ['fields' => []];
                if ($firstname !== '') $data['fields']['firstname'] = $firstname;
                if ($lastname !== '')  $data['fields']['lastname']  = $lastname;

                $resp = $nl->updateSubscriber($subId, $data);
                if (($resp['result'] ?? '') === 'success') {
                    flash('success', 'Abonné mis à jour.');
                } else {
                    flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                }
            }
            header('Location: ?page=newsletter&tab=subscribers');
            exit;
        }
    }

    // --- VIEW: Create form ---
    if ($action === 'create'):
        $groupsResp = $nl->getGroups();
        $allGroups  = InfomaniakNewsletter::data($groupsResp) ?? [];
    ?>
    <div class="page-header" style="margin-top:16px">
        <h2 style="font-size:18px; font-weight:600">Nouvel abonné</h2>
        <a href="?page=newsletter&tab=subscribers" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=newsletter&tab=subscribers&action=create" class="admin-form">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="email">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="firstname">Prénom</label>
                <input type="text" id="firstname" name="firstname">
            </div>
            <div class="form-group">
                <label for="lastname">Nom</label>
                <input type="text" id="lastname" name="lastname">
            </div>
        </div>

        <div class="form-group">
            <label>Groupes</label>
            <div class="nl-checkbox-grid">
                <?php foreach ($allGroups as $g): ?>
                <label class="nl-checkbox-label">
                    <input type="checkbox" name="groups[]" value="<?= (int) $g['id'] ?>">
                    <?= e($g['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Ajouter</button>
            <a href="?page=newsletter&tab=subscribers" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
    <?php
        return;
    endif;

    // --- VIEW: Edit form ---
    if ($action === 'edit' && $id):
        $subResp = $nl->getSubscriber($id);
        $sub     = InfomaniakNewsletter::data($subResp);
        if (!$sub) {
            flash('error', 'Abonné introuvable.');
            header('Location: ?page=newsletter&tab=subscribers');
            exit;
        }
    ?>
    <div class="page-header" style="margin-top:16px">
        <h2 style="font-size:18px; font-weight:600">Modifier l'abonné</h2>
        <a href="?page=newsletter&tab=subscribers" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=newsletter&tab=subscribers&action=edit" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="subscriber_id" value="<?= (int) $sub['id'] ?>">

        <div class="form-group">
            <label>Email</label>
            <input type="email" value="<?= e($sub['email'] ?? '') ?>" class="input-readonly" readonly>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="firstname">Prénom</label>
                <input type="text" id="firstname" name="firstname" value="<?= e($sub['fields']['firstname'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="lastname">Nom</label>
                <input type="text" id="lastname" name="lastname" value="<?= e($sub['fields']['lastname'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Statut</label>
            <p style="padding:8px 0"><?= nlStatusBadge($sub['status'] ?? 'unknown') ?></p>
        </div>

        <div class="form-group">
            <label>Source</label>
            <p class="form-hint" style="margin-top:0"><?= e($sub['source'] ?? '—') ?> — inscrit le <?= !empty($sub['created_at']) ? date('d.m.Y', $sub['created_at']) : '—' ?></p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=newsletter&tab=subscribers" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
    <?php
        return;
    endif;

    // --- VIEW: Subscriber list ---
    $search  = trim($_GET['search'] ?? '');
    $groupId = (int) ($_GET['group'] ?? 0);
    $pg      = max(1, (int) ($_GET['pg'] ?? 1));

    $subsResp = $nl->getSubscribers($pg, 50, $search ?: null, $groupId ?: null);

    $subscribers = [];
    $totalPages  = 1;
    $totalItems  = 0;
    if (($subsResp['result'] ?? '') === 'success') {
        $subscribers = $subsResp['data'] ?? [];
        $totalPages  = $subsResp['pages'] ?? 1;
        $totalItems  = $subsResp['total'] ?? count($subscribers);
    }

    // Groups for filter dropdown
    $groupsResp = $nl->getGroups();
    $allGroups  = InfomaniakNewsletter::data($groupsResp) ?? [];
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin:16px 0">
    <div style="color:#666; font-size:13px"><?= number_format($totalItems, 0, ',', '\'') ?> abonné(s)</div>
    <a href="?page=newsletter&tab=subscribers&action=create" class="btn btn-primary">Nouvel abonné</a>
</div>

<!-- Search & filter -->
<form method="get" class="nl-filters">
    <input type="hidden" name="page" value="newsletter">
    <input type="hidden" name="tab" value="subscribers">
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Rechercher par email ou nom..." class="nl-search-input">
    <select name="group" class="nl-filter-select">
        <option value="">Tous les groupes</option>
        <?php foreach ($allGroups as $g): ?>
            <option value="<?= (int) $g['id'] ?>" <?= $groupId === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filtrer</button>
    <?php if ($search || $groupId): ?>
        <a href="?page=newsletter&tab=subscribers" class="btn btn-sm btn-secondary">Effacer</a>
    <?php endif; ?>
</form>

<?php if (empty($subscribers)): ?>
    <p class="admin-empty">Aucun abonné trouvé.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Email</th>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Statut</th>
                <th>Source</th>
                <th>Inscrit le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($subscribers as $s): ?>
            <tr>
                <td><strong><?= e($s['email'] ?? '') ?></strong></td>
                <td><?= e($s['fields']['firstname'] ?? '') ?></td>
                <td><?= e($s['fields']['lastname'] ?? '') ?></td>
                <td><?= nlStatusBadge($s['status'] ?? '') ?></td>
                <td class="cell-readonly"><?= e($s['source'] ?? '') ?></td>
                <td class="cell-readonly"><?= !empty($s['created_at']) ? date('d.m.Y', $s['created_at']) : '—' ?></td>
                <td class="cell-actions">
                    <a href="?page=newsletter&tab=subscribers&action=edit&id=<?= (int) $s['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <?php if (canDelete()): ?>
                    <form method="post" action="?page=newsletter&tab=subscribers&action=delete" class="form-inline"
                          onsubmit="return confirm('Supprimer cet abonné ?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="subscriber_id" value="<?= (int) $s['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="nl-pagination">
    <?php if ($pg > 1): ?>
        <a href="?page=newsletter&tab=subscribers&pg=<?= $pg - 1 ?>&search=<?= urlencode($search) ?>&group=<?= $groupId ?>" class="btn btn-sm btn-secondary">&laquo; Précédent</a>
    <?php endif; ?>
    <span class="nl-pagination-info">Page <?= $pg ?> / <?= $totalPages ?></span>
    <?php if ($pg < $totalPages): ?>
        <a href="?page=newsletter&tab=subscribers&pg=<?= $pg + 1 ?>&search=<?= urlencode($search) ?>&group=<?= $groupId ?>" class="btn btn-sm btn-secondary">Suivant &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; // end subscribers list ?>

<?php

// =========================================================================
//  TAB: GROUPS
// =========================================================================

elseif ($tab === 'groups'):

    // --- POST handlers ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrfCheck();

        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                flash('error', 'Le nom du groupe est obligatoire.');
            } else {
                $resp = $nl->createGroup($name);
                if (InfomaniakNewsletter::data($resp) !== null) {
                    flash('success', 'Groupe créé : ' . $name);
                } else {
                    flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                }
            }
            header('Location: ?page=newsletter&tab=groups');
            exit;
        }

        if ($action === 'edit') {
            $gid  = (int) ($_POST['group_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            if ($gid > 0 && $name !== '') {
                $resp = $nl->updateGroup($gid, $name);
                if (($resp['result'] ?? '') === 'success') {
                    flash('success', 'Groupe renommé.');
                } else {
                    flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                }
            }
            header('Location: ?page=newsletter&tab=groups');
            exit;
        }

        if ($action === 'delete') {
            if (!canDelete()) {
                flash('error', 'Suppression réservée aux administrateurs.');
            } else {
                $gid = (int) ($_POST['group_id'] ?? 0);
                if ($gid > 0) {
                    $resp = $nl->deleteGroup($gid);
                    if (($resp['result'] ?? '') === 'success') {
                        flash('success', 'Groupe supprimé.');
                    } else {
                        flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                    }
                }
            }
            header('Location: ?page=newsletter&tab=groups');
            exit;
        }
    }

    // --- VIEW: Create form ---
    if ($action === 'create'):
    ?>
    <div class="page-header" style="margin-top:16px">
        <h2 style="font-size:18px; font-weight:600">Nouveau groupe</h2>
        <a href="?page=newsletter&tab=groups" class="btn btn-secondary">Retour</a>
    </div>
    <form method="post" action="?page=newsletter&tab=groups&action=create" class="admin-form">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="name">Nom du groupe <span class="required">*</span></label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="?page=newsletter&tab=groups" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
    <?php
        return;
    endif;

    // --- VIEW: Edit form ---
    if ($action === 'edit' && $id):
        $groupResp = $nl->getGroup($id);
        $group     = InfomaniakNewsletter::data($groupResp);
        if (!$group) {
            flash('error', 'Groupe introuvable.');
            header('Location: ?page=newsletter&tab=groups');
            exit;
        }

        $gSubsResp = $nl->getGroupSubscribers($id, 1, 100);
        $gSubs     = [];
        if (($gSubsResp['result'] ?? '') === 'success') {
            $gSubs = $gSubsResp['data'] ?? [];
        }
    ?>
    <div class="page-header" style="margin-top:16px">
        <h2 style="font-size:18px; font-weight:600">Modifier le groupe</h2>
        <a href="?page=newsletter&tab=groups" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=newsletter&tab=groups&action=edit" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
        <div class="form-group">
            <label for="name">Nom du groupe <span class="required">*</span></label>
            <input type="text" id="name" name="name" value="<?= e($group['name'] ?? '') ?>" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=newsletter&tab=groups" class="btn btn-secondary">Annuler</a>
        </div>
    </form>

    <?php if (!empty($gSubs)): ?>
    <h3 style="font-size:14px; font-weight:600; margin:24px 0 12px">Abonnés dans ce groupe (<?= count($gSubs) ?>)</h3>
    <div class="table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($gSubs as $s): ?>
                <tr>
                    <td><?= e($s['email'] ?? '') ?></td>
                    <td><?= e($s['fields']['firstname'] ?? '') ?></td>
                    <td><?= e($s['fields']['lastname'] ?? '') ?></td>
                    <td><?= nlStatusBadge($s['status'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php
        return;
    endif;

    // --- VIEW: Group list ---
    $groupsResp = $nl->getGroups();
    $allGroups  = InfomaniakNewsletter::data($groupsResp) ?? [];
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin:16px 0">
    <div style="color:#666; font-size:13px"><?= count($allGroups) ?> groupe(s)</div>
    <a href="?page=newsletter&tab=groups&action=create" class="btn btn-primary">Nouveau groupe</a>
</div>

<?php if (empty($allGroups)): ?>
    <p class="admin-empty">Aucun groupe.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>ID</th>
                <th>Dernière MAJ</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allGroups as $g): ?>
            <tr>
                <td><strong><?= e($g['name']) ?></strong></td>
                <td class="cell-readonly"><?= (int) $g['id'] ?></td>
                <td class="cell-readonly"><?= !empty($g['updated_at']) ? date('d.m.Y', $g['updated_at']) : '—' ?></td>
                <td class="cell-actions">
                    <a href="?page=newsletter&tab=groups&action=edit&id=<?= (int) $g['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
                    <?php if (canDelete()): ?>
                    <form method="post" action="?page=newsletter&tab=groups&action=delete" class="form-inline"
                          onsubmit="return confirm('Supprimer ce groupe ? Les abonnés ne seront pas supprimés.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="group_id" value="<?= (int) $g['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php

// =========================================================================
//  TAB: CAMPAIGNS
// =========================================================================

elseif ($tab === 'campaigns'):

    // --- POST handlers ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrfCheck();

        // CREATE campaign
        if ($action === 'create') {
            $subject   = trim($_POST['subject'] ?? '');
            $fromName  = trim($_POST['from_name'] ?? NEWSLETTER_FROM_NAME);
            $fromEmail = trim($_POST['from_email'] ?? NEWSLETTER_FROM_EMAIL);
            $content   = $_POST['content_html'] ?? '';
            $preheader = trim($_POST['preheader'] ?? '');

            if ($subject === '') {
                flash('error', 'L\'objet est obligatoire.');
                header('Location: ?page=newsletter&tab=campaigns&action=create');
                exit;
            }

            $data = [
                'subject'         => $subject,
                'email_from_name' => $fromName,
                'email_from_addr' => $fromEmail,
                'lang'            => 'fr_FR',
                'tracking_link'   => true,
                'tracking_opening'=> true,
            ];
            if ($content !== '')   $data['content_html'] = $content;
            if ($preheader !== '') $data['preheader']     = $preheader;

            // Recipients
            $recipientType = $_POST['recipient_type'] ?? 'all';
            if ($recipientType === 'all') {
                $data['recipients'] = ['all_subscribers' => true];
            } elseif ($recipientType === 'groups') {
                $selectedGroups = array_map('intval', $_POST['recipient_groups'] ?? []);
                if (!empty($selectedGroups)) {
                    $data['recipients'] = ['groups' => ['include' => $selectedGroups]];
                }
            }

            $resp = $nl->createCampaign($data);
            if (InfomaniakNewsletter::data($resp) !== null) {
                flash('success', 'Campagne créée : ' . $subject);
            } else {
                flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
            }
            header('Location: ?page=newsletter&tab=campaigns');
            exit;
        }

        // EDIT campaign
        if ($action === 'edit') {
            $campId    = (int) ($_POST['campaign_id'] ?? 0);
            $subject   = trim($_POST['subject'] ?? '');
            $fromName  = trim($_POST['from_name'] ?? '');
            $fromEmail = trim($_POST['from_email'] ?? '');
            $content   = $_POST['content_html'] ?? '';
            $preheader = trim($_POST['preheader'] ?? '');

            if ($campId > 0 && $subject !== '') {
                $data = [
                    'subject'         => $subject,
                    'email_from_name' => $fromName,
                    'email_from_addr' => $fromEmail,
                    'lang'            => 'fr_FR',
                ];
                if ($content !== '')   $data['content_html'] = $content;
                if ($preheader !== '') $data['preheader']     = $preheader;

                $recipientType = $_POST['recipient_type'] ?? 'all';
                if ($recipientType === 'all') {
                    $data['recipients'] = ['all_subscribers' => true];
                } elseif ($recipientType === 'groups') {
                    $selectedGroups = array_map('intval', $_POST['recipient_groups'] ?? []);
                    if (!empty($selectedGroups)) {
                        $data['recipients'] = ['groups' => ['include' => $selectedGroups]];
                    }
                }

                $resp = $nl->updateCampaign($campId, $data);
                if (($resp['result'] ?? '') === 'success') {
                    flash('success', 'Campagne mise à jour.');
                } else {
                    flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                }
            }
            header('Location: ?page=newsletter&tab=campaigns');
            exit;
        }

        // DELETE campaign
        if ($action === 'delete') {
            if (!canDelete()) {
                flash('error', 'Suppression réservée aux administrateurs.');
            } else {
                $campId = (int) ($_POST['campaign_id'] ?? 0);
                if ($campId > 0) {
                    $resp = $nl->deleteCampaign($campId);
                    if (($resp['result'] ?? '') === 'success') {
                        flash('success', 'Campagne supprimée.');
                    } else {
                        flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                    }
                }
            }
            header('Location: ?page=newsletter&tab=campaigns');
            exit;
        }

        // DUPLICATE campaign
        if ($action === 'duplicate') {
            $campId = (int) ($_POST['campaign_id'] ?? 0);
            if ($campId > 0) {
                $resp = $nl->duplicateCampaign($campId);
                if (InfomaniakNewsletter::data($resp) !== null) {
                    flash('success', 'Campagne dupliquée.');
                } else {
                    flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                }
            }
            header('Location: ?page=newsletter&tab=campaigns');
            exit;
        }

        // SEND TEST
        if ($action === 'test') {
            $campId   = (int) ($_POST['campaign_id'] ?? 0);
            $testEmail = trim($_POST['test_email'] ?? '');
            if ($campId > 0 && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                $resp = $nl->sendTest($campId, $testEmail);
                if (($resp['result'] ?? '') === 'success') {
                    flash('success', 'Email test envoyé à ' . $testEmail);
                } else {
                    flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                }
            } else {
                flash('error', 'Adresse email de test invalide.');
            }
            header('Location: ?page=newsletter&tab=campaigns');
            exit;
        }

        // SCHEDULE / SEND
        if ($action === 'schedule') {
            $campId    = (int) ($_POST['campaign_id'] ?? 0);
            $sendNow   = isset($_POST['send_now']);
            $scheduleAt = trim($_POST['schedule_at'] ?? '');

            if ($campId > 0) {
                if ($sendNow) {
                    $resp = $nl->scheduleCampaign($campId);
                } elseif ($scheduleAt !== '') {
                    $ts = strtotime($scheduleAt);
                    $resp = $nl->scheduleCampaign($campId, $ts ?: null);
                } else {
                    flash('error', 'Choisissez un mode d\'envoi.');
                    header('Location: ?page=newsletter&tab=campaigns');
                    exit;
                }

                if (($resp['result'] ?? '') === 'success' || ($resp['result'] ?? '') === 'asynchronous') {
                    flash('success', $sendNow ? 'Campagne envoyée !' : 'Campagne programmée.');
                } else {
                    flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                }
            }
            header('Location: ?page=newsletter&tab=campaigns');
            exit;
        }

        // CANCEL
        if ($action === 'cancel') {
            $campId = (int) ($_POST['campaign_id'] ?? 0);
            if ($campId > 0) {
                $resp = $nl->cancelCampaign($campId);
                if (($resp['result'] ?? '') === 'success') {
                    flash('success', 'Campagne annulée.');
                } else {
                    flash('error', 'Erreur : ' . InfomaniakNewsletter::error($resp));
                }
            }
            header('Location: ?page=newsletter&tab=campaigns');
            exit;
        }
    }

    // --- VIEW: Create campaign form ---
    if ($action === 'create'):
        $groupsResp = $nl->getGroups();
        $allGroups  = InfomaniakNewsletter::data($groupsResp) ?? [];
    ?>
    <div class="page-header" style="margin-top:16px">
        <h2 style="font-size:18px; font-weight:600">Nouvelle campagne</h2>
        <a href="?page=newsletter&tab=campaigns" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=newsletter&tab=campaigns&action=create" class="admin-form">
        <?= csrfField() ?>

        <div class="form-group">
            <label for="subject">Objet <span class="required">*</span></label>
            <input type="text" id="subject" name="subject" required>
        </div>

        <div class="form-group">
            <label for="preheader">Preheader</label>
            <input type="text" id="preheader" name="preheader" placeholder="Texte d'aperçu dans la boîte de réception">
            <small class="form-hint">Optionnel. Visible dans l'aperçu de l'email dans les clients mail.</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="from_name">Nom expéditeur</label>
                <input type="text" id="from_name" name="from_name" value="<?= e(NEWSLETTER_FROM_NAME) ?>">
            </div>
            <div class="form-group">
                <label for="from_email">Email expéditeur</label>
                <input type="email" id="from_email" name="from_email" value="<?= e(NEWSLETTER_FROM_EMAIL) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Destinataires</label>
            <div style="margin-bottom:8px">
                <label style="font-weight:400; display:inline-flex; align-items:center; gap:6px; margin-right:16px">
                    <input type="radio" name="recipient_type" value="all" checked onchange="toggleRecipientGroups()"> Tous les abonnés
                </label>
                <label style="font-weight:400; display:inline-flex; align-items:center; gap:6px">
                    <input type="radio" name="recipient_type" value="groups" onchange="toggleRecipientGroups()"> Groupes spécifiques
                </label>
            </div>
            <div id="recipient-groups" style="display:none" class="nl-checkbox-grid">
                <?php foreach ($allGroups as $g): ?>
                <label class="nl-checkbox-label">
                    <input type="checkbox" name="recipient_groups[]" value="<?= (int) $g['id'] ?>">
                    <?= e($g['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="content_html">Contenu HTML</label>
            <textarea id="content_html" name="content_html" rows="15"></textarea>
            <small class="form-hint">Laissez vide pour éditer le contenu directement dans l'éditeur Infomaniak.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer la campagne</button>
            <a href="?page=newsletter&tab=campaigns" class="btn btn-secondary">Annuler</a>
        </div>
    </form>

    <script>
    initTinyMCE('#content_html', 400);
    function toggleRecipientGroups() {
        var el = document.getElementById('recipient-groups');
        var radio = document.querySelector('input[name="recipient_type"]:checked');
        el.style.display = (radio && radio.value === 'groups') ? 'block' : 'none';
    }
    </script>
    <?php
        return;
    endif;

    // --- VIEW: Edit campaign form ---
    if ($action === 'edit' && $id):
        $campResp = $nl->getCampaign($id);
        $camp     = InfomaniakNewsletter::data($campResp);
        if (!$camp) {
            flash('error', 'Campagne introuvable.');
            header('Location: ?page=newsletter&tab=campaigns');
            exit;
        }
        $groupsResp = $nl->getGroups();
        $allGroups  = InfomaniakNewsletter::data($groupsResp) ?? [];
    ?>
    <div class="page-header" style="margin-top:16px">
        <h2 style="font-size:18px; font-weight:600">Modifier la campagne</h2>
        <a href="?page=newsletter&tab=campaigns" class="btn btn-secondary">Retour</a>
    </div>

    <form method="post" action="?page=newsletter&tab=campaigns&action=edit" class="admin-form">
        <?= csrfField() ?>
        <input type="hidden" name="campaign_id" value="<?= (int) $camp['id'] ?>">

        <div class="form-group">
            <label for="subject">Objet <span class="required">*</span></label>
            <input type="text" id="subject" name="subject" value="<?= e($camp['subject'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="preheader">Preheader</label>
            <input type="text" id="preheader" name="preheader" value="<?= e($camp['preheader'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="from_name">Nom expéditeur</label>
                <input type="text" id="from_name" name="from_name" value="<?= e($camp['email_from_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="from_email">Email expéditeur</label>
                <input type="email" id="from_email" name="from_email" value="<?= e($camp['email_from_addr'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Destinataires</label>
            <div style="margin-bottom:8px">
                <label style="font-weight:400; display:inline-flex; align-items:center; gap:6px; margin-right:16px">
                    <input type="radio" name="recipient_type" value="all" checked onchange="toggleRecipientGroups()"> Tous les abonnés
                </label>
                <label style="font-weight:400; display:inline-flex; align-items:center; gap:6px">
                    <input type="radio" name="recipient_type" value="groups" onchange="toggleRecipientGroups()"> Groupes spécifiques
                </label>
            </div>
            <div id="recipient-groups" style="display:none" class="nl-checkbox-grid">
                <?php foreach ($allGroups as $g): ?>
                <label class="nl-checkbox-label">
                    <input type="checkbox" name="recipient_groups[]" value="<?= (int) $g['id'] ?>">
                    <?= e($g['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="content_html">Contenu HTML</label>
            <textarea id="content_html" name="content_html" rows="15"><?= e($camp['content_html'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Statut actuel</label>
            <p style="padding:4px 0"><?= nlStatusBadge($camp['status'] ?? '') ?></p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="?page=newsletter&tab=campaigns" class="btn btn-secondary">Annuler</a>
        </div>
    </form>

    <script>
    initTinyMCE('#content_html', 400);
    function toggleRecipientGroups() {
        var el = document.getElementById('recipient-groups');
        var radio = document.querySelector('input[name="recipient_type"]:checked');
        el.style.display = (radio && radio.value === 'groups') ? 'block' : 'none';
    }
    </script>
    <?php
        return;
    endif;

    // --- VIEW: Campaign list ---
    $campsResp = $nl->getCampaigns();
    $campaigns = InfomaniakNewsletter::data($campsResp) ?? [];
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin:16px 0">
    <div style="color:#666; font-size:13px"><?= count($campaigns) ?> campagne(s)</div>
    <a href="?page=newsletter&tab=campaigns&action=create" class="btn btn-primary">Nouvelle campagne</a>
</div>

<?php if (empty($campaigns)): ?>
    <p class="admin-empty">Aucune campagne.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Objet</th>
                <th>Statut</th>
                <th>Expéditeur</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($campaigns as $c): ?>
            <tr>
                <td><strong><?= e($c['subject'] ?? '—') ?></strong></td>
                <td><?= nlStatusBadge($c['status'] ?? '') ?></td>
                <td><?= e($c['email_from_name'] ?? '') ?><br><small style="color:#999"><?= e($c['email_from_addr'] ?? '') ?></small></td>
                <td class="cell-readonly">
                    <?php if (!empty($c['started_at'])): ?>
                        <?= date('d.m.Y H:i', $c['started_at']) ?>
                    <?php else: ?>
                        <span style="color:#999">Non programmée</span>
                    <?php endif; ?>
                </td>
                <td class="cell-actions" style="flex-wrap:wrap">
                    <?php $campStatus = $c['status'] ?? ''; ?>

                    <?php if ($campStatus === 'draft'): ?>
                        <a href="?page=newsletter&tab=campaigns&action=edit&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>

                        <!-- Send test -->
                        <form method="post" action="?page=newsletter&tab=campaigns&action=test" class="form-inline nl-test-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                            <input type="email" name="test_email" placeholder="email test" class="nl-test-input" required>
                            <button type="submit" class="btn btn-sm btn-outline">Test</button>
                        </form>

                        <!-- Schedule / Send now -->
                        <form method="post" action="?page=newsletter&tab=campaigns&action=schedule" class="form-inline"
                              onsubmit="return confirm('Envoyer cette campagne maintenant à tous les destinataires ?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                            <input type="hidden" name="send_now" value="1">
                            <button type="submit" class="btn btn-sm btn-success">Envoyer</button>
                        </form>

                    <?php elseif ($campStatus === 'sent'): ?>
                        <a href="?page=newsletter&tab=stats&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-secondary">Voir stats</a>
                    <?php endif; ?>

                    <!-- Duplicate -->
                    <form method="post" action="?page=newsletter&tab=campaigns&action=duplicate" class="form-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-secondary">Dupliquer</button>
                    </form>

                    <?php if (canDelete() && $campStatus !== 'sending'): ?>
                    <form method="post" action="?page=newsletter&tab=campaigns&action=delete" class="form-inline"
                          onsubmit="return confirm('Supprimer cette campagne ?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="campaign_id" value="<?= (int) $c['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php

// =========================================================================
//  TAB: STATS
// =========================================================================

elseif ($tab === 'stats'):

    // If a campaign ID is given, show that campaign's stats
    if ($id):
        $campResp = $nl->getCampaign($id);
        $camp     = InfomaniakNewsletter::data($campResp);
        $tracking = InfomaniakNewsletter::data($nl->campaignTracking($id)) ?? [];
        $links    = InfomaniakNewsletter::data($nl->campaignLinks($id)) ?? [];

        if (!$camp) {
            flash('error', 'Campagne introuvable.');
            header('Location: ?page=newsletter&tab=stats');
            exit;
        }
    ?>

    <div class="page-header" style="margin-top:16px">
        <h2 style="font-size:18px; font-weight:600">Statistiques : <?= e($camp['subject'] ?? '') ?></h2>
        <a href="?page=newsletter&tab=stats" class="btn btn-secondary">Retour</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= number_format($tracking['sent'] ?? 0, 0, ',', '\'') ?></div>
            <div class="stat-label">Envoyés</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($tracking['opened'] ?? 0, 0, ',', '\'') ?></div>
            <div class="stat-label">Ouvertures</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($tracking['clicked'] ?? 0, 0, ',', '\'') ?></div>
            <div class="stat-label">Clics</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($tracking['bounced'] ?? 0, 0, ',', '\'') ?></div>
            <div class="stat-label">Bounces</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($tracking['unsubscribed'] ?? 0, 0, ',', '\'') ?></div>
            <div class="stat-label">Désinscriptions</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($tracking['junk'] ?? 0, 0, ',', '\'') ?></div>
            <div class="stat-label">Spam</div>
        </div>
    </div>

    <?php
        // Compute rates
        $sent = $tracking['sent'] ?? 0;
        if ($sent > 0):
            $openRate  = round(($tracking['opened'] ?? 0) / $sent * 100, 1);
            $clickRate = round(($tracking['clicked'] ?? 0) / $sent * 100, 1);
            $bounceRate = round(($tracking['bounced'] ?? 0) / $sent * 100, 1);
    ?>
    <div class="admin-info">
        <strong>Taux d'ouverture :</strong> <?= $openRate ?>% &nbsp;|&nbsp;
        <strong>Taux de clic :</strong> <?= $clickRate ?>% &nbsp;|&nbsp;
        <strong>Taux de bounce :</strong> <?= $bounceRate ?>%
    </div>
    <?php endif; ?>

    <?php if (!empty($links)): ?>
    <h3 style="font-size:14px; font-weight:600; margin:24px 0 12px">Liens cliqués</h3>
    <div class="table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Clics</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($links as $link): ?>
                <tr>
                    <td style="word-break:break-all"><a href="<?= e($link['url'] ?? '') ?>" target="_blank"><?= e($link['url'] ?? '') ?></a></td>
                    <td class="cell-readonly"><?= (int) ($link['total'] ?? $link['clicks'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php
        return;
    endif;

    // --- VIEW: Campaign stats overview (select a campaign) ---
    $campsResp = $nl->getCampaigns();
    $campaigns = InfomaniakNewsletter::data($campsResp) ?? [];
    // Filter only sent campaigns
    $sentCampaigns = array_filter($campaigns, fn($c) => ($c['status'] ?? '') === 'sent');
?>

<h2 style="font-size:16px; font-weight:600; margin:16px 0">Sélectionnez une campagne envoyée</h2>

<?php if (empty($sentCampaigns)): ?>
    <p class="admin-empty">Aucune campagne envoyée.</p>
<?php else: ?>
<div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Objet</th>
                <th>Expéditeur</th>
                <th>Date d'envoi</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($sentCampaigns as $c): ?>
            <tr>
                <td><strong><?= e($c['subject'] ?? '') ?></strong></td>
                <td><?= e($c['email_from_name'] ?? '') ?></td>
                <td class="cell-readonly"><?= $c['started_at'] ? date('d.m.Y H:i', $c['started_at']) : '—' ?></td>
                <td>
                    <a href="?page=newsletter&tab=stats&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-primary">Voir les statistiques</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php endif; // end tabs ?>

<?php
// =========================================================================
//  Helper: status badge
// =========================================================================

function nlStatusBadge(string $status): string
{
    $map = [
        'active'       => ['Actif', 'nl-badge--success'],
        'sent'         => ['Envoyée', 'nl-badge--success'],
        'draft'        => ['Brouillon', 'nl-badge--warning'],
        'sending'      => ['En cours', 'nl-badge--info'],
        'scheduled'    => ['Programmée', 'nl-badge--info'],
        'bounced'      => ['Bounced', 'nl-badge--danger'],
        'junk'         => ['Spam', 'nl-badge--danger'],
        'unsubscribed' => ['Désinscrit', 'nl-badge--muted'],
        'unconfirmed'  => ['Non confirmé', 'nl-badge--warning'],
    ];

    $info = $map[$status] ?? [ucfirst($status), 'nl-badge--muted'];
    return '<span class="nl-badge ' . $info[1] . '">' . htmlspecialchars($info[0], ENT_QUOTES, 'UTF-8') . '</span>';
}
