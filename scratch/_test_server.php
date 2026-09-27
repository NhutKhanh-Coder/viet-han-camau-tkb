<?php
echo "PHP_SELF: " . $_SERVER["PHP_SELF"] . "\n";
echo "SCRIPT_NAME: " . $_SERVER["SCRIPT_NAME"] . "\n";
echo "REQUEST_URI: " . $_SERVER["REQUEST_URI"] . "\n";
echo "basename: " . basename($_SERVER["PHP_SELF"]) . "\n";
unlink(__FILE__);
