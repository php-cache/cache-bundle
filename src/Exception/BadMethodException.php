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

use Psr\Cache\CacheException;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class BadMethodCallException extends \BadMethodCallException implements CacheException
{
}
