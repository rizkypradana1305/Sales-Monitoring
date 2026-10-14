<?php
require_once 'config.php';
if (is_logged_in()) logout();
redirect('login.php');
