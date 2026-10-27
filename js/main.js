// DEADSTOCK prototype JS — front-end only, no backend calls.

document.addEventListener('DOMContentLoaded', function () {

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

  // ---------- mobile nav toggle ----------
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('open');
    });
  }

  // ---------- like button (visual only) ----------
  document.querySelectorAll('.like-btn[data-likes]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var base = parseInt(btn.getAttribute('data-likes'), 10) || 0;
      var liked = btn.classList.toggle('liked');
      var countEl = btn.querySelector('.like-count');
      if (countEl) {
        countEl.textContent = liked ? base + 1 : base;
      }
    });
  });

  // ---------- follow button (visual only) ----------
  document.querySelectorAll('.follow-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var following = btn.classList.toggle('following');
      var label = following ? btn.getAttribute('data-following-label') : btn.getAttribute('data-follow-label');
      if (label) {
        btn.textContent = label;
      }
    });
  });

  // ---------- dropzone click-to-browse (upload page, non-functional) ----------
  var dropzone = document.querySelector('.dropzone');
  if (dropzone) {
    var input = document.getElementById('fileInput');
    dropzone.addEventListener('click', function () {
      if (input) input.click();
    });
    if (input) {
      input.addEventListener('change', function () {
        var nameEl = document.getElementById('fileName');
        if (nameEl && input.files.length) {
          nameEl.textContent = input.files[0].name;
        }
      });
    }
  }

  // ---------- avatar picker (settings page — real upload, live preview) ----------
  var avatarBtn = document.getElementById('avatarPickerBtn');
  var avatarInput = document.getElementById('avatarInput');
  var avatarPreview = document.getElementById('avatarPreviewImg');
  var avatarFileName = document.getElementById('avatarFileName');
  if (avatarBtn && avatarInput && avatarPreview) {
    avatarBtn.addEventListener('click', function () {
      avatarInput.click();
    });
    avatarInput.addEventListener('change', function () {
      if (avatarInput.files && avatarInput.files[0]) {
        var file = avatarInput.files[0];
        var reader = new FileReader();
        reader.onload = function (e) {
          avatarPreview.src = e.target.result;
        };
        reader.readAsDataURL(file);
        if (avatarFileName) {
          avatarFileName.textContent = file.name;
        }
      }
    });
  }

  // ---------- generic prototype-form guard ----------
  document.querySelectorAll('form[data-proto]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var note = form.querySelector('.proto-submit-note');
      if (note) {
        note.textContent = 'This is a prototype — nothing was actually uploaded or posted.';
      }
    });
  });

  // ---------- toast ----------
  var toastEl = document.getElementById('toast');
  var toastTimer = null;
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

  // ---------- report modal ----------
  var reportModal = document.getElementById('reportModal');
  var reportForm = document.getElementById('reportForm');
  var reportConfirm = document.getElementById('reportConfirm');
  var reportModalTitle = document.getElementById('reportModalTitle');
  var reportModalSub = document.getElementById('reportModalSub');

  function openReportModal(type, target) {
    if (!reportModal) return;
    var label = type === 'post' ? 'post' : (type === 'comment' ? 'comment' : 'user');
    reportModalTitle.textContent = 'Report ' + label;
    reportModalSub.textContent = target ? ('Reporting ' + target) : '';
    reportForm.reset();
    reportForm.hidden = false;
    reportConfirm.hidden = true;
    if (typeof reportModal.showModal === 'function' && !reportModal.open) {
      reportModal.showModal();
    }
  }

  function closeReportModal() {
    if (reportModal && typeof reportModal.close === 'function' && reportModal.open) {
      reportModal.close();
    }
  }

  document.querySelectorAll('[data-report-type]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeAllActionMenus();
      openReportModal(btn.getAttribute('data-report-type'), btn.getAttribute('data-report-target'));
    });
  });

  if (reportForm) {
    reportForm.addEventListener('submit', function (e) {
      e.preventDefault();
      reportForm.hidden = true;
      reportConfirm.hidden = false;
    });
  }

  ['reportModalCloseX', 'reportModalCancel', 'reportConfirmCloseX', 'reportModalDone'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('click', closeReportModal);
  });

  // Click on the backdrop (outside the modal box) closes it too.
  if (reportModal) {
    reportModal.addEventListener('click', function (e) {
      if (e.target === reportModal) {
        closeReportModal();
      }
    });
  }

  // ---------- block / unblock (visual only — fades the card + confirms via toast) ----------
  document.querySelectorAll('[data-block-target]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var label = btn.getAttribute('data-block-label') || 'this user';
      var card = btn.closest('.user-card') || btn.closest('.uploader-card');
      closeAllActionMenus();
      if (card) {
        card.classList.add('card-blocked-out');
        setTimeout(function () { card.style.display = 'none'; }, 300);
      }
      showToast('Blocked ' + label + ' — manage this anytime from your Blocked list.');
    });
  });

  document.querySelectorAll('[data-unblock-target]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var label = btn.getAttribute('data-unblock-label') || 'this user';
      var card = btn.closest('.user-card');
      if (card) {
        card.classList.add('card-blocked-out');
        setTimeout(function () { card.style.display = 'none'; }, 300);
      }
      showToast('Unblocked ' + label + '.');
    });
  });

});
