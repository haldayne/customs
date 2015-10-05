<?php
namespace Haldayne\UploadIterator;

class EmptyFilesTest extends \PHPUnit_Framework_TestCase
{
    public function test_empty_files()
    {
        $it = new UploadIterator(array ());
        $this->assertInstanceOf('\Haldayne\UploadIterator\UploadIterator', $it);
        $this->assertSame(0, $it->count());
    }
}
