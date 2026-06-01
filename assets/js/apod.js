const thumbBtn = document.getElementById('thumbViewBtn');
const listBtn = document.getElementById('listViewBtn');
const apodGallery = document.getElementById('apodGallery');
const paginationControls = document.getElementById('paginationControls');
const perPage = 12;
let currentPage = 1;
let apodData = [];

function setGalleryView(isGridView) {
  apodGallery.classList.toggle('grid-view', isGridView);
  apodGallery.classList.toggle('list-view', !isGridView);
  thumbBtn.classList.toggle('active', isGridView);
  listBtn.classList.toggle('active', !isGridView);
  thumbBtn.setAttribute('aria-pressed', isGridView ? 'true' : 'false');
  listBtn.setAttribute('aria-pressed', isGridView ? 'false' : 'true');
}

thumbBtn.addEventListener('click', () => {
  setGalleryView(true);
});

listBtn.addEventListener('click', () => {
  setGalleryView(false);
});

async function getApods() {
  try {
    const res = await fetch('/apod/data/apod.local.json');
    apodData = await res.json();
    renderPage(currentPage);
    renderPagination();
  } catch (err) {
    console.error('Error loading APODs:', err);
    const message = document.createElement('p');
    message.textContent = 'Failed to load APOD data.';
    apodGallery.replaceChildren(message);
  }
}

function slugify(title) {
  return title.toLowerCase()
    .replace(/[^\w\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
}

function createGalleryCard(entry, index, page) {
  const card = document.createElement('a');
  card.classList.add('apod-card');
  card.href = `image/${entry.slug || slugify(entry.title)}`;
  card.rel = 'noopener';

  const thumb = document.createElement('div');
  thumb.className = 'apod-thumb';
  thumb.style.aspectRatio = '16 / 9';
  thumb.style.overflow = 'hidden';

  const img = document.createElement('img');
  img.src = entry.url_thumb || `thumbs/${entry.date}.webp`;
  img.alt = entry.title;
  img.width = 480;
  img.height = 270;
  img.decoding = 'async';
  img.style.objectFit = 'cover';
  img.style.width = '100%';
  img.style.height = '100%';

  if (index === 0 && page === 1) {
    img.fetchPriority = 'high';
  } else {
    img.loading = 'lazy';
  }

  thumb.appendChild(img);

  if (entry.media_type === 'video') {
    const badge = document.createElement('span');
    badge.className = 'media-badge media-badge-video';
    badge.textContent = 'Video';
    thumb.appendChild(badge);
  }

  const meta = document.createElement('div');
  meta.className = 'apod-meta';

  const date = document.createElement('p');
  date.className = 'apod-date';

  const time = document.createElement('time');
  time.dateTime = entry.date;
  time.textContent = entry.date;
  date.appendChild(time);

  const title = document.createElement('div');
  title.className = 'apod-title';
  title.textContent = entry.title;

  meta.append(date, title);
  card.append(thumb, meta);

  return card;
}

function renderPage(page) {
  apodGallery.replaceChildren();
  const start = (page - 1) * perPage;
  const slice = apodData.slice(start, start + perPage);

  slice.forEach((entry, index) => {
    apodGallery.appendChild(createGalleryCard(entry, index, page));
  });
}

function renderPagination() {
  const totalPages = Math.ceil(apodData.length / perPage);
  paginationControls.replaceChildren();

  const scrollWrapper = document.createElement('div');
  scrollWrapper.className = 'pagination-scroll';
  scrollWrapper.setAttribute('role', 'navigation');
  scrollWrapper.setAttribute('aria-label', 'Gallery pages');

  const prevBtn = document.createElement('button');
  prevBtn.textContent = 'Previous';
  prevBtn.setAttribute('aria-label', 'Go to previous page');
  prevBtn.setAttribute('type', 'button');
  prevBtn.classList.add('prev-next-button');
  prevBtn.disabled = currentPage === 1;
  prevBtn.onclick = () => {
    if (currentPage > 1) {
      currentPage--;
      renderPage(currentPage);
      renderPagination();
    }
  };
  scrollWrapper.appendChild(prevBtn);

  function getPaginationRange(currentPage, totalPages, visiblePages = 7) {
    const half = Math.floor(visiblePages / 2);
    let start = Math.max(1, currentPage - half);
    let end = start + visiblePages - 1;

    if (end > totalPages) {
      end = totalPages;
      start = Math.max(1, end - visiblePages + 1);
    }

    const pages = [];
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    return pages;
  }

  const pageRange = getPaginationRange(currentPage, totalPages);

  pageRange.forEach((pageNumber) => {
    const pageBtn = document.createElement('button');
    pageBtn.textContent = pageNumber;
    pageBtn.className = 'page-button';

    if (pageNumber === currentPage) {
      pageBtn.classList.add('active');
      pageBtn.setAttribute('aria-current', 'page');
    }

    pageBtn.setAttribute('aria-label', `Go to page ${pageNumber}`);
    pageBtn.setAttribute('type', 'button');
    pageBtn.onclick = () => {
      currentPage = pageNumber;
      renderPage(currentPage);
      renderPagination();
    };

    scrollWrapper.appendChild(pageBtn);
  });

  const nextBtn = document.createElement('button');
  nextBtn.textContent = 'Next';
  nextBtn.setAttribute('aria-label', 'Go to next page');
  nextBtn.setAttribute('type', 'button');
  nextBtn.classList.add('prev-next-button');
  nextBtn.disabled = currentPage === totalPages;
  nextBtn.onclick = () => {
    if (currentPage < totalPages) {
      currentPage++;
      renderPage(currentPage);
      renderPagination();
    }
  };
  scrollWrapper.appendChild(nextBtn);

  paginationControls.appendChild(scrollWrapper);
}

getApods();
