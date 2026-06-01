<?php
$randomUrl = is_string($randomUrl ?? null) ? $randomUrl : APOD_BASE_PATH . '/random';
?>
<div class="sentinel" aria-hidden="true" role="presentation"></div>
<a href="#main" class="skip-link visually-hidden focusable">Skip to main content</a>
<header class="site-header">

  <div class="header-block flex flex-wrap flex-middle width-1-1">

    <h1 class="site-title flex flex-1">
      <span class="site-mark flex-none" aria-hidden="true">APOD</span>
      <a href="/apod/"><span class="site-title-long">Astronomy Picture of the Day</span><span class="site-title-short">APOD</span></a>
    </h1>

    <div class="header-button hidden-at-medium">
      <button
        type="button"
        aria-expanded="false"
        aria-label="Open Main Navigation"
        aria-controls="site-nav">Main Navigation
      </button>
    </div>

    <div class="header-right" aria-hidden="true">
      <nav class="site-nav" aria-label="Main navigation">
        <ul>
          <li><a href="/apod">Archive</a></li>
          <li><a href="<?= htmlspecialchars($randomUrl) ?>">Random</a></li>
          <li><a href="/apod/about">About</a></li>
        </ul>
      </nav>
    </div>
    <div class="nav-overlay hidden-at-medium" hidden></div>
  </div>
</header>

<script>
  const sentinel = document.querySelector('.sentinel');
  const sticky = document.querySelector('.site-header');

  const observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.boundingClientRect.top < 0 && !entry.isIntersecting) {
        // Sticky is now "stuck"
        sticky.classList.add('is-stuck');
      } else {
        // Sticky is not stuck
        sticky.classList.remove('is-stuck');
      }
    }, {
      threshold: [0],
      rootMargin: '0px 0px 0px 0px'
    }
  );

  observer.observe(sentinel);

  const navToggleButton = document.querySelector('.header-button button');
  const navMenu = document.querySelector('.header-right');
  const closeNavButton = document.querySelector('.close-nav');

  function updateAriaHidden() {
    if (window.innerWidth < 640) {
      navMenu.setAttribute('aria-hidden', 'true');
      navToggleButton.setAttribute('aria-expanded', 'false');
    } else {
      navMenu.removeAttribute('aria-hidden');
      navToggleButton.removeAttribute('aria-expanded');
    }
  }

  // Run on load
  window.addEventListener('DOMContentLoaded', updateAriaHidden);
  // Update if resized across the threshold
  window.addEventListener('resize', () => {
    clearTimeout(window._resizeTimer);
    window._resizeTimer = setTimeout(updateAriaHidden, 150);
  });

  function openNav() {
    navMenu.classList.add('open');
    navToggleButton.setAttribute('aria-expanded', 'true');
    navMenu.setAttribute('aria-hidden', 'false');
  }

  function closeNav() {
    navMenu.classList.remove('open');
    navToggleButton.setAttribute('aria-expanded', 'false');
    navMenu.setAttribute('aria-hidden', 'true');
  }

  // Toggle open on hamburger click
  navToggleButton?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (navMenu.classList.contains('open')) {
      closeNav();
    } else {
      openNav();
    }
  });

  // Close on close button click
  closeNavButton?.addEventListener('click', (e) => {
    e.stopPropagation();
    closeNav();
  });

  // Close if clicking outside the nav
  document.addEventListener('click', (e) => {
    if (
      navMenu.classList.contains('open') &&
      !navMenu.contains(e.target) &&
      !navToggleButton.contains(e.target)
    ) {
      closeNav();
    }
  });

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navMenu.classList.contains('open')) {
      closeNav();
    }
  });

  // Auto-close nav if window is resized above mobile threshold
  let resizeTimeout;

  function handleResize() {
    if (window.innerWidth >= 640) {
      closeNav();
      navToggleButton.removeAttribute('aria-expanded');
      navMenu.removeAttribute('aria-hidden');
    } else {
      navMenu.setAttribute('aria-hidden', 'true');
    }
  }
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(handleResize, 150);
    clearTimeout(window._resizeTimer);
    window._resizeTimer = setTimeout(updateAriaHidden, 150);
  });
  window.addEventListener('DOMContentLoaded', handleResize);
</script>
