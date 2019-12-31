<?php

/**
 *  敏捷类，提供常用帮助函数
 *
 * @author: Not well-known man
 *
 */
class Smart
{
    /**
     * 生成缩略图
     *
     * @param string $image   原图URL
     * @param int    $weight  缩略图宽
     * @param int    $height  缩略图高
     * @param int    $quality 质量，建议60，80
     *
     * @return string        缩略图URL
     */
    public static function thumb(string $image, int $weight = 300, int $height = 300, int $quality = 80): string
    {
        if (strpos($image, '://') !== FALSE || !is_file(UPLOAD_PATH . '/' . $image)) return $image;
        $path = pathinfo($image);
        $basename = $path['filename'] . '_' . $weight . 'x' . $height . '.' . $path['extension'];
        $filepath = $path['dirname'] . '/' . $basename;
        Elixir_Image::factory($image)->resize($weight, $height, Image::AUTO)->background('#FFFFFF')
            ->save(UPLOAD_PATH . '/' . $filepath, $quality);
        return $filepath;
    }

    /**
     * 图片预览JSON
     *
     * @param string $image
     *
     * @return string
     */
    public static function thumbPreviewJosn(string $image): string
    {
        if (!$image)
            return '[]';
        static $oss = NULL;
        if ($oss === NULL) $oss = OssclientModel::instance();
        $item = array('caption' => '');
        if (strpos($image, '/M0') !== FALSE) {
            $filename = parse_url($image, PHP_URL_PATH);
            $item['url'] = $oss->deleteObjectURL($filename);
            $item['src'] = $image;
        } else {
            $item['src'] = $image;
            $item['url'] = NULL;
        }
        return json_encode(array($item));
    }

    /**
     * 图片预览处理
     *
     * @param array $image
     *
     * @return array
     */
    public static function thumbPreview(string $image): array
    {
        if (!$image)
            return [];
        static $oss = NULL;
        if ($oss === NULL) $oss = OssclientModel::instance();
        $item = array('caption' => '');
        if (strpos($image, '/M0') !== FALSE) {
            $filename = parse_url($image, PHP_URL_PATH);
            $item['url'] = $oss->deleteObjectURL($filename);
            $item['src'] = $image;
        } else {
            $item['src'] = $image;
            $item['url'] = NULL;
        }
        return $item;
    }

    /**
     * 上传文件路径转绝对URL
     *
     * @param string $uri
     *
     * @return string
     */
    public static function absUploadUrl(string $uri, int $w = 0, int $h = 0, string $mode = 'lfit'): string
    {
        if ($w===0 && $h === 0)
            return $uri;
        $uri .= '?x-oss-process=image/resize,m_' . $mode;
        if ($h > 0)
         $uri .= ',h_' . $h;
        if ($w > 0)
            $uri .= ',w_' . $w;
        return $uri;
    }

    /**
     * 取得OSS实时缩略图URL
     *
     * @param string $image  原图URL
     * @param int    $weight 缩略图宽
     * @param int    $height 缩略图高
     *
     * @return string       缩略图URL
     */
    public static function ossThumb(string $image, int $weight = 300, int $height = 300): string
    {
//        if (!$image || strpos($image, Yaf\Application::app()->getConfig()->get('oss.httpserver')) === FALSE)
//            return $image;
        return substr($image, 0, strrpos($image, '.')) . '_' . $weight . 'x' . $height . strrchr($image, '.');
    }


//    /**
//     * 商品详情URL
//     *
//     * @param int $itemid
//     *
//     * @return string
//     */
//    public static function ecItemURL(int $itemid): string
//    {
//        return Yaf\Application::app()->getConfig()->get('site.m_url') . 'mall/item/' . $itemid . '.html';
//    }
}