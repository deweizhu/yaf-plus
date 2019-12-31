<?php
/**
 * 支持Swoole运行（启用此模式application/Constant.php中有些常量需要注释掉）
 * 用法：php swoole/http.php start|stop|restart
 * @author Not well-known man
 */

class HttpServer
{
    public static $instance;
    public static $pid = NULL;

    public $http;
    public static $request;
    public static $response;
    /**
     * @var Yaf\Application
     */
    private $application;

    public function __construct()
    {
        $http = new Swoole\Http\Server("", 9501);
        $workerNum = swoole_cpu_num() * 100;
        self::$pid = __DIR__ . '/httpd.pid';
        $http->set(
            array(
                'worker_num' => $workerNum, //进程数量
                'daemonize' => TRUE, //是否使用服务方式运行？
                'max_request' => 10000, //最大请求数？
                'dispatch_mode' => 1, //dispatch_mode=3，主进程会根据Worker的忙闲状态选择投递，只会投递给处于闲置状态的Worker
                'pid_file' => self::$pid,
                'log_file' => __DIR__ . '/httpd.log'
            )
        );

        $http->on('WorkerStart', array($this, 'onWorkerStart'));

        $http->on('request', function ($request, $response) {
            $uri = $request->server['request_uri'];
//            printf("[%s]get %s\n", date('Y-m-d H:i:s'), $uri);

            if ($uri == '/favicon.ico' || $request->server['path_info'] == '/favicon.ico') {
                $response->status(404);
                return $response->end();
            }

            //@see https://wiki.swoole.com/wiki/page/336.html
            Yaf\Registry::set('sw_request', $request);
            Yaf\Registry::set('sw_response', $response);
            self::$request = $request;
            self::$response = $response;

            ob_start();
            try {
                $this->application->getDispatcher()->dispatch(new Yaf\Request\Http($uri));
            } catch (Yaf\Exception $e) {
                Log::instance()->add(Log::ERROR, $e->getMessage());
                Log::instance()->write();
            }

            $result = ob_get_contents();
            ob_end_clean();

            $response->end($result);
        });

        $http->start();
        $this->http = $http;
    }

    public function onWorkerStart($serv, $work_id)
    {
        if (PHP_OS !== 'Darwin' && $work_id >= 0) {
            cli_set_process_title("Worker.{$work_id}");
        }
        // var_dump(get_included_files()); // 打印worker启动前已经加载的php文件
        \Yaf\Registry::set('swoole_serv', $serv);

        define('DOCROOT', realpath(__DIR__ . '/../'));
        define('APPPATH', DOCROOT . '/application');
        define('MODPATH', APPPATH . '/library');
        define('STORAGEPATH', DOCROOT . '/storage');


        $this->application = new \Yaf\Application(DOCROOT . '/conf/application.ini');
        ob_start();
        $this->application->bootstrap()->run();
        ob_end_clean();
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new HttpServer;
        }
        return self::$instance;
    }
}

$cmd = 'php';
if (!isset($argv[1])) {
    echo 'Usage: php ', basename(__FILE__), ' [start|stop|restart]', PHP_EOL;
    exit();
} else {
    switch ($argv[1]) {
        case 'start':
            HttpServer::instance();
            echo "Start OK.\n";
            break;
        case 'stop':
            exec("ps -ef | grep -E '" . basename(__FILE__) . " start' |grep -v 'grep'| awk '{print $2}'|xargs kill -15 > /dev/null 2>&1 &");
            echo "Stop OK.\n";
            break;
        case 'restart':
            exec($cmd . ' ' . __FILE__ . ' stop');
            echo "Stop OK.\n";
            exec($cmd . ' ' . __FILE__ . ' start');
            echo "Start OK.\n";
            break;
        default:
            exit("Not support this argv.\n");
            break;
    }
}