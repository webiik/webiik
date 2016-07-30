<?php
namespace Webiik;

/**
 * Class Translation
 * @package     Webiik
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2016 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
class Translation
{
    // Todo: Translations/localization (units)

    private $translation = [
        't1' => 'Dnes {timeStamp, date, short} mám {numCats, number} {numCats, plural, 0:koček 1: kočku 2-4:kočky 5+:koček}',
        't2' => 'Auto jede rychlostí {speed, conv, km/h, mph} km/h',
    ];
}