const thumbBtn = document.getElementById('thumbViewBtn');
    const listBtn = document.getElementById('listViewBtn');
    const apodGallery = document.getElementById('apodGallery');
    thumbBtn.addEventListener('click', () => {
      apodGallery.classList.remove('list-view');
      apodGallery.classList.add('grid-view');
      thumbBtn.classList.add('active');
      listBtn.classList.remove('active');
    });

    listBtn.addEventListener('click', () => {
      apodGallery.classList.add('list-view');
      apodGallery.classList.remove('grid-view');
      thumbBtn.classList.remove('active');
      listBtn.classList.add('active');
    });

    const paginationControls = document.getElementById('paginationControls');
    const perPage = 12;
    let currentPage = 1;
    let apodData = [];

    async function getApods() {
      try {
        const res = await fetch('/apod/data/apod.local.json');
        apodData = await res.json();
        renderPage(currentPage);
        renderPagination();
      } catch (err) {
        console.error('Error loading APODs:', err);
        apodGallery.innerHTML = `<p>Failed to load APOD data.</p>`;
      }
    }

function slugify(title) {
  return title.toLowerCase()
    .replace(/[^\w\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
}
    

    function renderPage(page) {
      apodGallery.innerHTML = '';
      const start = (page - 1) * perPage;
      const slice = apodData.slice(start, start + perPage);

      slice.forEach((entry, index) => {
        const card = document.createElement('a');
        card.classList.add('apod-card');
        const slug = slugify(entry.title);
        card.href = `image/${slug}`;
        
        card.rel = 'noopener';

        card.innerHTML = `
            <div class="apod-thumb" style="aspect-ratio: 16/9; overflow: hidden;">
              <img src="thumbs/${entry.date}.webp" alt="${entry.title}" width="480" height="270"
                  ${index === 0 && page === 1 ? 'fetchpriority="high"' : 'loading="lazy"'}
                  decoding="async"
                  style="object-fit: cover; width: 100%; height: 100%;">
            </div>
            <div class="apod-meta">
              <p class="apod-date"><time datetime="${entry.date}">${entry.date}</time></p>
              <div class="apod-title">${entry.title}</div>
            </div>
          `;

        apodGallery.appendChild(card);
      });
    }

function renderPagination() {
  const totalPages = Math.ceil(apodData.length / perPage);
  paginationControls.innerHTML = '';

  // Create wrapper for swipeable scroll
  const scrollWrapper = document.createElement('div');
  scrollWrapper.className = 'pagination-scroll';
  scrollWrapper.setAttribute('role', 'navigation');
  scrollWrapper.setAttribute('aria-label', 'Gallery pages');

  // Previous button
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

  /**
   * Returns an array of page numbers to display in pagination
   */
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

  // Page number buttons
  const visiblePages = 7;
  const pageRange = getPaginationRange(currentPage, totalPages, visiblePages);

  pageRange.forEach(i => {
    const pageBtn = document.createElement('button');
    pageBtn.textContent = i;
    pageBtn.className = 'page-button';

    if (i === currentPage) {
      pageBtn.classList.add('active');
      pageBtn.setAttribute('aria-current', 'page');
    }

    pageBtn.setAttribute('aria-label', `Go to page ${i}`);
    pageBtn.setAttribute('type', 'button');

    pageBtn.onclick = () => {
      currentPage = i;
      renderPage(currentPage);
      renderPagination();
    };

    scrollWrapper.appendChild(pageBtn);
  });

  // Next button
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

  // Append to controls container
  paginationControls.appendChild(scrollWrapper);
}



    getApods();
