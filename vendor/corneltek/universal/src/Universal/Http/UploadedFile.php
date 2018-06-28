<?php
namespace Universal\Http;
use Universal\Exception\InvalidUploadFileException;
use Universal\Exception\UploadedFileMoveFailException;
use Universal\Exception\UploadErrorException;
use Exception;
use SplFileObject;
use ArrayAccess;

/**
    $file = new Universal\Http\UploadedFile(array(
        'name' => 'filename',
        'tmp_name' => '/tmp/123fbffef',
        'type' => 'image/jpg',
        'size' => 33300,
        'error' => 0,
    ));
    $file->moveTo( "file_dirs" );
*/
class UploadedFile implements ArrayAccess
{
    /**
     * The original filename in $_FILES
     *
     * @var string
     */
    protected $originalFileName;


    /**
     * The path of tmp file
     *
     * @var string
     */
    protected $tmpName;


    /**
     * Mime type of the file
     *
     * @var string
     */
    protected $type;

    protected $size;

    protected $error;

    protected $savedPath;

    protected $stash = array();

    public function __construct($tmpName, $originalFileName = null, $type = null, $savedPath = null)
    {
        $this->tmpName = $tmpName;
        $this->originalFileName = $originalFileName ?: $this->tmpName;
        $this->type = $type;
        $this->savedPath = $savedPath;
        $file = $this->savedPath ?: $this->tmpName;
        if (file_exists($file)) {
            $this->size = filesize($file);
        }
    }

    static public function createFromArray(array & $stash)
    {
        $file = new self($stash['tmp_name'], $stash['name'], $stash['type']);
        if (isset($stash['saved_path'])) {
            $file->savedPath = $stash['saved_path'];
        }
        $file->setStashedArray($stash);
        return $file;
    }

    protected function setStashedArray(array & $stash)
    {
        $this->stash = $stash;
    }

    protected function getStashedArray()
    {
        return $this->stash;
    }

    public function getSplFileObject()
    {
        $path = $this->getCurrentPath();
        return new SplFileObject($path);
    }

    /**
     * getCurrentPath returns the current target file.
     *
     * Before move_uploaded_file function call, it returns $file->tmp_name
     * After move_uploaded_file  function call, it returns $file->savedPath
     *
     */
    public function getCurrentPath()
    {
        if ($this->savedPath) {
            return $this->savedPath;
        }
        return $this->tmpName;
    }

    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }


    /**
     * Return the temporary file name
     *
     * @return string temporary file name
     */
    public function getTmpName()
    {
        return $this->tmpName;
    }

    /**
     * Return the extension name from originalFileName
     *
     * @return string file extension
     */
    public function getExtension()
    {
        $parts = explode('.',$this->originalFileName);
        return end($parts);
    }

    /**
     * Validate file size by K bytes
     *
     * @param integer $limitSize file size in K bytes
     * @return boolean true if the file size is under the limitSize.
     */
    public function validateSize($limitSize)
    {
        return ($this->size / 1024) < $limitSize;
    }

    public function validateExtension(array $exts)
    {
        $ext = strtolower($this->getExtension());
        return in_array($ext, $exts);
    }

    public function getSavedPath()
    {
        return $this->savedPath;
    }

    public function getType()
    {
        return $this->type;
    }


    /**
     * @return integer file size in bytes
     */
    public function getSize()
    {
        return $this->size;
    }


    /**
     * isMoved checked 'saved_path' param, if the file is already moved, it
     * return true, otherwise it returns falsec:w
     *
     * @return boolean
     */
    public function isMoved()
    {
        return $this->getSavedPath() ? true : false;
    }

    /**
     * isUploadedFile calls is_uploaded_file function to confirm that the file
     * is uploaded through HTTPS? prototol
     *
     * @return boolean
     */
    public function isUploadedFile()
    {
        return is_uploaded_file($this->tmpName);
    }

    /**
     * move method moves file from 'tmp_name' to a new path.
     *
     * move method doesn't modify tmp_name attribute
     * rather than that, we set the saved_path attribute
     * for location of these moved files.
     *
     * Just like moveTo, but instead of passing directory, it only accept
     * filepath.
     *
     * @param string $newPath
     * @param boolean $rename
     * @return path|boolean
     *
     * return FALSE when operation failed.
     *
     * return path string if the operation succeeded.
     */
    public function move($newPath, $rename = false)
    {
        if ($this->savedPath) {
            return $this->savedPath;
        }

        $tmpFile = $this->tmpName;

        // Avoid file name duplication
        /*
        $fileCnt = 1;
        while (file_exists($newPath)) {
            $newPath =
                $targetDir . DIRECTORY_SEPARATOR . 
                    FileUtils::filename_suffix( $newPath , '_' . $fileCnt++ );
        }
        */

        $ret = false;
        if ($rename) {
            $ret = rename($tmpFile, $newPath);
        } else {
            $ret = $this->moveUploadedFile($tmpFile, $newPath );
        }
        $this->savedPath = $this->stash['saved_path'] = $newPath;

        if ($ret === false) {
            return $ret;
        }
        return $newPath;
    }

    /**
     * copy method copies the file from 'tmp_name' to a new path.
     *
     * @return boolean
     */
    public function copy($targetPath)
    {
        return copy($this->tmpName, $targetPath);
    }


    /**
     * copyTo method calls 'copy' method to copy the file.
     *
     * @return boolean
     */
    public function copyTo($targetDir)
    {
        // if targetFilename is not given,
        // we should take the filename from original filename by using basename.
        $targetFileName = basename($this->originalFileName);

        // relative file path.
        $newPath = $targetDir . DIRECTORY_SEPARATOR . $targetFileName;
        return $this->copy($newPath);
    }

    /**
     * moveTo method doesn't modify tmp_name attribute
     * rather than that, we set the saved_path attribute
     * for location of these moved files.
     *
     * @return path|boolean 
     *
     * return FALSE when operation failed.
     *
     * return path string if the operation succeeded.
     */
    public function moveTo($targetDir, $rename = false)
    {
        // if targetFilename is not given,
        // we should take the filename from original filename by using basename.
        $targetFileName = basename($this->originalFileName);

        // relative file path.
        $newPath = $targetDir . DIRECTORY_SEPARATOR . $targetFileName;
        return $this->move($newPath, $rename);
    }

    public function moveUploadedFile($target)
    {
        // if the tmp file is already moved
        if (isset($this->savedPath)) {
            return $this->savedPath;
        }
        if ($this->stash['error'] != 0) {
            throw new UploadErrorException($this->stash,"An error occured when uploading file {$this->tmpName}.", $this->stash['error']);
        }
        if (!is_uploaded_file($this->tmpName)) {
            throw new InvalidUploadFileException($this->stash, "File {$this->tmpName} is not an uploaded file.");
        }
        if (false === move_uploaded_file($this->tmpName, $target)) {
            throw new UploadedFileMoveFailException($this->stash, "File {$this->tmpName} upload failed.");
        }
        // Update stash value
        return $this->savedPath = $this->stash['saved_path'] = $moveTo;
    }

    public function deleteTmp()
    {
        unlink($this->tmpName);
    }

    public function found()
    {
        return $this->name ? true : false;
    }

    public function hasError()
    {
        return $this->error != 0;
    }

    public function getUserErrorMessage()
    {
        // error messages for normal users.
        switch ($this->error) {
            case UPLOAD_ERR_OK:
                return "OK";
            case UPLOAD_ERR_INI_SIZE || UPLOAD_ERR_FORM_SIZE:
                return "The upload file exceeds the limit.";
            case UPLOAD_ERR_PARTIAL:
                return "The uploaded file was only partially uploaded.";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded.";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk.";
            case UPLOAD_ERR_EXTENSION:
                return "A PHP extension stopped the file upload.";
            default:
                return "Unknown error.";
        }
    }

    /**
     * getSystemErrorMessage returns the system built-in error message.
     *
     * @return string
     */
    public function getSystemErrorMessage()
    {
        // built-in php error description
        switch ($this->error) {
            case UPLOAD_ERR_OK:
                return "There is no error, the file uploaded with success.";
            case UPLOAD_ERR_INI_SIZE:
                return "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
            case UPLOAD_ERR_FORM_SIZE:
                return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
            case UPLOAD_ERR_PARTIAL:
                return "The uploaded file was only partially uploaded.";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded.";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk. Introduced in PHP 5.1.0.";
            case UPLOAD_ERR_EXTENSION:
                return "A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.";
            default:
                return "Unknown Error.";
        }
    }


    public function offsetSet($name,$value)
    {
        $this->stash[ $name ] = $value;
    }
    
    public function offsetExists($name)
    {
        return isset($this->stash[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->stash[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->stash[$name]);
    }

}
