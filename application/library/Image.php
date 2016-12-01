<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * 图片处理类：
 *  $file = DOCROOT .'/2111-old.jpg';
 * Image::factory($file)->resize(684, 335, Image::AUTO)->background('#FFFFFF')
 * ->save(DOCROOT . '/thumb.jpg', 65);
 * Class Image
 */
abstract class Image extends Elixir_Image {}
