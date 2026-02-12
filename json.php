<?php
function account_exists($email): bool
{
    $accounts = get_accounts();
    foreach ($accounts as $account)
    {
        if ($account['email'] == $email)
        {
            return true;
        }
    }
    return false;
}

function authenticate_account($email, $pass): bool
{
    $accounts = get_accounts();
    foreach ($accounts as $account)
    {
        if ($account['email'] === $email && password_verify($pass, $account['pass']))
        {
            return true;
        }
    }
    return false;
}

function get_account($email, $pass)
{
    $accounts = get_accounts();
    foreach ($accounts as $account)
    {
        if ($account['email'] === $email && password_verify($pass, $account['pass']))
        {
            return $account;
        }
    }
    return null;
}

function get_accounts(): array
{
    $accounts = array();
    if (file_exists(JSON_ACCOUNTS_FILE))
    {
        $content = file_get_contents(JSON_ACCOUNTS_FILE);
        $accounts = json_decode($content, true);
    }
    return $accounts;
}

function update_account_data($email, $pass, $new_data): bool
{
    $accounts = get_accounts();
    foreach ($accounts as $index => $account)
    {
        if ($account['email'] === $email && password_verify($pass, $account['pass']))
        {
            $accounts[$index] = array_replace_recursive($accounts[$index], $new_data);
            $data = json_encode($accounts);
            file_put_contents(JSON_ACCOUNTS_FILE, $data);
            return true;
        }
    }
    return false;
}

function delete_profile($email, $pass, $game_mode, $profile): bool
{
    $accounts = get_accounts();
    foreach ($accounts as $index => $account)
    {
        if ($account['email'] === $email && password_verify($pass, $account['pass']))
        {
            unset($accounts[$index][$game_mode][$profile]);
            $data = json_encode($accounts);
            file_put_contents(JSON_ACCOUNTS_FILE, $data);
            return true;
        }
    }
    return false;
}

function create_new_account($account)
{
    $accounts = get_accounts();
    array_push($accounts, $account);
    $data = json_encode($accounts);
    file_put_contents(JSON_ACCOUNTS_FILE, $data);
}

function get_1v1($email)
{
    $account = get_account($email, $_POST['Pass']);
    return $account['1v1'];
}

function get_story($email, $pass)
{  
    $account = get_account($email, $pass);
    return $account['story'];
}

function get_story_profile($email, $profile)
{
    $story = get_story($email, $_POST['Pass']);
    return $story["profile{$profile}"];
}

function get_available_saveID($email)
{
    $whichProfile = $_POST['whichProfile'];
    $profile = get_story_profile($email, $whichProfile);
    if (isset($profile['poke']))
    {
        return count($profile['poke']) + 1;
    }
    return 1;
}

function get_gym($email)
{
    $account = get_account($email, $_POST['Pass']);
    if (isset($account['gym_challenges']))
    {
        return $account['gym_challenges'];
    }
    return 0;
}

function get_trainerVS($email, $pass)
{
    $account = get_account($email, $pass);
    return $account['trainerVS'];
}

function get_trainerVS_opponent()
{
    $accounts = get_accounts();
    $trainerVS_profiles = array();

    foreach ($accounts as $account)
    {
        if (isset($account['trainerVS']))
        {
            $trainerVS_profiles[] = $account['trainerVS'];
        }
    }

    $index = array_rand($trainerVS_profiles);
    $trainerVS_profiles[$index]['ID'] = $index;
    return $trainerVS_profiles[$index];
}
?>
