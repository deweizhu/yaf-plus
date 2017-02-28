<?php

/**
 * NLP 接口
 *
 * 该NLP服务包括四个功能：
 *     1、查找相关文档，根据调用参数的不同可用于相关推荐和专题推荐。
 *     2、查找相同文档，用于排重。
 *     3、提取文档关键词。
 *     4、提取文档摘要。
 *
 * @author : 知名不具
 */
final class Helper_Nlp
{

    private $_config;

    function __construct($config)
    {
        $this->_config = $config;
    }

    /**
     * 预处理文档信息
     *
     * @param  array $params array(
     *                       modelid    文档类型
     *                       content  文档内容
     *                       title    文档标题
     *                       tag      文档tag
     *                       created  文档创建时间(timestamp)
     *                       sw       摘要最大字数
     *                       kc       关键词最大数量
     *                       )
     *
     * @return array array(
     *              (array)s    相同的文档列表
     *              (array)k    关键词列表
     *              (string)m   摘要
     *          )
     */
    public function pretreat(array $params): array
    {
        $url = $this->_config['url'] . '/' . __FUNCTION__;
        $data = array(
            'cus' => $this->_config['prefix'] . Arr::get($params, 'modelid', 1),
            'ctn' => Arr::get($params, 'content'),
            'ti'  => Arr::get($params, 'title'),
            'tag' => Arr::get($params, 'tag'),
            'dt'  => Arr::get($params, 'created', TIMENOW),
            'sw'  => Arr::get($params, 'sw', 140),
            'kc'  => Arr::get($params, 'kc', 5)
        );
        $request = new Http_RequestCore($url);
        $request->set_method('POST');
        $request->set_body($data);
        $request->send_request();
        if ($request->get_response_code() != 200 || empty($request->get_response_body())) {
            return [];
        }
        $result = json_decode($request->get_response_body(), TRUE);
        if (isset($result['code']) && $result['code'] == -1)
            return [];
        if (isset($result['m']) && is_array($result['m'])) {
            $result['m'] = implode('', $result['m']);
        }
        return $result;
    }

    /**
     * 生成文档
     *
     * @param  array $params array(
     *                       modelid      文档类型
     *                       contentid    对应content的id
     *                       content      文档内容
     *                       title        文档标题
     *                       tag          文档tag
     *                       created      文档创建时间(timestamp)
     *                       rc           推荐文档最大数量
     *                       pc           推荐模式
     *                       )
     *
     * @return array array(
     *              (array)r    推荐文章id
     *          )
     */
    public function create(array $params): array
    {
        $url = $this->_config['url'] . '/' . __FUNCTION__;
        $data = array(
            'cus' => $this->_config['prefix'] . Arr::get($params, 'modelid', 1),
            'id'  => Arr::get($params, 'contentid'),
            'ctn' => Arr::get($params, 'content'),
            'ti'  => Arr::get($params, 'title'),
            'tag' => Arr::get($params, 'tag'),
            'dt'  => Arr::get($params, 'created', TIMENOW),
            'rc'  => Arr::get($params, 'rc', 5),
            'pc'  => Arr::get($params, 'pc', 0)
        );
        $request = new Http_RequestCore($url);
        $request->set_method('POST');
        $request->set_body($data);
        $request->send_request();
        if ($request->get_response_code() != 200 || empty($request->get_response_body())) {
            return [];
        }
        $result = json_decode($request->get_response_body(), TRUE);
        if (isset($result['code']) && $result['code'] == -1)
            return [];
        return $result;
    }

    /**
     * 获取关键词与重复文档
     *
     * @param  array $params array(
     *                       modelid        文档类型
     *                       contentid    对应content的id
     *                       kc           关键词最大数量
     *                       rc           推荐文档最大数量
     *                       pc           推荐模式
     *                       )
     *
     * @return array  array(
     *              (array)s    相同的文档列表
     *              (array)k    关键词列表
     *              (array)r    推荐文章id
     *          )
     */

    public function relate(array $params): array
    {
        $url = $this->_config['url'] . '/' . __FUNCTION__;
        $data = array(
            'cus' => $this->_config['prefix'] . Arr::get($params, 'modelid', 1),
            'id'  => Arr::get($params, 'contentid'),
            'kc'  => Arr::get($params, 'kc', 5),
            'rc'  => Arr::get($params, 'rc', 5),
            'pc'  => Arr::get($params, 'pc', 0)
        );
        $request = new Http_RequestCore($url);
        $request->set_method('POST');
        $request->set_body($data);
        $request->send_request();
        if ($request->get_response_code() != 200 || empty($request->get_response_body())) {
            return [];
        }
        $result = json_decode($request->get_response_body(), TRUE);
        if (isset($result['code']) && $result['code'] == -1)
            return [];
        return $result;
    }

    /**
     * 摘要提取
     *
     * @param  array $params array(
     *                       content  文档内容
     *                       title    文档标题
     *                       tag      文档tag
     *                       sw       摘要最大字数
     *                       )
     *
     * @return string     摘要
     */
    public function summary(array $params): string
    {
        $url = $this->_config['url'] . '/' . __FUNCTION__;
        $data = array(
            'ctn' => Arr::get($params, 'content'),
            'ti'  => Arr::get($params, 'title'),
            'tag' => Arr::get($params, 'tag'),
            'sw'  => Arr::get($params, 'sw', 140)
        );
        $request = new Http_RequestCore($url);
        $request->set_method('POST');
        $request->set_body($data);
        $request->send_request();
        if ($request->get_response_code() != 200 || empty($request->get_response_body())) {
            return '';
        }
        $result = json_decode($request->get_response_body(), TRUE);
        if (isset($result['code']) && $result['code'] == -1)
            return '';
        if (is_array($result)) {
            $result = implode('', $result);
        }
        return $result;
    }

    /**
     * 删除文档
     *
     * @param array $params array(
     *                      modelid        文档类型
     *                      contentid    对应content的id
     *                      )
     *
     * @return bool
     */
    public function remove(array $params): bool
    {
        $url = $this->_config['url'] . '/' . __FUNCTION__;
        $data = array(
            'cus' => $this->_config['prefix'] . Arr::get($params, 'modelid', 1),
            'id'  => Arr::get($params, 'contentid'),
        );
        $request = new Http_RequestCore($url);
        $request->set_method('POST');
        $request->set_body($data);
        $request->send_request();
        if ($request->get_response_code() != 200 || empty($request->get_response_body())) {
            return FALSE;
        }
        return TRUE;
    }
}