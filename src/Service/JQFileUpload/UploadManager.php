<?php

namespace Sourceml\Service\JQFileUpload;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\Request;
use Sourceml\Entity\JQFileUpload\Media;

class UploadManager {

    private $container;

    private $jQUploadHandler;

    private $handler;

    private $handlerName;

    private $id;

    protected $options;

    protected $rootDir;

    // relative to $rootDir
    protected $mediaRootDir;

    // relative to $mediaRootDir
    protected $mediaDir;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->jQUploadHandler = $this->container->get('jq_file_upload.upload_handler');
        $this->rootDir =
            dirname($this->container->get('kernel')->getRootDir())
            ."/".$this->container->getParameter('web_dir');
        $this->mediaRootDir = "medias";
        $this->mediaDir = "";
    }

    public function init($handlerName, $id = null) {
        $this->handlerName = $handlerName;
        $this->id = $id;
        $this->options = $this->getDefaultOptions();
        if(!$this->container->has('jq_file_upload.handler.'.$handlerName)) {
            throw new \Exception("upload service not found");
        }
        $this->handler = $this->container->get('jq_file_upload.handler.'.$handlerName);
        if(method_exists($this->handler, "init")) {
            $this->handler->init($id);
        }
        if(method_exists($this->handler, "getMediaDir")) {
            $this->setMediaDir($this->handler->getMediaDir());
        }
        if(method_exists($this->handler, "getOptions")) {
            if(!is_array($options = $this->handler->getOptions())) {
                throw new \Exception("upload service didn't return options");
            }
            $this->setOptions($options);
        }
        $this->jQUploadHandler->handle($this->options, false);
    }

    public function handle($request) {
        $this->jQUploadHandler->handle($this->options, false);
        switch($request->getMethod()) {
            case 'PATCH':
            case 'PUT':
            case 'POST':
                $this->preValidateUpload();
        }
        switch($request->getMethod()) {
            case 'GET':
                if(!method_exists($this->handler, "get")) {
                    throw new \Exception("not get method found in upload service");
                }
                $files = array();
                foreach($this->handler->get() as $media) {
                    $files[] = $this->getFileObject($media);
                }
                $this->jQUploadHandler->generate_response(
                    array(
                        $this->options['param_name'] => $files
                    )
                );
                break;
            case 'PATCH':
            case 'PUT':
            case 'POST':
                $this->makeUploadDir();
                if(!method_exists($this->handler, "post")) {
                    throw new \Exception("not post method found in upload service");
                }
                $files = $this->jQUploadHandler->post();
                if($file = reset($files)) {
                    if(isset($file->error)) {
                        throw new \Exception($file->error);
                    }
                    $media = $this->getMedia($file);
                    $media = $this->handler->post($media);
                    if($media->getError()) {
                        $this->jQUploadHandler->delete_file(basename($media->getName()));
                        throw new \Exception($media->getError());
                    }
                    $this->jQUploadHandler->generate_response(
                        array(
                            $this->options['param_name'] => array($this->getFileObject($media))
                        )
                    );
                }
                break;
            case 'DELETE':
                if(!method_exists($this->handler, "delete")) {
                    throw new \Exception("no delete method found in upload service");
                }
                if(!($media = $this->getMediaToDelete())) {
                    throw new \Exception("unable to load media infos");
                }
                $media = $this->handler->delete($media);
                if($media->getError()) {
                    throw new \Exception($media->getError());
                }
                $response = $this->jQUploadHandler->delete();
                $this->cleanUploadDir();
                $this->jQUploadHandler->generate_response($response);
                break;
        }
    }

    public function setOptions($options) {
        $this->options = $options + $this->options;
    }

    public function setMediaRootDir($mediaRootDir) {
        if($mediaRootDir && substr($mediaRootDir, 0, 1) == "/") {
            $mediaRootDir = substr($mediaRootDir, 1);
        }
        if($mediaRootDir && substr($mediaRootDir, -1) == "/") {
            $mediaRootDir = substr($mediaRootDir, 0, -1);
        }
        $this->mediaRootDir = $mediaRootDir;
        $request_stack = $this->container->get('request_stack');
        $base_uri = "";
        if($request = $request_stack->getCurrentRequest()) {
            $base_uri = $request->getBasePath();
        }
        $this->setOptions(
            array(
                'upload_dir' => $this->rootDir."/".$this->mediaRootDir."/".$this->mediaDir."/",
                'upload_url' => $base_uri."/".$this->mediaRootDir."/".$this->mediaDir."/",
            )
        );
    }

    public function getMediaRootDir() {
        return $this->mediaRootDir;
    }

    public function setMediaDir($mediaDir) {
        if($mediaDir && substr($mediaDir, 0, 1) == "/") {
            $mediaDir = substr($mediaDir, 1);
        }
        if($mediaDir && substr($mediaDir, -1) == "/") {
            $mediaDir = substr($mediaDir, 0, -1);
        }
        $this->mediaDir = $mediaDir;
        $request_stack = $this->container->get('request_stack');
        $base_uri = "";
        if($request = $request_stack->getCurrentRequest()) {
            $base_uri = $request->getBasePath();
        }
        $this->setOptions(
            array(
                'upload_dir' => $this->rootDir."/".$this->mediaRootDir."/".$this->mediaDir."/",
                'upload_url' => $base_uri."/".$this->mediaRootDir."/".$this->mediaDir."/",
            )
        );
    }

    public function makeUploadDir() {
        $upload_dir = $this->rootDir;
        $path = explode("/", $this->mediaRootDir."/".$this->mediaDir);
        foreach($path as $dir) {
            if(!$dir) {
                continue;
            }
            $upload_dir .= "/".$dir;
            if(is_dir($upload_dir)) {
                continue;
            }
            @mkdir($upload_dir);
            if(!is_dir($upload_dir)) {
                throw new \Exception("unable to make upload dir");
            }
        }
    }

    public function cleanUploadDir() {
        if($this->mediaDir) {
            $this->cleanEmptyDirectories(
                $this->rootDir."/".$this->mediaRootDir."/".$this->mediaDir
            );
        }
    }

    protected function cleanEmptyDirectories($dir) {
        if(is_dir($dir)) {
            if($dh = opendir($dir)) {
                $nb_files = 0;
                while(($file = readdir($dh)) !== false) {
                    if($file != "." && $file != "..") {
                        $nb_files++;
                        if(is_dir($dir."/".$file)) {
                            if($this->cleanEmptyDirectories($dir."/".$file)) {
                                $nb_files--;
                            }
                        }
                    }
                }
                closedir($dh);
                if($nb_files) {
                    return false;
                }
                return @rmdir($dir);
            }
        }
    }

    public function getFileObject($media) {
        $file = new \stdClass();
        $file->name = basename($media->getName());
        $file->size = $media->getSize();
        $file->type = $media->getMimeType();
        $file->url = $this->options["upload_url"].basename($media->getName());
        if($thumbnail = $media->getThumbnail()) {
            $file->thumbnailUrl =
                $this->options["upload_url"]
                ."thumbnail/".basename($thumbnail->getName());
        }
        $file->deleteUrl = $this->container->get('router')->generate(
            'jq_file_upload_server',
            array(
                "handlerName" => $this->handlerName,
                "id" => $this->id,
            )
        )."?file=".basename($media->getName());
        $file->deleteType = "DELETE";
        if($error = $media->getError()) {
            $file->error = $error;
        }
        return $file;
    }

    protected function getDefaultOptions() {
        $request_stack = $this->container->get('request_stack');
        $base_uri = "";
        if($request = $request_stack->getCurrentRequest()) {
            $base_uri = $request->getBasePath();
        }
        return array(
            'param_name' => 'files',
            'script_url' => $this->container->get('router')->generate(
                'jq_file_upload_server',
                array(
                    "handlerName" => $this->handlerName,
                    "id" => $this->id,
                )
            ),
            'upload_dir' => $this->rootDir."/".$this->mediaRootDir."/".$this->mediaDir."/",
            'upload_url' => $base_uri."/".$this->mediaRootDir."/".$this->mediaDir."/",

            // Defines which files can be displayed inline when downloaded:
            'inline_file_types' => '/\.(gif|jpe?g|png|mp4)$/i',
            // Defines which files (based on their names) are accepted for upload:
            'accept_file_types' => '/.+$/i',
//            'accept_file_types' => '/\.(gif|jpe?g|png)$/i',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            // The maximum number of files for the upload directory:
            'max_number_of_files' => null,
            // Defines which files are handled as image files:
            'image_versions' => array(
                'thumbnail' => array(
                    // Uncomment the following to use a defined directory for the thumbnails
                    // instead of a subdirectory based on the version identifier.
                    // Make sure that this directory doesn't allow execution of files if you
                    // don't pose any restrictions on the type of uploaded files, e.g. by
                    // copying the .htaccess file from the files directory for Apache:
                    //'upload_dir' => dirname($this->get_server_var('SCRIPT_FILENAME')).'/thumb/',
                    //'upload_url' => $this->get_full_url().'/thumb/',
                    // Uncomment the following to force the max
                    // dimensions and e.g. create square thumbnails:
                    //'crop' => true,
                    'max_width' => 200,
                    'max_height' => 150
                )
            ),
            'print_response' => true
        );
    }

    public function preValidateUpload() {
        if(!$_FILES) {
            throw new \Exception("post_max_size maby exceeded (".ini_get('post_max_size').")");
        }
        if(!isset($_FILES[$this->options["param_name"]])) {
            throw new \Exception("wrong files name param");
        }
        $files = $_FILES[$this->options["param_name"]];
        $error = is_array($files["error"]) ? $files["error"][0] : $files["error"];
        $error_message = "";
        switch($error) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "upload_max_filesize exceeded (".ini_get('upload_max_filesize').")";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "MAX_FILE_SIZE exceeded";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "partial content recieved";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "no file uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "tmp dir missing";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "unable to write file on disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = "file upload was stopped";
                break;
        }
        if(!$error_message) {
            if(method_exists($this->handler, "getAllowedFiles")) {
                $allowedFiles = $this->handler->getAllowedFiles();
                $name = is_array($files["name"]) ? $files["name"][0] : $files["name"];
                $fileInfos = pathinfo($name);
                $isAllowed = false;
                foreach($allowedFiles as $extension) {
                    if(strtolower($fileInfos["extension"]) == strtolower($extension)) {
                        $isAllowed = true;
                    }
                }
                if(!$isAllowed) {
                    throw new \Exception("file extension refused");
                }
            }
        }
        if($error_message) {
            throw new \Exception($error_message);
        }
    }

    public function getMedia($file) {
        $fileName = urldecode($file->name);
        $media_dir = $this->rootDir."/".$this->mediaRootDir."/".$this->mediaDir."/";
        if(!file_exists($media_dir.$fileName)) {
            throw new \Exception("unable to read file on disk");
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $media_dir.$fileName);
        finfo_close($finfo);
        $media = new Media();
        $media->setName(($this->mediaDir ? $this->mediaDir."/" : "").$fileName);
        $media->setSize(filesize($media_dir.$fileName));
        $media->setMimeType($mime_type ? $mime_type : "");
        if(isset($file->thumbnailUrl)) {
            $thumbnail_file = new \stdClass();
            $thumbnail_file->name = "thumbnail/".basename($file->thumbnailUrl);
            $thumbnail = $this->getMedia($thumbnail_file);
            $media->setThumbnail($thumbnail);
        }
        return $media;
    }

    public function getMediaToDelete() {
        $fileName = $_GET["file"];
        $em = $this->container->get('doctrine')->getManager();
        $mediaRepo = $em->getRepository(Media::class);
        if($media = $mediaRepo->findOneBy(["name" => $this->mediaDir."/".$fileName])) {
            return $media;
        }
        return null;
    }

    public function error($message) {
        $file = new \stdClass();
        $file->error = $this->jQUploadHandler->get_error_message($message);
        $this->jQUploadHandler->generate_response(
            array(
                $this->options["param_name"] => array($file)
            )
        );
    }

    public function makeMediaFromFiles($inputName) {
        $this->options["param_name"] = $inputName;
        $this->jQUploadHandler->handle($this->options, false);
        if($_FILES && isset($_FILES[$this->options["param_name"]])) {
            $files = $_FILES[$this->options["param_name"]];
            $error = is_array($files["error"]) ? $files["error"][0] : $files["error"];

            if($error == UPLOAD_ERR_NO_FILE) {
                return null;
            }
        }
        try {
            $this->preValidateUpload();
        }
        catch(\Exception $e) {
            $media = new Media();
            $media->setError($e->getMessage());
            return $media;
        }
        $files = $this->jQUploadHandler->post();
        if(($file = reset($files)) && !isset($file->error)) {
            $media = $this->getMedia($file);
            if($media->getError()) {
                $this->jQUploadHandler->delete_file(basename($media->getName()));
            }
        }
        return $media;
    }

    public function importMediaFromLocalFile($fileName) {
        $this->makeUploadDir();
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $fileName);
        finfo_close($finfo);
        $fileSize = filesize($fileName);
        $_FILES = array(
            "local" => array(
                "name" => basename($fileName),
                "type" => $mime_type ? $mime_type : "",
                "tmp_name" => $fileName,
                "error" => 0,
                "size" => $fileSize
            )
        );
        $_SERVER['CONTENT_LENGTH'] = $fileSize;
        return $this->makeMediaFromFiles("local");
    }

    public function delete_file($file_name) {
        if($res = $this->jQUploadHandler->delete_file($file_name)) {
            $this->cleanUploadDir();
        }
        return $res;
    }

}
