<?php
require 'vendor/autoload.php';

use ZxcvbnPhp\Zxcvbn;

$zxcvbn = new Zxcvbn();

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['password']) || !is_string($data['password'])) {
    die(json_encode(['error' => 'Invalid input']));
}
$password = trim($data['password']);


$strength = $zxcvbn->passwordStrength($password);

echo json_encode($strength);
?>