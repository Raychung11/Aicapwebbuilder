<?php
require_once __DIR__ . '/../inc/auth.php';
company_admin_logout();
header('Location: /company-admin/login.php');
