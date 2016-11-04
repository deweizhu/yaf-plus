<?php

/**
 *  简单分页帮助类
 *
 * @author: ZDW
 */
class Helper_Pager
{
    /**
     * 计算分页大小
     * @param array $filter
     * @return bool
     */
    public static function buildPageSize(array &$filter)
    {
        if (!isset($filter['record_count']))
            return FALSE;
        if (Cookie::get('page_size') !== NULL) {
            $filter['page_size'] = intval(Cookie::get('page_size'));
        } else {
            $filter['page_size'] = !isset($filter['page_size']) ? 10 : intval($filter['page_size']);
        }
        $filter['page'] = max(1, $filter['page']);
        $filter['record_count'] = intval($filter['record_count']);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
        /* 边界处理 */
        if ($filter['page'] > $filter['page_count']) {
            $filter['page'] = $filter['page_count'];
            $filter['record_count'] = 0;
        }
        $filter['offset'] = ($filter['page'] - 1) * $filter['page_size'];
        return $filter;
    }

    /**
     * 分页大小
     * @access  public
     * @return  array
     */
    public static function pageAndSize(array &$filter)
    {
        /* 每页显示 */
        $page_size = Arr::get($_GET, 'pgsize');
        if ($page_size > 0) {
            $filter['page_size'] = $page_size;
        } elseif (Cookie::get('page_size') !== NULL) {
            $filter['page_size'] = intval(Cookie::get('page_size'));
        } else {
            $filter['page_size'] = 10;
        }
        /* 当前页 */
        $filter['page'] = max(1, Arr::get($_GET, 'page'));
        $filter['sidx'] = Arr::get($_GET, 'sort_by', '');
        $filter['sord'] = Arr::get($_GET, 'sort_order', 'DESC');
        $filter['sort_by'] = Arr::get($_GET, 'sort_by', '');
        $filter['sort_order'] = Arr::get($_GET, 'sort_order', 'DESC');
        /* page 总数 */
        $filter['page_count'] = (!empty($filter['record_count']) && $filter['record_count'] > 0) ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 边界处理 */
        if ($filter['page'] > $filter['page_count']) {
            $filter['page'] = $filter['page_count'];
        }
        $filter['start'] = ($filter['page'] - 1) * $filter['page_size'];
        $filter['pagelink'] = self::makePageLink($filter['record_count'], $filter['page_size'],
            $filter['page'], '', $filter['page_count']); // 显示分页
        return $filter;
    }

    /**
     * 创建翻页URL
     * @param $num 总记录数
     * @param $perpage 每页记录数
     * @param $curpage 当前页数
     * @param $mpurl 除页变量外 URL
     * @param int $maxpages 最大页面值
     * @param int $page 一次最多显示几页
     * @return string
     */
    public static function makePageLink($num, $perpage, $curpage, $mpurl, $maxpages = 0, $page = 10)
    {
        $a_name = '';
        if (strpos($mpurl, '#') !== FALSE) {
            $a_strs = explode('#', $mpurl);
            $mpurl = $a_strs[0];
            $a_name = '#' . $a_strs[1];
        }
        if (strpos($mpurl, 'page=') !== FALSE) {
            $mpurl = preg_replace('/([&]?)page=([0-9]*)/', '', $mpurl);
        }
        if (strpos($mpurl, 'pgsize=') !== FALSE) {
            $mpurlvar = preg_replace('/([&]?)pgsize=([0-9]*)/', '', $mpurl);
        } else {
            $mpurlvar = $mpurl;
        }

        $pagevar = 'page=';
        $pagesizevar = 'pgsize=';

        $shownum = TRUE; //是否显示总记录数
        $showkbd = TRUE; //是否显示 <kbd>页数跳转输入框</kbd>
        $showpagejump = TRUE; //是否显示页数跳转输入框

        $dot = '...';
        $mpurl .= strpos($mpurl, '?') !== FALSE ? '&amp;' : '?';
        $mpurlvar .= strpos($mpurlvar, '?') !== FALSE ? '&amp;' : '?';

        $page -= strlen($curpage) - 1;
        if ($page <= 0) {
            $page = 1;
        }
        if ($perpage <= 0 || $perpage >= 1000) {
            $perpage = 10;
        }

//        if ($num > $perpage) {

        $offset = floor($page * 0.5);

        $realpages = @ceil($num / $perpage);
        $curpage = $curpage > $realpages ? $realpages : $curpage;
        $pages = $maxpages && $maxpages < $realpages ? $maxpages : $realpages;


        if ($page > $pages) {
            $from = 1;
            $to = $pages;
        } else {
            $from = $curpage - $offset;
            $to = $from + $page - 1;

            if ($from < 1) {
                $to = $curpage + 1 - $from;
                $from = 1;
                if ($to - $from < $page) {
                    $to = $page;
                }
            } elseif ($to > $pages) {
                $from = $pages - $page + 1;
                $to = $pages;
            }
        }

        $multipage = ($curpage - $offset > 1 && $pages > $page ? '<li><a href="' . $mpurl . $pagevar . '1' . $a_name . '" class="first">1 ' . $dot . '</a></li>' : '') .
            ($curpage > 1 ? '<li><a href="' . $mpurl . $pagevar . ($curpage - 1) . $a_name . '" class="prev">上一页</a></li>' : '');
        for ($i = $from; $i <= $to; $i++) {
            $multipage .= $i == $curpage ? '<li><strong>' . $i . '</strong></li>' :
                '<li><a href="' . $mpurl . $pagevar . $i . $a_name . '">' . $i . '</a></li>';
        }
        $multipage .= ($to < $pages ? '<li><a href="' . $mpurl . $pagevar . $pages . $a_name . '" class="last">' . $dot . ' ' . $realpages . '</a></li>' : '') .

            ($curpage < $pages ? '<li><a href="' . $mpurl . $pagevar . ($curpage + 1) . $a_name . '" class="nxt">下一页</a></li>' : '');

//        $multipage = $multipage ? '<div class="pg">' . ($shownum ? '<em>&nbsp;&nbsp;共&nbsp;' . $num .
//                '&nbsp;条记录</em>' : '') .
//            $multipage . '</div>' : '';
//        }
        return $multipage;
    }
}