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


function loadTrainerVS($email, $pass)
{
    $trainer = get_trainerVS($email, $pass);
    $response = [
        'Result' => 'Success',
        'nick' => 'Satoshi', // Default nickname
        'wa' => ''
    ];

    if ($trainer) 
    {

        $response = [
            'Result' => 'Success',
            'nick' => $trainer['Nickname'],
            'wa' => $trainer['avatar']
        ];
    }


    return http_build_query($response);
}

// Saves user trainerVS team. Sends opponent team as response.
function saveTrainerVS($email, $pass)
{
    $encoded_pkm = $_POST['extra'];
    $checksum = $_POST['extra2'];
    $needsSave = $_POST['extra3'];
    $misc = decode_trainervs_misc($_POST['extra5']);
    $nickname = $_POST['nickname'];


    $new_data['trainerVS']['poke'] = decode_trainervs_pokeinfo($encoded_pkm, $email);
    $new_data['trainerVS']['Nickname'] = $nickname;
    $new_data['trainerVS']['avatar'] = $misc['avatar'];
    $new_data['trainerVS']['wins'] = $misc['wins'];
    $new_data['trainerVS']['loses'] = $misc['loses'];



    if (update_account_data($email, $pass, $new_data))
    {
        $opponent = get_trainerVS_opponent();

        $response = [
            'Result' => 'Success',
            'nick' => $opponent['Nickname'],
            'extra' => encode_trainervs_profile($opponent)
        ];

        return http_build_query($response);
    }

    return 'Result=Failure&Reason=hacking';
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
    case 'loadTrainerVS':
        echo loadTrainerVS($email, $pass);
        break;
    case 'saveTrainerVS':
        echo saveTrainerVS($email, $pass);
        break;
}
?>
