document.addEventListener('DOMContentLoaded', function () {

  // ---------- expandable post captions and comments ----------
  function setupTextToggle(text, toggle, collapsedClass) {
    text.classList.add(collapsedClass);
    if (text.scrollHeight <= text.clientHeight + 1) {
      text.classList.remove(collapsedClass);
      return;
    }

    toggle.hidden = false;
    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      text.classList.toggle(collapsedClass, expanded);
      toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      toggle.textContent = expanded ? 'Show more' : 'Show less';
    });
  }

  var caption = document.getElementById('postCaption');
  var captionToggle = document.querySelector('.caption-toggle[aria-controls="postCaption"]');
  if (caption && captionToggle) {
    setupTextToggle(caption, captionToggle, 'is-collapsed');
  }

  document.querySelectorAll('.comment-text').forEach(function (comment) {
    var toggle = document.querySelector('[aria-controls="' + comment.id + '"].comment-toggle');
    if (toggle) {
      setupTextToggle(comment, toggle, 'is-collapsed');
    }
  });

  // ---------- scroll-in reveal ----------
  var revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in-view');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
      revealEls.forEach(function (el) { io.observe(el); });
    } else {
      revealEls.forEach(function (el) { el.classList.add('in-view'); });
    }
  }
  document.documentElement.classList.add('js-ready');

  // ---------- mobile nav toggle ----------
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('open');
    });
  }

  var searchInput = document.getElementById('searchQuery');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      var query = searchInput.value.trim() || '...';
      var searchTerm = document.querySelector('.search-category-prompt strong');
      if (searchTerm) searchTerm.textContent = query;

      document.querySelectorAll('[data-search-category]').forEach(function (option) {
        var url = new URL('search.php', window.location.href);
        url.searchParams.set('q', searchInput.value.trim());
        url.searchParams.set('type', option.getAttribute('data-search-category'));
        option.href = url.toString();
      });

      var status = document.querySelector('.search-status');
      if (status) {
        status.textContent = searchInput.value.trim()
          ? 'Choose a category to search for "' + searchInput.value.trim() + '".'
          : 'Enter a search term, then choose a category.';
      }
    });
  }
});