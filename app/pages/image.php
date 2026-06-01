<?php

$apodData = is_array($apodData ?? null) ? $apodData : [];
$slug = $_GET['slug'] ?? '';

$currentIndex = null;
foreach ($apodData as $i => $item) {
  if (($item['slug'] ?? '') === $slug) {
    $entry = $item;
    $currentIndex = $i;
    break;
  }
}

if ($currentIndex === null || empty($entry)) {
  echo '<p>Image not found.</p>';
  return;
}

$title = (string)($entry['title'] ?? 'Untitled');
$date = (string)($entry['date'] ?? '');
$credit = $entry['credit'] ?? $entry['copyright'] ?? 'NASA / APOD';
$explanation = nl2br(apod_h((string)($entry['explanation'] ?? '')));
$total        = count($apodData);

// ‣ PREVIOUS (wraps around to index 0 after the last)
$prevIndex = ($currentIndex + 1) % $total;
$prevItem  = $apodData[$prevIndex];
// guard against missing title
$prevTitle = $prevItem['title'] ?? 'Untitled';
$prevSlug  = $prevItem['slug'] ?? slugify((string)$prevTitle);

// ‣ NEXT (only if we’re not already at 0)
if ($currentIndex > 0) {
  $nextIndex = $currentIndex - 1;
  $nextItem  = $apodData[$nextIndex];
  $nextTitle = $nextItem['title'] ?? 'Untitled';
  $nextSlug  = $nextItem['slug'] ?? slugify((string)$nextTitle);
} else {
  // first image → no real “next” link
  $nextSlug  = null;
  $nextTitle = 'Something Cool';
}


$escapedTitle  = apod_h($title);
$mediaType = apod_media_type($entry);
$fullUrl = $mediaType === 'video' ? apod_video_embed_url($entry) : (string)($entry['url_full'] ?? '');
$escapedFull = apod_h($fullUrl);
$escapedEntrySlug = htmlspecialchars((string)($entry['slug'] ?? ''), ENT_QUOTES, 'UTF-8');

// Helper: slugify function in PHP to match JS logic
function slugify($text)
{
  $text = strtolower($text);
  $text = preg_replace('/[^\w\s-]/', '', $text);
  $text = preg_replace('/\s+/', '-', $text);
  $text = preg_replace('/-+/', '-', $text);
  return $text;
}

if (!$entry) {
  echo "<p>Image not found.</p>";
  return;
}
// ─────────────────────────────────────────────────────────────────────────────
?>

<figure class="apod-article">
  <div class="flex-1 apod-image-block">
    <?= apod_render_media($entry) ?>
  </div>

  <!-- Caption and Explanation -->
  <figcaption class="width-2-5 width-1-1-medium break apod-caption flex flex-column">

    <nav class="image-nav margin-top-small margin-bottom-medium" aria-label="Image navigation">
      <ul class="image-nav-list flex width-1-1 flex-middle list-reset">
        <li class="image-nav-item" role="none">
          <a class="image-nav-item-link image-item-nav-link-previous" aria-label="Previous image: <?= htmlspecialchars($prevTitle, ENT_QUOTES, 'UTF-8') ?>" rel="prev" href="/apod/image/<?= htmlspecialchars($prevSlug, ENT_QUOTES, 'UTF-8') ?>">
            <span class="image-nav-item-link-label">Previous:</span>
            <span class="show-at-medium"><?= htmlspecialchars($prevTitle, ENT_QUOTES, 'UTF-8') ?></span>
          </a>
        </li>
        <li class="image-nav-item" role="none">
          <?php if ($nextSlug): ?>
            <a class="image-nav-item-link image-item-nav-link-next" aria-label="Next Image: <?= htmlspecialchars($nextTitle, ENT_QUOTES, 'UTF-8') ?>" rel="next" href="/apod/image/<?= htmlspecialchars($nextSlug, ENT_QUOTES, 'UTF-8') ?>">
              <span class="image-nav-item-link-label">Next:</span>
              <span class="show-at-medium"><?= htmlspecialchars($nextTitle, ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          <?php else: ?>
            <span class="image-nav-item-link-label">Tomorrow’s Image:</span>
            <span><?= htmlspecialchars($nextTitle, ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </li>
      </ul>
    </nav>

    <header class="apod-caption-header">
      <h2><?= htmlspecialchars($title) ?></h2>
      <time class="apod-date" datetime="<?= htmlspecialchars($date) ?>">
        <?= htmlspecialchars($date) ?>
      </time>
    </header>

    <section id="explanation-<?= $escapedEntrySlug ?>" class="apod-explanation">
      <?= $explanation ?>
    </section>

    <footer class="position-relative flex-1 apod-credit">
      <small>
        <?= $mediaType === 'video' ? 'Video' : 'Image' ?> Credit &amp; Copyright: <?= apod_h((string)$credit) ?>
      </small>

      <div class="image-tools">
        <?php if ($mediaType === 'image' && $escapedFull !== ''): ?>
          <a href="<?= $escapedFull ?>"
            download
            class="button download-button"
            aria-label="Download full-size image">
            Download Full Size
          </a>
        <?php elseif ($mediaType === 'video' && $escapedFull !== ''): ?>
          <a href="<?= $escapedFull ?>"
            target="_blank"
            rel="noopener"
            class="button download-button"
            aria-label="Open video source">
            Open Video
          </a>
        <?php endif; ?>

        <div class="image-share position-relative" style="display: none;" hidden>
          <button
            id="share-btn"
            class="button button-share position-relative download-button"
            type="button"
            aria-controls="share-icons"
            aria-expanded="false">
            Share
          </button>

          <div
            id="share-icons"
            class="image-share-icons"
            role="region"
            aria-label="Share this image">
            <ul class="share-list flex flex-middle list-reset">
              <li><a href="https://twitter.com/intent/tweet?url=PAGE_URL" target="_blank">X</a></li>
              <li><a href="https://www.facebook.com/sharer/sharer.php?u=PAGE_URL" target="_blank">Facebook</a></li>
              <li><a href="https://www.linkedin.com/sharing/share-offsite/?url=PAGE_URL" target="_blank">LinkedIn</a></li>
              <!-- add more links/icons as desired -->
            </ul>
          </div>
        </div>


        <div class="uk-margin-top" hidden>
          <a href="index.php" class="uk-button uk-button-text">← Back to Gallery</a>
        </div>
      </div>
    </footer>
  </figcaption>
  <!-- Optional navigation footer or back link -->

</figure>


<script>
  (function() {
    const btn = document.getElementById('share-btn');
    const panel = document.getElementById('share-icons');
    let openScrollY = null; // “where” we were when we opened

    btn.addEventListener('click', () => {
      const expanded = btn.getAttribute('aria-expanded') === 'true';

      if (!expanded) {
        // ---- Opening ----
        btn.setAttribute('aria-expanded', 'true');
        panel.classList.add('open');
        panel.querySelector('a')?.focus();

        openScrollY = window.scrollY; // capture start
      } else {
        // ---- Closing ----
        btn.setAttribute('aria-expanded', 'false');
        panel.classList.remove('open');
        openScrollY = null;
      }
    });

    // close on outside click
    document.addEventListener('click', (e) => {
      if (panel.classList.contains('open') &&
        !btn.contains(e.target) &&
        !panel.contains(e.target)
      ) {
        btn.setAttribute('aria-expanded', 'false');
        panel.classList.remove('open');
        openScrollY = null;
      }
    });

    // close on scroll > 80px from open position
    document.addEventListener('scroll', () => {
      if (
        openScrollY !== null && // only if we’re open
        Math.abs(window.scrollY - openScrollY) > 80 // moved more than 80px
      ) {
        btn.setAttribute('aria-expanded', 'false');
        panel.classList.remove('open');
        openScrollY = null;
      }
    }, {
      passive: true
    });

    // close on Escape
    panel.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        btn.setAttribute('aria-expanded', 'false');
        panel.classList.remove('open');
        btn.focus();
        openScrollY = null;
      }
    });
  })();
</script>


<script>
  (function() {
    // Grab the link elements
    const prevLink = document.querySelector('.image-nav-item:first-child a');
    const nextLink = document.querySelector('.image-nav-item:last-child a');

    // Swipe detection vars
    let xDown = null;
    let yDown = null;
    const threshold = 50; // px required to count as a swipe

    function handleTouchStart(evt) {
      const first = evt.touches[0];
      xDown = first.clientX;
      yDown = first.clientY;
    }

    function handleTouchEnd(evt) {
      if (xDown === null || yDown === null) return;

      const last = evt.changedTouches[0];
      const xUp = last.clientX;
      const yUp = last.clientY;

      const xDiff = xDown - xUp;
      const yDiff = yDown - yUp;

      // only consider mostly‑horizontal swipes
      if (Math.abs(xDiff) > Math.abs(yDiff) && Math.abs(xDiff) > threshold) {
        if (xDiff > 0) {
          // swipe left → next
          if (nextLink) {
            window.location = nextLink.href;
          }
        } else {
          // swipe right → prev
          if (prevLink) {
            window.location = prevLink.href;
          }
        }
      }

      // reset
      xDown = null;
      yDown = null;
    }

    // Attach to the entire document (or scope to your image container)
    document.addEventListener('touchstart', handleTouchStart, false);
    document.addEventListener('touchend', handleTouchEnd, false);
  })();
</script>
