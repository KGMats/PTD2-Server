<?php
function account_exists($email)
{
    $accounts_file = "../../accounts.json";
    if (file_exists($accounts_file))
    {
        $content = file_get_contents($accounts_file);
        $accounts = json_decode($content, true);
        foreach ($accounts as $account)
        {
            if ($account['email'] == $email) 
            {
                return true;
            }
        }
    }
    return false;
}

function get_account($email, $pass)
{
    $accounts = get_accounts();
    foreach ($accounts as $account)
    {
        if ($account['email'] === $email && $account['pass'] === $pass) 
        {
            return $account;
        }
    }
    return null;
}

function get_accounts()
{
    $accounts_file = "../../accounts.json";
    $accounts = array();
    if (file_exists($accounts_file))
    {
        $content = file_get_contents($accounts_file);
        $accounts = json_decode($content, true);
    }
    return $accounts;
}

function update_account_data($email, $pass, $new_data): void
{
    $accounts = get_accounts();
    foreach ($accounts as $index => $account)
    {
        if ($account['email'] === $email && $account['pass'] === $pass) 
        {
            $accounts[$index] = array_merge($accounts[$index], $new_data);
            
            $data = json_encode($accounts);
            $handle = fopen("../../accounts.json", "w");
            fwrite($handle, $data);
            fclose($handle);
            break;
        }
    }
}
?>
