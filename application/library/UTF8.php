<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * A port of [phputf8](http://phputf8.sourceforge.net/) to a unified set
 * of files. Provides multi-byte aware replacement string functions.
 *
 * For UTF-8 support to work correctly, the following requirements must be met:
 *
 * - PCRE needs to be compiled with UTF-8 support (--enable-utf8)
 * - Support for [Unicode properties](http://php.net/manual/reference.pcre.pattern.modifiers.php)
 *   is highly recommended (--enable-unicode-properties)
 * - The [mbstring extension](http://php.net/mbstring) is highly recommended,
 *   but must not be overloading string functions
 *
 * [!!] This file is licensed differently from the rest of Kohana. As a port of
 * [phputf8](http://phputf8.sourceforge.net/), this file is released under the LGPL.
 *
 * @package    Kohana
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2007-2012 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
class UTF8 {

	/**
	 * @var  boolean  Does the server support UTF-8 natively?
	 */
	public static $server_utf8 = NULL;


	/**
	 * Recursively cleans arrays, objects, and strings. Removes ASCII control
	 * codes and converts to the requested charset while silently discarding
	 * incompatible characters.
	 *
	 *     self::clean($_GET); // Clean GET data
	 *
	 * @param   mixed   $var        variable to clean
	 * @param   string  $charset    character set, defaults to utf-8
	 * @return  mixed
	 * @uses    self::clean
	 * @uses    self::strip_ascii_ctrl
	 * @uses    self::is_ascii
	 */
	public static function clean($var, $charset = 'utf-8')
	{
		if (is_array($var) OR is_object($var))
		{
			foreach ($var as $key => $val)
			{
				// Recursion!
				$var[self::clean($key)] = self::clean($val);
			}
		}
		elseif (is_string($var) AND $var !== '')
		{
			// Remove control characters
			$var = self::strip_ascii_ctrl($var);

			if ( ! self::is_ascii($var))
			{
				// Temporarily save the mb_substitute_character() value into a variable
				$mb_substitute_character = mb_substitute_character();

				// Disable substituting illegal characters with the default '?' character
				mb_substitute_character('none');

				// convert encoding, this is expensive, used when $var is not ASCII
				$var = mb_convert_encoding($var, $charset, $charset);

				// Reset mb_substitute_character() value back to the original setting
				mb_substitute_character($mb_substitute_character);
			}
		}

		return $var;
	}

	/**
	 * Tests whether a string contains only 7-bit ASCII bytes. This is used to
	 * determine when to use native functions or UTF-8 functions.
	 *
	 *     $ascii = self::is_ascii($str);
	 *
	 * @param   mixed   $str    string or array of strings to check
	 * @return  boolean
	 */
	public static function is_ascii($str)
	{
		if (is_array($str))
		{
			$str = implode($str);
		}

		return ! preg_match('/[^\x00-\x7F]/S', $str);
	}

	/**
	 * Strips out device control codes in the ASCII range.
	 *
	 *     $str = self::strip_ascii_ctrl($str);
	 *
	 * @param   string  $str    string to clean
	 * @return  string
	 */
	public static function strip_ascii_ctrl($str)
	{
		return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $str);
	}

	/**
	 * Strips out all non-7bit ASCII bytes.
	 *
	 *     $str = self::strip_non_ascii($str);
	 *
	 * @param   string  $str    string to clean
	 * @return  string
	 */
	public static function strip_non_ascii($str)
	{
		return preg_replace('/[^\x00-\x7F]+/S', '', $str);
	}

	/**
	 * Replaces special/accented UTF-8 characters by ASCII-7 "equivalents".
	 *
	 *     $ascii = self::transliterate_to_ascii($utf8);
	 *
	 * @author  Andreas Gohr <andi@splitbrain.org>
	 * @param   string  $str    string to transliterate
	 * @param   integer $case   -1 lowercase only, +1 uppercase only, 0 both cases
	 * @return  string
	 */
	public static function transliterate_to_ascii($str, $case = 0)
	{
		static $utf8_lower_accents = NULL;
		static $utf8_upper_accents = NULL;

		if ($case <= 0)
		{
			if ($utf8_lower_accents === NULL)
			{
				$utf8_lower_accents = array(
					'à' => 'a',  'ô' => 'o',  'ď' => 'd',  'ḟ' => 'f',  'ë' => 'e',  'š' => 's',  'ơ' => 'o',
					'ß' => 'ss', 'ă' => 'a',  'ř' => 'r',  'ț' => 't',  'ň' => 'n',  'ā' => 'a',  'ķ' => 'k',
					'ŝ' => 's',  'ỳ' => 'y',  'ņ' => 'n',  'ĺ' => 'l',  'ħ' => 'h',  'ṗ' => 'p',  'ó' => 'o',
					'ú' => 'u',  'ě' => 'e',  'é' => 'e',  'ç' => 'c',  'ẁ' => 'w',  'ċ' => 'c',  'õ' => 'o',
					'ṡ' => 's',  'ø' => 'o',  'ģ' => 'g',  'ŧ' => 't',  'ș' => 's',  'ė' => 'e',  'ĉ' => 'c',
					'ś' => 's',  'î' => 'i',  'ű' => 'u',  'ć' => 'c',  'ę' => 'e',  'ŵ' => 'w',  'ṫ' => 't',
					'ū' => 'u',  'č' => 'c',  'ö' => 'o',  'è' => 'e',  'ŷ' => 'y',  'ą' => 'a',  'ł' => 'l',
					'ų' => 'u',  'ů' => 'u',  'ş' => 's',  'ğ' => 'g',  'ļ' => 'l',  'ƒ' => 'f',  'ž' => 'z',
					'ẃ' => 'w',  'ḃ' => 'b',  'å' => 'a',  'ì' => 'i',  'ï' => 'i',  'ḋ' => 'd',  'ť' => 't',
					'ŗ' => 'r',  'ä' => 'a',  'í' => 'i',  'ŕ' => 'r',  'ê' => 'e',  'ü' => 'u',  'ò' => 'o',
					'ē' => 'e',  'ñ' => 'n',  'ń' => 'n',  'ĥ' => 'h',  'ĝ' => 'g',  'đ' => 'd',  'ĵ' => 'j',
					'ÿ' => 'y',  'ũ' => 'u',  'ŭ' => 'u',  'ư' => 'u',  'ţ' => 't',  'ý' => 'y',  'ő' => 'o',
					'â' => 'a',  'ľ' => 'l',  'ẅ' => 'w',  'ż' => 'z',  'ī' => 'i',  'ã' => 'a',  'ġ' => 'g',
					'ṁ' => 'm',  'ō' => 'o',  'ĩ' => 'i',  'ù' => 'u',  'į' => 'i',  'ź' => 'z',  'á' => 'a',
					'û' => 'u',  'þ' => 'th', 'ð' => 'dh', 'æ' => 'ae', 'µ' => 'u',  'ĕ' => 'e',  'ı' => 'i',
				);
			}

			$str = str_replace(
				array_keys($utf8_lower_accents),
				array_values($utf8_lower_accents),
				$str
			);
		}

		if ($case >= 0)
		{
			if ($utf8_upper_accents === NULL)
			{
				$utf8_upper_accents = array(
					'À' => 'A',  'Ô' => 'O',  'Ď' => 'D',  'Ḟ' => 'F',  'Ë' => 'E',  'Š' => 'S',  'Ơ' => 'O',
					'Ă' => 'A',  'Ř' => 'R',  'Ț' => 'T',  'Ň' => 'N',  'Ā' => 'A',  'Ķ' => 'K',  'Ĕ' => 'E',
					'Ŝ' => 'S',  'Ỳ' => 'Y',  'Ņ' => 'N',  'Ĺ' => 'L',  'Ħ' => 'H',  'Ṗ' => 'P',  'Ó' => 'O',
					'Ú' => 'U',  'Ě' => 'E',  'É' => 'E',  'Ç' => 'C',  'Ẁ' => 'W',  'Ċ' => 'C',  'Õ' => 'O',
					'Ṡ' => 'S',  'Ø' => 'O',  'Ģ' => 'G',  'Ŧ' => 'T',  'Ș' => 'S',  'Ė' => 'E',  'Ĉ' => 'C',
					'Ś' => 'S',  'Î' => 'I',  'Ű' => 'U',  'Ć' => 'C',  'Ę' => 'E',  'Ŵ' => 'W',  'Ṫ' => 'T',
					'Ū' => 'U',  'Č' => 'C',  'Ö' => 'O',  'È' => 'E',  'Ŷ' => 'Y',  'Ą' => 'A',  'Ł' => 'L',
					'Ų' => 'U',  'Ů' => 'U',  'Ş' => 'S',  'Ğ' => 'G',  'Ļ' => 'L',  'Ƒ' => 'F',  'Ž' => 'Z',
					'Ẃ' => 'W',  'Ḃ' => 'B',  'Å' => 'A',  'Ì' => 'I',  'Ï' => 'I',  'Ḋ' => 'D',  'Ť' => 'T',
					'Ŗ' => 'R',  'Ä' => 'A',  'Í' => 'I',  'Ŕ' => 'R',  'Ê' => 'E',  'Ü' => 'U',  'Ò' => 'O',
					'Ē' => 'E',  'Ñ' => 'N',  'Ń' => 'N',  'Ĥ' => 'H',  'Ĝ' => 'G',  'Đ' => 'D',  'Ĵ' => 'J',
					'Ÿ' => 'Y',  'Ũ' => 'U',  'Ŭ' => 'U',  'Ư' => 'U',  'Ţ' => 'T',  'Ý' => 'Y',  'Ő' => 'O',
					'Â' => 'A',  'Ľ' => 'L',  'Ẅ' => 'W',  'Ż' => 'Z',  'Ī' => 'I',  'Ã' => 'A',  'Ġ' => 'G',
					'Ṁ' => 'M',  'Ō' => 'O',  'Ĩ' => 'I',  'Ù' => 'U',  'Į' => 'I',  'Ź' => 'Z',  'Á' => 'A',
					'Û' => 'U',  'Þ' => 'Th', 'Ð' => 'Dh', 'Æ' => 'Ae', 'İ' => 'I',
				);
			}

			$str = str_replace(
				array_keys($utf8_upper_accents),
				array_values($utf8_upper_accents),
				$str
			);
		}

		return $str;
	}

	/**
	 * Returns the length of the given string. This is a UTF8-aware version
	 * of [strlen](http://php.net/strlen).
	 *
	 *     $length = self::strlen($str);
	 *
	 * @param   string  $str    string being measured for length
	 * @return  integer
	 * @uses    self::$server_utf8
	 * @uses    'utf-8'
	 */
	public static function strlen($str)
	{
		if (self::$server_utf8)
			return mb_strlen($str, 'utf-8');

		if (self::is_ascii($str))
			return strlen($str);

		return strlen(utf8_decode($str));
	}

	/**
	 * Finds position of first occurrence of a UTF-8 string. This is a
	 * UTF8-aware version of [strpos](http://php.net/strpos).
	 *
	 *     $position = self::strpos($str, $search);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str    haystack
	 * @param   string  $search needle
	 * @param   integer $offset offset from which character in haystack to start searching
	 * @return  integer position of needle
	 * @return  boolean FALSE if the needle is not found
	 * @uses    self::$server_utf8
	 * @uses    'utf-8'
	 */
	public static function strpos($str, $search, $offset = 0)
	{
		if (self::$server_utf8)
			return mb_strpos($str, $search, $offset, 'utf-8');

		$offset = (int) $offset;

		if (self::is_ascii($str) AND self::is_ascii($search))
			return strpos($str, $search, $offset);

		if ($offset == 0)
		{
			$array = explode($search, $str, 2);
			return isset($array[1]) ? self::strlen($array[0]) : FALSE;
		}

		$str = self::substr($str, $offset);
		$pos = self::strpos($str, $search);
		return ($pos === FALSE) ? FALSE : ($pos + $offset);
	}

	/**
	 * Finds position of last occurrence of a char in a UTF-8 string. This is
	 * a UTF8-aware version of [strrpos](http://php.net/strrpos).
	 *
	 *     $position = self::strrpos($str, $search);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str    haystack
	 * @param   string  $search needle
	 * @param   integer $offset offset from which character in haystack to start searching
	 * @return  integer position of needle
	 * @return  boolean FALSE if the needle is not found
	 * @uses    self::$server_utf8
	 */
	public static function strrpos($str, $search, $offset = 0)
	{
		if (self::$server_utf8)
			return mb_strrpos($str, $search, $offset, 'utf-8');

		$offset = (int) $offset;

		if (self::is_ascii($str) AND self::is_ascii($search))
			return strrpos($str, $search, $offset);

		if ($offset == 0)
		{
			$array = explode($search, $str, -1);
			return isset($array[0]) ? self::strlen(implode($search, $array)) : FALSE;
		}

		$str = self::substr($str, $offset);
		$pos = self::strrpos($str, $search);
		return ($pos === FALSE) ? FALSE : ($pos + $offset);
	}

	/**
	 * Returns part of a UTF-8 string. This is a UTF8-aware version
	 * of [substr](http://php.net/substr).
	 *
	 *     $sub = self::substr($str, $offset);
	 *
	 * @author  Chris Smith <chris@jalakai.co.uk>
	 * @param   string  $str    input string
	 * @param   integer $offset offset
	 * @param   integer $length length limit
	 * @return  string
	 * @uses    self::$server_utf8
	 * @uses    'utf-8'
	 */
	public static function substr($str, $offset, $length = NULL)
	{
		if (self::$server_utf8)
			return ($length === NULL)
				? mb_substr($str, $offset, mb_strlen($str), 'utf-8')
				: mb_substr($str, $offset, $length, 'utf-8');

		if (self::is_ascii($str))
			return ($length === NULL) ? substr($str, $offset) : substr($str, $offset, $length);

		// Normalize params
		$str    = (string) $str;
		$strlen = self::strlen($str);
		$offset = (int) ($offset < 0) ? max(0, $strlen + $offset) : $offset; // Normalize to positive offset
		$length = ($length === NULL) ? NULL : (int) $length;

		// Impossible
		if ($length === 0 OR $offset >= $strlen OR ($length < 0 AND $length <= $offset - $strlen))
			return '';

		// Whole string
		if ($offset == 0 AND ($length === NULL OR $length >= $strlen))
			return $str;

		// Build regex
		$regex = '^';

		// Create an offset expression
		if ($offset > 0)
		{
			// PCRE repeating quantifiers must be less than 65536, so repeat when necessary
			$x = (int) ($offset / 65535);
			$y = (int) ($offset % 65535);
			$regex .= ($x == 0) ? '' : ('(?:.{65535}){'.$x.'}');
			$regex .= ($y == 0) ? '' : ('.{'.$y.'}');
		}

		// Create a length expression
		if ($length === NULL)
		{
			$regex .= '(.*)'; // No length set, grab it all
		}
		// Find length from the left (positive length)
		elseif ($length > 0)
		{
			// Reduce length so that it can't go beyond the end of the string
			$length = min($strlen - $offset, $length);

			$x = (int) ($length / 65535);
			$y = (int) ($length % 65535);
			$regex .= '(';
			$regex .= ($x == 0) ? '' : ('(?:.{65535}){'.$x.'}');
			$regex .= '.{'.$y.'})';
		}
		// Find length from the right (negative length)
		else
		{
			$x = (int) (-$length / 65535);
			$y = (int) (-$length % 65535);
			$regex .= '(.*)';
			$regex .= ($x == 0) ? '' : ('(?:.{65535}){'.$x.'}');
			$regex .= '.{'.$y.'}';
		}

		preg_match('/'.$regex.'/us', $str, $matches);
		return $matches[1];
	}

	/**
	 * Replaces text within a portion of a UTF-8 string. This is a UTF8-aware
	 * version of [substr_replace](http://php.net/substr_replace).
	 *
	 *     $str = self::substr_replace($str, $replacement, $offset);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str            input string
	 * @param   string  $replacement    replacement string
	 * @param   integer $offset         offset
	 * @return  string
	 */
	public static function substr_replace($str, $replacement, $offset, $length = NULL)
	{
		if (self::is_ascii($str))
			return ($length === NULL) ? substr_replace($str, $replacement, $offset) : substr_replace($str, $replacement, $offset, $length);

		$length = ($length === NULL) ? self::strlen($str) : (int) $length;
		preg_match_all('/./us', $str, $str_array);
		preg_match_all('/./us', $replacement, $replacement_array);

		array_splice($str_array[0], $offset, $length, $replacement_array[0]);
		return implode('', $str_array[0]);
	}

	/**
	 * Makes a UTF-8 string lowercase. This is a UTF8-aware version
	 * of [strtolower](http://php.net/strtolower).
	 *
	 *     $str = self::strtolower($str);
	 *
	 * @author  Andreas Gohr <andi@splitbrain.org>
	 * @param   string  $str mixed case string
	 * @return  string
	 * @uses    self::$server_utf8
	 * @uses    'utf-8'
	 */
	public static function strtolower($str)
	{
		if (self::$server_utf8)
			return mb_strtolower($str, 'utf-8');

		if (self::is_ascii($str))
			return strtolower($str);

		static $utf8_upper_to_lower = NULL;

		if ($utf8_upper_to_lower === NULL)
		{
			$utf8_upper_to_lower = array(
				0x0041=>0x0061, 0x03A6=>0x03C6, 0x0162=>0x0163, 0x00C5=>0x00E5, 0x0042=>0x0062,
				0x0139=>0x013A, 0x00C1=>0x00E1, 0x0141=>0x0142, 0x038E=>0x03CD, 0x0100=>0x0101,
				0x0490=>0x0491, 0x0394=>0x03B4, 0x015A=>0x015B, 0x0044=>0x0064, 0x0393=>0x03B3,
				0x00D4=>0x00F4, 0x042A=>0x044A, 0x0419=>0x0439, 0x0112=>0x0113, 0x041C=>0x043C,
				0x015E=>0x015F, 0x0143=>0x0144, 0x00CE=>0x00EE, 0x040E=>0x045E, 0x042F=>0x044F,
				0x039A=>0x03BA, 0x0154=>0x0155, 0x0049=>0x0069, 0x0053=>0x0073, 0x1E1E=>0x1E1F,
				0x0134=>0x0135, 0x0427=>0x0447, 0x03A0=>0x03C0, 0x0418=>0x0438, 0x00D3=>0x00F3,
				0x0420=>0x0440, 0x0404=>0x0454, 0x0415=>0x0435, 0x0429=>0x0449, 0x014A=>0x014B,
				0x0411=>0x0431, 0x0409=>0x0459, 0x1E02=>0x1E03, 0x00D6=>0x00F6, 0x00D9=>0x00F9,
				0x004E=>0x006E, 0x0401=>0x0451, 0x03A4=>0x03C4, 0x0423=>0x0443, 0x015C=>0x015D,
				0x0403=>0x0453, 0x03A8=>0x03C8, 0x0158=>0x0159, 0x0047=>0x0067, 0x00C4=>0x00E4,
				0x0386=>0x03AC, 0x0389=>0x03AE, 0x0166=>0x0167, 0x039E=>0x03BE, 0x0164=>0x0165,
				0x0116=>0x0117, 0x0108=>0x0109, 0x0056=>0x0076, 0x00DE=>0x00FE, 0x0156=>0x0157,
				0x00DA=>0x00FA, 0x1E60=>0x1E61, 0x1E82=>0x1E83, 0x00C2=>0x00E2, 0x0118=>0x0119,
				0x0145=>0x0146, 0x0050=>0x0070, 0x0150=>0x0151, 0x042E=>0x044E, 0x0128=>0x0129,
				0x03A7=>0x03C7, 0x013D=>0x013E, 0x0422=>0x0442, 0x005A=>0x007A, 0x0428=>0x0448,
				0x03A1=>0x03C1, 0x1E80=>0x1E81, 0x016C=>0x016D, 0x00D5=>0x00F5, 0x0055=>0x0075,
				0x0176=>0x0177, 0x00DC=>0x00FC, 0x1E56=>0x1E57, 0x03A3=>0x03C3, 0x041A=>0x043A,
				0x004D=>0x006D, 0x016A=>0x016B, 0x0170=>0x0171, 0x0424=>0x0444, 0x00CC=>0x00EC,
				0x0168=>0x0169, 0x039F=>0x03BF, 0x004B=>0x006B, 0x00D2=>0x00F2, 0x00C0=>0x00E0,
				0x0414=>0x0434, 0x03A9=>0x03C9, 0x1E6A=>0x1E6B, 0x00C3=>0x00E3, 0x042D=>0x044D,
				0x0416=>0x0436, 0x01A0=>0x01A1, 0x010C=>0x010D, 0x011C=>0x011D, 0x00D0=>0x00F0,
				0x013B=>0x013C, 0x040F=>0x045F, 0x040A=>0x045A, 0x00C8=>0x00E8, 0x03A5=>0x03C5,
				0x0046=>0x0066, 0x00DD=>0x00FD, 0x0043=>0x0063, 0x021A=>0x021B, 0x00CA=>0x00EA,
				0x0399=>0x03B9, 0x0179=>0x017A, 0x00CF=>0x00EF, 0x01AF=>0x01B0, 0x0045=>0x0065,
				0x039B=>0x03BB, 0x0398=>0x03B8, 0x039C=>0x03BC, 0x040C=>0x045C, 0x041F=>0x043F,
				0x042C=>0x044C, 0x00DE=>0x00FE, 0x00D0=>0x00F0, 0x1EF2=>0x1EF3, 0x0048=>0x0068,
				0x00CB=>0x00EB, 0x0110=>0x0111, 0x0413=>0x0433, 0x012E=>0x012F, 0x00C6=>0x00E6,
				0x0058=>0x0078, 0x0160=>0x0161, 0x016E=>0x016F, 0x0391=>0x03B1, 0x0407=>0x0457,
				0x0172=>0x0173, 0x0178=>0x00FF, 0x004F=>0x006F, 0x041B=>0x043B, 0x0395=>0x03B5,
				0x0425=>0x0445, 0x0120=>0x0121, 0x017D=>0x017E, 0x017B=>0x017C, 0x0396=>0x03B6,
				0x0392=>0x03B2, 0x0388=>0x03AD, 0x1E84=>0x1E85, 0x0174=>0x0175, 0x0051=>0x0071,
				0x0417=>0x0437, 0x1E0A=>0x1E0B, 0x0147=>0x0148, 0x0104=>0x0105, 0x0408=>0x0458,
				0x014C=>0x014D, 0x00CD=>0x00ED, 0x0059=>0x0079, 0x010A=>0x010B, 0x038F=>0x03CE,
				0x0052=>0x0072, 0x0410=>0x0430, 0x0405=>0x0455, 0x0402=>0x0452, 0x0126=>0x0127,
				0x0136=>0x0137, 0x012A=>0x012B, 0x038A=>0x03AF, 0x042B=>0x044B, 0x004C=>0x006C,
				0x0397=>0x03B7, 0x0124=>0x0125, 0x0218=>0x0219, 0x00DB=>0x00FB, 0x011E=>0x011F,
				0x041E=>0x043E, 0x1E40=>0x1E41, 0x039D=>0x03BD, 0x0106=>0x0107, 0x03AB=>0x03CB,
				0x0426=>0x0446, 0x00DE=>0x00FE, 0x00C7=>0x00E7, 0x03AA=>0x03CA, 0x0421=>0x0441,
				0x0412=>0x0432, 0x010E=>0x010F, 0x00D8=>0x00F8, 0x0057=>0x0077, 0x011A=>0x011B,
				0x0054=>0x0074, 0x004A=>0x006A, 0x040B=>0x045B, 0x0406=>0x0456, 0x0102=>0x0103,
				0x039B=>0x03BB, 0x00D1=>0x00F1, 0x041D=>0x043D, 0x038C=>0x03CC, 0x00C9=>0x00E9,
				0x00D0=>0x00F0, 0x0407=>0x0457, 0x0122=>0x0123,
			);
		}

		$uni = self::to_unicode($str);

		if ($uni === FALSE)
			return FALSE;

		for ($i = 0, $c = count($uni); $i < $c; $i++)
		{
			if (isset($utf8_upper_to_lower[$uni[$i]]))
			{
				$uni[$i] = $utf8_upper_to_lower[$uni[$i]];
			}
		}

		return self::from_unicode($uni);
	}

	/**
	 * Makes a UTF-8 string uppercase. This is a UTF8-aware version
	 * of [strtoupper](http://php.net/strtoupper).
	 *
	 * @author  Andreas Gohr <andi@splitbrain.org>
	 * @param   string  $str mixed case string
	 * @return  string
	 * @uses    self::$server_utf8
	 * @uses    'utf-8'
	 */
	public static function strtoupper($str)
	{
		if (self::$server_utf8)
			return mb_strtoupper($str, 'utf-8');

		if (self::is_ascii($str))
			return strtoupper($str);

		static $utf8_lower_to_upper = NULL;

		if ($utf8_lower_to_upper === NULL)
		{
			$utf8_lower_to_upper = array(
				0x0061=>0x0041, 0x03C6=>0x03A6, 0x0163=>0x0162, 0x00E5=>0x00C5, 0x0062=>0x0042,
				0x013A=>0x0139, 0x00E1=>0x00C1, 0x0142=>0x0141, 0x03CD=>0x038E, 0x0101=>0x0100,
				0x0491=>0x0490, 0x03B4=>0x0394, 0x015B=>0x015A, 0x0064=>0x0044, 0x03B3=>0x0393,
				0x00F4=>0x00D4, 0x044A=>0x042A, 0x0439=>0x0419, 0x0113=>0x0112, 0x043C=>0x041C,
				0x015F=>0x015E, 0x0144=>0x0143, 0x00EE=>0x00CE, 0x045E=>0x040E, 0x044F=>0x042F,
				0x03BA=>0x039A, 0x0155=>0x0154, 0x0069=>0x0049, 0x0073=>0x0053, 0x1E1F=>0x1E1E,
				0x0135=>0x0134, 0x0447=>0x0427, 0x03C0=>0x03A0, 0x0438=>0x0418, 0x00F3=>0x00D3,
				0x0440=>0x0420, 0x0454=>0x0404, 0x0435=>0x0415, 0x0449=>0x0429, 0x014B=>0x014A,
				0x0431=>0x0411, 0x0459=>0x0409, 0x1E03=>0x1E02, 0x00F6=>0x00D6, 0x00F9=>0x00D9,
				0x006E=>0x004E, 0x0451=>0x0401, 0x03C4=>0x03A4, 0x0443=>0x0423, 0x015D=>0x015C,
				0x0453=>0x0403, 0x03C8=>0x03A8, 0x0159=>0x0158, 0x0067=>0x0047, 0x00E4=>0x00C4,
				0x03AC=>0x0386, 0x03AE=>0x0389, 0x0167=>0x0166, 0x03BE=>0x039E, 0x0165=>0x0164,
				0x0117=>0x0116, 0x0109=>0x0108, 0x0076=>0x0056, 0x00FE=>0x00DE, 0x0157=>0x0156,
				0x00FA=>0x00DA, 0x1E61=>0x1E60, 0x1E83=>0x1E82, 0x00E2=>0x00C2, 0x0119=>0x0118,
				0x0146=>0x0145, 0x0070=>0x0050, 0x0151=>0x0150, 0x044E=>0x042E, 0x0129=>0x0128,
				0x03C7=>0x03A7, 0x013E=>0x013D, 0x0442=>0x0422, 0x007A=>0x005A, 0x0448=>0x0428,
				0x03C1=>0x03A1, 0x1E81=>0x1E80, 0x016D=>0x016C, 0x00F5=>0x00D5, 0x0075=>0x0055,
				0x0177=>0x0176, 0x00FC=>0x00DC, 0x1E57=>0x1E56, 0x03C3=>0x03A3, 0x043A=>0x041A,
				0x006D=>0x004D, 0x016B=>0x016A, 0x0171=>0x0170, 0x0444=>0x0424, 0x00EC=>0x00CC,
				0x0169=>0x0168, 0x03BF=>0x039F, 0x006B=>0x004B, 0x00F2=>0x00D2, 0x00E0=>0x00C0,
				0x0434=>0x0414, 0x03C9=>0x03A9, 0x1E6B=>0x1E6A, 0x00E3=>0x00C3, 0x044D=>0x042D,
				0x0436=>0x0416, 0x01A1=>0x01A0, 0x010D=>0x010C, 0x011D=>0x011C, 0x00F0=>0x00D0,
				0x013C=>0x013B, 0x045F=>0x040F, 0x045A=>0x040A, 0x00E8=>0x00C8, 0x03C5=>0x03A5,
				0x0066=>0x0046, 0x00FD=>0x00DD, 0x0063=>0x0043, 0x021B=>0x021A, 0x00EA=>0x00CA,
				0x03B9=>0x0399, 0x017A=>0x0179, 0x00EF=>0x00CF, 0x01B0=>0x01AF, 0x0065=>0x0045,
				0x03BB=>0x039B, 0x03B8=>0x0398, 0x03BC=>0x039C, 0x045C=>0x040C, 0x043F=>0x041F,
				0x044C=>0x042C, 0x00FE=>0x00DE, 0x00F0=>0x00D0, 0x1EF3=>0x1EF2, 0x0068=>0x0048,
				0x00EB=>0x00CB, 0x0111=>0x0110, 0x0433=>0x0413, 0x012F=>0x012E, 0x00E6=>0x00C6,
				0x0078=>0x0058, 0x0161=>0x0160, 0x016F=>0x016E, 0x03B1=>0x0391, 0x0457=>0x0407,
				0x0173=>0x0172, 0x00FF=>0x0178, 0x006F=>0x004F, 0x043B=>0x041B, 0x03B5=>0x0395,
				0x0445=>0x0425, 0x0121=>0x0120, 0x017E=>0x017D, 0x017C=>0x017B, 0x03B6=>0x0396,
				0x03B2=>0x0392, 0x03AD=>0x0388, 0x1E85=>0x1E84, 0x0175=>0x0174, 0x0071=>0x0051,
				0x0437=>0x0417, 0x1E0B=>0x1E0A, 0x0148=>0x0147, 0x0105=>0x0104, 0x0458=>0x0408,
				0x014D=>0x014C, 0x00ED=>0x00CD, 0x0079=>0x0059, 0x010B=>0x010A, 0x03CE=>0x038F,
				0x0072=>0x0052, 0x0430=>0x0410, 0x0455=>0x0405, 0x0452=>0x0402, 0x0127=>0x0126,
				0x0137=>0x0136, 0x012B=>0x012A, 0x03AF=>0x038A, 0x044B=>0x042B, 0x006C=>0x004C,
				0x03B7=>0x0397, 0x0125=>0x0124, 0x0219=>0x0218, 0x00FB=>0x00DB, 0x011F=>0x011E,
				0x043E=>0x041E, 0x1E41=>0x1E40, 0x03BD=>0x039D, 0x0107=>0x0106, 0x03CB=>0x03AB,
				0x0446=>0x0426, 0x00FE=>0x00DE, 0x00E7=>0x00C7, 0x03CA=>0x03AA, 0x0441=>0x0421,
				0x0432=>0x0412, 0x010F=>0x010E, 0x00F8=>0x00D8, 0x0077=>0x0057, 0x011B=>0x011A,
				0x0074=>0x0054, 0x006A=>0x004A, 0x045B=>0x040B, 0x0456=>0x0406, 0x0103=>0x0102,
				0x03BB=>0x039B, 0x00F1=>0x00D1, 0x043D=>0x041D, 0x03CC=>0x038C, 0x00E9=>0x00C9,
				0x00F0=>0x00D0, 0x0457=>0x0407, 0x0123=>0x0122,
			);
		}

		$uni = self::to_unicode($str);

		if ($uni === FALSE)
			return FALSE;

		for ($i = 0, $c = count($uni); $i < $c; $i++)
		{
			if (isset($utf8_lower_to_upper[$uni[$i]]))
			{
				$uni[$i] = $utf8_lower_to_upper[$uni[$i]];
			}
		}

		return self::from_unicode($uni);
	}

	/**
	 * Makes a UTF-8 string's first character uppercase. This is a UTF8-aware
	 * version of [ucfirst](http://php.net/ucfirst).
	 *
	 *     $str = self::ucfirst($str);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str mixed case string
	 * @return  string
	 */
	public static function ucfirst($str)
	{
		if (self::is_ascii($str))
			return ucfirst($str);

		preg_match('/^(.?)(.*)$/us', $str, $matches);
		return self::strtoupper($matches[1]).$matches[2];
	}

	/**
	 * Makes the first character of every word in a UTF-8 string uppercase.
	 * This is a UTF8-aware version of [ucwords](http://php.net/ucwords).
	 *
	 *     $str = self::ucwords($str);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str mixed case string
	 * @return  string
	 */
	public static function ucwords($str)
	{
		if (self::is_ascii($str))
			return ucwords($str);

		// [\x0c\x09\x0b\x0a\x0d\x20] matches form feeds, horizontal tabs, vertical tabs, linefeeds and carriage returns.
		// This corresponds to the definition of a 'word' defined at http://php.net/ucwords
		return preg_replace_callback(
			'/(?<=^|[\x0c\x09\x0b\x0a\x0d\x20])[^\x0c\x09\x0b\x0a\x0d\x20]/u',
			function($matches){
				return self::strtoupper($matches[0]);
			},
			$str);
	}

	/**
	 * Case-insensitive UTF-8 string comparison. This is a UTF8-aware version
	 * of [strcasecmp](http://php.net/strcasecmp).
	 *
	 *     $compare = self::strcasecmp($str1, $str2);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str1   string to compare
	 * @param   string  $str2   string to compare
	 * @return  integer less than 0 if str1 is less than str2
	 * @return  integer greater than 0 if str1 is greater than str2
	 * @return  integer 0 if they are equal
	 */
	public static function strcasecmp($str1, $str2)
	{
		if (self::is_ascii($str1) AND self::is_ascii($str2))
			return strcasecmp($str1, $str2);

		$str1 = self::strtolower($str1);
		$str2 = self::strtolower($str2);
		return strcmp($str1, $str2);
	}

	/**
	 * Returns a string or an array with all occurrences of search in subject
	 * (ignoring case) and replaced with the given replace value. This is a
	 * UTF8-aware version of [str_ireplace](http://php.net/str_ireplace).
	 *
	 * [!!] This function is very slow compared to the native version. Avoid
	 * using it when possible.
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com
	 * @param   string|array    $search     text to replace
	 * @param   string|array    $replace    replacement text
	 * @param   string|array    $str        subject text
	 * @param   integer         $count      number of matched and replaced needles will be returned via this parameter which is passed by reference
	 * @return  string  if the input was a string
	 * @return  array   if the input was an array
	 */
	public static function str_ireplace($search, $replace, $str, & $count = NULL)
	{
		if (self::is_ascii($search) AND self::is_ascii($replace) AND self::is_ascii($str))
			return str_ireplace($search, $replace, $str, $count);

		if (is_array($str))
		{
			foreach ($str as $key => $val)
			{
				$str[$key] = self::str_ireplace($search, $replace, $val, $count);
			}
			return $str;
		}

		if (is_array($search))
		{
			$keys = array_keys($search);

			foreach ($keys as $k)
			{
				if (is_array($replace))
				{
					if (array_key_exists($k, $replace))
					{
						$str = self::str_ireplace($search[$k], $replace[$k], $str, $count);
					}
					else
					{
						$str = self::str_ireplace($search[$k], '', $str, $count);
					}
				}
				else
				{
					$str = self::str_ireplace($search[$k], $replace, $str, $count);
				}
			}
			return $str;
		}

		$search = self::strtolower($search);
		$str_lower = self::strtolower($str);

		$total_matched_strlen = 0;
		$i = 0;

		while (preg_match('/(.*?)'.preg_quote($search, '/').'/s', $str_lower, $matches))
		{
			$matched_strlen = strlen($matches[0]);
			$str_lower = substr($str_lower, $matched_strlen);

			$offset = $total_matched_strlen + strlen($matches[1]) + ($i * (strlen($replace) - 1));
			$str = substr_replace($str, $replace, $offset, strlen($search));

			$total_matched_strlen += $matched_strlen;
			$i++;
		}

		$count += $i;
		return $str;
	}

	/**
	 * Case-insensitive UTF-8 version of strstr. Returns all of input string
	 * from the first occurrence of needle to the end. This is a UTF8-aware
	 * version of [stristr](http://php.net/stristr).
	 *
	 *     $found = self::stristr($str, $search);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str    input string
	 * @param   string  $search needle
	 * @return  string  matched substring if found
	 * @return  FALSE   if the substring was not found
	 */
	public static function stristr($str, $search)
	{
		if (self::is_ascii($str) AND self::is_ascii($search))
			return stristr($str, $search);

		if ($search == '')
			return $str;

		$str_lower = self::strtolower($str);
		$search_lower = self::strtolower($search);

		preg_match('/^(.*?)'.preg_quote($search_lower, '/').'/s', $str_lower, $matches);

		if (isset($matches[1]))
			return substr($str, strlen($matches[1]));

		return FALSE;
	}

	/**
	 * Finds the length of the initial segment matching mask. This is a
	 * UTF8-aware version of [strspn](http://php.net/strspn).
	 *
	 *     $found = self::strspn($str, $mask);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str    input string
	 * @param   string  $mask   mask for search
	 * @param   integer $offset start position of the string to examine
	 * @param   integer $length length of the string to examine
	 * @return  integer length of the initial segment that contains characters in the mask
	 */
	public static function strspn($str, $mask, $offset = NULL, $length = NULL)
	{
		if ($str == '' OR $mask == '')
			return 0;

		if (self::is_ascii($str) AND self::is_ascii($mask))
			return ($offset === NULL) ? strspn($str, $mask) : (($length === NULL) ? strspn($str, $mask, $offset) : strspn($str, $mask, $offset, $length));

		if ($offset !== NULL OR $length !== NULL)
		{
			$str = self::substr($str, $offset, $length);
		}

		// Escape these characters:  - [ ] . : \ ^ /
		// The . and : are escaped to prevent possible warnings about POSIX regex elements
		$mask = preg_replace('#[-[\].:\\\\^/]#', '\\\\$0', $mask);
		preg_match('/^[^'.$mask.']+/u', $str, $matches);

		return isset($matches[0]) ? self::strlen($matches[0]) : 0;
	}

	/**
	 * Finds the length of the initial segment not matching mask. This is a
	 * UTF8-aware version of [strcspn](http://php.net/strcspn).
	 *
	 *     $found = self::strcspn($str, $mask);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str    input string
	 * @param   string  $mask   mask for search
	 * @param   integer $offset start position of the string to examine
	 * @param   integer $length length of the string to examine
	 * @return  integer length of the initial segment that contains characters not in the mask
	 */
	public static function strcspn($str, $mask, $offset = NULL, $length = NULL)
	{
		if ($str == '' OR $mask == '')
			return 0;

		if (self::is_ascii($str) AND self::is_ascii($mask))
			return ($offset === NULL) ? strcspn($str, $mask) : (($length === NULL) ? strcspn($str, $mask, $offset) : strcspn($str, $mask, $offset, $length));

		if ($offset !== NULL OR $length !== NULL)
		{
			$str = self::substr($str, $offset, $length);
		}

		// Escape these characters:  - [ ] . : \ ^ /
		// The . and : are escaped to prevent possible warnings about POSIX regex elements
		$mask = preg_replace('#[-[\].:\\\\^/]#', '\\\\$0', $mask);
		preg_match('/^[^'.$mask.']+/u', $str, $matches);

		return isset($matches[0]) ? self::strlen($matches[0]) : 0;
	}

	/**
	 * Pads a UTF-8 string to a certain length with another string. This is a
	 * UTF8-aware version of [str_pad](http://php.net/str_pad).
	 *
	 *     $str = self::str_pad($str, $length);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str                input string
	 * @param   integer $final_str_length   desired string length after padding
	 * @param   string  $pad_str            string to use as padding
	 * @param   string  $pad_type           padding type: STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH
	 * @return  string
	 */
	public static function str_pad($str, $final_str_length, $pad_str = ' ', $pad_type = STR_PAD_RIGHT)
	{
		if (self::is_ascii($str) AND self::is_ascii($pad_str))
			return str_pad($str, $final_str_length, $pad_str, $pad_type);

		$str_length = self::strlen($str);

		if ($final_str_length <= 0 OR $final_str_length <= $str_length)
			return $str;

		$pad_str_length = self::strlen($pad_str);
		$pad_length = $final_str_length - $str_length;

		if ($pad_type == STR_PAD_RIGHT)
		{
			$repeat = ceil($pad_length / $pad_str_length);
			return self::substr($str.str_repeat($pad_str, $repeat), 0, $final_str_length);
		}

		if ($pad_type == STR_PAD_LEFT)
		{
			$repeat = ceil($pad_length / $pad_str_length);
			return self::substr(str_repeat($pad_str, $repeat), 0, floor($pad_length)).$str;
		}

		if ($pad_type == STR_PAD_BOTH)
		{
			$pad_length /= 2;
			$pad_length_left = floor($pad_length);
			$pad_length_right = ceil($pad_length);
			$repeat_left = ceil($pad_length_left / $pad_str_length);
			$repeat_right = ceil($pad_length_right / $pad_str_length);

			$pad_left = self::substr(str_repeat($pad_str, $repeat_left), 0, $pad_length_left);
			$pad_right = self::substr(str_repeat($pad_str, $repeat_right), 0, $pad_length_right);
			return $pad_left.$str.$pad_right;
		}

		throw new Kohana_Exception("self::str_pad: Unknown padding type (:pad_type)", array(
			':pad_type' => $pad_type,
		));
	}

	/**
	 * Converts a UTF-8 string to an array. This is a UTF8-aware version of
	 * [str_split](http://php.net/str_split).
	 *
	 *     $array = self::str_split($str);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str            input string
	 * @param   integer $split_length   maximum length of each chunk
	 * @return  array
	 */
	public static function str_split($str, $split_length = 1)
	{
		$split_length = (int) $split_length;

		if (self::is_ascii($str))
			return str_split($str, $split_length);

		if ($split_length < 1)
			return FALSE;

		if (self::strlen($str) <= $split_length)
			return array($str);

		preg_match_all('/.{'.$split_length.'}|[^\x00]{1,'.$split_length.'}$/us', $str, $matches);

		return $matches[0];
	}

	/**
	 * Reverses a UTF-8 string. This is a UTF8-aware version of [strrev](http://php.net/strrev).
	 *
	 *     $str = self::strrev($str);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $str string to be reversed
	 * @return  string
	 */
	public static function strrev($str)
	{
		if (self::is_ascii($str))
			return strrev($str);

		preg_match_all('/./us', $str, $matches);
		return implode('', array_reverse($matches[0]));
	}

	/**
	 * Strips whitespace (or other UTF-8 characters) from the beginning and
	 * end of a string. This is a UTF8-aware version of [trim](http://php.net/trim).
	 *
	 *     $str = self::trim($str);
	 *
	 * @author  Andreas Gohr <andi@splitbrain.org>
	 * @param   string  $str        input string
	 * @param   string  $charlist   string of characters to remove
	 * @return  string
	 */
	public static function trim($str, $charlist = NULL)
	{
		if ($charlist === NULL)
			return trim($str);

		return self::ltrim(self::rtrim($str, $charlist), $charlist);
	}

	/**
	 * Strips whitespace (or other UTF-8 characters) from the beginning of
	 * a string. This is a UTF8-aware version of [ltrim](http://php.net/ltrim).
	 *
	 *     $str = self::ltrim($str);
	 *
	 * @author  Andreas Gohr <andi@splitbrain.org>
	 * @param   string  $str        input string
	 * @param   string  $charlist   string of characters to remove
	 * @return  string
	 */
	public static function ltrim($str, $charlist = NULL)
	{
		if ($charlist === NULL)
			return ltrim($str);

		if (self::is_ascii($charlist))
			return ltrim($str, $charlist);

		$charlist = preg_replace('#[-\[\]:\\\\^/]#', '\\\\$0', $charlist);

		return preg_replace('/^['.$charlist.']+/u', '', $str);
	}

	/**
	 * Strips whitespace (or other UTF-8 characters) from the end of a string.
	 * This is a UTF8-aware version of [rtrim](http://php.net/rtrim).
	 *
	 *     $str = self::rtrim($str);
	 *
	 * @author  Andreas Gohr <andi@splitbrain.org>
	 * @param   string  $str        input string
	 * @param   string  $charlist   string of characters to remove
	 * @return  string
	 */
	public static function rtrim($str, $charlist = NULL)
	{
		if ($charlist === NULL)
			return rtrim($str);

		if (self::is_ascii($charlist))
			return rtrim($str, $charlist);

		$charlist = preg_replace('#[-\[\]:\\\\^/]#', '\\\\$0', $charlist);

		return preg_replace('/['.$charlist.']++$/uD', '', $str);
	}

	/**
	 * Returns the unicode ordinal for a character. This is a UTF8-aware
	 * version of [ord](http://php.net/ord).
	 *
	 *     $digit = self::ord($character);
	 *
	 * @author  Harry Fuecks <hfuecks@gmail.com>
	 * @param   string  $chr    UTF-8 encoded character
	 * @return  integer
	 */
	public static function ord($chr)
	{
		$ord0 = ord($chr);

		if ($ord0 >= 0 AND $ord0 <= 127)
			return $ord0;

		if ( ! isset($chr[1]))
		{
			throw new Kohana_Exception('Short sequence - at least 2 bytes expected, only 1 seen');
		}

		$ord1 = ord($chr[1]);

		if ($ord0 >= 192 AND $ord0 <= 223)
			return ($ord0 - 192) * 64 + ($ord1 - 128);

		if ( ! isset($chr[2]))
		{
			throw new Kohana_Exception('Short sequence - at least 3 bytes expected, only 2 seen');
		}

		$ord2 = ord($chr[2]);

		if ($ord0 >= 224 AND $ord0 <= 239)
			return ($ord0 - 224) * 4096 + ($ord1 - 128) * 64 + ($ord2 - 128);

		if ( ! isset($chr[3]))
		{
			throw new Kohana_Exception('Short sequence - at least 4 bytes expected, only 3 seen');
		}

		$ord3 = ord($chr[3]);

		if ($ord0 >= 240 AND $ord0 <= 247)
			return ($ord0 - 240) * 262144 + ($ord1 - 128) * 4096 + ($ord2-128) * 64 + ($ord3 - 128);

		if ( ! isset($chr[4]))
		{
			throw new Kohana_Exception('Short sequence - at least 5 bytes expected, only 4 seen');
		}

		$ord4 = ord($chr[4]);

		if ($ord0 >= 248 AND $ord0 <= 251)
			return ($ord0 - 248) * 16777216 + ($ord1-128) * 262144 + ($ord2 - 128) * 4096 + ($ord3 - 128) * 64 + ($ord4 - 128);

		if ( ! isset($chr[5]))
		{
			throw new Kohana_Exception('Short sequence - at least 6 bytes expected, only 5 seen');
		}

		if ($ord0 >= 252 AND $ord0 <= 253)
			return ($ord0 - 252) * 1073741824 + ($ord1 - 128) * 16777216 + ($ord2 - 128) * 262144 + ($ord3 - 128) * 4096 + ($ord4 - 128) * 64 + (ord($chr[5]) - 128);

		if ($ord0 >= 254 AND $ord0 <= 255)
		{
			throw new Kohana_Exception("Invalid UTF-8 with surrogate ordinal ':ordinal'", array(
				':ordinal' => $ord0,
			));
		}
	}

	/**
	 * Takes an UTF-8 string and returns an array of ints representing the Unicode characters.
	 * Astral planes are supported i.e. the ints in the output can be > 0xFFFF.
	 * Occurrences of the BOM are ignored. Surrogates are not allowed.
	 *
	 *     $array = self::to_unicode($str);
	 *
	 * The Original Code is Mozilla Communicator client code.
	 * The Initial Developer of the Original Code is Netscape Communications Corporation.
	 * Portions created by the Initial Developer are Copyright (C) 1998 the Initial Developer.
	 * Ported to PHP by Henri Sivonen <hsivonen@iki.fi>, see <http://hsivonen.iki.fi/php-utf8/>
	 * Slight modifications to fit with phputf8 library by Harry Fuecks <hfuecks@gmail.com>
	 *
	 * @param   string  $str    UTF-8 encoded string
	 * @return  array   unicode code points
	 * @return  FALSE   if the string is invalid
	 */
	public static function to_unicode($str)
	{
		// Cached expected number of octets after the current octet until the beginning of the next UTF8 character sequence
		$m_state = 0;
		// Cached Unicode character
		$m_ucs4  = 0;
		// Cached expected number of octets in the current sequence
		$m_bytes = 1;

		$out = array();

		$len = strlen($str);

		for ($i = 0; $i < $len; $i++)
		{
			$in = ord($str[$i]);

			if ($m_state == 0)
			{
				// When m_state is zero we expect either a US-ASCII character or a multi-octet sequence.
				if (0 == (0x80 & $in))
				{
					// US-ASCII, pass straight through.
					$out[] = $in;
					$m_bytes = 1;
				}
				elseif (0xC0 == (0xE0 & $in))
				{
					// First octet of 2 octet sequence
					$m_ucs4 = $in;
					$m_ucs4 = ($m_ucs4 & 0x1F) << 6;
					$m_state = 1;
					$m_bytes = 2;
				}
				elseif (0xE0 == (0xF0 & $in))
				{
					// First octet of 3 octet sequence
					$m_ucs4 = $in;
					$m_ucs4 = ($m_ucs4 & 0x0F) << 12;
					$m_state = 2;
					$m_bytes = 3;
				}
				elseif (0xF0 == (0xF8 & $in))
				{
					// First octet of 4 octet sequence
					$m_ucs4 = $in;
					$m_ucs4 = ($m_ucs4 & 0x07) << 18;
					$m_state = 3;
					$m_bytes = 4;
				}
				elseif (0xF8 == (0xFC & $in))
				{
					/** First octet of 5 octet sequence.
					 *
					 * This is illegal because the encoded codepoint must be either
					 * (a) not the shortest form or
					 * (b) outside the Unicode range of 0-0x10FFFF.
					 * Rather than trying to resynchronize, we will carry on until the end
					 * of the sequence and let the later error handling code catch it.
					 **/
					$m_ucs4 = $in;
					$m_ucs4 = ($m_ucs4 & 0x03) << 24;
					$m_state = 4;
					$m_bytes = 5;
				}
				elseif (0xFC == (0xFE & $in))
				{
					// First octet of 6 octet sequence, see comments for 5 octet sequence.
					$m_ucs4 = $in;
					$m_ucs4 = ($m_ucs4 & 1) << 30;
					$m_state = 5;
					$m_bytes = 6;
				}
				else
				{
					// Current octet is neither in the US-ASCII range nor a legal first octet of a multi-octet sequence.
					trigger_error('self::to_unicode: Illegal sequence identifier in UTF-8 at byte '.$i, E_USER_WARNING);
					return FALSE;
				}
			}
			else
			{
				// When m_state is non-zero, we expect a continuation of the multi-octet sequence
				if (0x80 == (0xC0 & $in))
				{
					// Legal continuation
					$shift = ($m_state - 1) * 6;
					$tmp = $in;
					$tmp = ($tmp & 0x0000003F) << $shift;
					$m_ucs4 |= $tmp;

					// End of the multi-octet sequence. mUcs4 now contains the final Unicode codepoint to be output
					if (0 == --$m_state)
					{
						// Check for illegal sequences and codepoints

						// From Unicode 3.1, non-shortest form is illegal
						if (((2 == $m_bytes) AND ($m_ucs4 < 0x0080)) OR
							((3 == $m_bytes) AND ($m_ucs4 < 0x0800)) OR
							((4 == $m_bytes) AND ($m_ucs4 < 0x10000)) OR
							(4 < $m_bytes) OR
							// From Unicode 3.2, surrogate characters are illegal
							(($m_ucs4 & 0xFFFFF800) == 0xD800) OR
							// Codepoints outside the Unicode range are illegal
							($m_ucs4 > 0x10FFFF))
						{
							trigger_error('self::to_unicode: Illegal sequence or codepoint in UTF-8 at byte '.$i, E_USER_WARNING);
							return FALSE;
						}

						if (0xFEFF != $m_ucs4)
						{
							// BOM is legal but we don't want to output it
							$out[] = $m_ucs4;
						}

						// Initialize UTF-8 cache
						$m_state = 0;
						$m_ucs4  = 0;
						$m_bytes = 1;
					}
				}
				else
				{
					// ((0xC0 & (*in) != 0x80) AND (m_state != 0))
					// Incomplete multi-octet sequence
					throw new Kohana_Exception("self::to_unicode: Incomplete multi-octet sequence in UTF-8 at byte ':byte'", array(
						':byte' => $i,
					));
				}
			}
		}

		return $out;
	}

	/**
	 * Takes an array of ints representing the Unicode characters and returns a UTF-8 string.
	 * Astral planes are supported i.e. the ints in the input can be > 0xFFFF.
	 * Occurrences of the BOM are ignored. Surrogates are not allowed.
	 *
	 *     $str = self::to_unicode($array);
	 *
	 * The Original Code is Mozilla Communicator client code.
	 * The Initial Developer of the Original Code is Netscape Communications Corporation.
	 * Portions created by the Initial Developer are Copyright (C) 1998 the Initial Developer.
	 * Ported to PHP by Henri Sivonen <hsivonen@iki.fi>, see http://hsivonen.iki.fi/php-utf8/
	 * Slight modifications to fit with phputf8 library by Harry Fuecks <hfuecks@gmail.com>.
	 *
	 * @param   array   $str    unicode code points representing a string
	 * @return  string  utf8 string of characters
	 * @return  boolean FALSE if a code point cannot be found
	 */
	public static function from_unicode($arr)
	{
		ob_start();

		$keys = array_keys($arr);

		foreach ($keys as $k)
		{
			// ASCII range (including control chars)
			if (($arr[$k] >= 0) AND ($arr[$k] <= 0x007f))
			{
				echo chr($arr[$k]);
			}
			// 2 byte sequence
			elseif ($arr[$k] <= 0x07ff)
			{
				echo chr(0xc0 | ($arr[$k] >> 6));
				echo chr(0x80 | ($arr[$k] & 0x003f));
			}
			// Byte order mark (skip)
			elseif ($arr[$k] == 0xFEFF)
			{
				// nop -- zap the BOM
			}
			// Test for illegal surrogates
			elseif ($arr[$k] >= 0xD800 AND $arr[$k] <= 0xDFFF)
			{
				// Found a surrogate
				throw new Kohana_Exception("self::from_unicode: Illegal surrogate at index: ':index', value: ':value'", array(
					':index' => $k,
					':value' => $arr[$k],
				));
			}
			// 3 byte sequence
			elseif ($arr[$k] <= 0xffff)
			{
				echo chr(0xe0 | ($arr[$k] >> 12));
				echo chr(0x80 | (($arr[$k] >> 6) & 0x003f));
				echo chr(0x80 | ($arr[$k] & 0x003f));
			}
			// 4 byte sequence
			elseif ($arr[$k] <= 0x10ffff)
			{
				echo chr(0xf0 | ($arr[$k] >> 18));
				echo chr(0x80 | (($arr[$k] >> 12) & 0x3f));
				echo chr(0x80 | (($arr[$k] >> 6) & 0x3f));
				echo chr(0x80 | ($arr[$k] & 0x3f));
			}
			// Out of range
			else
			{
				throw new Kohana_Exception("self::from_unicode: Codepoint out of Unicode range at index: ':index', value: ':value'", array(
					':index' => $k,
					':value' => $arr[$k],
				));
			}
		}

		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

}

if (UTF8::$server_utf8 === NULL)
{
	// Determine if this server supports UTF-8 natively
	UTF8::$server_utf8 = extension_loaded('mbstring');
}
