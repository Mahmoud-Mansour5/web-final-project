<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../html/login.html');
        exit();
    }
}

function checkAdmin() {
    checkLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: ../html/after-login.html');
        exit();
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
?> 