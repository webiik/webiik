<?php
return [
    'routes' => [
        '/' => '/',
        '/account' => '/account',
    ],
    'seo' => [
        '/' => [
            'title' => '',
            'kw' => '',
            'desc' => '',
        ],
    ],
    'shared' => [
        'key' => 'asd',
    ],
    'formats' => [
        'date' => [
            'default' => 'j. M Y'
        ],
        'time' => [
            'default' => 'H:i:s'
        ],
        'number' => [
            'default' => [2, ',', ' ']
        ],
        'currency' => [
            'usd' => '$ %i'
        ],
        'monthsLong' => [
            'January' => 'January',
            'February' => 'February',
            'March' => 'March',
            'April' => 'April',
            'May' => 'May',
            'June' => 'June',
            'July' => 'July',
            'August' => 'August',
            'September' => 'September',
            'October' => 'October',
            'November' => 'November',
            'December' => 'December',
        ],
        'monthsShort' => [
            'Jan' => 'Jan',
            'Feb' => 'Feb',
            'Mar' => 'Mar',
            'Apr' => 'Apr',
            'May' => 'May',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Aug',
            'Sep' => 'Sep',
            'Oct' => 'Oct',
            'Nov' => 'Nov',
            'Dec' => 'Dec',
        ],
        'daysLong' => [
            'Monday' => 'Monday',
            'Tuesday' => 'Tuesday',
            'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday',
            'Friday' => 'Friday',
            'Saturday' => 'Saturday',
            'Sunday' => 'Sunday',
        ],
        'daysShort' => [
            'Mon' => 'Mon',
            'Tue' => 'Tue',
            'Wed' => 'Wed',
            'Thu' => 'Thu',
            'Fri' => 'Fri',
            'Sat' => 'Sat',
            'Sun' => 'Sun',
        ],
    ],
];