<?php

namespace phpDM;

class Helpers
{
	public static function toCamelCase(string $str, $ucFirst = false): string {
		$parts = explode('_', $str);
		$str = implode('', array_map(function ($part) {
			return ucfirst(strtolower($part));
		}, $parts));
		if (!$ucFirst) {
			$str = lcfirst($str);
		}
		return $str;
	}

	public static function toSnakeCase(string $str): string {
		preg_match_all('#([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)#', $str, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		return implode('_', $ret);
	}

	public static function randStr(int $length = 8) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$str = '';
		do {
			$str .= $chars[mt_rand(0, strlen($chars) - 1)];
		} while (strlen($str) < $length);

		return $str;
	}
}