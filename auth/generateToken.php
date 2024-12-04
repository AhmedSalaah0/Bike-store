<?php
$verification_token = bin2hex(random_bytes(16));
$verification_link = "http://localhost/restApi/auth/verfiy?token=" . $verification_token;