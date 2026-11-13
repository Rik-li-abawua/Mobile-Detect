<?php

namespace DetectionTests;

use DateInterval;
use Detection\Cache\Cache;
use Detection\Cache\CacheInvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CacheTest extends TestCase
{
    protected Cache $cache;
    protected function setUp(): void
    {
        $this->cache = new Cache();
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testGetInvalidCacheKeyThrowsException()
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->get('');
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetInvalidCacheKeyThrowsException()
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->set('', 'a', 100);
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testGetNonExistentReturnsNull()
    {
        $this->assertNull($this->cache->get('random'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testGetNonExistentReturnsCustomDefault(): void
    {
        $this->assertEquals('customDefault', $this->cache->get('random', 'customDefault'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testGetExpiredItemReturnsDefault(): void
    {
        $this->cache->set('expiring', 'value', 1);
        sleep(2);
        $this->assertNull($this->cache->get('expiring'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testGetExpiredItemReturnsCustomDefault(): void
    {
        $this->cache->set('expiring', 'value', 1);
        sleep(2);
        $this->assertEquals('fallback', $this->cache->get('expiring', 'fallback'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetGetBooleanValues()
    {
        $this->cache->set('isMobile', true, 100);
        $this->assertTrue($this->cache->get('isMobile'));

        $this->cache->set('isTablet', false, 100);
        $this->assertFalse($this->cache->get('isTablet'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetGetZeroTTL()
    {
        $this->cache->set('isMobile', true, 0);
        $this->assertNull($this->cache->get('isMobile'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetGetNegativeTTL()
    {
        $this->cache->set('isMobile', true, -999);
        $this->assertNull($this->cache->get('isMobile'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetZeroTTLWithInvalidKeyThrowsException()
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->set('', true, 0);
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetNegativeTTLWithInvalidKeyThrowsException()
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->set('', true, -999);
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetValidTtlAsAnIntegerReturnsTheSetValue()
    {
        $this->cache->set('isMobile', 'someValue', 1000);
        $this->assertEquals('someValue', $this->cache->get('isMobile'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetNullTtlReturnsTheSetValue()
    {
        $this->cache->set('isMobile', 'abc');
        $this->assertEquals('abc', $this->cache->get('isMobile'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetWithDateIntervalTtl(): void
    {
        $this->cache->set('withInterval', 'intervalValue', new DateInterval('PT1H'));
        $this->assertEquals('intervalValue', $this->cache->get('withInterval'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testDeletionOfValidRecord()
    {
        $this->cache->set('isMobile', 'a b c', 100);
        $this->assertEquals('a b c', $this->cache->get('isMobile'));
        $this->cache->delete('isMobile');
        $this->assertNull($this->cache->get('isMobile'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testDeleteInvalidKeyThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->delete('invalid key');
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testDeleteNonExistentKeyReturnsTrue(): void
    {
        $this->assertTrue($this->cache->delete('non_existent'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testClear()
    {
        $this->cache->set('isMobile', true);
        $this->cache->set('isTablet', true);
        $this->assertCount(2, $this->cache->getKeys());
        $this->cache->clear();
        $this->assertCount(0, $this->cache->getKeys());
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testGetMultiple(): void
    {
        $this->cache->set('isMobile', true, 100);
        $this->cache->set('isTablet', false, 200);

        $this->assertEquals(
            [
            'isMobile' => true,
            'isTablet' => false,
            'isUnknown' => null,
            ],
            $this->cache->getMultiple(['isMobile', 'isTablet', 'isUnknown'])
        );
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetMultiple(): void
    {
        $this->cache->setMultiple(['isA' => true, 'isB' => false], 200);
        $this->assertEquals([
            'isA' => true,
            'isB' => false
        ], $this->cache->getMultiple(['isA', 'isB']));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testDeleteMultiple(): void
    {
        $this->cache->setMultiple(['isA' => true, 'isB' => false, 'isC' => true], 300);

        $this->cache->deleteMultiple(['isA', 'isB']);

        $this->assertEquals([
            'isA' => null,
            'isB' => null,
            'isC' => true
        ], $this->cache->getMultiple(['isA', 'isB', 'isC']));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testHasReturnsTrueForValidCacheRecord(): void
    {
        $this->cache->set('isA', 'some value1');
        $this->assertTrue($this->cache->has('isA'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testHasReturnsTrueForValidNonNullTtl(): void
    {
        $this->cache->set('isB', 'some value', 3600);
        $this->assertTrue($this->cache->has('isB'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testHasReturnsFalseForExpiredCacheRecord(): void
    {
        $this->cache->set('isA', 'some value2', 1);
        sleep(2);
        $this->assertFalse($this->cache->has('isA'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testHasReturnsFalseForNonExistentCacheRecord(): void
    {
        $this->assertFalse($this->cache->has('non_existent'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testHasThrowsExceptionForInvalidKey(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->has('invalid key');
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testGetMultipleWithCustomDefault(): void
    {
        $this->cache->set('exists', 'value', 100);

        $this->assertEquals(
            [
                'exists' => 'value',
                'missing' => 'customDefault',
            ],
            $this->cache->getMultiple(['exists', 'missing'], 'customDefault')
        );
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testSetMultipleReturnsFalseWhenOneFails(): void
    {
        // Setting with zero TTL causes set() to return false
        $result = $this->cache->setMultiple(['isA' => true, 'isB' => false], 0);
        $this->assertFalse($result);
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testCheckKeyWithInvalidCharactersThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->get('invalid@key');
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testCheckKeyWithSpecialCharsThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->get('key{}[]');
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testCheckKeyExceeding64CharsThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $longKey = str_repeat('a', 65);
        $this->cache->get($longKey);
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testCheckKeyWith64CharsIsValid(): void
    {
        $validKey = str_repeat('a', 64);
        $this->cache->set($validKey, 'value');
        $this->assertEquals('value', $this->cache->get($validKey));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testEvictExpiredRemovesExpiredItems(): void
    {
        $this->cache->set('expiring1', 'value1', 1);
        $this->cache->set('expiring2', 'value2', 1);
        $this->cache->set('persistent', 'value3', 3600);

        $this->assertCount(3, $this->cache->getKeys());

        sleep(2);

        $evicted = $this->cache->evictExpired();

        $this->assertEquals(2, $evicted);
        $this->assertCount(1, $this->cache->getKeys());
        $this->assertEquals('value3', $this->cache->get('persistent'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testEvictExpiredKeepsNullTtlItems(): void
    {
        $this->cache->set('noTtl', 'value1');
        $this->cache->set('expiring', 'value2', 1);

        sleep(2);

        $evicted = $this->cache->evictExpired();

        $this->assertEquals(1, $evicted);
        $this->assertCount(1, $this->cache->getKeys());
        $this->assertEquals('value1', $this->cache->get('noTtl'));
    }

    /**
     * @throws CacheInvalidArgumentException
     */
    public function testEvictExpiredReturnsZeroWhenNothingToEvict(): void
    {
        $this->cache->set('valid1', 'value1', 3600);
        $this->cache->set('valid2', 'value2');

        $evicted = $this->cache->evictExpired();

        $this->assertEquals(0, $evicted);
        $this->assertCount(2, $this->cache->getKeys());
    }

    public function testEvictExpiredOnEmptyCache(): void
    {
        $evicted = $this->cache->evictExpired();

        $this->assertEquals(0, $evicted);
    }

    /**
     * Regression test for https://github.com/serbanghita/Mobile-Detect/issues/989
     *
     * Cache must remain LSP-compatible with PSR-16 v1 (untyped parameters) so it
     * loads cleanly in hosts where another plugin has registered an older
     * Psr\SimpleCache\CacheInterface. Re-adding scalar type declarations on the
     * listed parameters will break that at class load with a fatal error.
     */
    public function testPublicMethodParametersAreUntypedForPsr16V1Compatibility(): void
    {
        // [method => parameter index] pairs that MUST have no type declaration.
        $untypedSlots = [
            ['get', 0],              // $key
            ['set', 0],              // $key
            ['set', 2],              // $ttl
            ['delete', 0],           // $key
            ['has', 0],              // $key
            ['getMultiple', 0],      // $keys
            ['setMultiple', 0],      // $values
            ['setMultiple', 1],      // $ttl
            ['deleteMultiple', 0],   // $keys
        ];

        foreach ($untypedSlots as [$method, $index]) {
            $param = (new \ReflectionMethod(Cache::class, $method))->getParameters()[$index];
            self::assertFalse(
                $param->hasType(),
                sprintf(
                    'Cache::%s() parameter $%s must have no type declaration for PSR-16 v1 LSP compatibility, got: %s',
                    $method,
                    $param->getName(),
                    (string) $param->getType()
                )
            );
        }
    }

    public function testSetWithInvalidTtlTypeThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->set('isMobile', true, 'not-a-valid-ttl');
    }

    public function testSetMultipleWithNonIterableThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->setMultiple('not-iterable');
    }

    public function testGetMultipleWithNonIterableThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->getMultiple('not-iterable');
    }

    public function testDeleteMultipleWithNonIterableThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->deleteMultiple('not-iterable');
    }

    public function testGetWithNonStringKeyThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->get(123);
    }

    public function testSetWithNonStringKeyThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->cache->set(['notAKey'], 'value');
    }

    /**
     * Regression test for GHSA-mgj4-qjmw-v56v.
     *
     * Inserting more unique keys than the default cap must not grow the cache
     * past the cap. This is the core guarantee that prevents unbounded memory
     * growth in long-running workers when keys are influenced by external input
     * (e.g. attacker-supplied User-Agent strings).
     */
    public function testCacheRespectsMaxEntriesDefault(): void
    {
        $this->assertSame(Cache::DEFAULT_MAX_ENTRIES, $this->cache->getMaxEntries());

        for ($i = 0; $i < Cache::DEFAULT_MAX_ENTRIES + 500; $i++) {
            $this->cache->set('k_' . $i, $i, 3600);
        }

        $this->assertCount(Cache::DEFAULT_MAX_ENTRIES, $this->cache->getKeys());
    }

    public function testCacheEvictsOldestEntryFirstWhenFull(): void
    {
        $cache = new Cache(2);

        $cache->set('a', 1, 3600);
        $cache->set('b', 2, 3600);
        $cache->set('c', 3, 3600); // forces eviction of 'a'

        $this->assertSame(['b', 'c'], $cache->getKeys());
        $this->assertNull($cache->get('a'));
        $this->assertSame(2, $cache->get('b'));
        $this->assertSame(3, $cache->get('c'));
    }

    public function testCacheConstructorAcceptsCustomMaxEntries(): void
    {
        $cache = new Cache(5);
        $this->assertSame(5, $cache->getMaxEntries());

        for ($i = 0; $i < 10; $i++) {
            $cache->set('k_' . $i, $i, 3600);
        }

        $this->assertCount(5, $cache->getKeys());
    }

    public function testOverwriteExistingKeyDoesNotTriggerEviction(): void
    {
        $cache = new Cache(2);

        $cache->set('a', 1, 3600);
        $cache->set('b', 2, 3600);
        $cache->set('a', 99, 3600); // overwrite, must not evict 'b'

        $this->assertCount(2, $cache->getKeys());
        $this->assertSame(99, $cache->get('a'));
        $this->assertSame(2, $cache->get('b'));
    }
}
