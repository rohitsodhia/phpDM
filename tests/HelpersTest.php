<?php

use PHPUnit\Framework\TestCase;
use phpDM\Helpers;

class HelpersTest extends TestCase
{

	public function testToCamelCaseSingleWord()
	{
		$input = 'solo';
		$output = Helpers::toCamelCase($input);
		$this->assertEquals($input, $output);
	}

	public function testToCamelCaseTwoWords()
	{
		$input = 'two_words';
		$output = Helpers::toCamelCase($input);
		$this->assertEquals('twoWords', $output);
	}

	public function testToCamelCaseTwoWordsUCFirst()
	{
		$input = 'two_words';
		$output = Helpers::toCamelCase($input, true);
		$this->assertEquals('TwoWords', $output);
	}

	public function testToSnakeCaseSingleWord()
	{
		$input = 'solo';
		$output = Helpers::toSnakeCase($input);
		$this->assertEquals($input, $output);
	}

	public function testToSnakeCaseTwoWords()
	{
		$input = 'twoWords';
		$output = Helpers::toSnakeCase($input);
		$this->assertEquals('two_words', $output);
	}

}