<?php
header('Content-Type: application/x-www-form-urlencoded');

if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
    exit();
}

if(isset($_POST['debug']))
{
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require_once '../config.php';
require_once ROOT_DIR . '/obfuscation.php';

if (STORAGE_METHOD === 'MYSQL')
{
    require_once ROOT_DIR . '/MySQL.php';
}
else
{
    require_once ROOT_DIR . '/json.php';
}


function loadGym($email, $pass)
{
    $gym_beaten = get_gym($email);
    $encoded_data = encode_gym($gym_beaten);
    return "Result=Success&extra=$encoded_data";
}


function saveGym($email, $pass)
{
    $new_data['gym_challenges'] = decode_gym($_POST['extra']);
    if (update_account_data($email, $pass, $new_data))
    {
        return 'Result=Success';
    }
    return 'Result=Failure&Reason=NotFound';
}

$email = $_POST['Email'];
$pass = $_POST['Pass'];

switch ($_POST['Action'])
{
    case 'loadGym':
        echo loadGym($email, $pass);
        break;
    
    case 'saveGym':
        echo saveGym($email, $pass);
        break;
}
?>
