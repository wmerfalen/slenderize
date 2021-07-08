<?php
/**
 * @author William Merfalen <wmerfalen@gmail.com>
 * @package slenderize
 * @license wtfpl  [@see https://www.wtfpl.net]
 */

namespace slenderize;

class Config
{
    /** @var array $m_vars The variables that are set */
    private static $m_vars = [];
    public static function set_vars(array $items)
    {
        self::$m_vars = $items;
    }
    public static function get(string $item)
    {
        return self::$m_vars[$item] ?? null;
    }

    public static function set(string $item, $value)
    {
        self::$m_vars[$item] = $value;
    }
}
