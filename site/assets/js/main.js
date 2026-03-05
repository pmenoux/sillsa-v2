/**
 * SILL SA — main.js
 * Vanilla JavaScript, zero dependencies.
 * Loaded with `defer` from footer.php.
 */
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  /* =================================================================
     1. Mobile nav toggle
     ================================================================= */
  (function initMobileNav() {
    var toggle = document.querySelector('.nav-toggle');
    var navList = document.querySelector('.nav-list');
    if (!toggle || !navList) return;

    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var expanded = navList.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(expanded));
    });

    // Close nav when clicking outside
    document.addEventListener('click', function (e) {
      if (!navList.classList.contains('is-open')) return;
      if (navList.contains(e.target) || toggle.contains(e.target)) return;
      navList.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    });
  })();

  /* =================================================================
     2. Scroll reveal (IntersectionObserver)
     ================================================================= */
  (function initScrollReveal() {
    var els = document.querySelectorAll('.reveal');
    if (!els.length) return;

    if (!('IntersectionObserver' in window)) {
      // Fallback: show everything immediately
      els.forEach(function (el) { el.classList.add('revealed'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          obs.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.15,
      rootMargin: '0px 0px -50px 0px'
    });

    els.forEach(function (el) { observer.observe(el); });
  })();

  /* =================================================================
     3. KPI counter animation
     ================================================================= */
  (function initCounters() {
    var counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    // Ease-out cubic: f(t) = 1 - (1-t)^3
    function easeOutCubic(t) {
      return 1 - Math.pow(1 - t, 3);
    }

    function animateCounter(el) {
      var target = parseFloat(el.getAttribute('data-count'));
      var decimals = parseInt(el.getAttribute('data-decimals'), 10) || 0;
      var duration = 1500;           // ms
      var startTime = null;

      function step(timestamp) {
        if (!startTime) startTime = timestamp;
        var elapsed = timestamp - startTime;
        var progress = Math.min(elapsed / duration, 1);
        var value = target * easeOutCubic(progress);
        el.textContent = value.toFixed(decimals);
        if (progress < 1) {
          requestAnimationFrame(step);
        } else {
          el.textContent = target.toFixed(decimals);
        }
      }

      requestAnimationFrame(step);
    }

    if (!('IntersectionObserver' in window)) {
      counters.forEach(animateCounter);
      return;
    }

    var observer = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          obs.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.5
    });

    counters.forEach(function (el) { observer.observe(el); });
  })();

  /* =================================================================
     4. SVG carte interaction (portefeuille page)
     ================================================================= */
  (function initMapInteraction() {
    var mapPoints = document.querySelectorAll('.map-point');
    if (!mapPoints.length) return;

    var tooltip = document.querySelector('.map-tooltip');
    var infoPanel = document.querySelector('.map-info-panel');
    var panelCloseBtn = infoPanel
      ? infoPanel.querySelector('.panel-close')
      : null;

    // Tooltip show/hide on hover
    var wrapper = document.querySelector('.carte-wrapper');
    mapPoints.forEach(function (point) {
      point.addEventListener('mouseenter', function () {
        if (!tooltip) return;
        var label = point.getAttribute('data-label') || '';
        if (label && tooltip.querySelector('.tooltip-text')) {
          tooltip.querySelector('.tooltip-text').textContent = label;
        }
        // Position tooltip above the point
        var ptRect = point.getBoundingClientRect();
        var wrapRect = wrapper.getBoundingClientRect();
        tooltip.style.left = (ptRect.left - wrapRect.left + ptRect.width / 2) + 'px';
        tooltip.style.top = (ptRect.top - wrapRect.top - 8) + 'px';
        tooltip.style.transform = 'translate(-50%, -100%)';
        tooltip.classList.add('visible');
      });

      point.addEventListener('mouseleave', function () {
        if (!tooltip) return;
        tooltip.classList.remove('visible');
      });

      // Click: fetch building data
      point.addEventListener('click', function (e) {
        var slug = point.getAttribute('data-slug');
        if (!slug || !infoPanel) return;
        e.preventDefault();

        fetch('/api/immeuble/' + encodeURIComponent(slug))
          .then(function (response) {
            if (!response.ok) throw new Error(response.status);
            return response.text();
          })
          .then(function (html) {
            infoPanel.innerHTML = html;
            infoPanel.classList.add('open');

            // Re-bind close button inside the newly loaded content
            var closeBtn = infoPanel.querySelector('.panel-close');
            if (closeBtn) {
              closeBtn.addEventListener('click', function () {
                infoPanel.classList.remove('open');
              });
            }
          })
          .catch(function () {
            // Fallback: navigate to the building page
            window.location.href = '/portefeuille/' + encodeURIComponent(slug);
          });
      });
    });

    // Close panel via original close button (if it exists in static markup)
    if (panelCloseBtn) {
      panelCloseBtn.addEventListener('click', function () {
        infoPanel.classList.remove('open');
      });
    }
  })();

  /* =================================================================
     5. Dropdown nav (desktop hover, mobile click)
     ================================================================= */
  (function initDropdowns() {
    var dropdownItems = document.querySelectorAll('.has-dropdown');
    if (!dropdownItems.length) return;

    function isMobile() {
      return window.innerWidth < 768;
    }

    dropdownItems.forEach(function (item) {
      var link = item.querySelector('a');
      if (!link) return;

      link.addEventListener('click', function (e) {
        if (!isMobile()) return; // desktop uses CSS :hover
        e.preventDefault();
        // Close sibling dropdowns
        dropdownItems.forEach(function (sibling) {
          if (sibling !== item) {
            sibling.classList.remove('dropdown-open');
          }
        });
        item.classList.toggle('dropdown-open');
      });

      // Accessibility: keyboard support for desktop
      item.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          item.classList.remove('dropdown-open');
          if (link) link.focus();
        }
      });
    });

    // Close dropdowns when clicking outside on mobile
    document.addEventListener('click', function (e) {
      if (!isMobile()) return;
      dropdownItems.forEach(function (item) {
        if (!item.contains(e.target)) {
          item.classList.remove('dropdown-open');
        }
      });
    });
  })();

  /* =================================================================
     6. Timeline category filter
     ----------------------------------------------------------------
     Handled inline in the PHP template (chronologie page).
     NOT included here.
     ================================================================= */

  /* =================================================================
     7. Smooth scroll for anchor links
     ================================================================= */
  (function initSmoothScroll() {
    var HEADER_OFFSET = 80; // px, matches fixed header height

    document.addEventListener('click', function (e) {
      var link = e.target.closest('a[href^="#"]');
      if (!link) return;

      var hash = link.getAttribute('href');
      if (!hash || hash === '#') return;

      var target = document.querySelector(hash);
      if (!target) return;

      e.preventDefault();

      var top = target.getBoundingClientRect().top
              + window.pageYOffset
              - HEADER_OFFSET;

      window.scrollTo({
        top: top,
        behavior: 'smooth'
      });

      // Update URL hash without jumping
      if (history.pushState) {
        history.pushState(null, '', hash);
      }
    });
  })();

});
