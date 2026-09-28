<?php
// public/logout.php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/auth.php';

logoutUser();
appRedirect('/public/login.php');