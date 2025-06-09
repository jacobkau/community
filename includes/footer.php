<div class="share-modal-overlay">
  <div class="share-modal-content"
    style="background-color: var(--card-bg); border-color:1px var(--border-color); color: var(--text-color);">
    <button class="share-modal-close">&times;</button>
    <h5>Share this <span class="share-item-type">post</span></h5>
    <div class="input-group mb-3">
      <input type="text" class="form-control share-url" readonly>
      <button class="btn btn-outline-secondary copy-url-btn" onclick="copyShareUrl()">
        <i class="fas fa-copy"></i> Copy
      </button>
    </div>
    <div class="share-buttons">
      <a class="btn btn-facebook" target="_blank"
        style="background-color: #3b5998; color: #fff; border: none; margin-right: 5px;">
        <i class="fab fa-facebook-f"></i> Facebook
      </a>
      <a class="btn btn-twitter" target="_blank"
        style="background-color: #1da1f2; color: #fff; border: none; margin-right: 5px;">
        <i class="fab fa-twitter"></i> Twitter
      </a>
      <a class="btn btn-whatsapp" target="_blank" style="background-color: #25d366; color: #fff; border: none;">
        <i class="fab fa-whatsapp"></i> WhatsApp
      </a>
    </div>
  </div>
</div>


<footer class="theme-footer col-12 pt-4 pb-4"> <!-- Adjust margin to match sidebar width -->
  <div class="container text-center">
    <p class="mb-1 fw-bolder" style="color:#f5f5f5;">&copy; <?= date("Y") ?> Bits Catholic Portal Community. All rights reserved.</p>
    <p class="small fw-bold">
      <a href="privacy.php" class="footer-link">Privacy Policy</a> |
      <a href="terms.php" class="footer-link">Terms of Service</a> |
      <a href="/bits-catholic-portal/public/contact.php" class="footer-link">Contact Us</a>
    </p>
    <a href="#" class="btn btn-secondary back-to-top" style="position: fixed; bottom: 20px; right: 20px; display: none;">
      Back to Top
    </a>
  </div>
</footer>
<!-- <script src="./assets/js/script.js"></script> -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Toastr (optional) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</body>

</html>