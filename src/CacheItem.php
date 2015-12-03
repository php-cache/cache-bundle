<?php

/**
 * This file is part of cache-bundle
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Aequasi\Bundle\CacheBundle;

use Aequasi\Bundle\CacheBundle\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheItem implements CacheItemInterface
{
    /**
     * @type string
     */
    private $key;

    /**
     * @type mixed
     */
    private $value;

    /**
     * @type \DateTime|null
     */
    private $expirationDate = null;

    /**
     * CacheItem constructor.
     *
     * @param string $key
     *
     * @throws InvalidArgumentException
     */
    public function __construct($key)
    {
        $this->key = $key;
        $this->value = serialize(null);
    }

    /**
     * {@inheritDoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritDoc}
     */
    public function get()
    {
        if (!$this->isHit()) {
            return null;
        }

        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function isHit()
    {
        if ($this->expirationDate !== null && new \DateTime > $this->expirationDate) {
            return false;
        }

        return $this->value !== serialize(null);
    }

    /**
     * {@inheritDoc}
     */
    public function set($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAt($expiration = null)
    {
        $this->expirationDate = $expiration;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAfter($time = null)
    {
        if ($time === null) {
            $this->expirationDate = null;
        } elseif ($time instanceof \DateInterval) {
            $this->expirationDate = new \DateTime();
            $this->expirationDate->add($time);
        } else {
            $this->expirationDate = new DateTime();
            $this->expirationDate->add(new \DateInterval('P'.$time.'S'));
        }

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }
}
