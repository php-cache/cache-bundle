<?php

/**
 * This file is part of cache-bundle
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Aequasi\Bundle\CacheBundle\Exception;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class InvalidArgumentException extends \Exception implements \Psr\Cache\InvalidArgumentException
{
}
