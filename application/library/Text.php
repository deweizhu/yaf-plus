<?php

/**
 * Text helper class. Provides simple methods for working with text.
 *
 * @package    Elixir
 * @category   Helpers
 * @author    Not well-known man
 * @copyright  (c) 2007-2012 Elixir Team
 * @license
 */
class Text
{

    /**
     * The cache of snake-cased words.
     *
     * @var array
     */
    protected static $snakeCache = [];
    
    /**
     * The cache of camel-cased words.
     *
     * @var array
     */
    protected static $camelCache = [];
    
    /**
     * The cache of studly-cased words.
     *
     * @var array
     */
    protected static $studlyCache = [];
    
    /**
     * @var  array   number units and text equivalents
     */
    public static $units = array(
        1000000000 => 'billion',
        1000000    => 'million',
        1000       => 'thousand',
        100        => 'hundred',
        90         => 'ninety',
        80         => 'eighty',
        70         => 'seventy',
        60         => 'sixty',
        50         => 'fifty',
        40         => 'fourty',
        30         => 'thirty',
        20         => 'twenty',
        19         => 'nineteen',
        18         => 'eighteen',
        17         => 'seventeen',
        16         => 'sixteen',
        15         => 'fifteen',
        14         => 'fourteen',
        13         => 'thirteen',
        12         => 'twelve',
        11         => 'eleven',
        10         => 'ten',
        9          => 'nine',
        8          => 'eight',
        7          => 'seven',
        6          => 'six',
        5          => 'five',
        4          => 'four',
        3          => 'three',
        2          => 'two',
        1          => 'one',
    );

    /**
     * Limits a phrase to a given number of words.
     *
     *     $text = Text::limit_words($text);
     *
     * @param   string $str phrase to limit words of
     * @param   integer $limit number of words to limit to
     * @param   string $end_char end character or entity
     * @return  string
     */
    public static function limit_words($str, $limit = 100, $end_char = NULL)
    {
        $limit = (int)$limit;
        $end_char = ($end_char === NULL) ? '…' : $end_char;

        if (trim($str) === '')
            return $str;

        if ($limit <= 0)
            return $end_char;

        preg_match('/^\s*+(?:\S++\s*+){1,' . $limit . '}/u', $str, $matches);

        // Only attach the end character if the matched string is shorter
        // than the starting string.
        return rtrim($matches[0]) . ((strlen($matches[0]) === strlen($str)) ? '' : $end_char);
    }

    /**
     * Limits a phrase to a given number of characters.
     *
     *     $text = Text::limit_chars($text);
     *
     * @param   string $str phrase to limit characters of
     * @param   integer $limit number of characters to limit to
     * @param   string $end_char end character or entity
     * @param   boolean $preserve_words enable or disable the preservation of words while limiting
     * @return  string
     * @uses    UTF8::strlen
     */
    public static function limit_chars($str, $limit = 100, $end_char = NULL, $preserve_words = FALSE)
    {
        $end_char = ($end_char === NULL) ? '…' : $end_char;

        $limit = (int)$limit;

        if (trim($str) === '' OR UTF8::strlen($str) <= $limit)
            return $str;

        if ($limit <= 0)
            return $end_char;

        if ($preserve_words === FALSE)
            return rtrim(UTF8::substr($str, 0, $limit)) . $end_char;

        // Don't preserve words. The limit is considered the top limit.
        // No strings with a length longer than $limit should be returned.
        if (!preg_match('/^.{0,' . $limit . '}\s/us', $str, $matches))
            return $end_char;

        return rtrim($matches[0]) . ((strlen($matches[0]) === strlen($str)) ? '' : $end_char);
    }

    /**
     * Alternates between two or more strings.
     *
     *     echo Text::alternate('one', 'two'); // "one"
     *     echo Text::alternate('one', 'two'); // "two"
     *     echo Text::alternate('one', 'two'); // "one"
     *
     * Note that using multiple iterations of different strings may produce
     * unexpected results.
     *
     * @param   string $str,... strings to alternate between
     * @return  string
     */
    public static function alternate()
    {
        static $i;

        if (func_num_args() === 0) {
            $i = 0;
            return '';
        }

        $args = func_get_args();
        return $args[($i++ % count($args))];
    }

    /**
     * Generates a random string of a given type and length.
     *
     *
     *     $str = Text::random(); // 8 character random string
     *
     * The following types are supported:
     *
     * alnum
     * :  Upper and lower case a-z, 0-9 (default)
     *
     * alpha
     * :  Upper and lower case a-z
     *
     * hexdec
     * :  Hexadecimal characters a-f, 0-9
     *
     * distinct
     * :  Uppercase characters and numbers that cannot be confused
     *
     * You can also create a custom type by providing the "pool" of characters
     * as the type.
     *
     * @param   string $type a type of pool, or a string of characters to use as the pool
     * @param   integer $length length of string to return
     * @return  string
     * @uses    UTF8::split
     */
    public static function random($type = NULL, $length = 8)
    {
        if ($type === NULL) {
            // Default is to generate an alphanumeric string
            $type = 'alnum';
        }

        $utf8 = FALSE;

        switch ($type) {
            case 'alnum':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'hexdec':
                $pool = '0123456789abcdef';
                break;
            case 'numeric':
                $pool = '0123456789';
                break;
            case 'nozero':
                $pool = '123456789';
                break;
            case 'distinct':
                $pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
                break;
            default:
                $pool = (string)$type;
                $utf8 = !UTF8::is_ascii($pool);
                break;
        }

        // Split the pool into an array of characters
        $pool = ($utf8 === TRUE) ? UTF8::str_split($pool, 1) : str_split($pool, 1);

        // Largest pool key
        $max = count($pool) - 1;

        $str = '';
        for ($i = 0; $i < $length; $i++) {
            // Select a random character from the pool and add it to the string
            $str .= $pool[mt_rand(0, $max)];
        }

        // Make sure alnum strings contain at least one letter and one digit
        if ($type === 'alnum' AND $length > 1) {
            if (ctype_alpha($str)) {
                // Add a random digit
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(48, 57));
            } elseif (ctype_digit($str)) {
                // Add a random letter
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(65, 90));
            }
        }
        return $str;
    }

    /**
     * Uppercase words that are not separated by spaces, using a custom
     * delimiter or the default.
     *
     *      $str = Text::ucfirst('content-type'); // returns "Content-Type"
     *
     * @param   string $string string to transform
     * @param   string $delimiter delimiter to use
     * @uses    UTF8::ucfirst
     * @return  string
     */
    public static function ucfirst($string, $delimiter = '-')
    {
        // Put the keys back the Case-Convention expected
        return implode($delimiter, array_map('UTF8::ucfirst', explode($delimiter, $string)));
    }

    /**
     * Reduces multiple slashes in a string to single slashes.
     *
     *     $str = Text::reduce_slashes('foo//bar/baz'); // "foo/bar/baz"
     *
     * @param   string $str string to reduce slashes of
     * @return  string
     */
    public static function reduce_slashes($str)
    {
        return preg_replace('#(?<!:)//+#', '/', $str);
    }

    /**
     * Replaces the given words with a string.
     *
     *     // Displays "What the #####, man!"
     *     echo Text::censor('What the frick, man!', array(
     *         'frick' => '#####',
     *     ));
     *
     * @param   string $str phrase to replace words in
     * @param   array $badwords words to replace
     * @param   string $replacement replacement string
     * @param   boolean $replace_partial_words replace words across word boundaries (space, period, etc)
     * @return  string
     * @uses    UTF8::strlen
     */
    public static function censor($str, $badwords, $replacement = '#', $replace_partial_words = TRUE)
    {
        foreach ((array)$badwords as $key => $badword) {
            $badwords[$key] = str_replace('\*', '\S*?', preg_quote((string)$badword));
        }

        $regex = '(' . implode('|', $badwords) . ')';

        if ($replace_partial_words === FALSE) {
            // Just using \b isn't sufficient when we need to replace a badword that already contains word boundaries itself
            $regex = '(?<=\b|\s|^)' . $regex . '(?=\b|\s|$)';
        }

        $regex = '!' . $regex . '!ui';

        // if $replacement is a single character: replace each of the characters of the badword with $replacement
        if (UTF8::strlen($replacement) == 1) {
            return preg_replace_callback($regex, function ($matches) use ($replacement) {
                return str_repeat($replacement, UTF8::strlen($matches[1]));
            }, $str);
        }

        // if $replacement is not a single character, fully replace the badword with $replacement
        return preg_replace($regex, $replacement, $str);
    }

    /**
     * Finds the text that is similar between a set of words.
     *
     *     $match = Text::similar(array('fred', 'fran', 'free'); // "fr"
     *
     * @param   array $words words to find similar text of
     * @return  string
     */
    public static function similar(array $words)
    {
        // First word is the word to match against
        $word = current($words);

        for ($i = 0, $max = strlen($word); $i < $max; ++$i) {
            foreach ($words as $w) {
                // Once a difference is found, break out of the loops
                if (!isset($w[$i]) OR $w[$i] !== $word[$i])
                    break 2;
            }
        }

        // Return the similar text
        return substr($word, 0, $i);
    }


    /**
     * Automatically applies "p" and "br" markup to text.
     * Basically [nl2br](http://php.net/nl2br) on steroids.
     *
     *     echo Text::auto_p($text);
     *
     * [!!] This method is not foolproof since it uses regex to parse HTML.
     *
     * @param   string $str subject
     * @param   boolean $br convert single linebreaks to <br />
     * @return  string
     */
    public static function auto_p($str, $br = TRUE)
    {
        // Trim whitespace
        if (($str = trim($str)) === '')
            return '';

        // Standardize newlines
        $str = str_replace(array("\r\n", "\r"), "\n", $str);

        // Trim whitespace on each line
        $str = preg_replace('~^[ \t]+~m', '', $str);
        $str = preg_replace('~[ \t]+$~m', '', $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found = (strpos($str, '<') !== FALSE)) {
            // Elements that should not be surrounded by p tags
            $no_p = '(?:p|div|h[1-6r]|ul|ol|li|blockquote|d[dlt]|pre|t[dhr]|t(?:able|body|foot|head)|c(?:aption|olgroup)|form|s(?:elect|tyle)|a(?:ddress|rea)|ma(?:p|th))';

            // Put at least two linebreaks before and after $no_p elements
            $str = preg_replace('~^<' . $no_p . '[^>]*+>~im', "\n$0", $str);
            $str = preg_replace('~</' . $no_p . '\s*+>$~im', "$0\n", $str);
        }

        // Do the <p> magic!
        $str = '<p>' . trim($str) . '</p>';
        $str = preg_replace('~\n{2,}~', "</p>\n\n<p>", $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found !== FALSE) {
            // Remove p tags around $no_p elements
            $str = preg_replace('~<p>(?=</?' . $no_p . '[^>]*+>)~i', '', $str);
            $str = preg_replace('~(</?' . $no_p . '[^>]*+>)</p>~i', '$1', $str);
        }

        // Convert single linebreaks to <br />
        if ($br === TRUE) {
            $str = preg_replace('~(?<!\n)\n(?!\n)~', "<br />\n", $str);
        }

        return $str;
    }

    /**
     * Returns human readable sizes. Based on original functions written by
     * [Aidan Lister](http://aidanlister.com/repos/v/function.size_readable.php)
     * and [Quentin Zervaas](http://www.phpriot.com/d/code/strings/filesize-format/).
     *
     *     echo Text::bytes(filesize($file));
     *
     * @param   integer $bytes size in bytes
     * @param   string $force_unit a definitive unit
     * @param   string $format the return string format
     * @param   boolean $si whether to use SI prefixes or IEC
     * @return  string
     */
    public static function bytes($bytes, $force_unit = NULL, $format = NULL, $si = TRUE)
    {
        // Format string
        $format = ($format === NULL) ? '%01.2f %s' : (string)$format;

        // IEC prefixes (binary)
        if ($si == FALSE OR strpos($force_unit, 'i') !== FALSE) {
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
            $mod = 1024;
        } // SI prefixes (decimal)
        else {
            $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
            $mod = 1000;
        }

        // Determine unit to use
        if (($power = array_search((string)$force_unit, $units)) === FALSE) {
            $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
        }

        return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
    }

    /**
     * Format a number to human-readable text.
     *
     *     // Display: one thousand and twenty-four
     *     echo Text::number(1024);
     *
     *     // Display: five million, six hundred and thirty-two
     *     echo Text::number(5000632);
     *
     * @param   integer $number number to format
     * @return  string
     * @since   3.0.8
     */
    public static function number($number)
    {
        // The number must always be an integer
        $number = (int)$number;

        // Uncompiled text version
        $text = array();

        // Last matched unit within the loop
        $last_unit = NULL;

        // The last matched item within the loop
        $last_item = '';

        foreach (Text::$units as $unit => $name) {
            if ($number / $unit >= 1) {
                // $value = the number of times the number is divisible by unit
                $number -= $unit * ($value = (int)floor($number / $unit));
                // Temporary var for textifying the current unit
                $item = '';

                if ($unit < 100) {
                    if ($last_unit < 100 AND $last_unit >= 20) {
                        $last_item .= '-' . $name;
                    } else {
                        $item = $name;
                    }
                } else {
                    $item = Text::number($value) . ' ' . $name;
                }

                // In the situation that we need to make a composite number (i.e. twenty-three)
                // then we need to modify the previous entry
                if (empty($item)) {
                    array_pop($text);

                    $item = $last_item;
                }

                $last_item = $text[] = $item;
                $last_unit = $unit;
            }
        }

        if (count($text) > 1) {
            $and = array_pop($text);
        }

        $text = implode(', ', $text);

        if (isset($and)) {
            $text .= ' and ' . $and;
        }

        return $text;
    }

    /**
     * Prevents [widow words](http://www.shauninman.com/archive/2006/08/22/widont_wordpress_plugin)
     * by inserting a non-breaking space between the last two words.
     *
     *     echo Text::widont($text);
     *
     * regex courtesy of the Typogrify project
     * @link http://code.google.com/p/typogrify/
     *
     * @param   string $str text to remove widows from
     * @return  string
     */
    public static function widont($str)
    {
        // use '%' as delimiter and 'x' as modifier
        $widont_regex = "%
			((?:</?(?:a|em|span|strong|i|b)[^>]*>)|[^<>\s]) # must be proceeded by an approved inline opening or closing tag or a nontag/nonspace
			\s+                                             # the space to replace
			([^<>\s]+                                       # must be flollowed by non-tag non-space characters
			\s*                                             # optional white space!
			(</(a|em|span|strong|i|b)>\s*)*                 # optional closing inline tags with optional white space after each
			((</(p|h[1-6]|li|dt|dd)>)|$))                   # end with a closing p, h1-6, li or the end of the string
		%x";
        return preg_replace($widont_regex, '$1&nbsp;$2', $str);
    }

    /**
     *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
     *
     * @access  public
     * @param   string $str 待转换字串
     *
     * @return  string       $str         处理后字串
     */
    public static function semiangle($str):string
    {
        $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
                     '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
                     'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
                     'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
                     'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
                     'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
                     'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
                     'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
                     'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
                     'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
                     'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
                     'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
                     'ｙ' => 'y', 'ｚ' => 'z',
                     '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
                     '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
                     '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
                     '》' => '>',
                     '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
                     '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
                     '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
                     '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
                     '　' => ' ');

        return strtr($str, $arr);
    }

    /**
     * 判断utf8
     * @param $val
     * @return bool
     */
    public static function is_utf8($val):bool
    {
        // From http://w3.org/International/questions/qa-forms-utf-8.html
        return preg_match('%^(?:
[\x09\x0A\x0D\x20-\x7E] # ASCII
| [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
| \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
| \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
| \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
| [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
| \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
)*$%xs', $val);
    }

    /**
     * 36位UUID
     * @return string
     */
    public static function uuid():string
    {
        // The field names refer to RFC 4122 section 4.1.2
        return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
            mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
            mt_rand(0, 65535), // 16 bits for "time_mid"
            mt_rand(0, 4095), // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
            bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
            // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
            // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
            // 8 bits for "clk_seq_low"
            mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"
        );
    }

    /**
     * 32位UUID，无-横杠
     * @return string
     */
    public static function uuid32():string
    {
        // The field names refer to RFC 4122 section 4.1.2
        return sprintf('%04x%04x%04x%03x4%04x%04x%04x%04x',
            mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
            mt_rand(0, 65535), // 16 bits for "time_mid"
            mt_rand(0, 4095), // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
            bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
            // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
            // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
            // 8 bits for "clk_seq_low"
            mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"
        );
    }

    /**
     * 给用户生成唯一会话token
     *
     * @param string $data
     * @return string
     */
    public static function authtoken($data = ''):string
    {
        if (PHP_SAPI == 'cli') {
            $http_host = '';
        } else {
            $http_scheme = (($scheme = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : null) == 'off' || empty($scheme)) ? 'http' : 'https';
            $http_host = $http_scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        return self::token($http_host . $data);
    }
    /**
     * 生成guid
     *
     * @param  $randid  字符串
     * @return string   guid
     */
    public static function token($mix = null):string
    {
        if (is_null($mix)) {
            $randid = uniqid(mt_rand(), true);
        } else {
            if (is_object($mix) && function_exists('spl_object_hash')) {
                $randid = spl_object_hash($mix);
            } elseif (is_resource($mix)) {
                $randid = get_resource_type($mix) . strval($mix);
            } else {
                $randid = serialize($mix);
            }
        }
        $randid = strtoupper(md5($randid));
        //$hyphen = chr(45);
        $hyphen = '';
        $result = array();
        $result[] = substr($randid, 0, 8);
        $result[] = substr($randid, 8, 4);
        $result[] = substr($randid, 12, 4);
        $result[] = substr($randid, 16, 4);
        $result[] = substr($randid, 20, 12);
        return implode($hyphen, $result);
    }
    /**
     * 去除 文本信息里的class样式及css样式 例如：<style type='css/text'>
     * @param $str
     * @return mixed
     */
    public static function filterStyle(string $str):string
    {
        $str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU", '', $str);
        $str = preg_replace('#class=["\']([^"\']*)["\']#i', '', $str);
        //过滤链接
        $str = preg_replace('/<a.*?>(.*?)<\/a>/i', '${1}', $str);
        //内容图片url转换
        $str = preg_replace('#<img([^>]+)>#', '', $str);
        $str = preg_replace('#<p>([\s]+)</p>#', '', $str);
        return $str;
    }

    /**
     * 获得HTML里的文本
     * @param $str
     * @return string
     */
    public static function html2text(string $str):string
    {
        $str = preg_replace(
            "/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU", '', $str);
        $str = str_replace(array('<br />', '<br>', '<br/>'), "\n", $str);
        $str = strip_tags($str);
        $str = preg_replace("#(?:[\n[\s|　]*\n]*)#is", "\n", $str);
        $str = html_entity_decode($str);
        return $str;
    }

    /**
     * 多币种格式化价格
     *
     * @access  public
     * @param   float $price 价格
     * @param   string $currency 货币名称简写字母（三个大小字母）
     * @return  string
     */
    public static function currency_price_format(float $price, string $currency = 'CNY'):string
    {
        if ($price === '') {
            $price = 0;
        }
        $code = '';
        switch (strtoupper($currency)) {
            case 'USD': //美元
                $code = 'USD:%s';
                break;
            case 'EUR': //欧元
                $code = 'EUR:%s';
                break;
            case 'GBP': //英磅
                $code = 'GBP:%s';
                break;
            case 'HKD': //港币
                $code = 'HKD:%s';
                break;
            case 'TWD': //台币
                $code = 'TWD:%s';
                break;
            case 'AUD': //澳元
                $code = 'AUD:%s';
                break;
            case 'JPY': //日元
                $code = 'JPY:%s';
                break;
            case 'KRW': //韩元
                $code = 'KRW:%s';
                break;
            case 'CAD': //加拿大元
                $code = 'CAD:%s';
                break;
            case 'MOP': //澳门元
                $code = 'MOP:%s';
                break;
            case 'CNY': //人民币
            default:
                $code = '￥%s';
                break;

        }
        $price = number_format($price, 2, '.', '');

        return sprintf($code, $price);
    }
    
    /**
     * 函数将给定字串转换为驼峰式
     * 例： camel('foo_bar'); // fooBar
     *
     * @param  string  $value
     * @return string
     */
    public static function camel(string $value):string
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }
    
        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }
    
    /**
     * 判断字串是否包含给定值：
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function contains(string $haystack, $needles):bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
    
        return false;
    }
    
    /**
     * 函数判断字串是否以给定值结尾
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function endsWith(string $haystack, $needles):bool
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === static::substr($haystack, -static::length($needle))) {
                return true;
            }
        }
    
        return false;
    }
    
    /**
     * 函数为字串添加给定单例
     * 例：$string = finish('this/string', '/'); // this/string/
     * @param  string  $value
     * @param  string  $cap
     * @return string
     */
    public static function finish(string $value, string $cap):string
    {
        $quoted = preg_quote($cap, '/');
    
        return preg_replace('/(?:'.$quoted.')+$/u', '', $value).$cap;
    }
    
    /**
     * 函数判断字串是否匹配给定形式。星号表示通配符：
     * 例：$value = is('foo*', 'foobar');   //true
     *
     * @param  string  $pattern
     * @param  string  $value
     * @return bool
     */
    public static function is(string $pattern, string $value):bool
    {
        if ($pattern == $value) {
            return true;
        }
    
        $pattern = preg_quote($pattern, '#');
    
        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern).'\z';
    
        return (bool) preg_match('#^'.$pattern.'#u', $value);
    }
    
    /**
     * 返回字符串长度
     *
     * @param  string  $value
     * @return int
     */
    public static function length(string $value):int
    {
        return mb_strlen($value);
    }
    
    /**
     * 函数限制一个字符串的长度。该函数接收一个字符串作为第一个参数，最大长度作为第二个参数：
     *
     * @param  string  $value
     * @param  int     $limit
     * @param  string  $end
     * @return string
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'):string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }
    
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')).$end;
    }
    
    /**
     * 将给定的字符串所有字母转换成小写
     *
     * @param  string  $value
     * @return string
     */
    public static function lower(string $value):string
    {
        return mb_strtolower($value, 'UTF-8');
    }
    
    /**
     * Limit the number of words in a string.
     *
     * @param  string  $value
     * @param  int     $words
     * @param  string  $end
     * @return string
     */
    public static function words(string $value, int $words = 100, string $end = '...'):string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);
    
        if (! isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }
    
        return rtrim($matches[0]).$end;
    }
    
    /**
     * Parse a Class@method style callback into class and method.
     *
     * @param  string  $callback
     * @param  string  $default
     * @return array
     */
    public static function parseCallback(string $callback, string $default):array
    {
        return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
    }
    
    /**
     * 获取随机字符串
     *
     * @param  int  $length
     * @return string
     */
    public static function randomStr(int $length = 16):string
    {
        $string = '';
    
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
    
            $bytes = static::randomBytes($size);
    
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
    
        return $string;
    }
    
    /**
     * Generate a more truly "" bytes.
     *
     * @param  int  $length
     * @return string
     */
    public static function randomBytes(int $length = 16):string
    {
        return random_bytes($length);
    }
    
    /**
     * Generate a "random" alpha-numeric string.
     *
     * Should not be considered sufficient for cryptography, etc.
     *
     * @param  int  $length
     * @return string
     */
    public static function quickRandom(int $length = 16):string
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }
    
    /**
     * Compares two strings using a constant-time algorithm.
     *
     * Note: This method will leak length information.
     *
     * Note: Adapted from Symfony\Component\Security\Core\Util\StringUtils.
     *
     * @param  string  $knownString
     * @param  string  $userInput
     * @return bool
     */
    public static function equals(string $knownString, string $userInput):string
    {
        if (! is_string($knownString)) {
            $knownString = (string) $knownString;
        }
    
        if (! is_string($userInput)) {
            $userInput = (string) $userInput;
        }
    
        if (function_exists('hash_equals')) {
            return hash_equals($knownString, $userInput);
        }
    
        $knownLength = mb_strlen($knownString, '8bit');
    
        if (mb_strlen($userInput, '8bit') !== $knownLength) {
            return false;
        }
    
        $result = 0;
    
        for ($i = 0; $i < $knownLength; ++$i) {
            $result |= (ord($knownString[$i]) ^ ord($userInput[$i]));
        }
    
        return 0 === $result;
    }
    
    /**
     * 将指定字符串字符转换成大写
     *
     * @param  string  $value
     * @return string
     */
    public static function upper(string $value):string
    {
        return mb_strtoupper($value, 'UTF-8');
    }
    
    /**
     * Convert the given string to title case.
     *
     * @param  string  $value
     * @return string
     */
    public static function title(string $value):string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
    
    /**
     * 函数将字串转换为URL友好型
     * 例：slug("xxxx media", "-");  //xxxx-media
     *
     * @param  string  $title
     * @param  string  $separator
     * @return string
     */
    public static function slug(string $title, string $separator = '-'):string
    {
        $title = static::ascii($title);
    
        // Convert all dashes/underscores into separator
        $flip = $separator == '-' ? '_' : '-';
    
        $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);
    
        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));
    
        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);
    
        return trim($title, $separator);
    }
    
    /**
     * 将给定字串转换为蛇形式
     * 例：snake('fooBar'); //foo_bar
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    public static function snake(string $value, string $delimiter = '_'):string
    {
        $key = $value;
    
        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }
    
        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', $value);
    
            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }
    
        return static::$snakeCache[$key][$delimiter] = $value;
    }
    
    /**
     * 函数判断字串是否以给定值开头：
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function startsWith(string $haystack, $needles):bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }
    
        return false;
    }
    
    /**
     * 函数将字串转换为 StudlyCase 型
     * 例：studly('foo_bar'); // FooBar
     *
     * @param  string  $value
     * @return string
     */
    public static function studly(string $value):string
    {
        $key = $value;
    
        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }
    
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
    
        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }
    
    /**
     * 字符串截取
     *
     * @param  string  $string
     * @param  int  $start
     * @param  int|null  $length
     * @return string
     */
    public static function substr(string $string, int $start, $length = null):string
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }
    
}
