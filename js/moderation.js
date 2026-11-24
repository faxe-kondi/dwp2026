document.addEventListener('DOMContentLoaded', function () {

  // ---------- report modal ----------
  var reportModal = document.getElementById('reportModal');
  var reportForm = document.getElementById('reportForm');
  var reportConfirm = document.getElementById('reportConfirm');
  var reportModalTitle = document.getElementById('reportModalTitle');
  var reportModalSub = document.getElementById('reportModalSub');

  function openReportModal(type, target, targetId) {
    if (!reportModal) return;
    var label = type === 'post' ? 'post' : (type === 'comment' ? 'comment' : 'user');
    reportModalTitle.textContent = 'Report ' + label;
    reportModalSub.textContent = target ? ('Reporting ' + target) : '';
    reportForm.reset();
    reportForm.elements.namedItem('target_type').value = type;
    reportForm.elements.namedItem('target_id').value = targetId;
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
      window.DeadstockUI.closeAllActionMenus();
      openReportModal(
        btn.getAttribute('data-report-type'),
        btn.getAttribute('data-report-target'),
        btn.getAttribute('data-report-id')
      );
    });
  });

  if (reportForm) {
    reportForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var submitButton = reportForm.querySelector('button[type="submit"]');
      submitButton.disabled = true;
      var body = new URLSearchParams(new FormData(reportForm));

      fetch(reportForm.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: body.toString()
      }).then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok) {
            var messages = {
              login_required: 'Log in to submit a report.',
              invalid_request: 'Your session expired or the report details are invalid. Refresh and try again.',
              target_not_found: 'This item is no longer available to report.',
              cannot_report_own_content: 'You cannot report your own content.'
            };
            throw new Error(messages[data.error] || data.message || 'Could not submit the report. Please try again.');
          }
          return data;
        });
      }).then(function () {
        reportForm.hidden = true;
        reportConfirm.hidden = false;
      }).catch(function (error) {
        window.DeadstockUI.showToast(error.message);
      }).then(function () {
        submitButton.disabled = false;
      });
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

  // ---------- ban user modal ----------
  var banUserModal = document.getElementById('banUserModal');
  var banUserForm = document.getElementById('banUserForm');
  var banUserModalSub = document.getElementById('banUserModalSub');
  var banUserModalSubmit = document.getElementById('banUserModalSubmit');

  function closeBanUserModal() {
    if (banUserModal && typeof banUserModal.close === 'function' && banUserModal.open) {
      banUserModal.close();
    }
  }

  document.querySelectorAll('[data-ban-user]').forEach(function (button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      window.DeadstockUI.closeAllActionMenus();
      if (!banUserModal || !banUserForm) return;

      banUserForm.reset();
      banUserForm.elements.namedItem('user_id').value = button.getAttribute('data-user-id');
      var username = button.getAttribute('data-username');
      var resolvesReports = button.getAttribute('data-resolve-reports') === 'true';
      var isAlreadyBanned = button.getAttribute('data-user-banned') === 'true';
      banUserForm.action = resolvesReports
        ? 'controllers/ModerateReportController.php'
        : 'controllers/ModerateUserController.php';
      if (resolvesReports) {
        banUserForm.elements.namedItem('type').value = 'users';
        banUserForm.elements.namedItem('id').value = button.getAttribute('data-user-id');
        banUserModalSub.textContent = isAlreadyBanned
          ? 'Keep @' + username + ' banned and resolve the pending reports?'
          : 'Ban @' + username + ' and resolve the pending reports?';
        banUserModalSubmit.textContent = isAlreadyBanned ? 'Keep banned and resolve reports' : 'Ban and resolve reports';
      } else {
        banUserModalSub.textContent = 'Ban @' + username + '?';
        banUserModalSubmit.textContent = 'Ban user';
      }
      if (typeof banUserModal.showModal === 'function' && !banUserModal.open) {
        banUserModal.showModal();
      }
    });
  });

  ['banUserModalClose', 'banUserModalCancel'].forEach(function (id) {
    var button = document.getElementById(id);
    if (button) button.addEventListener('click', closeBanUserModal);
  });

  if (banUserModal) {
    banUserModal.addEventListener('click', function (e) {
      if (e.target === banUserModal) {
        closeBanUserModal();
      }
    });
  }

});
