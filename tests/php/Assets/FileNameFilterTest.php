<?php

namespace SilverStripe\Assets\Tests;

use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Parsers\Transliterator;

class FileNameFilterTest extends SapphireTest {

    public function setUp() {
        parent::setUp();

        Config::inst()->update('SilverStripe\\Assets\\FileNameFilter', 'default_replacements', array(
            '/\s/' => '-', // remove whitespace
            '/_/' => '-', // underscores to dashes
            '/[^A-Za-z0-9+.\-]+/' => '', // remove non-ASCII chars, only allow alphanumeric plus dash and dot
            '/[\-]{2,}/' => '-', // remove duplicate dashes
            '/^[\.\-_]+/' => '', // Remove all leading dots, dashes or underscores
        ));
    }

	public function testFilter() {
		$name = 'Brötchen  für allë-mit_Unterstrich!.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(false);
		$this->assertEquals(
			'Brtchen-fr-all-mit-Unterstrich.jpg',
			$filter->filter($name)
		);
	}

	public function testFilterWithTransliterator() {
		$name = 'Brötchen  für allë-mit_Unterstrich!.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(new Transliterator());
		$this->assertEquals(
			'Broetchen-fuer-alle-mit-Unterstrich.jpg',
			$filter->filter($name)
		);
	}

	public function testFilterWithCustomRules() {
		$name = 'Kuchen ist besser.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(false);
		$filter->setReplacements(array('/[\s-]/' => '_'));
		$this->assertEquals(
			'Kuchen_ist_besser.jpg',
			$filter->filter($name)
		);
	}

	public function testFilterWithEmptyString() {
		$name = 'ö ö ö.jpg';
		$filter = new FileNameFilter();
		$filter->setTransliterator(new Transliterator());
		$result = $filter->filter($name);
		$this->assertFalse(
			empty($result)
		);
		$this->assertStringEndsWith(
			'.jpg',
			$result
		);
		$this->assertGreaterThan(
			strlen('.jpg'),
			strlen($result)
		);
	}

	public function testUnderscoresStartOfNameRemoved() {
		$name = '_test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	public function testDoubleUnderscoresStartOfNameRemoved() {
		$name = '__test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	public function testDotsStartOfNameRemoved() {
		$name = '.test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	public function testDoubleDotsStartOfNameRemoved() {
		$name = '..test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	public function testMixedInvalidCharsStartOfNameRemoved() {
		$name = '..#@$#@$^__test.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test.txt', $filter->filter($name));
	}

	public function testWhitespaceRemoved() {
		$name = ' test doc.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test-doc.txt', $filter->filter($name));
	}

	public function testUnderscoresReplacedWithDashes() {
		$name = 'test_doc.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test-doc.txt', $filter->filter($name));
	}

	public function testNonAsciiCharsReplacedWithDashes() {
		$name = '!@#$%^test_123@##@$#%^.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test-123.txt', $filter->filter($name));
	}

	public function testDuplicateDashesRemoved() {
		$name = 'test--document.txt';
		$filter = new FileNameFilter();
		$this->assertEquals('test-document.txt', $filter->filter($name));
	}

}
