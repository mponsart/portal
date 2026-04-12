<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

include __DIR__ . '/views/admin-news-new.php';
