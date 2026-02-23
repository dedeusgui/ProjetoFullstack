<div class="sidebar-header">
    <button type="button" class="sidebar-user sidebar-user-button" data-open-profile-modal aria-controls="profileModalOverlay" aria-haspopup="dialog">
        <div class="user-avatar">
            <?php if (!empty($userData['avatar_url'])): ?>
                <img src="<?php echo htmlspecialchars($userData['avatar_url'], ENT_QUOTES, 'UTF-8'); ?>"
                    alt="Avatar de <?php echo htmlspecialchars($userData['name'], ENT_QUOTES, 'UTF-8'); ?>"
                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
            <?php else: ?>
                <?php echo htmlspecialchars($userData['initials'], ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <h4 class="user-name"><?php echo htmlspecialchars($userData['name'], ENT_QUOTES, 'UTF-8'); ?></h4>
            <p class="user-email"><?php echo htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <span class="user-level-badge">Nível <?php echo (int) ($userData['level'] ?? 1); ?></span>
    </button>
</div>
