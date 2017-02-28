<?php

/**
 *  敏捷类，提供常用帮助函数
 *
 * @author: 知名不具
 * @date: 2016-12-02
 */
class Smart
{
    /**
     * 生成缩略图
     *
     * @param string $image 原图URL
     * @param int $weight 缩略图宽
     * @param int $height 缩略图高
     * @param int $quality 质量，建议60，80
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
     * 上传文件路径转绝对URL
     *
     * @param string $uri
     * @return string
     */
    public static function absUploadUrl(string $uri): string
    {
        if ($uri && strpos($uri, '://') === FALSE) {
            if ($uri[0] === 'M' AND $uri[1] === '0')
                $uri = Yaf_Application::app()->getConfig()->get('oss.httpserver') . $uri;
            else
                $uri = Yaf_Application::app()->getConfig()->get('site.upload_url') . $uri;
        }
        return $uri;
    }

}