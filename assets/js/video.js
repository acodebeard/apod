document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-apod-video]').forEach((video) => {
    const button = video.querySelector('[data-apod-video-load]');
    const poster = video.querySelector('.apod-video-poster');
    const frame = video.querySelector('.apod-video-frame');
    const iframe = frame ? frame.querySelector('iframe[data-src]') : null;

    if (!button || !poster || !frame || !iframe) {
      return;
    }

    button.addEventListener('click', () => {
      const src = iframe.dataset.src;
      if (!src) {
        return;
      }

      if (!iframe.getAttribute('src')) {
        iframe.setAttribute('src', src);
      }

      poster.hidden = true;
      frame.hidden = false;
      video.classList.add('is-playing');
      button.setAttribute('aria-expanded', 'true');
      frame.focus();
    });
  });
});
