<?php
require_once '../config/bootstrap.php';
bootApp();

use App\Profile\ProfileService;

actionRequireLoggedIn();
$allowedReturnPages = ['dashboard.php', 'habits.php', 'history.php'];
$redirectPath = actionResolveReturnPath($allowedReturnPages, 'dashboard.php');
actionRequirePost('dashboard.php');

$userId = (int) getAuthenticatedUserId();
$profileService = new ProfileService($conn);
$result = $profileService->resetAppearance($userId);

actionFlashAndRedirect(
    $result['success'] ? 'success_message' : 'error_message',
    $result['message'],
    $redirectPath
);
