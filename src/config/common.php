<?php
return [
    'components' => [
        'cms' => [
            'smsHandlers'             => [
                'smsru' => [
                    'class' => \skeeks\cms\sms\smsru\SmsruHandler::class
                ]
            ]
        ],
    ],
];