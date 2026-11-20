<?php
require_once __DIR__ . '/../inc/auth.php';
super_admin_logout();
header('Location: /admin/login.php');
