<?php

declare(strict_types=1);

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Command;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class CacheFlushCommand extends Command
{
    private const BUILT_IN_TYPES = ['session', 'router'];

    /**
     * @param array<string, mixed> $builtInPools
     */
    public function __construct(
        private readonly ContainerInterface $pools,
        private readonly array $builtInPools = [],
    ) {
        parent::__construct('cache:flush');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Flushes a configured cache pool')
            ->addArgument('type', InputArgument::OPTIONAL, 'Cache type: all, session, router, symfony, or provider')
            ->addArgument('service', InputArgument::OPTIONAL, 'Cache pool service ID when type is provider');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');
        if (null === $type) {
            $question = new ConfirmationQuestion('Clear all configured caches? [y/N] ', false);
            if (!(new QuestionHelper())->ask($input, $output, $question)) {
                return self::SUCCESS;
            }
            $type = 'all';
        }

        if (!\is_string($type) || !\in_array($type, $this->validTypes(), true)) {
            $output->writeln(\sprintf('<error>Unknown cache type "%s".</error>', \is_scalar($type) ? (string) $type : get_debug_type($type)));

            return self::FAILURE;
        }

        if ('provider' === $type) {
            return $this->clearProvider($input, $output);
        }

        if ('symfony' === $type) {
            return $this->clearSymfonyCache($output);
        }

        if ('all' === $type) {
            $success = true;
            foreach (self::BUILT_IN_TYPES as $builtInType) {
                $success = $this->clearBuiltIn($builtInType) && $success;
            }

            return self::SUCCESS === $this->clearSymfonyCache($output) && $success
                ? self::SUCCESS
                : self::FAILURE;
        }

        return $this->clearBuiltIn($type) ? self::SUCCESS : self::FAILURE;
    }

    private function clearBuiltIn(string $type): bool
    {
        if (!\array_key_exists($type, $this->builtInPools)) {
            return true;
        }

        $serviceId = $this->builtInPools[$type];
        if (!\is_string($serviceId)) {
            return false;
        }

        $pool = $this->getPool($serviceId);
        if ($pool instanceof TaggableCacheItemPoolInterface) {
            return $pool->invalidateTag($type);
        }

        return $pool?->clear() ?? false;
    }

    private function clearProvider(InputInterface $input, OutputInterface $output): int
    {
        $serviceId = $input->getArgument('service');
        if (!\is_string($serviceId) || '' === $serviceId || !$this->pools->has($serviceId)) {
            $output->writeln('<error>Pass a cache pool service ID that exists.</error>');

            return self::FAILURE;
        }

        return $this->getPool($serviceId)?->clear() ? self::SUCCESS : self::FAILURE;
    }

    private function clearSymfonyCache(OutputInterface $output): int
    {
        $application = $this->getApplication();
        if (null === $application || !$application->has('cache:clear')) {
            return self::FAILURE;
        }

        return $application->find('cache:clear')->run(new ArrayInput(['command' => 'cache:clear']), $output);
    }

    private function getPool(string $serviceId): ?CacheItemPoolInterface
    {
        if (!$this->pools->has($serviceId)) {
            return null;
        }

        $pool = $this->pools->get($serviceId);

        return $pool instanceof CacheItemPoolInterface ? $pool : null;
    }

    /**
     * @return list<string>
     */
    private function validTypes(): array
    {
        return ['all', ...self::BUILT_IN_TYPES, 'symfony', 'provider'];
    }
}
