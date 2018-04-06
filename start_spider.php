<?php

use Beanbun\Beanbun;
use Beanbun\Middleware\Parser;
use Beanbun\Lib\Db;

require_once(__DIR__ . '/vendor/autoload.php');

Db::$config['spider'] = [
    'server' => '127.0.0.1',
    'port' => '3306',
    'username' => 'root',
    'password' => 'newlife',
    'database_name' => 'spider',
    'database_type' => 'mysql',
    'charset' => 'utf8',
];

$beanbun = new Beanbun;
$beanbun->name = 'sb';
$beanbun->count = 2;
$beanbun->timeout = 10;
$beanbun->seed = [
    'http://tech.huanqiu.com/',
];
$beanbun->max = 10;
$beanbun->daemonize = true;
$beanbun->userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36';
$beanbun->urlFilter = [
    '/http:\/\/tech.huanqiu.com\/[a-z]{1,15}\/\d{4}-\d{2}\/\d{7,8}.html/',
//    '/http:\/\/tech.huanqiu.com\/it//'
];

$beanbun->setQueue('memory', [
    'name' => 'queue',
    'host' => '127.0.0.1',
    'port' => '2207',
//    'algorithm' => 'breadth'
]);
$beanbun->middleware(new Parser());
$beanbun->fields = [
    [
        'name' => 'info',
        'children' => [
            [
                'name' => 'title',
                'selector' => ['.l_a .tle', 'text'],
            ],
            [
                'name' => 'time',
                'selector' => ['.l_a .la_tool .la_t_a', 'text'],
            ],
            [
                'name' => 'content',
                'selector' => ['.l_a .la_con', 'html'],
            ],
            [
                'name' => 'description',
                'selector' => ['meta[name=description]', 'content'],
            ],
        ]
    ]
];


$beanbun->beforeDownloadPage = function ($beanbun) {
};
$beanbun->afterDownloadPage = function ($beanbun) {
//    var_dump($beanbun->data);
    if($beanbun->url!="http://tech.huanqiu.com/"){
        Db::instance('spider')->insert('news', [
            "title" => $beanbun->data['info']['title'],
            "content" => $beanbun->data['info']['content'],
            "url" => $beanbun->url,
            "description" => htmlspecialchars($beanbun->data['info']['description']),
        ]);
    }
    $beanbun->queue()->queued($beanbun->queue);
};

$beanbun->afterDiscover = function ($beanbun) {};
$beanbun->start();