<?php namespace Zephyrus\Security\Uploaders;

use Zephyrus\Exceptions\UploadException;

/**
 * REFERENCES
 * http://www.net-security.org/dl/articles/php-file-upload.pdf
 */
class ImageUpload extends FileUpload
{
    const PERMITTED_MIME_TYPES = ['image/gif', 'image/jpeg', 'image/png'];
    const PERMITTED_EXTENSIONS = ['gif', 'jpeg', 'jpg', 'png'];

    /**
     * @var bool
     */
    private $forcingImageRebuild = false;

    /**
     * @param mixed[] $data
     * @throws UploadException
     * @throws \Exception
     */
    public function __construct($data)
    {
        parent::__construct($data);
        parent::addAllowedExtensions(self::PERMITTED_EXTENSIONS);
        parent::addAllowedMimeTypes(self::PERMITTED_MIME_TYPES);
    }

    /**
     * @return boolean
     */
    public function isForcingImageRebuild()
    {
        return $this->forcingImageRebuild;
    }

    /**
     * @param boolean $forcingImageRebuild
     */
    public function setForcingImageRebuild($forcingImageRebuild)
    {
        $this->forcingImageRebuild = $forcingImageRebuild;
    }

    /**
     * Do specific validation for images (second mime type validation using
     * exif functions, image size validation and image rebuild).
     *
     * @throws \Exception
     */
    protected function validateUpload()
    {
        parent::validateUpload();
        $this->validateImageMimeType();

        $info = getimagesize(parent::getTemporaryFilename());
        if (empty($info) || $info[0] == "" || $info[1] == "") {
            throw new \Exception("Uploaded image appears to be corrupt (possible injection)");
        }

        if ($this->forcingImageRebuild) {
            $this->rebuildImage($info[0], $info[1]);
        }
    }

    /**
     * Validate that the mime type is an actual valid image
     *
     * @throws \Exception
     */
    private function validateImageMimeType()
    {
        $imageType = exif_imagetype(parent::getTemporaryFilename());
        if (!$imageType) {
            throw new \Exception("Uploaded image appears to be corrupt (possible injection)");
        }
        $mime = image_type_to_mime_type($imageType);
        if (!in_array($mime, self::PERMITTED_MIME_TYPES)) {
            throw new \Exception("Mime type ($mime) not allowed (possible injection)");
        }
    }

    /**
     * @param $width
     * @param $height
     * @throws \Exception
     */
    private function rebuildImage($width, $height)
    {
        $final_image = imagecreatetruecolor($width, $height);
        $temp_image = null;
        $ext = $this->getExtension();

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $temp_image = @imagecreatefromjpeg($this->getTemporaryFilename());
                break;

            case 'gif':
                $temp_image = @imagecreatefromgif($this->getTemporaryFilename());
                break;

            case 'png':
                $temp_image = @imagecreatefrompng($this->getTemporaryFilename());
                break;
        }

        if (!$temp_image) {
            throw new \Exception("Uploaded image appears to be corrupt (possible injection)");
        }

        // Maintenir la transparence possible d'une image
        imagealphablending($final_image, false);
        imagesavealpha($final_image, true);

        // Copier l'image temporaire vers le tampon finale selon les
        // nouvelles dimensions.
        imagecopyresampled(
            $final_image,
            $temp_image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $width,
            $height
        );

        // Sauvegarder l'image selon l'extension
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($final_image, $this->getTemporaryFilename(), 100);
                break;

            case 'gif':
                imagegif($final_image, $this->getTemporaryFilename());
                break;

            case 'png':
                imagepng($final_image, $this->getTemporaryFilename());
                break;
        }
    }
}