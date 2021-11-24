<?php

const letterList = ['m', 'y', 'w', 'c', 'q', 'a', 'p', 'r', 'e', 'o'];

function convertToString(int $param1): string
{
    if ($param1 < count(letterList))
    {
        return letterList[$param1];
    }
    return '-1';
}

function convertToInt(string $param1): string
{
    for ($i = 0; $i < count(letterList); $i++)
    {
        if (strcmp($param1,letterList[$i]) === 0)
        {
            return "$i";
        }
    }
    return '-1';
}

function convertStringToIntString(string $param1): string
{
    $loc3 = '';
    for ($i = 0; $i < strlen($param1); $i++)
    {
        $loc4 = convertToInt($param1[$i]);
        if ($loc4 === '-1')
        {
            return '-100';
        }
        $loc3 .= $loc4;
    }
    return $loc3;
}

function convertStringToInt(string $param1): int
{
    return (int) convertStringToIntString($param1);
}

function convertIntToString(int $num)
{
    $result = '';
    $str_int = "$num";
    for ($i = 0; $i < strlen($str_int); $i++)
    {
        $loc5 = convertToString((int) $str_int[$i]);
        if ($loc5 === '-1')
        {
            return '-100';
        }
        $result .= $loc5;
    }
    return $result;
}

function get_Length(int $param1, int $param2): string
{
    $loc3 = $param1 + $param2 + 2;
    $loc5 = "$loc3";
    $loc4 = strlen($loc5);

    if ($loc4 != $param1)
    {
        return get_Length($loc4, $param2);
    }
    return "$loc4$loc5";
}
function decode_pokeinfo(string $encoded_pokeinfo): array
{
    $pokemons = array();

    // getting the number of pokes
    $loc17 = 0;
    $loc26 = convertStringToInt($encoded_pokeinfo[$loc17++]);
    $loc14 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc26));
    $loc17 += $loc26;
    $loc26 = convertStringToInt($encoded_pokeinfo[$loc17++]);
    $loc19 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc26));
    $loc17 += $loc26;

    for ($i = 1; $i <= $loc19; $i++)
    {
        $poke = array();
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $num = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc7 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $loc31 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc7));
        $loc17 += $loc7;
        $xp = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $lvl = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $move1 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $move2 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $move3 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $move4 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17]);
        $loc23 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $gender = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc7 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $loc31 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc7));
        $loc17 += $loc7;
        $loc29 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $pos = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $shiny = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $item = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $loc4 = convertStringToInt(substr($encoded_pokeinfo, $loc17, $loc31));

        $poke['num'] = $num;
        $poke['gender'] = $gender;
        $poke['experience'] = $xp;
        $poke['move1'] = $move1;
        $poke['move2'] = $move2;
        $poke['move3'] = $move3;
        $poke['move4'] = $move4;
        $poke['shiny'] = $shiny;
        $poke['saveID'] = $loc29;
        $poke['targetingType'] = $loc23;
        $poke['pos'] = $pos;
        $poke['myTag'] = $loc4;
        $poke['item'] = $item;
        $poke['lvl'] = $lvl;

        array_push($pokemons, $poke);
    }

    return $pokemons;
}


function decode_1v1(string $encoded_data): array
{
    $data = array();

    // Getting the size of data
    $tmp1 = 0;
    $len1 = convertStringToInt($encoded_data[$tmp1++]);
    $len2 = convertStringToInt(substr($encoded_data, $tmp1, $len1));
    $tmp1 += $len1;


    $money_len = convertStringToInt($encoded_data[$tmp1++]);
    $money = convertStringToInt(substr($encoded_data, $tmp1, $money_len));
    $tmp1 += $money_len;
    $levels_lenght = convertStringToInt($encoded_data[$tmp1++]);
    $levels_unlocked = convertStringToInt(substr($encoded_data, $tmp1, $levels_lenght));

    $data['money'] = $money;
    $data['levelUnlocked'] = $levels_unlocked;


    return $data;
}

function encode_1v1(array $profiles): string
{
    $encoded_data = '';
    $PA = 0;

    foreach ($profiles as $key => $profile)
    {
        $PA++;
        $whichProfile = convertIntToString($key[7]);
        $encoded_money = convertIntToString($profile['money']);
        $money_len = convertIntToString(strlen($encoded_money));

        $encoded_levels = convertIntToString($profile['levelUnlocked']);
        $levels_len = convertIntToString(strlen($encoded_levels));

        $encoded_data .= $whichProfile . $money_len . $encoded_money . $levels_len . $encoded_levels;
    }
    $PA = convertIntToString($PA);
    $data_len = strlen($encoded_data);
    $data_len_len = strlen($data_len); // WTF
    $encoded_len = convertIntToString(get_Length($data_len_len, $data_len));

    $encoded_data = $encoded_len . $PA . $encoded_data;

    return $encoded_data;
}
?>
