<?php
/**
 *
 * @author Not well-known man
 */

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

class CaptchaController extends Yaf\Controller_Abstract
{
    public function getAction()
    {
        $w = Request::query()->getInt('w', 161);
        $h = Request::query()->getInt('h', 68);
        $key = Request::query()->get('key', NULL);
        $len = Request::query()->getInt('len', 4);

        $phrase = new PhraseBuilder;
        // 设置验证码位数
        $code = $phrase->build($len);
        // 生成验证码图片的Builder对象，配置相应属性
        $builder = new CaptchaBuilder($code, $phrase);
        // 设置背景颜色
        $builder->setBackgroundColor(220, 210, 230);
        $builder->setMaxAngle(25);
        $builder->setMaxOffset(0);
        $builder->setMaxBehindLines(0);
        $builder->setMaxFrontLines(0);
        $builder->build($w, $h);
        //存到redis
        if ($key) {
            $this->cache->set('captcha:' . $key, $builder->getPhrase());
        } else {
            //存到session
            Session::instance()->set('phrase', $builder->getPhrase());
        }

        // 生成图片
        Response::header('Cache-Control', 'no-cache, must-revalidate');
        Response::header('Content-Type', 'image/jpeg');
        $builder->output();
        return FALSE;
    }

    public function testAction()
    {
        $userInput = Request::post()->get('code');
        $builder = new CaptchaBuilder;
        if ($builder->testPhrase($userInput)) {
            // instructions if user phrase is good
            echo 'good';
        } else {
            // user phrase is wrong
            echo 'wrong';
        }
        return FALSE;
    }
}