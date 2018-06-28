<?php

namespace Satooshi\Bundle\CoverallsV1Bundle\Entity\Git;

use Satooshi\Bundle\CoverallsV1Bundle\Entity\Coveralls;

/**
 * Remote info.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class Remote extends Coveralls
{
    /**
     * Remote name.
     *
     * @var string
     */
    protected $name;

    /**
     * Remote URL.
     *
     * @var string
     */
    protected $url;

    // API

    /**
     * {@inheritdoc}
     *
     * @see \Satooshi\Bundle\CoverallsBundle\Entity\ArrayConvertable::toArray()
     */
    public function toArray()
    {
        return array(
            'name' => $this->name,
            'url'  => $this->url,
        );
    }

    // accessor

    /**
     * Set remote name.
     *
     * @param string $name Remote name.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Entity\Git\Remote
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Return remote name.
     *
     * @return string
     */
    public function getName()
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return;
    }

    /**
     * Set remote URL.
     *
     * @param string $url Remote URL.
     *
     * @return \Satooshi\Bundle\CoverallsV1Bundle\Entity\Git\Remote
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Return remote URL.
     *
     * @return string
     */
    public function getUrl()
    {
        if (isset($this->url)) {
            return $this->url;
        }

        return;
    }
}
