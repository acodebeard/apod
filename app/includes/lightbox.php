<?php
$slug        = (string)($entry['slug'] ?? '');
$date        = (string)($entry['date'] ?? '');
$basename    = "apod-{$date}-full";
$titleText   = !empty($entry['title']) ? (string)$entry['title'] : 'Image';
$escapedTitle = apod_h($titleText);

// For your lightbox’s full‐size URL:
$escapedFull = apod_h($entry['url_full'] ?? '');

// Build a proper srcset from your url_main array
$srcsetParts = [];
$widths = [1200, 980, 640, 440];
foreach ($widths as $w) {
  // url_main was generated in your JSON rebuild
  $url = $entry['url_main'][$w] ?? ($entry['url_thumb'] ?? '');
  if ($url) {
    $srcsetParts[] = apod_h((string)$url) . " {$w}w";
  }
}
$srcset = implode(",\n      ", $srcsetParts);

// Pick a sensible <img> src—here I’m defaulting to the 640px version
$imgSrc = apod_h($entry['url_main'][640] ?? ($entry['url_thumb'] ?? ''));
?>

<div class="apod-media">
  <a
    href="#!"
    class="lightbox-trigger"
    data-full="<?= $escapedFull ?>"
    data-alt="<?= $escapedTitle ?>"
    aria-label="View full-size image of <?= $escapedTitle ?>">
    <picture>
      <source
        srcset="<?= $srcset ?>"
        type="image/webp"
        sizes="(min-width: 1200px) 1200px, 100vw">
      <img
        src="<?= $imgSrc ?>"
        alt="<?= $escapedTitle ?>"
        width="1200"
        height="675"
        decoding="async"
        aria-describedby="explanation-<?= apod_h($slug) ?>"
        style="width:100%;height:auto;max-width:1200px;">
    </picture>
  </a>
</div>

<!-- LIGHTBOX MARKUP -->
<div id="lightbox" class="lightbox" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="lightbox-backdrop"></div>
  <div class="lightbox-content" role="document">
    <button class="lightbox-close" aria-label="Close image">×</button>
    <img id="lightbox-image" src="" alt="">
  </div>
</div>


<noscript>
  <p class="visually-hidden">JavaScript is disabled. Displaying standard image.</p>
  <img
    src="/apod/images/main/980/<?= $basename ?>.webp"
    alt="<?= apod_h($titleText ?: 'Astronomy Picture of the Day') ?>"
    width="960"
    height="540">
</noscript>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    (function() {
      const lightbox = document.getElementById('lightbox');
      const imgEl = document.getElementById('lightbox-image');
      const closeBtn = lightbox.querySelector('.lightbox-close');
      const backdrop = lightbox.querySelector('.lightbox-backdrop');
      let lastFocused;

      // open handler
      function openLightbox(e) {
        e.preventDefault();
        lastFocused = document.activeElement;

        const trigger = e.currentTarget;
        imgEl.src = trigger.dataset.full;
        imgEl.alt = trigger.dataset.alt || '';

        lightbox.classList.add('open');
        lightbox.removeAttribute('aria-hidden');
        closeBtn.focus();
        document.addEventListener('keydown', onKeyDown);
        document.body.classList.add('no-scroll');
      }

      // close handler
      function closeLightbox() {
        lightbox.classList.remove('open');
        lightbox.setAttribute('aria-hidden', 'true');
        imgEl.src = '';
        lastFocused && lastFocused.focus();
        document.removeEventListener('keydown', onKeyDown);
        document.body.classList.remove('no-scroll');
      }

      // handle Escape, Tab trapping
      function onKeyDown(e) {
        if (e.key === 'Escape') {
          return closeLightbox();
        }
        if (e.key === 'Tab') {
          // simple trap between closeBtn and image
          const focusable = [closeBtn];
          const first = focusable[0];
          const last = focusable[focusable.length - 1];
          if (e.shiftKey) { // backwards
            if (document.activeElement === first) {
              e.preventDefault();
              last.focus();
            }
          } else { // forwards
            if (document.activeElement === last) {
              e.preventDefault();
              first.focus();
            }
          }
        }
      }

      // wire it all up
      document.querySelectorAll('.lightbox-trigger')
        .forEach(el => el.addEventListener('click', openLightbox));
      closeBtn.addEventListener('click', closeLightbox);
      backdrop.addEventListener('click', closeLightbox);
    })();
  })
</script>
