<?php
include_once("config.inc.php");
include_once("sd_tools/tracking.php");

//1 pixel transparent image
header("Content-Type: image/png");
echo base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH4wEXCR0RNqfyewAAAAh0RVh0Q29tbWVudAD2zJa/AAAADUlEQVQI12NgYGBgAAAABQABXvMqOgAAAABJRU5ErkJggg==");
?>