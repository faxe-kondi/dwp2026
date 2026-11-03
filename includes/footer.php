<footer class="site-footer">
  <div class="wrap">
    <div class="footer-brand">
      <a href="index.php" class="brand">DEAD<span>STOCK</span></a>
      <p>A place to post the fit, the find, and the fight to get it. Built by students, for the racks nobody else is checking.</p>
    </div>
    <div class="footer-cols">
      <div>
        <h4>SITE</h4>
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="browse.php">Browse</a></li>
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
        </ul>
      </div>
      <?php endif; ?>
      <div>
        <h4>PROJECT</h4>
        <ul>
          <li><a href="#">School prototype</a></li>
          <li><a href="#">v0.1 — hardcoded</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="wrap">
      DEADSTOCK &mdash; class project prototype. no real accounts, uploads, or data.
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

<div class="toast" id="toast" aria-live="polite"></div>

<script src="js/main.js?v=<?= filemtime(__DIR__ . '/../js/main.js'); ?>"></script>
</body>
</html>