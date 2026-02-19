<?php
require_once '../config/bootstrap.php';
bootApp();
require_once '../app/profile/ProfileService.php';

actionRequireLoggedIn();
$allowedReturnPages = ['dashboard.php', 'habits.php', 'history.php'];
$redirectPath = actionResolveReturnPath($allowedReturnPages, 'dashboard.php');
actionRequirePost('dashboard.php');

$userId = (int) getUserId();
$profileService = new ProfileService($conn);
$result = $profileService->resetAppearance($userId);

actionFlashAndRedirect(
    $result['success'] ? 'success_message' : 'error_message',
    $result['message'],
    $redirectPath
);
