<?php
require_once __DIR__ . '/../config/sessions.php';
destroy_session();
header("Location: " . BASE_URL . "index.php?msg=logged_out");
exit();