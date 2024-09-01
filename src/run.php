<?php
require_once __DIR__ . '/vendor/autoload.php';

$allMarkdown = [
    'environment.md',
    'getting_started/extension.md',
    'start/start_server.md',
    'start/start_tcp_server.md',
    'start/start_udp_server.md',
    'start/start_http_server.md',
    'start/start_ws_server.md',
    'start/start_mqtt.md',
    'start/start_task.md',
    'start/coroutine.md',
    'server/init.md',
    'server/tcp_init.md',
    'server/methods.md',
    'server/properties.md',
    'server/setting.md',
    'server/events.md',
    'server/task_class.md',
    'server/packet_class.md',
    'server/pipemessage_class.md',
    'server/statusinfo_class.md',
    'server/taskresult_class.md',
    'server/event_class.md',
    'server/server_port.md',
    'http_server.md',
    'websocket_server.md',
    'redis_server.md',
    'server/port.md',
    'server/co_init.md',
    'coroutine/server.md',
    'coroutine/http_server.md',
    'coroutine/ws_server.md',
    'client_init.md',
    'client.md',
    'coroutine_client/init.md',
    'coroutine_client/client.md',
    'coroutine_client/socket.md',
    'coroutine_client/http_client.md',
    'coroutine_client/http2_client.md',
    'coroutine_client/postgresql.md',
    'coroutine_client/fastcgi.md',
    'coroutine_client/mysql.md',
    'coroutine_client/redis.md',
    'coroutine.md',
    'runtime.md',
    'coroutine/coroutine.md',
    'coroutine/scheduler.md',
    'coroutine/system.md',
    'coroutine/proc_open.md',
    'coroutine/channel.md',
    'coroutine/wait_group.md',
    'coroutine/barrier.md',
    'coroutine/multi_call.md',
    'coroutine/conn_pool.md',
    'library.md',
    'coroutine/gdb.md',
    'coroutine/notice.md',
    'timer.md',
    'process/process.md',
    'process/process_pool.md',
    'process/process_manager.md',
    'memory/table.md',
    'memory/atomic.md',
    'memory/lock.md',
    'event.md',
    'question/install.md',
    'question/use.md',
    'question/swoole.md',
    'consts.md',
    'other/errno.md',
    'other/config.md',
    'functions.md',
    'other/tools.md',
    'other/alias.md',
    'other/issue.md',
    'other/sysctl.md',
    'other/signal.md',
    'other/discussion.md',
    'CONTRIBUTING.md',
    'other/donate.md',
    'case.md',
    'version/supported.md',
    'version/bc.md',
    'version/log.md',
    'learn.md',
    'getting_started/notice.md',
    'learn_other.md',
    'blog_list.md',
];


class GenSwooleDocset
{
    private $swooleWiki = 'http://wiki.swoole.com/';

    private $md   = [];
    private $tags = [];

    public function __construct($md)
    {
        $this->md = $md;

        if (!is_dir("./md")) {
            $this->mkdir("md");
        }

        if (!is_dir("./html")) {
            $this->mkdir("html");
        }
    }

    public function run()
    {
        $result = [];

        foreach ($this->md as $_md) {
            list($mdFile, $content) = $this->getMarkdown($_md);
            $htmlFile = $this->genHtml($_md, $content);

            $result[$_md] = [
                'html'   => $htmlFile,
                'md'     => $mdFile,
                'tag'    => $this->tags[$_md] ?? [],
                "prefix" => trim($_md, ".md")
            ];
        }

        return $result;
    }

    private function mkdir($folder)
    {
        $folder = trim($folder);
        $old    = umask(0);
        mkdir($folder);
        umask($old);

        return true;
    }

    public function genSql($result)
    {
        $sqlTpl = "INSERT INTO `searchIndex`(name, type, path) VALUES ('{KEYWORD}','Keyword','{HTML-TPL}')";

        $sqls = [];
        foreach ($result as $node) {
            $mdName   = strtr($node['md'], [
                "./md/" => '',
                '.md'   => '',
                "12"    => ':'
            ]);
            $htmlPath = str_replace("./html/", "", $node['html']);
            $sqls[]   = strtr($sqlTpl, [
                "{KEYWORD}"  => $mdName,
                "{HTML-TPL}" => $htmlPath
            ]);

            if (!isset($node['tag']) || empty($node['tag'])) {
                continue;
            }

            foreach ($node['tag'] as $tag) {
                $sqls[] = strtr($sqlTpl, [
                    "{KEYWORD}"  => $mdName . "\\" . $tag,
                    "{HTML-TPL}" => $htmlPath . "#" . $tag
                ]);
            }

        }

        file_put_contents("docset.sql", implode(";\n", $sqls));

        return true;
    }

    private function getMarkdown($fileName)
    {
        $file = './md/' . str_replace("/", "12", $fileName);

        $fileUrl = $this->swooleWiki . 'zh-cn/' . $fileName;
        echo $fileUrl , PHP_EOL;
        $content = file_get_contents($fileUrl);

        $lines = explode("\n", $content);
        $lines = array_filter($lines);

        $newContent = [];
        foreach ($lines as $line) {
            $tag = $this->hasTag($line);
            if ($tag !== false) {
                $newContent[]            = '<a name="' . $tag . '"></a>';
                $this->tags[$fileName][] = $tag;
            }
            $newContent[] = $line;
        }

        file_put_contents($file, implode("\n", $newContent));

        return [$file, implode("\n", $newContent)];
    }

    private function hasTag($line)
    {
        if (stripos($line, "##") === 0) {
            $line = trim($line, "# ");
            $line = str_replace("()", "", $line);
            //if (preg_match("/^[a-zA-Z].*/", $line)) {
            return $line;
            //}
        }
        return false;
    }

    private function genHtml($fileName, $content)
    {
        $fileName = str_replace(".md", ".html", $fileName);
        $htmlFile = './html/' . str_replace("/", "12", $fileName);
        $logic    = new Parsedown();
        $logic->setMarkupEscaped(false);
        $logic->setSafeMode(false);
        $htmlBody    = $logic->text($content);
        $htmlContent = <<<HTMLCONTENT
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>Swoole4 文档</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta name="description" content="由Swoole官方提供的Swoole4系列全量新文档">
    <meta name="keywords" content="php,协程,swoole,swoole4,wiki,swoole文档,swoole手册,swoole4文档,swoole扩展">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <link rel="stylesheet" href="css/Word2Chm.css" type="text/css" />
   <link rel="stylesheet" href="css/default.css" type="text/css" />
             <link rel="stylesheet" href="css/noframe.css" type="text/css" />
      <link rel="stylesheet" href="css/bootstrap.css" type="text/css" />
</head>
<body>
{$htmlBody}
</body>
</html>
HTMLCONTENT;

        file_put_contents($htmlFile, $htmlContent);

        return $htmlFile;
    }

}


$logic  = new GenSwooleDocset($allMarkdown);
$result = $logic->run();

file_put_contents("./result.log", var_export($result, true));

$logic->genSql($result);

echo "over", PHP_EOL;

