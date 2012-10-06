<?php

namespace Asciigram;

class ImageUpload
{
    /**
     * @var Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected $image;

    /**
     * @var string
     */
    protected $message;

    /**
     * @return Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}
