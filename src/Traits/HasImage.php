<?php

namespace Attla\Authentic\Traits;

trait HasImage
{
    /**
     * Default user image
     *
     * @return string
     */
    protected $defaultImage = 'bottts-neutral';

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

        return $this->gravatar($this->email, 150, $this->defaultImage ?? 'bottts-neutral');
    }

    /**
     * Get gravatar image by user email
     *
     * @param string $email
     * @param int $size
     * @param string $default
     * @return string
     */
    public function gravatar($email, $size, $default = 'identicon')
    {
        $token = md5(strtolower(trim($email)));

        if (in_array($default, ['bottts-neutral', 'shapes', 'thumbs'])) {
            $default = $this->dicebear($token, $default);
        } else if ($default == 'multiavatar') {
            $default = "https://api.multiavatar.com/{$token}.png";
        }

        return "https://s.gravatar.com/avatar/{$token}?size={$size}&d=" . $default;
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
}