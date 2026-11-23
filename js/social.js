document.addEventListener('DOMContentLoaded', function () {

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
          window.DeadstockUI.showToast(error.message);
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
        var profileStats = document.querySelector('.profile-stats');
        if (profileStats) {
          var profileUserId = profileStats.getAttribute('data-profile-user-id');
          var targetUserId = btn.getAttribute('data-user-id');
          var viewerUserId = profileStats.getAttribute('data-viewer-user-id');
          if (profileUserId === targetUserId && data.profile_counts) {
            profileStats.querySelector('[data-profile-stat="followers"]').textContent = data.profile_counts.followers;
            profileStats.querySelector('[data-profile-stat="following"]').textContent = data.profile_counts.following;
          }
          if (profileUserId === viewerUserId && data.viewer_counts) {
            profileStats.querySelector('[data-profile-stat="following"]').textContent = data.viewer_counts.following;
          }
        }
        updateFollowLabel(btn.matches(':hover') || btn.matches(':focus'));
      }).catch(function (error) {
        window.DeadstockUI.showToast(error.message);
      }).then(function () {
        btn.disabled = false;
      });
    });
  });

  // ---------- block / unblock ----------
  document.querySelectorAll('[data-block-url]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      window.DeadstockUI.closeAllActionMenus();
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
          window.DeadstockUI.showToast(data.blocked
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
          window.DeadstockUI.showToast(data.blocked
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
        window.DeadstockUI.showToast(data.blocked
          ? 'Blocked ' + label + '. Manage this anytime from your Blocked list.'
          : 'Unblocked ' + label + '.');
      }).catch(function (error) {
        window.DeadstockUI.showToast(error.message);
      }).then(function () {
        btn.disabled = false;
      });
    });
  });

});
