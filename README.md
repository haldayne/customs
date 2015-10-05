# *Safe, simple iterator for $_FILES*

Receiving files from users is a common need, but sadly PHP does not provide a uniform interface for accessing the files. Depending upon how the files were uploaded (a single file, multiple with different names, or multiple files with the same name) the `$_FILES` super-global takes on a different structure. To handle these cases, while defensively protecting against upload-based attacks, requires an enormous amount of code.

This library provides a single, simple iterator for accessing the uploaded files.

# Let's get started

You need at least PHP 5.5.0.  No other extensions are required.

Install via composer: `php composer.phar require haldayne/upload-iterator 1.0.x-dev`

# Using UploadIterator

## Dead simple: receiving and moving uploaded files

```php
use Haldayne\UploadIterator;

$upload = new UploadIterator();
foreach ($upload as $file) {
    if ($file instanceof UploadFailure) {
        throw new \RuntimeException($file->getErrorMessage(), $file->getErrorCode());
    } else {
        $file->moveTo('/path/to/folder/');
    }
}
```

# Background

The [`$_FILES` super-global][1] contains meta-data for one, or many files, uploaded to your PHP code.  To avoid upload-based attacks, one must code defensively around this structure: using `is_uploaded_file`, checking the `error` of each file, and so on.

Annoyingly, though, this superglobal observes several possible formats, which prevents consistent handling:

1. Empty, when no files are uploaded
1. A string-keyed, five-element array of (a) string values when a single file is uploaded or (b) numerically indexed arrays when multiple files are uploaded
1. An arbitrary format, when [corrupted][2] by an engine bug

As a user-land developer, I prefer to have a single iteration path over this structure, regardless of the number of uploads.  I also want my code to have built-in defenses against upload-based attacks.


# Related projects

:alien: :heavy_minus_sign: [A simple Symfony2 bundle to ease file uploads with ORM entities and ODM documents.](https://github.com/dustin10/VichUploaderBundle)

> The VichUploaderBundle is a Symfony2 bundle that attempts to ease file uploads that are attached to ORM entities, MongoDB ODM documents, PHPCR ODM documents or Propel models.
> 
> * Automatically name and save a file to a configured directory
> * Inject the file back into the entity or document when it is loaded from the datastore as an instance of `Symfony\Component\HttpFoundation\File\File`
> * Delete the file from the file system upon removal of the entity or document from the datastore
> * Templating helpers to generate public URLs to the file


[1]: http://php.net/manual/en/reserved.variables.files.php
[2]: https://nealpoole.com/blog/2011/10/directory-traversal-via-php-multi-file-uploads/
