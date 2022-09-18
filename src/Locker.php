<?php

namespace WebmanTech\SymfonyLock;

use support\Container;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class Locker
{
    public static function __callStatic($name, $arguments)
    {
        $key = $arguments[0] ?? '';
        unset($arguments[0]);
        return static::createLock($name . $key, ...$arguments);
    }

    /**
     * 创建锁
     * @param string $key
     * @param float|null $ttl
     * @param bool|null $autoRelease
     * @param string|null $prefix
     * @return LockInterface
     */
    protected static function createLock(string $key, ?float $ttl = null, ?bool $autoRelease = null, ?string $prefix = null)
    {
        $config = config('plugin.webman-tech.symfony-lock.lock.default_config', []);
        $ttl = $ttl !== null ? $ttl : ($config['ttl'] ?? 300);
        $autoRelease = $autoRelease !== null ? $autoRelease : ($config['auto_release'] ?? true);
        $prefix = $prefix !== null ? $prefix : ($config['prefix'] ?? 'lock_');
        return static::getLockFactory()->createLock($prefix . $key, $ttl, $autoRelease);
    }

    protected static $factory = null;

    /**
     * @return LockFactory
     */
    protected static function getLockFactory()
    {
        if (static::$factory === null) {
            $storage = config('plugin.webman-tech.symfony-lock.lock.storage');
            $storageConfig = config('plugin.webman-tech.symfony-lock.lock.storage_configs')[$storage];
            if (is_callable($storageConfig['construct'])) {
                $storageConfig['construct'] = call_user_func($storageConfig['construct']);
            }
            $storageInstance = Container::make($storageConfig['class'], $storageConfig['construct']);
            static::$factory = new LockFactory($storageInstance);
        }

        return static::$factory;
    }
}
