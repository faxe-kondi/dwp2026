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

<dialog id="reportModal" class="modal">
  <div class="modal-box">
    <form data-proto id="reportForm">
      <div class="modal-head">
        <h3 id="reportModalTitle">Report</h3>
        <button type="button" class="modal-close" id="reportModalCloseX" aria-label="Close">&times;</button>
      </div>
      <p class="modal-sub" id="reportModalSub"></p>

      <div class="field">
        <label for="reportReason">Reason</label>
        <select id="reportReason" name="reason">
          <option>Spam</option>
          <option>Counterfeit or fake item</option>
          <option>Harassment or hate</option>
          <option>Inappropriate content</option>
          <option>Other</option>
        </select>
      </div>

      <div class="field" style="margin-bottom:0;">
        <label for="reportDetails">Additional details (optional)</label>
        <textarea id="reportDetails" rows="3" placeholder="Anything else mods should know..."></textarea>
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
      <p class="modal-confirm">Thanks &mdash; this is a prototype, so nothing was actually sent, but a real report would go to the mod queue from here.</p>
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