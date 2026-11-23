(function () {
  var toastEl = document.getElementById('toast');
  var toastTimer = null;

  // ---------- toast ----------
  function showToast(message) {
    if (!toastEl) return;
    toastEl.textContent = message;
    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      toastEl.classList.remove('show');
    }, 3800);
  }

  // ---------- action menu (Report / Block dropdown) ----------
  function closeAllActionMenus() {
    document.querySelectorAll('.action-menu.open').forEach(function (menu) {
      menu.classList.remove('open');
      var trigger = menu.querySelector('.action-menu-trigger');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    });
  }

  window.DeadstockUI = {
    showToast: showToast,
    closeAllActionMenus: closeAllActionMenus
  };

  document.querySelectorAll('.action-menu-trigger').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      var menu = trigger.closest('.action-menu');
      var isOpen = menu.classList.contains('open');
      closeAllActionMenus();
      if (!isOpen) {
        menu.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });
  });

  document.addEventListener('click', closeAllActionMenus);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAllActionMenus();
  });
})();
