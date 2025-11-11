<?php
declare(strict_types=1);

namespace gijsbos\Http\Utils;

use finfo;
use InvalidArgumentException;
use gijsbos\ExtFuncs\Exceptions\FileUploadHandlerException;

/**
 * FileUploadHandler
 */
class FileUploadHandler
{
    const KB = 1024;
    const MB = 1048576;
    const GB = 1073741824;
    const TB = 1099511627776;

    // Mime Types
    const MIME_JPG = ["mime" => "image/jpeg", "ext" => "jpg"];
    const MIME_PNG = ["mime" => "image/png", "ext" => "png"];
    const MIME_WEBP = ["mime" => "image/webp", "ext" => "webp"];
    const MIME_MP4 = ["mime" => "video/mp4", "ext" => "mp4"];
    const MIME_OGG = ["mime" => "video/ogg", "ext" => "ogg"];
    const MIME_GIF = ["mime" => "image/gif", "ext" => "gif"];
    const MIME_TXT = ["mime" => "text/plain", "ext" => "txt"];
    const MIME_CSV = ["mime" => "text/plain", "ext" => "csv"];
    const MIME_PHP = ["mime" => "text/x-php", "ext" => "php"];
    const MIME_PDF = ["mime" => "application/pdf", "ext" => "pdf"];

    // Working vars
    const MIME_REGEXP = "/^[a-z0-9]+\/[a-z0-9]+$/";
    const MOVE_FUNCTIONS = ['move_uploaded_file','copy','rename'];
    const FILE_ARRAY = ['fileName','fileExtension','fileSize','filePath','sha256','MIME'];

    // Public var
    public static $MIME_TYPES = null;

    // Props
    private $sha256AsDefaultName;
    private $moveFunction;

    /**
     * __construct
     * 
     * @param string $fileName fileName as send in POST
     * @param int $maxSize maximum size in bytes (use FileUploadHandler::MB * 5 etc)
     * @param array $mimeTypes allowed for file upload
     */
    public function __construct(bool $sha256AsDefaultName = true, null|string|callable $moveFunction = null)
    {
        $this->sha256AsDefaultName = $sha256AsDefaultName;
        $this->moveFunction = $moveFunction !== null ? $moveFunction : 'move_uploaded_file';
    }

    /**
     * convertSizeStringToBytes
     */
    public static function convertSizeStringToBytes(string $size)
    {
        preg_match("/(\d+)(\w+)?/", $size, $matches);

        if(count($matches))
        {
            $number = intval($matches[1]);
            $format = @$matches[2];

            if(is_int($number) && is_null($format))
                return $number;

            return match($format) {
                "MB" => $number * self::MB,
                "M" => $number * self::MB,
                "GB" => $number * self::GB,
                "G" => $number * self::GB,
                "TB" => $number * self::TB,
                "T" => $number * self::TB,
                default => throw new InvalidArgumentException("Invalid size format '$format'"),
            };
        }

        throw new InvalidArgumentException("Invalid size argument '$size'");
    }

    /**
     * readLocal
     *  Mimics file being send by storing file information in global $_FILES
     */
    public static function readLocal(string $filePath, null|string &$fileName = null) : void
    {
        $fileName = $fileName !== null ? $fileName : basename($filePath);

        // Check if file exists
        if(!is_file($filePath))
            throw new FileUploadHandlerException("fileNotReceived", "File '%s' has not been received", $filePath);

        // Create file in $_FILES
        $_FILES[$fileName]['tmp_name'] = $filePath;
        $_FILES[$fileName]['error'] = "\0"; // Note that a null character ("\0") is not equivalent to the PHP null constant
        $_FILES[$fileName]['size'] = filesize($filePath);
    }

    /**
     * getFileNameByTmpName
     *  Lookup the fileName in $_FILES array using the tmp_name value.
     */
    public static function getFileNameByTmpName(string $tmpName) : string
    {
        foreach($_FILES as $fileName => $data)
            if(array_key_exists("tmp_name", $data) && $data["tmp_name"] == $tmpName)
                return $fileName;

        throw new FileUploadHandlerException("fileNameNotFound", "Could not resolve file name using tmp_name");
    }

    /**
     * isMimeArrayInput
     */
    public static function isMimeArrayInput($mime) : bool
    {
        return is_array($mime) && array_key_exists("mime", $mime) && array_key_exists("ext", $mime);
    }

    /**
     * hasMimeArray
     * 
     * @param string|array $mimeArrayOrExtension - string or MIME constant defined in this class
     * @param array $mimeTypes - list of mime types composed of constants
     */
    public static function hasMimeArray(array $mime, array $mimeTypes) : bool
    {
        return in_array($mime, $mimeTypes);
    }

    /**
     * getMimeArrayConstants
     */
    public static function getMimeArrayConstants() : array
    {
        $constants = (new \ReflectionClass(self::class))->getConstants();

        // Filter out STATUS constants
        $constants = array_filter($constants, function($value){ return self::isMimeArrayInput($value); });

        // Return array
        return $constants;
    }

    /**
     * getMimeTypeArraysWithMimeType
     */
    public static function getMimeTypeArraysWithMimeType(string $mimeType, null|array $mimeTypes = null) : array
    {
        $mimeTypes = $mimeTypes !== null ? $mimeTypes : self::getMimeArrayConstants();

        return array_filter($mimeTypes, function($value) use($mimeType)
        {
            return $value["mime"] === $mimeType;
        });
    }

    /**
     * hasMimeArrayExtension
     */
    public static function hasMimeArrayExtension(string $extension, null|array $mimeTypes = null) : bool
    {
        $mimeTypes = $mimeTypes !== null ? $mimeTypes : self::getMimeArrayConstants();

        foreach($mimeTypes as $m)
            if($m["ext"] == $extension)
                return true;

        return false;
    }

    /**
     * getMimeArray
     */
    public static function getMimeArray($mime) : array
    {
        if(self::isMimeArrayInput($mime))
            return $mime;
        else
        {
            // Get mime type
            $mimeTypes = self::getMimeTypeArraysWithMimeType($mime);

            if(count($mimeTypes) === 0)
                throw new FileUploadHandlerException("mimeTypeNotFound", "Could not find mime type '%s'", $mime);

            return array_shift($mimeTypes);
        }
    }

    /**
     * getMimeType
     * DO NOT TRUST $_FILES[$fileName]['mime'] VALUE !! Check MIME manually
     */
    public static function getMimeType(string $filePath) : string
    {
        return (new finfo(FILEINFO_MIME_TYPE))->file($filePath);
    }

    /**
     * getFileExtension
     */
    public static function getFileExtension(string $filePath)
    {
        // Get from path
        $pathInfo = pathinfo($filePath);

        // Check if set
        if(@$pathInfo["extension"] !== null)
            return $pathInfo["extension"];

        // Lookup through mime
        $mimeType = self::getMimeType($filePath);

        // Lookup
        foreach(self::getMimeArrayConstants() as $mime)
            if($mime["mime"] == $mimeType)
                return $mime["ext"];

        // Return empty
        return null;
    }

    /**
     * mimeTypeArrayExists
     */
    public static function mimeTypeArrayExists($mimeTypeNeedle, array $mimeTypeArrayHaystack) : bool
    {
        return in_array(is_string($mimeTypeNeedle) ? self::getMimeArray($mimeTypeNeedle) : $mimeTypeNeedle, $mimeTypeArrayHaystack);
    }

    /**
     * fileMimeIsAllowed
     */
    public static function fileMimeIsAllowed(string $filePath, array $mimeTypes)
    {
        $mimeType = self::getMimeType($filePath);

        // Check if mimeType is in mimeTypes
        $mimeTypes = self::getMimeTypeArraysWithMimeType($mimeType, $mimeTypes);

        // Check result
        if(!count($mimeTypes))
            return false;
        else
            return true;
    }

    /**
     * getMimeExtension
     */
    public static function getMimeExtension($mimeType)
    {
        if(is_string($mimeType))
            $mimeType = ["mime" => $mimeType];

        foreach(self::getMimeArrayConstants() as $mime)
            if($mimeType["mime"] == $mime["mime"])
                return $mime["ext"];
            
        return null;
    }

    /**
     * createFileArray
     */
    public static function createFileArray(string $filePath, string $fileName)
    {
        return array(
            "filePath" => $filePath,
            "fileName" => $fileName,
            "fileExtension" => self::getFileExtension($filePath),
            "fileSize" => filesize($filePath),
            "sha256" => hash_file('sha256', $filePath),
            "MIME" => self::getMimeType($filePath),
        );
    }

    /**
     * readPath
     */
    public function readPath(string $filePath, null|int $maxSize = null, null|array $allowMimeTypes = null, null|string $fileName = null)
    {
        // Create fileArray
        $fileArray = self::createFileArray($filePath, $fileName !== null ? $fileName : basename($filePath));

        // Get mime type
        $fileSize = $fileArray["fileSize"];
        $mimeType = $fileArray["MIME"];

        // You should also check filesize here. 
        if($maxSize !== null && $fileSize > $maxSize)
            throw new FileUploadHandlerException("fileSizeExceeded", "Exceeded file size limit '%dB', received '%dB'", $maxSize, $fileSize);

        // Check if in array
        if($allowMimeTypes !== null && !self::fileMimeIsAllowed($filePath, $allowMimeTypes))
            throw new FileUploadHandlerException("fileInvalidFormat", "Invalid file format '%s'", $mimeType);

        // Return info
        return $fileArray;
    }

    /**
     * read
     * 
     * @param array [fileName,fileExtension,fileSize,filePath,sha256,MIME]
     */
    public function read(string $fileName, null|int $maxSize = null, null|array $allowMimeTypes = null) : array
    {
        // Check if file has been set
        if(!array_key_exists($fileName, $_FILES))
            throw new FileUploadHandlerException("fileNotReceived", "File '%s' has not been received", $fileName);

        // Undefined | Multiple Files | $_FILES Corruption Attack. If this request falls under any of them, treat it invalid
        if(!isset($_FILES[$fileName]['error']) || is_array($_FILES[$fileName]['error']))
            throw new FileUploadHandlerException("parametersInvalid", "Invalid parameters", $fileName);

        // Set filePath
        $filePath = $_FILES[$fileName]['tmp_name'];
        $fileSize = $_FILES[$fileName]['size'];

        // Check error value
        switch (intval($_FILES[$fileName]['error']))
        {
            case UPLOAD_ERR_OK:
            break;
            case UPLOAD_ERR_NO_FILE:
                throw new FileUploadHandlerException("fileNotReceived", "No file sent");
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new FileUploadHandlerException("fileSizeExceeded", "Exceeded file size limit '%dB', received '%dB'", $maxSize, $fileSize);
            default:
                throw new FileUploadHandlerException("unknownError", "Unknown errors " . $_FILES[$fileName]['error']);
        }

        // Return info
        return self::readPath($filePath, $maxSize, $allowMimeTypes, $fileName);
    }

    /**
     * isFileArray
     */
    public static function isFileArray(array $fileArray) : bool
    {
        foreach(self::FILE_ARRAY as $key)
            if(!array_key_exists($key, $fileArray))
                return false;

        return true;
    }

    /**
     * validateFileArray
     */
    public static function validateFileArray(array $fileArray) : void
    {
        if(!self::isFileArray($fileArray))
            throw new FileUploadHandlerException("writeError", "Write failed, invalid argument 1 'fileArray' does not contain values '%s', received '%s'", implode(",", self::FILE_ARRAY), implode(",", array_keys($fileArray)));
    }

    /**
     * getStoreName
     */
    private function getStoreName(string $sha256, string $fileName, null|string $storeName = null)
    {
        if($storeName !== null)
            return $storeName;

        if($this->sha256AsDefaultName)
            return $sha256;

        return $fileName;
    }

    /**
     * write
     * 
     * @param array $fileArray - Array containing file parameters from read function
     * @param string $uploadFolder - Destination folder
     * @param string $storeName - File stored as name
     * @param bool $sha256AsDefaultName - Use sha256 as default store name
     * @param bool $useRename - Use php rename instead of move_uploaded_file
     */
    public function write(array $fileArray, string $uploadFolder, null|string $storeName = null)
    {
        self::validateFileArray($fileArray);
        
        // Extract
        extract($fileArray);

        // Get storeName
        $storeName = $this->getStoreName($sha256, $fileName, $storeName);

        // Set uploadFolder
        $uploadFolder = str_ends_with($uploadFolder, "/") ? $uploadFolder : $uploadFolder . "/";

        // Get filePath
        $newFilePath = sprintf("%s%s", $uploadFolder, $storeName);

        // Check if folder exists
        if(!is_dir(dirname($newFilePath)))
            throw new FileUploadHandlerException("uploadFolderNotFound", "Could not move file to folder, folder '%s' does not exist", dirname($newFilePath));

        // Check if moveFunction is valid
        if(!(is_callable($this->moveFunction) && in_array($this->moveFunction, self::MOVE_FUNCTIONS)))
            throw new FileUploadHandlerException("moveFunctionInvalid", "Invalid move function '{$this->moveFunction}'");

        // Move file
        if(!call_user_func_array($this->moveFunction, [$filePath, $newFilePath]))
            throw new FileUploadHandlerException("uploadMoveFileFailed", "Failed to move uploaded file using function {$this->moveFunction}");

        // Return data
        return [
            "fileName" => $storeName,
            "filePath" => $newFilePath,
            "sha256" => $sha256,
        ];
    }

    /**
     *  handle
     * 
     * @param string $fileName name provided by upload form
     * @param string $storeName storage name
     * @return array fileName, fileExtension, filePath, fileSize, MIME, sha256
     */
    public function handle(string $fileName, int $maxSize, array $mimeTypes, string $uploadFolder, null|string $storeName = null) : array
    {
        // Read file
        $file = $this->read($fileName, $maxSize, $mimeTypes);

        // Write file
        $write = $this->write($file, $uploadFolder, $storeName);

        // Return info
        return array_merge($file, $write);
    }
}