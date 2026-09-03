<?php

// Suppress deprecations and notices from bleeding-edge PHP 8.5 runtime
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

// Forward Vercel requests to Laravel public/index.php
require __DIR__ . '/../public/index.php';
