<?php
// templates/chronologie.php
// The timeline is displayed on the homepage.
// This template should not normally be reached as the router handles the redirect.
// Fallback: redirect to homepage #chronologie
header('Location: ' . SITE_URL . '/#chronologie', true, 301);
exit;
