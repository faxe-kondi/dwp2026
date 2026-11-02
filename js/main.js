// DEADSTOCK prototype JS — front-end only, no backend calls.

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

  // ---------- mobile nav toggle ----------
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('open');
    });
  }

  // ---------- post likes ----------
  document.querySelectorAll('.like-btn[data-likes]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (btn.disabled) return;

      if (btn.hasAttribute('data-like-url')) {
        btn.disabled = true;
        btn.classList.add('is-saving');

        var body = new URLSearchParams();
        body.set('post_id', btn.getAttribute('data-post-id'));
        body.set('csrf_token', btn.getAttribute('data-csrf-token'));

        fetch(btn.getAttribute('data-like-url'), {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
          body: body.toString()
        }).then(function (response) {
          return response.json().then(function (data) {
            if (!response.ok) {
              var messages = {
                login_required: 'Log in to like posts.',
                own_post: 'You cannot like your own post.',
                post_not_found: 'This post is no longer available.',
                invalid_request: 'Your session expired. Refresh the page and try again.'
              };
              throw new Error(messages[data.error] || 'Could not save your like. Please try again.');
            }
            return data;
          });
        }).then(function (data) {
          btn.classList.toggle('liked', data.liked);
          btn.setAttribute('data-liked', data.liked ? 'true' : 'false');
          btn.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
          btn.setAttribute('aria-label', data.liked ? 'Unlike post' : 'Like post');
          btn.setAttribute('data-likes', data.likes);

          var countEl = btn.querySelector('.like-count');
          if (countEl) countEl.textContent = data.likes;
          var labelEl = btn.querySelector('.like-label');
          if (labelEl) labelEl.textContent = data.liked ? 'Liked' : 'Like';
        }).catch(function (error) {
          showToast(error.message);
        }).then(function () {
          btn.disabled = false;
          btn.classList.remove('is-saving');
        });
        return;
      }

      var base = parseInt(btn.getAttribute('data-likes'), 10) || 0;
      var liked = btn.classList.toggle('liked');
      var countEl = btn.querySelector('.like-count');
      if (countEl) {
        countEl.textContent = liked ? base + 1 : base;
      }
      btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
    });
  });

  // ---------- follow button ----------
  document.querySelectorAll('.follow-btn').forEach(function (btn) {
    function updateFollowLabel(isActive) {
      var isFollowing = btn.getAttribute('data-following') === 'true'
        || (!btn.hasAttribute('data-follow-url') && btn.classList.contains('following'));
      var label = isFollowing && isActive
        ? btn.getAttribute('data-following-hover-label')
        : (isFollowing ? btn.getAttribute('data-following-label') : btn.getAttribute('data-follow-label'));
      if (label) {
        btn.textContent = label;
      }
    }

    btn.addEventListener('mouseenter', function () { updateFollowLabel(true); });
    btn.addEventListener('mouseleave', function () { updateFollowLabel(false); });
    btn.addEventListener('focus', function () { updateFollowLabel(true); });
    btn.addEventListener('blur', function () { updateFollowLabel(false); });

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      if (btn.disabled) return;

      if (!btn.hasAttribute('data-follow-url')) {
        var visualFollowing = btn.classList.toggle('following');
        btn.setAttribute('data-following', visualFollowing ? 'true' : 'false');
        updateFollowLabel(btn.matches(':hover') || btn.matches(':focus'));
        return;
      }

      var following = btn.getAttribute('data-following') !== 'true';
      var body = new URLSearchParams();
      body.set('user_id', btn.getAttribute('data-user-id'));
      body.set('csrf_token', btn.getAttribute('data-csrf-token'));
      body.set('following', following ? '1' : '0');
      btn.disabled = true;

      fetch(btn.getAttribute('data-follow-url'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: body.toString()
      }).then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok) {
            var messages = {
              login_required: 'Log in to follow users.',
              cannot_follow_self: 'You cannot follow yourself.',
              blocked_relation: 'You cannot follow this user while either of you has blocked the other.',
              user_not_found: 'This user is no longer available.',
              invalid_request: 'Your session expired. Refresh the page and try again.'
            };
            throw new Error(messages[data.error] || 'Could not update your follow. Please try again.');
          }
          return data;
        });
      }).then(function (data) {
        btn.classList.toggle('following', data.following);
        btn.setAttribute('data-following', data.following ? 'true' : 'false');
        btn.setAttribute('aria-pressed', data.following ? 'true' : 'false');
        updateFollowLabel(btn.matches(':hover') || btn.matches(':focus'));
      }).catch(function (error) {
        showToast(error.message);
      }).then(function () {
        btn.disabled = false;
      });
    });
  });

  // ---------- dropzone: click-to-browse, drag-and-drop, live preview (upload page) ----------
  var dropzone = document.querySelector('.dropzone');
  if (dropzone) {
    var input = document.getElementById('fileInput');
    var dropzonePreview = document.getElementById('dropzonePreview');
    var fileNameEl = document.getElementById('fileName');
    var submitButton = dropzone.closest('form').querySelector('[type="submit"]');
    var previewUrl = null;
    var selectionId = 0;

    function optimizeImage(file) {
      if (!window.createImageBitmap) {
        return Promise.resolve(file);
      }

      return createImageBitmap(file).then(function (bitmap) {
        var size = 1200;
        var cropSize = Math.min(bitmap.width, bitmap.height);
        var sourceX = Math.floor((bitmap.width - cropSize) / 2);
        var sourceY = Math.floor((bitmap.height - cropSize) / 2);
        var canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        var context = canvas.getContext('2d');
        if (!context) {
          bitmap.close();
          throw new Error('Could not prepare an optimized image.');
        }

        context.fillStyle = '#fff';
        context.fillRect(0, 0, size, size);
        context.drawImage(bitmap, sourceX, sourceY, cropSize, cropSize, 0, 0, size, size);
        bitmap.close();

        return new Promise(function (resolve, reject) {
          canvas.toBlob(function (blob) {
            if (!blob) {
              reject(new Error('Could not encode an optimized image.'));
              return;
            }
            if (blob.type !== 'image/webp') {
              reject(new Error('This browser cannot encode WebP images.'));
              return;
            }
            resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.webp', {
              type: 'image/webp',
              lastModified: Date.now()
            }));
          }, 'image/webp', 0.82);
        });
      });
    }

    function updatePreview(file) {
      if (!dropzonePreview) { return; }
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = URL.createObjectURL(file);
      dropzonePreview.src = previewUrl;
      dropzonePreview.hidden = false;
      dropzone.classList.add('has-preview');
    }

    function showSelectedFile(file) {
      var currentSelection = ++selectionId;
      if (submitButton) submitButton.disabled = true;
      if (fileNameEl) fileNameEl.textContent = 'Optimizing image…';

      optimizeImage(file).then(function (optimizedFile) {
        if (currentSelection !== selectionId) { return; }
        if (optimizedFile !== file && input) {
          var transfer = new DataTransfer();
          transfer.items.add(optimizedFile);
          input.files = transfer.files;
        }
        updatePreview(optimizedFile);
        if (fileNameEl) {
          fileNameEl.textContent = optimizedFile === file
            ? file.name + ' (will be optimized on upload)'
            : file.name + ' (optimized to square)';
        }
      }).catch(function (error) {
        if (currentSelection !== selectionId) { return; }
        console.error('Image optimization failed:', error);
        updatePreview(file);
        if (fileNameEl) fileNameEl.textContent = file.name + ' (optimization unavailable)';
      }).then(function () {
        if (currentSelection === selectionId && submitButton) {
          submitButton.disabled = false;
        }
      });
    }

    dropzone.addEventListener('click', function () {
      if (input) input.click();
    });

    if (input) {
      input.addEventListener('change', function () {
        if (input.files && input.files[0]) showSelectedFile(input.files[0]);
      });
    }

    ['dragover', 'dragenter'].forEach(function (evt) {
      dropzone.addEventListener(evt, function (e) {
        e.preventDefault();
        dropzone.classList.add('drag-over');
      });
    });
    ['dragleave', 'dragend'].forEach(function (evt) {
      dropzone.addEventListener(evt, function () {
        dropzone.classList.remove('drag-over');
      });
    });
    dropzone.addEventListener('drop', function (e) {
      e.preventDefault();
      dropzone.classList.remove('drag-over');
      if (input && e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        showSelectedFile(input.files[0]);
      }
    });
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

  // ---------- block / unblock ----------
  document.querySelectorAll('[data-block-url]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeAllActionMenus();
      if (btn.disabled) return;

      var blocking = btn.getAttribute('data-blocked') !== 'true';
      var label = btn.getAttribute('data-block-label') || btn.getAttribute('data-unblock-label') || 'this user';
      var body = new URLSearchParams();
      body.set('user_id', btn.getAttribute('data-user-id'));
      body.set('csrf_token', btn.getAttribute('data-csrf-token'));
      body.set('blocked', blocking ? '1' : '0');
      btn.disabled = true;

      fetch(btn.getAttribute('data-block-url'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: body.toString()
      }).then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok) {
            var messages = {
              login_required: 'Log in to block users.',
              cannot_block_self: 'You cannot block yourself.',
              user_not_found: 'This user is no longer available.',
              invalid_request: 'Your session expired. Refresh the page and try again.'
            };
            throw new Error(messages[data.error] || 'Could not update your block. Please try again.');
          }
          return data;
        });
      }).then(function (data) {
        btn.setAttribute('data-blocked', data.blocked ? 'true' : 'false');
        var blockedCard = btn.closest('[data-blocked-card]');
        if (blockedCard) {
          btn.textContent = data.blocked ? 'Unblock' : 'Block';
          showToast(data.blocked
            ? 'Blocked ' + label + '.'
            : 'Unblocked ' + label + '. You can block them again here.');
          return;
        }

        if (data.blocked && btn.closest('.uploader-card')) {
          window.location.reload();
          return;
        }

        if (
          btn.closest('.profile-header')
          || btn.closest('.profile-blocked-state')
          || btn.closest('.uploader-card')
        ) {
          showToast(data.blocked
            ? 'Blocked ' + label + '.'
            : 'Unblocked ' + label + '.');
          window.location.reload();
          return;
        }

        var card = btn.closest('.user-card') || btn.closest('.uploader-card') || btn.closest('.profile-header');
        if (card) {
          card.classList.add('card-blocked-out');
          setTimeout(function () { card.style.display = 'none'; }, 300);
        }
        showToast(data.blocked
          ? 'Blocked ' + label + '. Manage this anytime from your Blocked list.'
          : 'Unblocked ' + label + '.');
      }).catch(function (error) {
        showToast(error.message);
      }).then(function () {
        btn.disabled = false;
      });
    });
  });

});