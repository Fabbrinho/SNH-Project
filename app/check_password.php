<?php
require 'vendor/autoload.php';

use ZxcvbnPhp\Zxcvbn;

$zxcvbn = new Zxcvbn();

$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'];

$strength = $zxcvbn->passwordStrength($password);

echo json_encode($strength);
?>