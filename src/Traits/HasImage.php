<?php

namespace Attla\Authentic\Traits;

trait HasImage
{
    /**
     * Boot trait
     *
     * @return void
     */
    public static function bootHasImage()
    {
        static::building(function ($model) {
            $model->appends[] = 'image';
        });
    }

    /**
     * Get user image
     *
     * @return string
     */
    public function getImageAttribute()
    {
        if (!empty($this->social_image)) {
            return $this->social_image;
        }

        return $this->gravatar($this->image_hash, 150, $this->getDefaultImage());
    }

    /**
     * Get user image
     *
     * @return string
     */
    public function getImageHashAttribute()
    {
        return md5(strtolower(trim($this->email)));
    }

    /**
     * Get gravatar image by user email
     *
     * @param string $email
     * @param int $size
     * @param string $default
     * @return string
     */
    public function gravatar($seed, $size, $default = 'identicon')
    {
        if (in_array($default, ['initials', 'initial', 'letters', 'letter'])) {
            $default = $this->initials($seed);
        } else if (in_array($default, ['bottts-neutral', 'shapes', 'thumbs'])) {
            $default = $this->dicebear($seed, $default);
        } else if ($default == 'multiavatar') {
            $default = "https://api.multiavatar.com/{$seed}.png";
        }

        return "https://s.gravatar.com/avatar/{$seed}?size={$size}&d=" . $default;
    }

    /**
     * Get dicebear image
     *
     * @param string $seed
     * @param string $style
     * @return string
     */
    public function dicebear($seed, $style = 'bots')
    {
        return "https://api.dicebear.com/9.x/{$style}/svg?seed=" . $seed;
    }

    /**
     * Get initials image
     *
     * @param string $seed
     * @return string
     */
    public function initials($seed)
    {
        return "https://api.dicebear.com/7.x/initials/svg?backgroundType=gradientLinear"
             . "&fontFamily=Helvetica&fontSize=40&seed=" . $seed;
    }

    /**
     * Get default user image
     *
     * @return string
     */
    public function getDefaultImage()
    {
        $default = 'bottts-neutral';
        if (isset($this->defaultImage)) {
            return $this->defaultImage ?: $default;
        }

        return $default;
    }
}
