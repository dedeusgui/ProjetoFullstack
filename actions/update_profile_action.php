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
$result = $profileService->updateProfile($userId, $_POST);

if ($result['success']) {
    if (!empty($result['email'])) {
        $_SESSION['user_email'] = $result['email'];
    }
    actionFlashAndRedirect('success_message', $result['message'], $redirectPath);
}

actionFlashAndRedirect('error_message', $result['message'], $redirectPath);
