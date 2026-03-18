<?php
// templates/bonnes-pratiques.php — Guide des bonnes pratiques énergie
// Swiss Design: grid of thematic cards with inline SVG icons
?>

<section class="page-header">
  <div class="container">
    <nav class="breadcrumb"><a href="<?= SITE_URL ?>/">Accueil</a> / <a href="<?= SITE_URL ?>/location">Location</a> / Bonnes pratiques</nav>
    <h1>Bonnes pratiques</h1>
    <p class="page-chapeau">Économiser l'énergie, améliorer le confort au quotidien.</p>
  </div>
</section>

<section class="section-bp">
  <div class="container">

    <!-- ═══ INTRO ═══ -->
    <div class="bp-intro reveal">
      <div class="bp-intro-rule"></div>
      <p>Nos immeubles sont conçus pour offrir un haut niveau de performance énergétique. Quelques gestes au quotidien permettent d'en tirer le meilleur parti, de réduire vos charges et de préserver le confort de chacun.</p>
    </div>

    <!-- ═══ THEMATIC CARDS GRID ═══ -->
    <div class="bp-grid">

      <!-- 1. AÉRATION -->
      <article class="bp-card reveal">
        <div class="bp-card-header">
          <div class="bp-icon">
            <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="6" y="8" width="36" height="32" rx="1"/>
              <line x1="24" y1="8" x2="24" y2="40"/>
              <line x1="6" y1="24" x2="42" y2="24"/>
              <path d="M15 16 L15 12" opacity="0.5"/>
              <path d="M33 16 L33 12" opacity="0.5"/>
              <!-- Air flow arrows -->
              <path d="M10 18 C14 15, 18 21, 22 18" stroke-width="1" opacity="0.4"/>
              <path d="M26 30 C30 27, 34 33, 38 30" stroke-width="1" opacity="0.4"/>
            </svg>
          </div>
          <h2>Aération</h2>
        </div>
        <div class="bp-card-body">
          <p class="bp-subtitle">Ventilation double-flux</p>
          <ul>
            <li>Votre logement est équipé d'une ventilation mécanique qui assure un renouvellement d'air suffisant en continu.</li>
            <li>Pour aérer en complément, ouvrez les fenêtres <strong>en grand</strong>, 3 fois par jour pendant 5 à 10 minutes, idéalement en courant d'air.</li>
            <li>Ne laissez jamais les fenêtres en imposte : cette position génère des pertes de chaleur sans améliorer l'aération.</li>
            <li>Si l'air vous paraît trop sec en hiver, réduisez légèrement la température de la pièce concernée.</li>
            <li>En cas de bruit ou de courants d'air liés à la ventilation, contactez la gérance. N'obstruez pas les bouches de reprise.</li>
          </ul>
        </div>
      </article>

      <!-- 2. CHAUFFAGE -->
      <article class="bp-card reveal">
        <div class="bp-card-header">
          <div class="bp-icon">
            <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <!-- Radiator -->
              <rect x="10" y="14" width="28" height="24" rx="2"/>
              <line x1="16" y1="18" x2="16" y2="34"/>
              <line x1="22" y1="18" x2="22" y2="34"/>
              <line x1="28" y1="18" x2="28" y2="34"/>
              <line x1="34" y1="18" x2="34" y2="34"/>
              <!-- Heat waves -->
              <path d="M18 10 C19 7, 21 7, 20 10" stroke-width="1" opacity="0.4"/>
              <path d="M24 8 C25 5, 27 5, 26 8" stroke-width="1" opacity="0.4"/>
              <path d="M30 10 C31 7, 33 7, 32 10" stroke-width="1" opacity="0.4"/>
              <!-- Thermostat -->
              <circle cx="40" cy="26" r="4" stroke-width="1"/>
              <line x1="40" y1="24" x2="40" y2="26"/>
            </svg>
          </div>
          <h2>Chauffage</h2>
        </div>
        <div class="bp-card-body">
          <p class="bp-subtitle">Température idéale : 20 °C</p>
          <ul>
            <li>Réglez la température à <strong>20 °C maximum</strong> dans les pièces de vie. 18 °C suffisent dans les chambres.</li>
            <li>Évitez de disposer des tapis épais sur une grande surface : cela entrave la diffusion de chaleur par le sol.</li>
            <li>S'il fait trop froid, remontez d'abord les stores pour bénéficier des apports solaires gratuits.</li>
            <li>Montez le thermostat d'un seul cran à la fois, puis attendez au moins 12 heures avant de réajuster.</li>
            <li>Fermez les stores la nuit : cela réduit sensiblement les déperditions thermiques.</li>
          </ul>
        </div>
      </article>

      <!-- 3. CONFORT ESTIVAL -->
      <article class="bp-card reveal">
        <div class="bp-card-header">
          <div class="bp-icon">
            <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <!-- Sun -->
              <circle cx="24" cy="20" r="8"/>
              <line x1="24" y1="6" x2="24" y2="9"/>
              <line x1="24" y1="31" x2="24" y2="34"/>
              <line x1="12" y1="20" x2="15" y2="20"/>
              <line x1="33" y1="20" x2="36" y2="20"/>
              <line x1="15.5" y1="11.5" x2="17.6" y2="13.6"/>
              <line x1="30.4" y1="26.4" x2="32.5" y2="28.5"/>
              <line x1="32.5" y1="11.5" x2="30.4" y2="13.6"/>
              <line x1="17.6" y1="26.4" x2="15.5" y2="28.5"/>
              <!-- Blind -->
              <line x1="8" y1="38" x2="40" y2="38" stroke-width="2"/>
              <line x1="8" y1="42" x2="40" y2="42" stroke-width="2" opacity="0.5"/>
            </svg>
          </div>
          <h2>Confort estival</h2>
        </div>
        <div class="bp-card-body">
          <p class="bp-subtitle">Fraîcheur naturelle sans climatisation</p>
          <ul>
            <li>En journée, <strong>baissez les stores</strong> et gardez les fenêtres fermées pour bloquer la chaleur extérieure.</li>
            <li>Le matin et le soir, ouvrez fenêtres et stores pour créer des mouvements d'air frais.</li>
            <li>La nuit, ouvrez toutes les fenêtres et les portes intérieures pour faire circuler l'air.</li>
            <li>Un ventilateur sur pied ou de table crée une sensation de fraîcheur efficace et économique.</li>
            <li>Anticipez la descente des protections solaires des terrasses pour éviter la surchauffe des surfaces extérieures.</li>
            <li>Éteignez les appareils en veille : ils dégagent de la chaleur inutile.</li>
          </ul>
        </div>
      </article>

      <!-- 4. EAU CHAUDE -->
      <article class="bp-card reveal">
        <div class="bp-card-header">
          <div class="bp-icon">
            <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <!-- Droplet -->
              <path d="M24 6 C24 6, 12 22, 12 30 C12 36.6 17.4 42 24 42 C30.6 42 36 36.6 36 30 C36 22, 24 6, 24 6 Z"/>
              <!-- Wave inside -->
              <path d="M16 32 C19 29, 21 35, 24 32 C27 29, 29 35, 32 32" stroke-width="1" opacity="0.4"/>
            </svg>
          </div>
          <h2>Eau chaude</h2>
        </div>
        <div class="bp-card-body">
          <p class="bp-subtitle">Douches, robinets, économies</p>
          <ul>
            <li>Privilégiez les douches aux bains : elles consomment nettement moins d'eau chaude.</li>
            <li>Remettez le mitigeur sur la position <strong>froid</strong> après utilisation pour éviter de tirer de l'eau chaude inutilement.</li>
            <li>Si vous changez de pommeau, choisissez un modèle de classe énergétique A ou B.</li>
            <li>Des brise-jets sur les robinets les plus utilisés réduisent efficacement la consommation d'eau.</li>
          </ul>
        </div>
      </article>

      <!-- 5. ÉCLAIRAGE -->
      <article class="bp-card reveal">
        <div class="bp-card-header">
          <div class="bp-icon">
            <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <!-- Bulb -->
              <path d="M18 30 C18 30, 14 26, 14 20 C14 14.5 18.5 10 24 10 C29.5 10 34 14.5 34 20 C34 26, 30 30, 30 30"/>
              <line x1="18" y1="34" x2="30" y2="34"/>
              <line x1="20" y1="38" x2="28" y2="38"/>
              <line x1="18" y1="30" x2="30" y2="30"/>
              <!-- Light rays -->
              <line x1="24" y1="2" x2="24" y2="5" opacity="0.4"/>
              <line x1="38" y1="20" x2="41" y2="20" opacity="0.4"/>
              <line x1="7" y1="20" x2="10" y2="20" opacity="0.4"/>
              <line x1="35" y1="9" x2="37" y2="7" opacity="0.4"/>
              <line x1="11" y1="7" x2="13" y2="9" opacity="0.4"/>
            </svg>
          </div>
          <h2>Éclairage</h2>
        </div>
        <div class="bp-card-body">
          <p class="bp-subtitle">LED et bons réflexes</p>
          <ul>
            <li>Remplacez vos ampoules par des <strong>LED</strong> lors de chaque changement.</li>
            <li>Éteignez la lumière en quittant une pièce.</li>
            <li>Nettoyez régulièrement vos luminaires pour maintenir leur efficacité lumineuse.</li>
          </ul>
        </div>
      </article>

      <!-- 6. APPAREILS ÉLECTRIQUES -->
      <article class="bp-card reveal">
        <div class="bp-card-header">
          <div class="bp-icon">
            <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <!-- Plug -->
              <rect x="14" y="20" width="20" height="16" rx="2"/>
              <circle cx="21" cy="28" r="2"/>
              <circle cx="33" cy="28" r="2"/>
              <line x1="21" y1="20" x2="21" y2="14"/>
              <line x1="27" y1="20" x2="27" y2="14"/>
              <!-- Power off symbol -->
              <path d="M36 10 C40 13, 42 18, 40 22" stroke-width="1" opacity="0.4"/>
              <line x1="38" y1="6" x2="38" y2="12" stroke-width="1" opacity="0.4"/>
            </svg>
          </div>
          <h2>Appareils électriques</h2>
        </div>
        <div class="bp-card-body">
          <p class="bp-subtitle">Veille et étiquette-énergie</p>
          <ul>
            <li>Ne laissez pas vos appareils en veille : utilisez des multiprises à interrupteur.</li>
            <li>Lors de l'achat d'un nouvel appareil, choisissez les modèles les plus efficients selon l'étiquette-énergie.</li>
            <li>Chauffez l'eau à la bouilloire : c'est plus rapide et économe qu'une casserole.</li>
          </ul>
        </div>
      </article>

    </div><!-- /.bp-grid -->

    <!-- ═══ RESOURCES ═══ -->
    <div class="bp-resources reveal">
      <div class="bp-resources-rule"></div>
      <h2>Pour aller plus loin</h2>
      <div class="bp-resources-grid">
        <a href="https://pubdb.bfe.admin.ch/fr/publication/download/11613" target="_blank" rel="noopener" class="bp-resource-link">
          <span class="bp-resource-type">PDF</span>
          <span class="bp-resource-title">Mieux habiter : trucs et astuces pour un meilleur confort</span>
          <span class="bp-resource-source">SuisseEnergie, 2022</span>
        </a>
        <a href="https://pubdb.bfe.admin.ch/fr/publication/download/7865" target="_blank" rel="noopener" class="bp-resource-link">
          <span class="bp-resource-type">PDF</span>
          <span class="bp-resource-title">Économiser de l'énergie au quotidien</span>
          <span class="bp-resource-source">SuisseEnergie, 2015</span>
        </a>
      </div>
    </div>

  </div>
</section>
