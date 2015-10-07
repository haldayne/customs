<?php
namespace Haldayne\UploadIterator;

/**
 * Iterate over the $_FILES super-global, or an array in that same format.
 */
class UploadIterator implements \ArrayAccess, \SeekableIterator, \Countable
{
    public function __construct(array $input = null)
    {
        $this->input = (null === $input ? $_FILES : $input);
        $this->import();
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
        return $this->files[$this->index];
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        ++$this->index;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        return array_key_exists($this->index, $this->files);
    }

    public function seek($position)
    {
        if ($this->offsetGet($position)) {
            $this->index = $position;
        } else {
            throw new \OutOfBoundsException("Cannot seek to $position");
        }
    }

    // implements \Countable

    public function count()
    {
        return count($this->files);
    }

    // PRIVATE API

    private $input;
    private $files;
    private $index;

    /**
     * Import a structure purporting to be a valid $_FILES format into a flat
     * array of UploadFile or UploadError. Resolve all recursive structure and
     * reconstitues the HTML form name.
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
     * HTML names.
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
     * Companion to `names`, which recursively traverses the iterator,
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
        switch ($info['error']) {
        case UPLOAD_ERR_OK:
            return $info; // return UploadFileFactory::fromInfo($name, $info);
            
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
            return $info; // return UploadErrorFactory::fromInfo($name, $info);

        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
        case UPLOAD_ERR_EXTENSION:
            return $info; // throw UploadExceptionFactory::fromInfo($name, $info);
        }
    }

    /*
    private function check_name_sanity($name)
    {
        if (! (is_string($name) && 0 < strlen($name))) {
            throw new \RuntimeException('HTML variable name must be non-empty string');
        }
    }

    private function check_info_sanity($info)
    {
        if (! is_array($info) && 5 === count($info)) {
            throw new \RuntimeException('Not an array with exactly five elements');
        }

        $keys = array_diff(array_keys($info), ['name', 'type', 'size', 'tmp_name', 'error']);
        if ([] !== $keys) {
            throw new \RuntimeException('Missing expected keys: ' . implode(',', $keys));
        }

        $name = $info['name'];
        $type = $info['type'];
        $size = $info['size'];
        $tmpn = $info['tmp_name'];
        $errn = $info['error'];

        if (

        if (! (is_string($name) && 0 < strlen($name))) {
            throw new \RuntimeException('Client file name must be non-empty string');
        }

        if (! is_string($type)) {
            throw new \RuntimeException('Client file type must be string');
        }

        if (! (is_int($size) && 0 <= $size)) {
            throw new \RuntimeException('Server file size must be zero or greater integer');
        }

        if (! (is_string($tmpn) && 0 < strlen($tmpn))) {
            throw new \RuntimeException('Server path must be non-empty string');
        } else if (! file_exists($tmpn)) {
            throw new \RuntimeException('Server path must exist');
        }

        if (! is_int($errn)) {
            throw new \RuntimeException('Server error code must be integer');
        } else switch ($errn) {
            case UPLOAD_ERR_OK:
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
            case UPLOAD_ERR_PARTIAL:
            case UPLOAD_ERR_NO_FILE:
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                break;
            default:
                throw new \RuntimeException('Server error not a defined UPLOAD_ERR_* constant');
        }
    }
    */
}
