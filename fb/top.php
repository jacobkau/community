  <?php if (!empty($profile_user['profile_pic'])): ?>
      <img src="<?= htmlspecialchars($profile_user['profile_pic']) ?>"
          class="profile-img"
          alt="<?= htmlspecialchars($profile_user['username']) ?>">
  <?php else: ?>
      <div class="avatar-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
          style="width: 168px; height: 168px; position: absolute; bottom: -30px; left: 10px;">
          <span style="font-size: 48px; font-weight: bold;">
              <?= strtoupper(substr($profile_user['username'], 0, 1)) ?>
          </span>
      </div>
  <?php endif; ?>

  <div class="mt-2" style="position: absolute; bottom: -40px; left: 200px;">
      <h3><?= htmlspecialchars($profile_user['username']) ?></h3>
      <?php if (!empty($profile_user['bio'])): ?>
          <p style="color:var(--text-color)"><?= htmlspecialchars($profile_user['bio']) ?></p>
      <?php endif; ?>
      <?php if (!empty($profile_user['email'])): ?>
          <p style="color:var(--text-color)"><?= htmlspecialchars($profile_user['email']) ?></p>
      <?php endif; ?>
      
  </div>
  <div class="d-flex justify-content-end">
          <?php if ($is_own_profile): ?>
              <a href="settings.php" class="btn btn-light rounded-pill me-2" style="cursor:pointer;z-index:9999999">
                  <i class="bi bi-pencil-fill"></i> Edit Profile
              </a>
  <?php else: ?>
      <button class="btn btn-light rounded-pill me-2">
          <i class="bi bi-chat-left-text-fill"></i> Message
      </button>
      <button class="btn btn-primary rounded-pill follow-btn"
          data-user-id="<?= $profile_user['id'] ?>"
          data-action="<?= $profile_user['is_following'] ? 'unfollow' : 'follow' ?>">
          <?= $profile_user['is_following'] ? '<i class="bi bi-check-circle-fill"></i> Following' : '<i class="bi bi-person-plus-fill"></i> Add Friend' ?>
      </button>
  <?php endif; ?>
  </div>