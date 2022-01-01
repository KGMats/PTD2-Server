<?php

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if($mysqli->connect_error) {
    echo 'Result=Failure&Reason=DatabaseConnection';
    exit;
}

setup_database($mysqli);

function setup_database($mysqli)
{
    $create_accounts = 'CREATE TABLE IF NOT EXISTS accounts(
        email VARCHAR(50) NOT NULL,
        pass VARCHAR(255) NOT NULL,
        dex1 VARCHAR(151),
        dex2 VARCHAR(100),
        dex3 VARCHAR(135),
        dex4 VARCHAR(107),
        dex5 VARCHAR(156),
        dex6 VARCHAR(90),
        PRIMARY KEY (email)
    );';

    $create_pokes = 'CREATE TABLE IF NOT EXISTS pokes(
        owner TINYINT(1) unsigned NOT NULL,
        Nickname VARCHAR(10) NOT NULL,
        num SMALLINT(3) unsigned NOT NULL,
        saveID MEDIUMINT(7) unsigned NOT NULL,
        xp INT(7) unsigned NOT NULL,
        lvl TINYINT(3) unsigned NOT NULL,
        move1 SMALLINT(3) unsigned NOT NULL,
        move2 SMALLINT(3) unsigned NOT NULL,
        move3 SMALLINT(3) unsigned NOT NULL,
        move4 SMALLINT(3) unsigned NOT NULL,
        targetingType TINYINT(1) unsigned NOT NULL,
        gender TINYINT(1) unsigned NOT NULL,
        pos INT(5) unsigned NOT NULL,
        extra TINYINT(1) unsigned NOT NULL,
        item TINYINT(2) unsigned NOT NULL,
        tag VARCHAR(2) NOT NULL,
        email VARCHAR(50) NOT NULL,
        FOREIGN KEY (email) REFERENCES accounts (email)
    );';

    $create_story = 'CREATE TABLE IF NOT EXISTS story(
        num TINYINT(1) unsigned NOT NULL,
        Nickname VARCHAR(40) NOT NULL,
        Color TINYINT(1) unsigned NOT NULL,
        Gender TINYINT(1) unsigned NOT NULL,
        Money INT(5) unsigned NOT NULL,
        MapLoc TINYINT(3) unsigned DEFAULT 3,
        MapSpot TINYINT(3) unsigned DEFAULT 1,
        CurrentSave VARCHAR(15),
        CurrentTime TINYINT(4) unsigned NOT NULL,
        email VARCHAR(50) NOT NULL,
        FOREIGN KEY(email) REFERENCES accounts (email)
    );';
    
    $create_1v1 = 'CREATE TABLE IF NOT EXISTS 1v1(
        num TINYINT(1) unsigned NOT NULL,
        money INT(5) unsigned NOT NULL,
        levelUnlocked TINYINT(2) unsigned NOT NULL,
        email VARCHAR(50) NOT NULL,
        FOREIGN KEY(email) REFERENCES accounts (email)
    );';

    $create_items = 'CREATE TABLE IF NOT EXISTS items(
        num TINYINT(2) unsigned NOT NULL,
        value SMALLINT(2) unsigned NOT NULL,
        email VARCHAR(50) NOT NULL,
        owner TINYINT(1) unsigned NOT NULL,
        FOREIGN KEY (email) REFERENCES accounts (email)
    );';

    $create_extra = 'CREATE TABLE IF NOT EXISTS extra(
        num TINYINT(2) unsigned NOT NULL,
        value TINYINT(2) unsigned NOT NULL,
        email VARCHAR(50) NOT NULL,
        owner TINYINT(1) unsigned NOT NULL,
        FOREIGN KEY (email) REFERENCES accounts (email)
    );';
    $create_tables = $create_accounts
        . $create_story . $create_pokes 
        . $create_items . $create_extra
        . $create_1v1;

    $mysqli->multi_query($create_tables);
    while ($mysqli->next_result())
    {
        if ($result = $mysqli -> store_result())
        {
            $result -> free_result();
        }
    }
}

function account_exists(string $email)
{
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT email FROM accounts WHERE email=?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row;
}

function create_new_account(array $account): void
{
    global $mysqli;
    $stmt = $mysqli->prepare('INSERT INTO accounts (email, pass) VALUES (?,?);');
    $stmt->bind_param('ss', $account['email'], $account['pass']);
    $stmt->execute();
    $stmt->close();
}

function autenticate_account(string $email, string $pass): bool
{
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT pass FROM accounts WHERE email=?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return password_verify($pass, $row['pass']);
}

function get_story($email, $pass)
{
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT dex1, dex2, dex3, dex4, dex5, dex6 FROM accounts WHERE email=?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if (isset($row['dex1']))
    {
        $account = [
            'pokedex_1' => $row['dex1'],
            'pokedex_2' => $row['dex2'],
            'pokedex_3' => $row['dex3'],
            'pokedex_4' => $row['dex4'],
            'pokedex_5' => $row['dex5'],
            'pokedex_6' => $row['dex6'],
        ];
        $stmt = $mysqli->prepare('SELECT Nickname, Money, Color, num  FROM story WHERE email=?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc())
        {
            $extra = array();
            $extra_stmt = $mysqli->prepare('SELECT num, value  FROM extra WHERE email=? && owner=?');
            $extra_stmt->bind_param('si', $email,$row['num']);
            $extra_stmt->execute();
            $extra_stmt->close();
            $extra_result = $extra_stmt->get_result();
            while ($extra_row = $extra_result->fetch_assoc())
            {
                array_push($extra, $extra_row);
            }
            $account["profile{$row['num']}"] = [
                'Nickname' => $row['Nickname'],
                'Color' => $row['Color'],
                'Money' => $row['Money'],
                'extra' => $extra
            ];
        }
        return $account;
    }
    return null;
}

function get_story_profile($email, $whichProfile)
{
    global $mysqli;
    $profile_stmt = $mysqli->prepare('SELECT Gender, MapLoc, MapSpot, CurrentSave, CurrentTime FROM story WHERE email=? && num=?');
    $profile_stmt->bind_param('si', $email, $whichProfile);
    $profile_stmt->execute();
    $profile_stmt->close();
    $result = $profile_stmt->get_result();
    $profile = $result->fetch_array(MYSQLI_ASSOC);


    $pokes = array();
    $pokes_stmt = $mysqli->prepare('SELECT Nickname, num, saveID, xp, lvl, move1, move2, move3, move4, targetingType, gender, pos, extra, item, tag FROM pokes WHERE email=? && owner=?');
    $pokes_stmt->bind_param('si', $email, $whichProfile);
    $pokes_stmt->execute();
    $pokes_stmt->close();
    $pokes_result = $pokes_stmt->get_result();

    while ($poke = $pokes_result->fetch_array(MYSQLI_ASSOC))
    {
        array_push($pokes, $poke);
    }

    $extra = array();
    $extra_stmt = $mysqli->prepare('SELECT num, value  FROM extra WHERE email=? && owner=?');
    $extra_stmt->bind_param('si', $email, $whichProfile);
    $extra_stmt->execute();
    $extra_stmt->close();
    $extra_result = $extra_stmt->get_result();
    while ($extra_row = $extra_result->fetch_array(MYSQLI_NUM))
    {
        $extra[$extra_row[0]] = $extra_row[1];
    }

    $items = array();
    $items_stmt = $mysqli->prepare('SELECT num, value  FROM extra WHERE email=? && owner=?');
    $items_stmt->bind_param('si', $email, $whichProfile);
    $items_stmt->execute();
    $items_stmt->close();
    $items_result = $items_stmt->get_result();
    while ($item_row = $items_result->fetch_array(MYSQLI_NUM))
    {
        $items[$items_row[0]] = $items_row[1];
    }

    $profile['poke'] = $pokes;
    $profile['extra'] = $extra;
    $profile['items'] = $items;
    return $profile;
}

function update_account_data($email, $pass, $new_data)
{
    global $mysqli;
    if (!autenticate_account($email, $pass))
    {
        return false;
    }

    if (isset($new_data['story']))
    {
        $save_query = 'UPDATE story SET ';
        $save_params['types'] = '';
        $save_params['values'] = [];

        $whichProfile = array_keys($new_data['story'])[0][-1];
        if (isset($new_data['story']['pokedex_1']))
        {
            $stmt = $mysqli->prepare('UPDATE accounts SET dex1=?, dex2=?, dex3=?, dex4=?, dex5=?, dex6=? WHERE email=?');
            $stmt->bind_param('sssssss',
                $new_data['story']['pokedex_1'], $new_data['story']['pokedex_2'],
                $new_data['story']['pokedex_3'], $new_data['story']['pokedex_4'],
                $new_data['story']['pokedex_5'], $new_data['story']['pokedex_6'],
                $email
            );
            $stmt->execute();
            $stmt->close();
        }

        foreach ($new_data['story']["profile{$whichProfile}"] as $key => $value)
        {
            switch ($key)
            {
            case 'Nickname':
                // New account
                $new_acc_query = 'INSERT INTO story
                    (num, Nickname, Color, Gender, Money, CurrentTime, email)
                    VALUES (?,?,?,?,?,?,?)';
                $new_acc_stmt = $mysqli->prepare($new_acc_query);
                $new_acc_stmt->bind_param('isiiiis',
                    $whichProfile,
                    $value,
                    $new_data['story']["profile{$whichProfile}"]['Color'],
                    $new_data['story']["profile{$whichProfile}"]['Gender'],
                    $new_data['story']["profile{$whichProfile}"]['Money'],
                    $new_data['story']["profile{$whichProfile}"]['CurrentTime'],
                    $email);
                $new_acc_stmt->execute();
                $new_acc_stmt->close();
                break;
            case 'items':
            case 'extra':
                foreach ($value as $item_num => $quantity)
                {
                   $stmt = $mysqli->prepare("UPDATE $key SET value=? where email=? && num=? && owner=?");
                    $stmt->bind_param('isii', $quantity, $email, $item_num, $whichProfile);
                    $stmt->execute();
                    $stmt->close();
                   if ($stmt->affected_rows === 0)
                   {
                       $stmt = $mysqli->prepare("insert into $key values (?, ?, ?, ?)");
                       $stmt->bind_param('iisi', $item_num, $quantity, $email, $whichProfile);
                       $stmt->execute();
                   }
                }
                unset($new_data['story']["profile{$whichProfile}"][$key]);
                break;
            case 'poke':
                foreach ($value as $poke)
                {
                    $saveID = $poke['saveID'];
                    unset($poke['saveID']);
                    if (count($poke) >= 10) // Captured
                    {
                        $pokes_stmt = $mysqli->prepare('INSERT INTO pokes VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $pokes_stmt->bind_param('isiiiiiiiiiiiiiss',
                            $whichProfile, $poke['Nickname'], $poke['num'],
                            $saveID, $poke['xp'], $poke['lvl'],
                            $poke['move1'], $poke['move2'], $poke['move3'], $poke['move4'],
                            $poke['targetingType'], $poke['gender'], $poke['pos'],
                            $poke['extra'], $poke['item'], $poke['tag'], $email,
                        );
                    }
                    else if (isset($poke))
                    {
                        $pokes_query = 'UPDATE pokes SET ';
                        $params = [
                            '',  // [0] = type (i,s,b etc)
                            []]; // [1] = properties values
                        foreach ($poke as $property_name => $property_value)
                        {
                            $pokes_query .= "{$property_name}=?, ";
                            $params[0] .= is_int($property_value) ? 'i' : 's';
                            array_push($params[1], $property_value);
                        }
                        $pokes_query = substr($pokes_query, 0, -2) . ' WHERE saveID=? && email=? && owner=?';
                        array_push($params[1], $saveID);
                        array_push($params[1], $email);
                        array_push($params[1], $whichProfile);
                        $params[0] .= 'isi';
                        $pokes_stmt = $mysqli->prepare($pokes_query);
                        $pokes_stmt->bind_param($params[0], ...$params[1]);
                    }
                    if (isset($pokes_stmt))
                    {
                        $pokes_stmt->execute();
                    }
                }
                break;
            default:
                $save_query .= "$key=?, ";
                $save_params['types'] .= 'i';
                array_push($save_params['values'], $value);
            }
        }
        $save_query = substr($save_query, 0, -2) . ' WHERE email=? && num=?';
        $save_stmt = $mysqli->prepare($save_query);
        $save_params['types'] .= 'si';
        array_push($save_params['values'], $email);
        array_push($save_params['values'], $whichProfile);
        $save_stmt->bind_param($save_params['types'], ...$save_params['values']);
        $save_stmt->execute();
        $save_stmt->close();
    } else if (isset($new_data['1v1']))
    {
        $whichProfile = array_keys($new_data['1v1'])[0][-1];
        $new_data = $new_data['1v1']["profile{$whichProfile}"];
        $save_query = 'UPDATE 1v1 SET money=?, levelUnlocked=? where email=? && num=?';
        $save_stmt = $mysqli->prepare($save_query);
        $save_stmt->bind_param('iisi', $new_data['money'], $new_data['levelUnlocked'], $email, $whichProfile);
        $save_stmt->execute();
        $save_stmt->close();
        if ($save_stmt->affected_rows === 0)
        {
            $save_stmt = $mysqli->prepare('INSERT INTO 1v1 values (?, ?, ?, ?)');
            $save_stmt->bind_param('iiis', $whichProfile, $new_data['money'], $new_data['levelUnlocked'], $email);
            $save_stmt->execute();
            $save_stmt->close();
        }
    }
    return true;
}

function get_1v1($email)
{
    global $mysqli;
    $versus_stmt = $mysqli->prepare('SELECT money, levelUnlocked, num FROM 1v1 WHERE email=?');
    $versus_stmt->bind_param('s', $email);
    $versus_stmt->execute();
    $versus_stmt->close();
    while ($result = $versus_stmt->get_result())
    {
        $profile = $result->fetch_assoc();
        if ($profile)
        {
            $profiles["profile{$profile['num']}"] = $profile;
        }
    }
    return $profiles;
}

function get_avaliable_saveID($email)
{
    global $mysqli;
    $profile = $_POST['whichProfile'];
    $stmt = $mysqli->prepare('select MAX(saveID) from pokes where email=? && owner=?');
    $stmt->bind_param('si', $email, $profile);
    $stmt->execute();
    $stmt->close();
    $result = $stmt->get_result();
    $avaliable_id = $result->fetch_array(MYSQLI_NUM)[0];
    if (is_null($avaliable_id))
    {
        $avaliable_id = 1;
    } else {
        $avaliable_id++;
    }
    return $avaliable_id;
}

function delete_profile($email, $pass, $gamemode, $profile)
{
    if (!autenticate_account($email, $pass))
    {
        return false;
    }

    global $mysqli;
    $profile = $profile[7];

    switch ($gamemode) {
        case 'story':
            $delete_stmt = $mysqli->prepare('
            DELETE extra, items, pokes, story FROM extra
            INNER JOIN (items, pokes, story)
            ON (extra.email=? AND extra.owner=? AND items.email=? AND items.owner=?
            AND pokes.email=? AND pokes.owner=? AND story.email=? AND story.num=?)
                ');
            $delete_stmt->bind_param('sisisisi',
                $email, $profile, $email, $profile,
                $email, $profile, $email, $profile);
            break;

        case '1v1':
            $delete_stmt = $mysqli->prepare('DELETE FROM 1v1 WHERE email=? && num=?');
            $delete_stmt->bind_param('si', $email, $profile);
            break;
        default:
            return false;
            break;

    }
    $delete_stmt->execute();
    $delete_stmt->close();
    return true;
}
?>
