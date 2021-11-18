<?php

const letterList = ['m', 'y', 'w', 'c', 'q', 'a', 'p', 'r', 'e', 'o'];

function convertToString(int $param1): string
{
    if ($param1 < count(letterList))
    {
        return letterList[$param1];
    }
    return -1;
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
        $loc3 = $loc3 . $loc4;
    }
    return $loc3;
}

function convertStringToInt(string $param1): int
{
    return (int) convertStringToIntString($param1);
}

function AC_substr(string $string, int $startIndex, int $endIndex): string
{
    // That is a copy of Actionscript String.substr
    return substr($string, $startIndex, $endIndex - $startIndex);
}

function decode_pokeinfo(string $encoded_pokeinfo): array
{
    $pokemons = array();

    // getting the number of pokes
    $loc17 = 0;
    $loc26 = convertStringToInt($encoded_pokeinfo[$loc17++]);
    $loc14 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc26));
    $loc17 += $loc26;
    $loc26 = convertStringToInt($encoded_pokeinfo[$loc17++]);
    $loc19 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc26));
    $loc17 += $loc26;

    for ($i = 1; $i <= $loc19; $i++)
    {
        $poke = array();
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $num = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc7 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $loc31 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc7));
        $loc17 += $loc7;
        $xp = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $lvl = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $move1 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $move2 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $move3 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $move4 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17]);
        $loc23 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $gender = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc7 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $loc31 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc7));
        $loc17 += $loc7;
        $loc29 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $pos = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $shiny = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $item = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));
        $loc17 += $loc31;
        $loc31 = convertStringToInt($encoded_pokeinfo[$loc17++]);
        $loc4 = convertStringToInt(AC_substr($encoded_pokeinfo, $loc17, $loc31));

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

        $pokemons = array_push($pokemons, $poke);
    }

    return $pokemons;
}


// print_r(decode_pokeinfo('wqayyyyyymyycyaaycyceyryywqcwyrymyyyyymymymyn')); // This string is a representation of a Cyndaquil
?>
