# *Safe, simple iterator for $_FILES*

Receiving files from users is a common need, but sadly PHP does not provide a uniform interface for accessing the files. Depending upon how the files were uploaded (a single file, multiple with different names, or multiple files with the same name) the `$_FILES` super-global takes on a different structure. To handle these cases, while defensively protecting against upload-based attacks, requires an enormous amount of code.

This library provides a single, simple iterator for accessing the uploaded files.

# Let's get started

You need at least PHP 5.5.0.  No other extensions are required.

Install via composer: `php composer.phar require haldayne/upload-iterator 1.0.x-dev`

# Using UploadIterator

Using the iterator is dead simple: instantiate the iterator, then iterate! The iterator produces an UploadFile object for every successfully received file.  What about error cases?

UploadIterator takes the stance that server errors are exceptions, while everything else is an error to show the client. This makes crystal clear problems that *you*, the developer/operator, must correct. This also makes it dead simple to discriminate between upload success and error.

Here's an example:

```php
use Haldayne\UploadIterator;

try {
    $upload = new UploadIterator();
} catch (UploadException $ex) {
    // uh oh, your server has a problem: no temp dir for storing files,
    // couldn't write file, extension prevents upload, etc. You need to
    // handle this, because it's not something the client did wrong.
    throw $ex;
}
foreach ($upload as $file) {
    if ($file instanceof UploadFile) {
        $stored_path = $file->moveTo('/path/to/folder/');
        echo "The file was permanently stored at $stored_path.";

    } else { // $file is an instance of UploadError
        echo 'Sorry, your upload was not stored.';

        // you can emit just a generic message
        echo $file->getGenericErrorMessage();

        // or you can get specific
        if ($file->isTooBig($maximum)) {
            echo "The file was too big. Maximum is $maximum bytes.";
        } else if ($file->isPartial($received)) {
            echo "The file upload wasn't complete. Only $received bytes received.";
        } else if ($file->notUploaded()) {
            echo "No file uploaded.";
        }
    }
}
```

# Features

Handing uploaded files is a common task, but dealing with the [`$_FILES` super-global][1] is not easy. Common tasks should be easy. If they're not, make them!

## Handling `$_FILES` schizomorphia

The `$_FILES` superglobal has different formats depending upon how you write the HTML form. If the form has two file inputs with different names, then `$_FILES` has two elements:

```
<input type='file' name='fileA' />
<input type='file' name='fileB' />

/* // $_FILES = array ( // notice two outer elements "fileA" and "fileB"
  "fileA" => array (
    "name" => "cat.png", // notice all these are string keys
    "type" => "image/png",
    "tmp_name" => "/tmp/phpZuLGPe",
    "error" => 0,
    "size" => 35669
  ),
  "fileB" => array (
    "name" => "dog.png",
    "type" => "image/png",
    "tmp_name" => "/tmp/phpUee89j",
    "error" => 0,
    "size" => 43225
  )
)
*/
```

Ok, that's simple.  But file inputs with an array name are different:

```
<input type='file' name='file[A]' />
<input type='file' name='file[B]' />

/* // $_FILES = array (
  "file" => array ( // notice only one outer element
    "name" => array ( // with array keys!
      "A" => "cat.png",
      "B" => "dog.png"
    ),
    "type" => array (
      "A" => "image/png",
      "B" => "image/png"
    ),
    "tmp_name" => array (
      "A" => "/tmp/phpZuLGPe",
      "B" => "/tmp/phpUee89j"
    ),
    "error" => array (
      "A" => 0,
      "B" => 0
    ),
    "size" => array (
      "A" => 35669,
      "B" => 43225
    )
  )
)
*/
```

God help you if you do these:

```
<input type='file' name='file[a][b][c][d]' /> // six levels deep
<input type='file' name='file 1' /> // one level, key named "file_1"
<input type='file' name='file 1[.1]' /> // two levels, keys "file_1" => ".1"

```

That's three different scenarios, all dependent upon your form. As a user-land developer, I prefer to have a single iteration path over this structure, regardless of how the form asked for the files. This is basic separation of presentation from logic. And this is the first feature of the library: one iterator to rule them all!

## Handling errors and other common upload questions

Now the next problem: a lot can go wrong when uploading files. The client could give you too few files, or too many. The files could be too big, or too small. Or the wrong MIME type. Your server could be misconfigured, or have run out of disk space. Every one of these is an `if` conditional. You, the developer, need to handle each one.

Since `$_FILES` is just an array of data, you have no tidy object methods to call to detect these conditions. If you don't have the `finfo` extension installed, you have to write a separate branch to fall back on the `file` binary or some other kind of MIME type logic.  This is just a lot of work.

This library bundles all this related functionality together in easy-to-use object methods, so you can ask the questions and get on with your application code. You can:

* Count how many files were uploaded, in total or by HTML name
* Get the definitive MIME type for the file, or ask for a guess of the MIME type
* Decide whether the file was too big for the system to accept, or partially uploaded
* Discern between client errors and server errors

## Minimizing the chances for developer blunders

The information in `$_FILES` cannot be trusted. Developers should not store files named using the name the client gave, because these names can contain unsafe characters. To encourage this philosophy, this library assumes a default defensive posture:

```
use Haldayne\UploadIterator;
foreach (new UploadIterator as $file) {
    if ($file instanceof UploadFile) {
        $stored_path = $file->move('/path/to/folder/');
        // you choose "where" the file goes, not what it will be named
        // $stored_path === /path/to/folder/69c779f0746503ba7e42f87ce1e91152.png
    }
}
```

Stored files are given a random, unique file name that preserves the original file's extension. But what if you want to know the original file name?  You have two choices: (a) do your own thing to store this meta-data, or (b) use the meta-data file created by `moveTo`.

So `move` creates a small meta-data file that sits beside the uploaded file. The meta-data file is just a PHP array containing all the original file information:

```
$ ls /path/to/folder
69c779f0746503ba7e42f87ce1e91152.png
69c779f0746503ba7e42f87ce1e91152.png.meta

$ cat /path/to/folder/69c779f0746503ba7e42f87ce1e91152.png.meta
<?php return array (
  'name' => 'Picture of my Cat.png',
  'type' => 'image/png',
  'size' => 35889,
  'date' => 10987685941
);
```

TODO: this is a security hazard, someone could fish the meta data. but they could also fish all the other content. is that bad?  How can we help with fishing?


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
