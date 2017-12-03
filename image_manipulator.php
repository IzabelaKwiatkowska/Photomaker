<?php
class ImageManipulator
{
    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var resource
     */
    protected $image;

    /**
     * @var int
     */
    protected $image_type;

    /**
     * Image manipulator constructor
     *
     * @param string $file OPTIONAL Path to image file or image data as string
     * @return void
     */
    public function __construct($file = null)
    {
        if (null !== $file) {
            if (is_file($file)) {
                $this->setImageFile($file);
            } else {
                $this->setImageString($file);
            }
        }
    }

    /**
     * Set image resource from file
     *
     * @param string $file Path to image file
     * @return ImageManipulator for a fluent interface
     * @throws InvalidArgumentException
     */
    public function setImageFile($file)
    {
        if (!(is_readable($file) && is_file($file))) {
            throw new InvalidArgumentException("Image file $file is not readable");
        }

        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }

        list ($this->width, $this->height, $type) = getimagesize($file);

        $this->image_type = $type;

        switch ($type) {
            case IMAGETYPE_GIF  :
                $this->image = imagecreatefromgif($file);
                break;
            case IMAGETYPE_JPEG :
                $this->image = @imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG  :
                $this->image = imagecreatefrompng($file);
                imagesavealpha($this->image, true);

                // kod poniżej dodany, zeby przeźroczystość zamieniała się w białe tło (domyślnie zamieniała się w czarne)
                /*$backgroundImg = imagecreatetruecolor($this->width, $this->height);
                $color = imagecolorallocate($backgroundImg, 255, 255, 255);
                imagefill($backgroundImg, 0, 0, $color);
                imagecopy($backgroundImg, $this->image, 0, 0, 0, 0, $this->width, $this->height);
                imagecopy($this->image, $backgroundImg, 0, 0, 0, 0, $this->width, $this->height);*/
                break;
            default             :
                throw new InvalidArgumentException("Image type $type not supported");
        }

        return $this;
    }

    /**
     * Set image resource from string data
     *
     * @param string $data
     * @return ImageManipulator for a fluent interface
     * @throws RuntimeException
     */
    public function setImageString($data)
    {
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }

        if (!$this->image = imagecreatefromstring($data)) {
            throw new RuntimeException('Cannot create image from data string');
        }

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
        return $this;
    }

    /**
     * Resamples the current image
     *
     * @param int  $width                New width
     * @param int  $height               New height
     * @param bool $constrainProportions Constrain current image proportions when resizing
     * @return ImageManipulator for a fluent interface
     * @throws RuntimeException
     */
    public function resample($_width, $_height, $constrainProportions = true)
    {

        if (!is_resource($this->image)) {
            throw new RuntimeException('No image set');
        }
        $width = $_width;
        $height = $_height;
        if ($constrainProportions) {
            if ($this->height >= $this->width) {
                $width = round($height / $this->height * $this->width);
                if ($width<$_width) {
                    $width = $_width;
                    $height = round(($width/$this->width) * $this->height);
                }
            } else {
                $height = round($width / $this->width * $this->height);
                if ($height<$_height) {
                    $height = $_height;
                    $width = round(($height/$this->height) * $this->width);
                }
            }
        }

        $temp = imagecreatetruecolor($width, $height);
        imagealphablending($temp, false);
        imagesavealpha($temp, true);
        imagecopyresampled($temp, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);

        return $this->_replace($temp);
    }

    /**
     * Enlarge canvas
     *
     * @param int   $width  Canvas width
     * @param int   $height Canvas height
     * @param array $rgb    RGB colour values
     * @param int   $xpos   X-Position of image in new canvas, null for centre
     * @param int   $ypos   Y-Position of image in new canvas, null for centre
     * @return ImageManipulator for a fluent interface
     * @throws RuntimeException
     */
    public function enlargeCanvas($width, $height, array $rgb = array(), $xpos = null, $ypos = null)
    {
        if (!is_resource($this->image)) {
            throw new RuntimeException('No image set');
        }

        $width = max($width, $this->width);
        $height = max($height, $this->height);

        $temp = imagecreatetruecolor($width, $height);
        if (count($rgb) == 3) {
            $bg = imagecolorallocate($temp, $rgb[0], $rgb[1], $rgb[2]);
            imagefill($temp, 0, 0, $bg);
        }

        if (null === $xpos) {
            $xpos = round(($width - $this->width) / 2);
        }
        if (null === $ypos) {
            $ypos = round(($height - $this->height) / 2);
        }

        imagecopy($temp, $this->image, (int) $xpos, (int) $ypos, 0, 0, $this->width, $this->height);
        return $this->_replace($temp);
    }

    /**
     * Crop image
     *
     * @param int|array $x1 Top left x-coordinate of crop box or array of coordinates
     * @param int       $y1 Top left y-coordinate of crop box
     * @param int       $x2 Bottom right x-coordinate of crop box
     * @param int       $y2 Bottom right y-coordinate of crop box
     * @return ImageManipulator for a fluent interface
     * @throws RuntimeException
     */
    public function crop($x1, $y1 = 0, $x2 = 0, $y2 = 0)
    {
        if (!is_resource($this->image)) {
            throw new RuntimeException('No image set');
        }
        if (is_array($x1) && 4 == count($x1)) {
            list($x1, $y1, $x2, $y2) = $x1;
        }

        $x1 = max($x1, 0);
        $y1 = max($y1, 0);

        $x2 = min($x2, $this->width);
        $y2 = min($y2, $this->height);

        $width = $x2 - $x1;
        $height = $y2 - $y1;

        $temp = imagecreatetruecolor($width, $height);
        imagecopy($temp, $this->image, 0, 0, $x1, $y1, $width, $height);

        return $this->_replace($temp);
    }

    public function cropInCenter($width, $height) {
        $original_width = $this->getWidth();
        $original_height = $this->getHeight();
        if ($width>$original_width && $height>$original_height) {
            return $this->resample($width, $height);
        }
        $width = min($width, $original_width);
        $height = min($height, $original_height);
        $diff_width = $original_width - $width;
        $diff_height = $original_height - $height;

        $x1 = round($diff_width/2);
        $y1 = round($diff_height/2);
        $x2 = $x1 + $width;
        $y2 = $y1 + $height;

        return $this->crop($x1, $y1, $x2, $y2);
    }

    public function resampleAndCrop($width, $height) {
        $this->resample($width, $height);
        $this->cropInCenter($width, $height);
    }

    /**
     * Replace current image resource with a new one
     *
     * @param resource $res New image resource
     * @return ImageManipulator for a fluent interface
     * @throws UnexpectedValueException
     */
    protected function _replace($res)
    {
        if (!is_resource($res)) {
            throw new UnexpectedValueException('Invalid resource');
        }
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }
        $this->image = $res;
        $this->width = imagesx($res);
        $this->height = imagesy($res);
        return $this;
    }

    /**
     * Save current image to file
     *
     * @param string $fileName
     * @return bool
     * @throws RuntimeException
     */
    public function save($fileName, $type = IMAGETYPE_JPEG, $quality = 95)
    {
        $dir = dirname($fileName);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new RuntimeException('Error creating directory ' . $dir);
            }
        }

        try {
            switch ($type) {
                case IMAGETYPE_GIF  :
                    if (!imagegif($this->image, $fileName)) {
                        throw new RuntimeException;
                    }
                    break;
                case IMAGETYPE_PNG  :
                    if (!imagepng($this->image, $fileName)) {
                        throw new RuntimeException;
                    }
                    break;
                case IMAGETYPE_JPEG :
                default             :
                    if (!imagejpeg($this->image, $fileName, $quality)) {
                        throw new RuntimeException;
                    }
            }
        } catch (Exception $ex) {
            throw new RuntimeException('Error saving image file to ' . $fileName);
        }
        return true;
    }

    /**
     * Returns the GD image resource
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->image;
    }

    /**
     * Get current image resource width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Get current image height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Create watermark on image
     * @param string $watermark_path full path to watermark
     */
    public function createWatermarkImage($watermark_path)
    {

        if (file_exists($watermark_path) === false) {
            return false;
        }

        $watermarks_calcs = $this->calculateWatermark($watermark_path);

        $img_width = $this->getWidth();
        $img_height = $this->getHeight();

        // create stamp
        $stamp = imagecreatefrompng($watermark_path);
        self::filter_opacity($stamp, 35);

        // resize watermark
        $resized_stamp = imagecreatetruecolor($img_width, $img_height);

        imagesavealpha($resized_stamp, true);
        $transparent = imagecolorallocatealpha($resized_stamp, 0, 0, 0, 127);
        $color_transparent = imagefill($resized_stamp, 0, 0, $transparent);
        imagecolortransparent($resized_stamp, $transparent);

        imagealphablending($resized_stamp, false);

        foreach ($watermarks_calcs as $wt_calc) {
            imagecopyresampled(
                $resized_stamp,
                $stamp,
                $wt_calc['x'],//watermark['scaled_pos_x'],
                $wt_calc['y'],//$watermark['scaled_pos_y'],
                0,
                0,
                $wt_calc['width'],//$watermark['scaled_width'],
                $wt_calc['height'],//$watermark['scaled_height'],
                $wt_calc['org_width'],
                $wt_calc['org_height']
            );
        }

        imagealphablending($resized_stamp, true);

        imagecopy(
            $this->image,
            $resized_stamp,
            0,
            0,
            0,
            0,
            $img_width,
            $img_height
        );

        return true;
    }

    protected function filter_opacity(&$img, $opacity) //params: image resource id, opacity in percentage (eg. 80)
    {
        if (!isset($opacity)) {
            return false;
        }
        $opacity /= 100;

        //get image width and height
        $w = imagesx($img);
        $h = imagesy($img);

        //turn alpha blending off
        imagealphablending($img, false);

        //find the most opaque pixel in the image (the one with the smallest alpha value)
        $minalpha = 127;
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $alpha = (imagecolorat($img, $x, $y) >> 24) & 0xFF;
                if ($alpha < $minalpha) {
                    $minalpha = $alpha;
                }
            }
        }

        //loop through image pixels and modify alpha for each
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                //get current alpha value (represents the TANSPARENCY!)
                $colorxy = imagecolorat($img, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;
                //calculate new alpha
                if ($minalpha !== 127) {
                    $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minalpha);
                } else {
                    $alpha += 127 * $opacity;
                }
                //get the color index with new alpha
                $alphacolorxy = imagecolorallocatealpha($img, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
                //set pixel with the new color + opacity
                if (!imagesetpixel($img, $x, $y, $alphacolorxy)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function calculateWatermark($watermark_path) {
        list($wt_width, $wt_height, $wt_type) = getimagesize($watermark_path);
        $img_width = $this->getWidth();
        $img_height = $this->getHeight();

        $org_wt_width = $wt_width;
        $org_wt_height = $wt_height;

        if ($wt_width > $img_width) {
            $wt_width = $img_width;
            $wt_height = $wt_height * ($wt_width / $org_wt_width);
        }
        if ($wt_height > $img_height) {
            $wt_height = $img_height;
            $wt_width = $wt_width * ($wt_height / $org_wt_height);
        }

        //liczymy ile znaków wodnych zmiesci sie w pionie i poziomie
        $diff_w = floor($img_width / $wt_width);
        $diff_h = floor($img_height / $wt_height);

        //liczymy ile znaki wodne laczenie zajmą w pionie i poziomie (jeden obok drugiego)
        $all_wts_w = $wt_width * $diff_w;
        $all_wts_h = $wt_height * $diff_h;

        //liczymy o ile nalezy odsunac znaki wodne, tak aby abyly wysrodkowane
        $margin_left = floor(($img_width - $all_wts_w) / 2);
        $margin_top = floor(($img_height - $all_wts_h) / 2);

        $result = array();

        for ($row = 0; $row < $diff_h; $row++) {
            for ($column = 0; $column < $diff_w; $column++) {

                $pos_x = ($column * $wt_width) + $margin_left;
                $pos_y = ($row * $wt_height) + $margin_top;

                $result[] = array(
                    'x' => $pos_x,
                    'y' => $pos_y,
                    'width' => $wt_width,
                    'height' => $wt_height,
                    'org_width' => $org_wt_width,
                    'org_height' => $org_wt_height
                );
            }
        }

        return $result;


    }

}