<?php


namespace MyRadio\MyRadio;


use MyRadio\MyRadioException;

class ImageUtils
{
    /**
     * Given a path to an image, returns a GD image resource for it.
     * Throws a MyRadioException if the type is unrecognised.
     * @param string $path a path to an image, either local or remote
     * @return resource a GD image resource
     */
    public static function loadImage(string $path)
    {
        $sizeinfo = getimagesize($path);
        if ($sizeinfo === false) {
            throw new MyRadioException('Failed to get image details');
        }
        switch ($sizeinfo[2]) {
            case IMAGETYPE_JPEG:
            case IMAGETYPE_JPEG2000:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            default:
                $type = image_type_to_extension($sizeinfo[2]);
                throw new MyRadioException("Unrecognised image format $type", 400);
        }
    }

    /**
     * Crops the given image to the given dimensions, enlarging it if it's too small,
     * reducing it if it's too big, and cutting off anything outside the given dimensions.
     *
     * If the given image is already the right size, it is returned unchanged.
     *
     * Note that the size of the new image may be 1px off on either axis, due to floating point rounding.
     *
     * @param $image resource
     * @param int $newX
     * @param int $newY
     * @return resource
     * @throws MyRadioException if the given image has a dimension 0, or the resize fails for any reason
     */
    public static function cropAndResizeImage($image, int $newX, int $newY) {
        // To understand this code, imagine we have an 800x600 image and we're resizing it to 1400x1400.
        $oldX = imagesx($image); // 800
        $oldY = imagesy($image); // 600

        if ($oldX === 0 || $oldY === 0) {
            throw new MyRadioException('Tried to resize an image with a 0-dimension', 400);
        }

        if ($oldX === $newX && $oldY === $newY) {
            return $image;
        }

        $oldSmallest = min($oldX, $oldY); // 600
        $newBiggest = max($newX, $newY); // 1400
        $scaleFactor = $newBiggest / $oldSmallest; // 2.333...

        // We intentionally use the old _smallest_ and new _biggest_, and ceil() everything.
        // This is to ensure that, no matter the dimensions, the new image is _at least_
        // as big as the target.

        $newResizedX = ceil($oldX * $scaleFactor); // 1840
        $newResizedY = ceil($oldY * $scaleFactor); // 1380 (~1400)

        // We'll set up these variables now, we'll need them later
        $srcX = 0;
        $srcY = 0;
        $srcW = $oldX;
        $srcH = $oldY;

        // Now, cut off anything outside the given dimens
        // We're guaranteed that $newResizedX >= $newX
        // and $newResizedY >= $newY.
        // We're also guaranteed that either
        //    $newResizedX > $newX, or
        //    $newResizedY > $newY, or
        //    $newResizedX == $newXn AND $newResizedY == $newY
        // (that is, $newResizedX > $newX AND $newResizedY > $newY will never be true)
        //
        // (Sketch of a) proof: if we're enlarging the image, see the example so far
        // If we're reducing, imagine the opposite of this example - 1400x1400->800x600.
        // Scale factor is (max(800,600) / min(1400,1400)) = 800/1400 = 0.57.
        // so new dimensions are 800x800
        if ($newResizedX > $newX) {
            // Calculate the overspill
            $overspillX = $newResizedX - $newX; // 440
            // Alter it by the scale factor
            $overspillX /= $scaleFactor; // ~188
            // Cut off equally from either side
            $srcX += ceil($overspillX / 2); // 188
            $srcW -= ceil($overspillX / 2); // 612
        } else if ($newResizedY > $newY) {
            $overspillY = $newResizedY - $newY; // calculations not reproduced
            $overspillY /= $scaleFactor;
            $srcY += ceil($overspillY / 2);
            $srcH -= ceil($overspillY / 2);
        }

        $newImage = imagecreatetruecolor($newX, $newY);
        $result = imagecopyresampled(
            $newImage,
            $image,
            0,
            0,
            $srcX,
            $srcY,
            $newX,
            $newY,
            $srcW,
            $srcH
        );
        if ($result === false) {
            throw new MyRadioException('Failed to resize image');
        }
        return $newImage;
    }
}