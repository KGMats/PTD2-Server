<?php

require_once '../../json.php';
require_once '../../obfuscation.php';

function create_account($email, $pass): string
{
    if (account_exists($email))
    {
        return 'Result=Failure&Reason=taken';
    }
    $accounts = get_accounts();
    $account = ['email' => $email, 'pass' => $pass];
    array_push($accounts, $account);
    $data = json_encode($accounts);
    file_put_contents('../../accounts.json', $data);
    return load_account($email, $pass);
}

function load_account($email, $pass): string
{
    if (get_account($email, $pass))
    {
        return 'Result=Success&Reason=loadedAccount';
    }
    return 'Result=Failure&Reason=NotFound';
}


function load_story($email, $pass): string
{
    # For now this function is not complete, so the game does not recognize
    # the response as a valid response, softlocking the game on the
    # "Got a response from server" popup
    $account = get_account($email, $pass);
    $new_account = true;
    for ($i = 1; $i <= 3; $i++)
    {
        if (array_key_exists("profile{$i}", $account))
        {
            $new_account = false;
            $profile = $account["profile{$i}"];
            echo "PN{$i}={$profile['Nickname']}&";
            echo 'extra=ycm&';

            echo 'dw=' . Date('w') . '&';

            echo "dextra1={$profile['pokedex_1']}&";
            echo "dextra2={$profile['pokedex_2']}&";
            echo "dextra3={$profile['pokedex_3']}&";
            echo "dextra4={$profile['pokedex_4']}&";
            echo "dextra5={$profile['pokedex_5']}&";
            echo "dextra6={$profile['pokedex_6']}";
        }
    }
    if ($new_account)
    {
        return 'Result=Success&extra=ycm';
    }
}


function save_story($email, $pass): string
{
    $new_data = array();
    $whichProfile = $_POST['whichProfile'];
    parse_str($_POST['extra'], $save_info);


    if(isset($save_info['NewGameSave']))
    {
        $new_data["profile{$whichProfile}"] = ["Nickname" => $save_info['Nickname'], "Color" => $save_info["Color"], "Gender" => $save_info["Gender"]];
    }

    if(isset($save_info['MapSave']))
    {
        $new_data["profile{$whichProfile}"]['MapLoc'] = $save_info['MapLoc'];
        $new_data["profile{$whichProfile}"]['MapSpot'] = $save_info['MapSpot'];
    }

    if(isset($save_info['MSave']))
    {
        $new_data["profile{$whichProfile}"]['Money'] = $save_info['MA'];
    }

    if(isset($save_info['TimeSave']))
    {
        $new_data["profile{$whichProfile}"]['CurrentTime'] = $save_info['CT'];
    }

    if(isset($_POST['needD']))
    {
        for ($gen = 1; $gen <= 6; $gen++)
        {
            $new_data["profile{$_POST["whichProfile"]}"]["pokedex_{$gen}"] = $_POST["dextra{$gen}"];
        }
    }

    $new_data["profile"]['poke'] = decode_pokeinfo($_POST['extra3']);
    $new_data['inventory'] = decode_inventory($_POST['extra4']);


    update_account_data($email, $pass, $new_data);
    return 'Result=Success';
}

function load_1v1($email, $pass)
{
    $account = get_account($email, $pass);
    if (isset($account['1v1']))
    {
        $encoded_data = encode_1v1($account['1v1']);
        return "Result=Success&extra=$encoded_data&extra2=yqym";
    }
    return 'Result=Success&extra=ycm&extra2=yqym';
}

function save_1v1($email, $pass): string
{
    $whichProfile = $_POST['whichProfile'];
    $new_data['1v1']["profile{$whichProfile}"] = decode_1v1($_POST['extra']);
    if (update_account_data($email, $pass, $new_data))
    {
        return 'Result=Success&Reason=loadedAccount';
    }
    return 'Result=Failure&Reason=NotFound';
}

function delete_1v1(string $email, string $pass): string
{
    $whichProfile = $_POST['whichProfile'];
    delete_profile($email, $pass, '1v1', "profile{$whichProfile}");
    return 'Result=Success';
}


$email = $_POST['Email'];
$pass = $_POST['Pass'];

switch ($_POST['Action'])
{
case 'createAccount':
    echo create_account($email, $pass);
    break;
case 'loadAccount':
    echo load_account($email, $pass);
    break;
case 'loadStory':
    echo load_story($email, $pass);
    break;
case 'saveStory':
    echo save_story($email, $pass);
    break;
case 'load1on1':
    echo load_1v1($email, $pass);
    break;
case 'save1on1':
    echo save_1v1($email, $pass);
    break;
case 'delete1on1':
    echo delete_1v1($email, $pass);
    break;
}
?>
