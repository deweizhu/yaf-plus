<?php

/**
 * Class OssClient
 *
 * Object Storage Service(OSS) 的客户端类，封装了用户通过OSS API对OSS服务的各种操作，
 * 用户通过OssClient实例可以进行Bucket，Object，MultipartUpload, ACL等操作，具体
 * 的接口规则可以参考官方OSS API文档
 */
class Oss_Client
{

    /**
     *
     * @var FastDFS
     */
    private $_fdfs;
    private $_tracker;
    private $_storage;

    /**
     * 单实例
     *
     * @var null
     */
    public static $instance = NULL;

    /**
     * 单实例
     *
     * @return Oss_Client
     */
    public static function instance(): Oss_Client
    {
        if (self::$instance === NULL || !self::$instance instanceof Oss_Client)
            self::$instance = new Oss_Client();
        return self::$instance;
    }

    /**
     * 要使用这个类，你一定要在php的ini文件中进行fastdfs的配置
     *
     * @throws Elixir_Exception
     */
    public function __construct()
    {
        $this->_fdfs = new FastDFS();
        $this->_fdfs->tracker_make_all_connections();
        $tracker = $this->_fdfs->tracker_get_connection();
        $this->_tracker = $this->_fdfs->connect_server($tracker['ip_addr'], $tracker['port']);
    }

    /**
     * 上传文件
     *
     * @param string $local_file 本地文件
     * @param string $file_ext   文件扩展名
     * @param array  $slave_file 附属文件
     * @param string $group      存储空间卷组
     *
     * @return string
     * @throws Elixir_Exception
     */
    public function uploadFile(string $local_file, string $file_ext = '', array $slave_file = [], string $group = 'group1'): string
    {
        $location = '';
        $this->_checkServer($group);
        $file = $this->_fdfs->storage_upload_by_filename($local_file, $file_ext, [], $group, $this->_tracker,
            $this->_storage);
        if (isset($file['filename'])) {
            $location = $file['filename'];
        }
        if ($location !== '' && !empty($slave_file)) {
            foreach ($slave_file as $key => $val) {
                $this->_fdfs->storage_upload_slave_by_filename($val, $group, $file['filename'], $key, $file_ext);
            }
        }
        return $location;
    }

    /**
     * 上传文件内容
     *
     * @param string $file_buff  文件内容 buffer
     * @param string $file_ext   文件扩展名
     * @param array  $slave_file 附属文件内容 buffer
     * @param string $group      存储空间卷组
     *
     * @return string
     * @throws Elixir_Exception
     */
    public function uploadBuff(string $file_buff, string $file_ext = '', array $slave_file = [], string $group = 'group1'): string
    {
        $location = '';
        $this->_checkServer($group);
        $file = $this->_fdfs->storage_upload_by_filebuff($file_buff, $file_ext, [], $group, $this->_tracker,
            $this->_storage);
        if (isset($file['filename'])) {
            $location = $file['filename'];
        }
        if ($location !== '' && !empty($slave_file)) {
            foreach ($slave_file as $key => $val) {
                $this->_fdfs->storage_upload_slave_by_filebuff($val, $group, $file['filename'], $key, $file_ext);
            }
        }
        return $location;
    }

    /**
     * 删除文件
     * @param string $file_id
     * @param string $group
     *
     * @return bool
     */
    public function deleteFile(string $file_id, string $group = 'group1'): bool
    {
        $this->_checkServer($group);
        return $this->_fdfs->storage_delete_file($group, $file_id, $this->_tracker, $this->_storage);
    }


    /**
     * 检查存储服务
     *
     * @param string $group
     *
     * @throws Elixir_Exception
     */
    private function _checkServer(string $group)
    {
        if (!$this->_fdfs->active_test($this->_tracker)) {
            throw new Elixir_Exception('tracker server active_test errno: :errno , error info: :error ',
                array(':errno' => $this->_fdfs->get_last_error_no(), ':error' => $this->_fdfs->get_last_error_info()));
        }
        if (!$this->_storage) {
//            $this->_storage = $this->_fdfs->tracker_query_storage_store($group, $this->_tracker);
            $this->_storage = $this->_fdfs->tracker_query_storage_store($group);
            if ($server = $this->_fdfs->connect_server($this->_storage['ip_addr'], $this->_storage['port']))
                $this->_storage['sock'] = $server['sock'];
        }
        if (!$this->_fdfs->active_test($this->_storage)) {
            throw new Elixir_Exception('storage server active_test errno: :errno , error info: :error ',
                array(':errno' => $this->_fdfs->get_last_error_no(), ':error' => $this->_fdfs->get_last_error_info()));
        }
    }

    function __destruct()
    {
        if ($this->_tracker)
            $this->_fdfs->disconnect_server($this->_tracker);
        if ($this->_storage)
            $this->_fdfs->disconnect_server($this->_storage);
        $this->_fdfs->tracker_close_all_connections();
    }

}