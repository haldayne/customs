<?php
namespace Haldayne\Customs;

/**
 * Iterate over the $_FILES super-global, or an array in that same format.
 */
class UploadIterator implements \ArrayAccess, \SeekableIterator, \Countable
{
    /**
     * Create a new UploadIterator.
     *
     * With no arguments, creates an iterator over the $_FILES super-global.
     * You may instead pass your own array having the same format as $_FILES.
     * If any of the files indicate a security concern or a server problem
     * that prevented their storage, then the constructor throws an exception.
     *
     * @param array $input An alternate $_FILES-like array to iterate over.
     * @throws UploadException
     * @api
     * @since 1.0.0
     */
    public function __construct(array $input = null)
    {
        $this->input = (null === $input ? $_FILES : $input);
        $this->import();
    }

    // implements \ArrayAccess

    /**
     * Implements isset() checks on the iterator.
     *
     * ```
     * $it = new Haldayne\Customs\UploadIterator;
     * if (isset($it[0])) { ... }
     * ```
     *
     * @param int $offset The offset to check.
     * @return bool
     * @internal
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->files);
    }

    /**
     * Implements bracket get access to the iterator.
     *
     * ```
     * $it = new Haldayne\Customs\UploadIterator;
     * $it[0];
     * ```
     *
     * @param int $offset The offset to check.
     * @return UploadFile|UploadError
     * @throws \OutOfBoundsException When the offset does not exist.
     * @internal
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->files[$offset];
        } else {
            throw new \OutOfBoundsException("Offset $offset does not exist");
        }
    }

    /**
     * Satisfies the \ArrayAccess interface, but you may not update the
     * iterator.
     * @throws \LogicException
     * @internal
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('Cannot update the iterator');
    }

    /**
     * Satisfies the \ArrayAccess interface, but you may not update the
     * iterator.
     * @throws \LogicException
     * @internal
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('Cannot update the iterator');
    }

    // implements \SeekableIterator

    /**
     * Returns the current upload entity within the iterator.
     *
     * @return UploadFile|UploadError
     * @api
     * @since 1.0.0
     */
    public function current()
    {
        return $this->files[$this->index];
    }

    /**
     * Returns the key of the current upload entity within the iterator.
     *
     * @return int
     * @api
     * @since 1.0.0
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * Advance the iterator to the next upload entity.
     *
     * @return void
     * @api
     * @since 1.0.0
     */
    public function next()
    {
        ++$this->index;
    }

    /**
     * Rewind to the first element of the iterator.
     *
     * @return void
     * @api
     * @since 1.0.0
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * Check if the current position within the iterator is valid.
     *
     * @return bool
     * @api
     * @since 1.0.0
     */
    public function valid()
    {
        return array_key_exists($this->index, $this->files);
    }

    /**
     * Arbitrarily move the current position within the iterator.
     *
     * @return void
     * @throws \OutOfBoundsException
     * @api
     * @since 1.0.0
     */
    public function seek($position)
    {
        if ($this->offsetGet($position)) {
            $this->index = $position;
        } else {
            throw new \OutOfBoundsException("Cannot seek to $position");
        }
    }

    // implements \Countable

    /**
     * Return a count of upload entities within the iterator.
     *
     * @return int
     * @api
     * @since 1.0.0
     */
    public function count()
    {
        return count($this->files);
    }

    // PRIVATE API

    /** @var array $input The $_FILES or similar array provided in ctor */
    private $input;
    /** @var array $files The internal array holding processed upload entities */
    private $files;
    /** @var int $index The pointer to the current index in the internal array */
    private $index;

    /**
     * Import a structure purporting to be a valid $_FILES format into a flat
     * array of UploadFile or UploadError. Resolve all recursive structure and
     * reconstitue the HTML form name.
     */
    private function import()
    {
        // if the input is empty, the import is empty
        if (empty($this->input)) {
            $this->files = [];
            $this->index = 0;
            return;
        }

        // get all the HTML names from the input
        $names = $this->names();

        // walk those names, gathering the original input and wrapping that
        // input in our object classes
        foreach ($names as $name) {
            $this->files[] = $this->wrap($name, $this->gather($name));
        }
    }

    /**
     * Using the "name" key in the input as a model, figure out all the
     * HTML names given in the original input.
     */
    private function names()
    {
        $names = [];

        // get all the top level names from the input
        $keys0 = array_keys($this->input);

        // explore the name member of each top-level key
        foreach ($keys0 as $key0) {
            // if the key is an array, plunge into it
            if (is_array($this->input[$key0]['name'])) {
                $it = new \RecursiveArrayIterator($this->input[$key0]['name']);
                iterator_apply(
                    $it,
                    function () use ($it, $key0, &$names) { // I need PBR
                        $this->reducer($it, $key0, $names);
                    }
                );

            // otherwise, the key is a scalar and we're done
            } else {
                $names[] = $key0;
            }
        }

        return $names;
    }

    /**
     * Helper to `names`, which recursively traverses the iterator,
     * appending new keys onto the base-so-far.
     */
    private function reducer(\RecursiveArrayIterator $it, $base, &$names)
    {
        while ($it->valid()) {
            $sub_base = sprintf('%s[%s]', $base, $it->key());
            if ($it->hasChildren()) {
                $this->reducer($it->getChildren(), $sub_base);
            } else {
                $names[] = $sub_base;
            }
            $it->next();
        }
    }

    /**
     * Given an HTML name, gather all its information into a standard
     * info structure.
     */
    private function gather($name)
    {
        // tokenize by the first [ to get the top-level key
        $key = strtok($name, '[');
        $name     =& $this->input[$key]['name'];
        $type     =& $this->input[$key]['type'];
        $size     =& $this->input[$key]['size'];
        $tmp_name =& $this->input[$key]['tmp_name'];
        $error    =& $this->input[$key]['error'];

        // continue tokenizing, deep diving to keep our pointer updated
        while (! empty($key = rtrim(strtok('['), ']'))) {
            $name     =& $name[$key];
            $type     =& $type[$key];
            $size     =& $size[$key];
            $tmp_name =& $tmp_name[$key];
            $error    =& $error[$key];
        }

        return compact('name', 'type', 'size', 'tmp_name', 'error');
    }
    
    /**
     * Wrap the file upload information in an appropriate object class, raising
     * an exception if one would be warranted by the error.
     *
     * @param string $name The HTML form element name
     * @param array $info The file upload information
     * @return UploadFile|UploadError
     * @throws UploadException
     */
    private function wrap($name, array $info)
    {
        // ensure the local server file was actually uploaded
        if (! is_uploaded_file($info['tmp_name'])) {
            throw new \RuntimeException(); // ContrabandUploadException
        }

        // return the correct object based on the type
        switch ($info['error']) {
        case UPLOAD_ERR_OK:
            return new UploadFile($name, $info['name'], $info['tmp_name']);
            
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
            return new UploadError($name, $info['error'], $info['size']);

        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
        case UPLOAD_ERR_EXTENSION:
            throw new \RuntimeException();

        default:
            throw new \RuntimeException();
        }
    }
}
