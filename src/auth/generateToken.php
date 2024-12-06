<?php
$verification_token = bin2hex(random_bytes(16));
$verification_link = "http://localhost/bike-store/auth/verify?token=" . $verification_token;