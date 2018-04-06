<?php

use Beanbun\Beanbun;
use Beanbun\Middleware\Parser;
use Beanbun\Lib\Db;

require_once(__DIR__ . '/vendor/autoload.php');
//spider实例
Db::$config['spider'] = [
    'server' => '127.0.0.1',
    'port' => '3306',
    'username' => 'root',
    'password' => 'newlife',
    'database_name' => 'spider',
    'database_type' => 'mysql',
    'charset' => 'utf8',
];

$config = Db::instance('spider')->select("config", ["id", "filter", "url_filter", "seed", "user_agent"], ["id" => 1, "LIMIT" => 1])[0];
$field = json_decode($config['filter'], true);
$beanbun = new Beanbun;
$beanbun->max = 4;
$beanbun->name = 'spider';
$beanbun->count = 2;
$beanbun->timeout = 10;
$beanbun->seed = json_decode($config['seed'], true);
$beanbun->initField = json_decode($config['seed'], true);
$beanbun->daemonize = true;
$beanbun->userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36';
$beanbun->urlFilter = json_decode($config['url_filter'], true);
//    '/http:\/\/tech.huanqiu.com\/[a-z]{1,15}\/\d{4}-\d{2}\/\d{7,8}.html/',;
$beanbun->setQueue('memory', [
    'name' => 'queue',
    'host' => '127.0.0.1',
    'port' => '2207',
//    'algorithm' => 'breadth'
]);
$beanbun->middleware(new Parser());
$beanbun->fields = $field;
//var_dump($beanbun->fields);
//$beanbun->fields = [
//    [
//        'name' => 'info',
//        'children' => [
//            [
//                'name' => 'title',
//                'selector' => ['.l_a .tle', 'text'],
//            ],
//            [
//                'name' => 'time',
//                'selector' => ['.l_a .la_tool .la_t_a', 'text'],
//            ],
//            [
//                'name' => 'content',
//                'selector' => ['.l_a .la_con', 'html'],
//            ],
//            [
//                'name' => 'description',
//                'selector' => ['meta[name=description]', 'content'],
//            ],
//        ]
//    ]
//];


$beanbun->beforeDownloadPage = function ($beanbun) {
};

$beanbun->afterDownloadPage = function ($beanbun) {
    if (!in_array($beanbun->url, $beanbun->initField)) {
        $content = [
            "title" => $beanbun->data['info']['title'],
            "content" => $beanbun->data['info']['content'],
            "url" => $beanbun->url,
            "description" => htmlspecialchars($beanbun->data['info']['description']),
        ];
        $res = Db::instance('spider')->insert('news', $content);
        if ($res) {
            $beanbun->succ_num++;
        } else {
            $beanbun->fail_num++;
//            $beanbun->log(json_encode($content)."\n");
            Db::instance('spider')->insert("fail_history", [
                "url" => $beanbun->url,
                "json" => json_encode($content)
            ]);
        }
    }
    $beanbun->rec_num++;
    $queue = $beanbun->queue()->next();
    $beanbun->queue()->queued($queue);
    if($beanbun->rec_num>=$beanbun->max){
        $beanbun->queue()->clean();
//        $beanbun->log("爬取上限已到!\n");
//        $beanbun->log("停止爬取!\n");
    }
};

$beanbun->afterDiscover = function ($beanbun) {
};

$beanbun->stopWorker = function ($beanbun) {
    $beanbun->queue()->clean();
    Db::instance('spider')->insert("history", [
        "succ_num" => $beanbun->succ_num,
        "fail_num" => $beanbun->fail_num,
        "time" => date("Y-m-d H:i:s"),
    ]);
    $beanbun->succ_num = 0;
    $beanbun->fail_num = 0;
};
$beanbun->start();