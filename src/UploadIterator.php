<?php
namespace Haldayne\UploadIterator;

/**
 * Iterate over the $_FILES super-global, or an array in that same format.
 */
class UploadIterator implements \ArrayAccess, \SeekableIterator, \Countable
{
    public function __construct(array $input = null)
    {
        $this->files = $this->convert(null === $input ? $_FILES : $input);
        $this->pos   = 0;
    }

    // implements \ArrayAccess

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->files);
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->files[$offset];
        } else {
            throw new \OutOfBoundsException("Offset $offset does not exist");
        }
    }

    public function offsetSet($offset, $value)
    {
        throw new \LogicException('Cannot update the iterator');
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException('Cannot update the iterator');
    }

    // implements \SeekableIterator

    public function current()
    {
        return $this->files[$this->pos];
    }

    public function key()
    {
        return $this->pos;
    }

    public function next()
    {
        ++$this->pos;
    }

    public function rewind()
    {
        $this->pos = 0;
    }

    public function valid()
    {
        return array_key_exists($this->pos, $this->files);
    }

    // implements \Countable

    public function count()
    {
        return count($this->files);
    }

    // PRIVATE API

    private $files;
    private $pos;

    /**
     * Convert an array structure purporting to be one of the structures $_FILES
     * takes on into a flat array of UploadFile or UploadFailure.
     *
     * @param array $files
     * @return array
     */
    private function convert(array $files)
    {
    }
}
