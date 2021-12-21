<?php
header('Content-Type: application/x-www-form-urlencoded');

if(isset($_POST['debug']))
{
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require_once '../../json.php';
require_once '../../obfuscation.php';

function create_account($email, $pass): string
{
    if (account_exists($email))
    {
        return 'Result=Failure&Reason=taken';
    }
    $accounts = get_accounts();
    $account = ['email' => $email, 'pass' => password_hash($pass, PASSWORD_DEFAULT)];
    array_push($accounts, $account);
    $data = json_encode($accounts);
    file_put_contents('../../accounts.json', $data);
    return load_account($email, $pass);
}

function load_account($email, $pass): string
{
    if (autenticate_account($email, $pass))
    {
        return 'Result=Success&Reason=loadedAccount&p=1'; // Trainer pass for everyone!
    }
    return 'Result=Failure&Reason=NotFound';
}


function load_story($email, $pass): string
{
    $account = get_account($email, $pass);
    $new_account = true;
    if ($account)
    {
        $result = 'Result=Success&';
        if (isset($account['story']))
        {
            $result .= 'extra=' . encode_story($account['story']);
            $result .= '&dw=' . date('w');
            for ($gen = 1; $gen <= 6; $gen++)
            {
                $result .= "&dextra$gen=" . $account['story']["pokedex_$gen"];
                $result .= "&dcextra$gen=" . convertIntToString(create_Check_Sum($account['story']["pokedex_$gen"]));
            }
            for ($i = 1; $i <= 3; $i++)
            {
                if (array_key_exists("profile{$i}", $account['story']))
                {
                    $profile = $account['story']["profile{$i}"];
                    $result .= "&Nickname{$i}={$profile['Nickname']}&";
                    $result .= "Version{$i}={$profile['Color']}";
                }
            }
            return $result;
        }
        return 'Result=Success&extra=ycm';
    }
    return 'Result=Failure&Reason=NotFound';
}

function load_story_profile($email, $pass): string
{
    $result = '';
    $account = get_account($email, $pass);
    if ($account)
    {
        $whichProfile = $_POST['whichProfile'];
        $profile = $account['story']["profile{$whichProfile}"];
        $encoded_data = encode_story_profile($profile);
        $result .= "CS={$profile['CurrentSave']}&";
        $result .= "CT={$profile['CurrentTime']}&";
        $result .= "Gender={$profile['Gender']}&";
        $result .= "extra={$encoded_data[0]}&";
        $result .= "extra2={$encoded_data[1]}&";
        $result .= "extra3={$encoded_data[2]}&";
        $result .= "extra4={$encoded_data[3]}&";
        $result .= "extra5={$encoded_data[4]}";
        foreach ($profile['poke'] as $id => $poke)
        {
            $tmp = $poke['pos'] + 1;
            $result .= "&PN{$tmp}={$poke['Nickname']}";
        }
        $result = 'Result=Success&' . $result;
        return $result;
    }
    return 'Result=Failure&Reason=NotFound';
}

function save_story($email, $pass): string
{
    $new_data = array();
    $whichProfile = $_POST['whichProfile'];
    parse_str($_POST['extra'], $save_info);


    if(isset($save_info['NewGameSave']))
    {
        $profile = array();
        $new_data['story']["profile{$whichProfile}"] = ['Nickname' => $save_info['Nickname'],
            'Color' => $save_info["Color"],
            'Gender' => $save_info["Gender"],
            'Money' => 10,
            'CurrentTime' => 100];
    }
    else
    {
        $profile = get_account($email, $pass)['story']["profile{$whichProfile}"];
    }

    if(isset($save_info['MapSave']))
    {
        $new_data['story']["profile{$whichProfile}"]['MapLoc'] = $save_info['MapLoc'];
        $new_data['story']["profile{$whichProfile}"]['MapSpot'] = $save_info['MapSpot'];
    }

    if(isset($save_info['MSave']))
    {
        $new_data['story']["profile{$whichProfile}"]['Money'] = convertStringToInt($save_info['MA']);
    }

    if(isset($save_info['CS']))
    {
        $new_data['story']["profile{$whichProfile}"]['CurrentSave'] = $save_info['CS'];
    }

    if(isset($save_info['TimeSave']))
    {
        $new_data['story']["profile{$whichProfile}"]['CurrentTime'] = $save_info['CT'];
    }

    if(isset($_POST['needD']))
    {
        for ($gen = 1; $gen <= 6; $gen++)
        {
            $new_data['story']["pokedex_{$gen}"] = $_POST["dextra{$gen}"];
        }
    }

    $pokes = decode_pokeinfo($_POST['extra3'], $profile);
    $counter = 1;
    foreach ($pokes as $key => $poke)
    {
        if (isset($poke['needNickname']))
        {
            $nickname = $save_info["PokeNick{$counter}"];
            $pokes[$key]['Nickname'] = $nickname;
            unset($pokes[$key]['needNickname']);
        }
        $counter++;
    }

    $items = decode_inventory($_POST['extra4']);
    $extra = decode_extra($_POST['extra2']);

    $new_data['story']["profile{$_POST["whichProfile"]}"]['poke'] = $pokes;
    $new_data['story']["profile{$_POST["whichProfile"]}"]['extra'] = $extra;
    $new_data['story']["profile{$_POST["whichProfile"]}"]['items'] = $items;
    update_account_data($email, $pass, $new_data);
    return 'Result=Success';
}

function delete_story(string $email, string $pass): string
{
    $whichProfile = $_POST['whichProfile'];
    if (delete_profile($email, $pass, 'story', "profile{$whichProfile}")) {
        return 'Result=Success';
    }
    return 'Result=Failure&Reason=NotFound';
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
case 'loadStoryProfile':
    echo load_story_profile($email, $pass);
    break;
case 'deleteStory':
    echo delete_story($email, $pass);
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
