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
    $accounts_file = '../../accounts.json';
    $accounts = array();
    if (file_exists($accounts_file))
    {
        $content = file_get_contents($accounts_file);
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
            file_put_contents('../../accounts.json', $data);
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
            file_put_contents('../../accounts.json', $data);
            return true;
        }
    }
    return false;
}
?>
