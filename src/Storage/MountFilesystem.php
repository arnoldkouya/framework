<?php

namespace Bow\Storage;

use Bow\Http\UploadFile;
use Bow\Storage\Contracts\FilesystemInterface;
use InvalidArgumentException;

class MountFilesystem implements FilesystemInterface
{
    /**
     * The base work directory
     *
     * @var string
     */
    private $base_directory;

    /**
     * The current working directory
     *
     * @var string
     */
    private $current_working_dir;

    /**
     * MountFilesystem constructor.
     *
     * @param string $basedir
     */
    public function __construct($basedir)
    {
        $this->base_directory = realpath($basedir);
        $this->current_working_dir = $this->base_directory;

        // Set the root folder
        chdir($this->base_directory);
    }

    /**
     * Function to upload a file
     *
     * @param  UploadFile $file
     * @param  string $location
     * @param  array $option
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function store(UploadFile $file, $location = null, array $option = [])
    {
        if (is_array($location)) {
            $option = $location;
            $location = null;
        }

        if (isset($option['as'])) {
            $filename = $option['as'];
        } else {
            $filename = $file->getHashName();
        }

        if (is_null($location)) {
            $location = $filename;
        } else {
            $location = trim($location, '/') . '/' . $filename;
        }

        return $this->put($location, $file->getContent());
    }

    /**
     * Add content after the contents of the file
     *
     * @param  string $file
     * @param  string $content
     *
     * @return bool
     */
    public function append($file, $content)
    {
        return file_put_contents($file, $content, FILE_APPEND);
    }

    /**
     * Add content before the contents of the file
     *
     * @param  string $file
     * @param  string $content
     *
     * @return bool
     * @throws
     */
    public function prepend($file, $content)
    {
        $tmp_content = file_get_contents($file);

        $this->put($file, $content);

        return $this->append($file, $tmp_content);
    }

    /**
     * Put other file content in given file
     *
     * @param string $file
     * @param string $content
     *
     * @return bool
     */
    public function put($file, $content)
    {
        $file = $this->path($file);

        $dirname = dirname($file);

        $this->makeDirectory($dirname);

        return file_put_contents($file, $content);
    }

    /**
     * Delete file or directory
     *
     * @param  string $file
     *
     * @return boolean
     */
    public function delete($file)
    {
        $file = $this->path($file);

        if (is_dir($file)) {
            return @rmdir($file);
        }

        return @unlink($file);
    }

    /**
     * List the files of a folder passed as a parameter
     *
     * @param  string $dirname
     *
     * @return array
     */
    public function files($dirname)
    {
        $dirname = $this->path($dirname);

        $directory_contents = glob($dirname . "/*");

        return array_filter($directory_contents, function ($file) {
            return filetype($file) == "file";
        });
    }

    /**
     * List the folder of a folder passed as a parameter
     *
     * @param  string $dirname
     *
     * @return array
     */
    public function directories($dirname)
    {
        $directory_contents = glob($this->path($dirname) . "/*");

        return array_filter($directory_contents, function ($file) {
            return filetype($file) == "dir";
        });
    }

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int $mode
     *
     * @return boolean
     */
    public function makeDirectory($dirname, $mode = 0777)
    {
        $directories = explode('/', $dirname);

        foreach ($directories as $directory) {
            if (false === $this->makeActualDirectory($directory, $mode)) {
                chdir($this->base_directory);
                return false;
            }
            chdir($directory);
            $this->current_working_dir = getcwd();
        }

        chdir($this->base_directory);

        return true;
    }

    /**
     * Create a directory.
     *
     * @param $dirname
     * @param $mode
     * @return bool
     */
    private function makeActualDirectory($dirname, $mode)
    {
        $listing = glob($this->current_working_dir . '/*', GLOB_ONLYDIR) ?: [];

        $directories = array_map(function ($value) {
            return pathinfo($value, PATHINFO_BASENAME);
        }, $listing);

        // Skip directory creation if it already exists
        if (in_array($dirname, $directories, true)) {
            return true;
        }

        return @mkdir($dirname, $mode);
    }

    /**
     * Recover the contents of the file
     *
     * @param  string $filename
     *
     * @return null|string
     */
    public function get($filename)
    {
        $filename = $this->path($filename);

        if (!(is_file($filename) && stream_is_local($filename))) {
            return null;
        }

        return file_get_contents($filename);
    }

    /**
     * Copy the contents of a source file to a target file.
     *
     * @param  string $target
     * @param  string $source
     *
     * @return bool
     */
    public function copy($target, $source)
    {
        if (!$this->exists($target)) {
            throw new \RuntimeException("$target does not exist.", E_ERROR);
        }

        if (!$this->exists($source)) {
            $this->makeDirectory(dirname($source), true);
        }

        return file_put_contents($source, $this->get($target));
    }

    /**
     * Renames or moves a source file to a target file.
     *
     * @param string $target
     * @param string $source
     *
     * @return void
     */
    public function move($target, $source)
    {
        $this->copy($target, $source);

        $this->delete($target);
    }

    /**
     * Check the existence of a file
     *
     * @param string $filename
     *
     * @return bool
     */
    public function exists($filename)
    {
        $filename = $this->path($filename);

        if (!$this->isDirectory($filename)) {
            return file_exists($filename);
        }

        $tmp = getcwd();

        $r = chdir($filename);

        chdir($tmp);

        return $r;
    }

    /**
     * The file extension
     *
     * @param string $filename
     *
     * @return string
     */
    public function extension($filename)
    {
        if ($this->exists($filename)) {
            return pathinfo($this->path($filename), PATHINFO_EXTENSION);
        }

        return null;
    }

    /**
     * isFile alias of is_file.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function isFile($filename)
    {
        return is_file($this->path($filename));
    }

    /**
     * isDirectory alias of is_dir.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function isDirectory($dirname)
    {
        return is_dir($this->path($dirname));
    }

    /**
     * Resolves file path.
     * Give the absolute path of a path
     *
     * @param string $filename
     *
     * @return string
     */
    public function path($filename)
    {
        if (preg_match('~^' . $this->base_directory . '~', $filename)) {
            return $filename;
        }

        return rtrim($this->base_directory, '/') . '/' . ltrim($filename, '/');
    }
}
