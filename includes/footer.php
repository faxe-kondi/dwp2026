<?php
require_once __DIR__ . '/SiteSettings.php';
$footerContact = trim(SiteSettings::get('contact'));
?>
<footer class="site-footer">
  <div class="wrap">
    <div class="footer-brand">
      <a href="index.php" class="brand">DEAD<span>STOCK</span></a>
      <p>A community for sharing the finds, fits, and stories worth passing on.</p>
    </div>
    <div class="footer-cols">
      <div>
        <h4>SITE</h4>
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="browse.php">Browse</a></li>
          <li><a href="search.php">Search</a></li>
          <li><a href="about.php">About</a></li>
        </ul>
      </div>
      <?php if (Auth::check()): ?>
      <div>
        <h4>ACCOUNT</h4>
        <ul>
          <li><a href="upload.php">Upload</a></li>
          <li><a href="profile.php">Your profile</a></li>
          <li><a href="liked.php">Your likes</a></li>
          <li><a href="following.php">Following</a></li>
          <li><a href="blocked.php">Blocked users</a></li>
          <li><a href="settings.php">Settings</a></li>
          <li><a href="logout.php">Log out</a></li>
        </ul>
      </div>
      <?php endif; ?>
      <?php if ($footerContact !== ''): ?>
        <div class="footer-contact">
          <h4>CONTACT</h4>
          <p><?= nl2br(htmlspecialchars($footerContact)); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="wrap">
      DEADSTOCK &mdash; Share your finds. Respect the community.
    </div>
  </div>
</footer>

<?php require_once __DIR__ . '/Csrf.php'; ?>
<dialog id="reportModal" class="modal">
  <div class="modal-box">
    <form id="reportForm" action="includes/submit-report.php" method="post">
      <?= Csrf::field(); ?>
      <input type="hidden" name="target_type">
      <input type="hidden" name="target_id">
      <div class="modal-head">
        <h3 id="reportModalTitle">Report</h3>
        <button type="button" class="modal-close" id="reportModalCloseX" aria-label="Close">&times;</button>
      </div>
      <p class="modal-sub" id="reportModalSub"></p>

      <div class="field">
        <label for="reportReason">Reason</label>
        <select id="reportReason" name="reason_id">
          <option value="1">Spam</option>
          <option value="2">Counterfeit or fake item</option>
          <option value="3">Harassment or hate</option>
          <option value="4">Inappropriate content</option>
          <option value="5">Other</option>
        </select>
      </div>

      <div class="field" style="margin-bottom:0;">
        <label for="reportDetails">Additional details (optional)</label>
        <textarea id="reportDetails" name="details" rows="3" maxlength="1000" placeholder="Anything else mods should know..."></textarea>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn" id="reportModalCancel">Cancel</button>
        <button type="submit" class="btn btn-solid">Submit report</button>
      </div>
    </form>

    <div id="reportConfirm" hidden>
      <div class="modal-head">
        <h3>Report sent</h3>
        <button type="button" class="modal-close" id="reportConfirmCloseX" aria-label="Close">&times;</button>
      </div>
      <p class="modal-confirm">Your report has been submitted. Thank you for helping keep the community safe.</p>
      <div class="modal-confirm-actions">
        <button type="button" class="btn btn-solid" id="reportModalDone">Done</button>
      </div>
    </div>
  </div>
</dialog>

<dialog id="banUserModal" class="modal">
  <div class="modal-box">
    <form id="banUserForm" action="includes/moderate-user.php" method="post">
      <?= Csrf::field(); ?>
      <input type="hidden" name="user_id">
      <input type="hidden" name="type">
      <input type="hidden" name="id">
      <input type="hidden" name="action" value="ban">
      <div class="modal-head">
        <h3 id="banUserModalTitle">Ban user</h3>
        <button type="button" class="modal-close" id="banUserModalClose" aria-label="Close">&times;</button>
      </div>
      <p class="modal-sub" id="banUserModalSub"></p>

      <div class="field" style="margin-bottom:0;">
        <label for="banUserReason">Ban reason (optional)</label>
        <input type="text" id="banUserReason" name="reason" maxlength="255" placeholder="Enter a reason">
      </div>

      <div class="modal-actions">
        <button type="button" class="btn" id="banUserModalCancel">Cancel</button>
        <button type="submit" class="btn btn-solid" id="banUserModalSubmit">Ban user</button>
      </div>
    </form>
  </div>
</dialog>

<div class="toast" id="toast" aria-live="polite"></div>

<script src="js/main.js?v=<?= filemtime(__DIR__ . '/../js/main.js'); ?>"></script>
</body>
</html>