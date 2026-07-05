<?php

namespace Tests\Unit\Support;

use App\Support\UrlGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlGuardTest extends TestCase
{
    #[DataProvider('safeUrls')]
    public function test_allows_trusted_hosts(string $url): void
    {
        $this->assertTrue(UrlGuard::isSafeToFetch($url), "Seharusnya aman: {$url}");
    }

    #[DataProvider('unsafeUrls')]
    public function test_blocks_unsafe_or_internal_urls(string $url): void
    {
        $this->assertFalse(UrlGuard::isSafeToFetch($url), "Seharusnya diblokir: {$url}");
    }

    public function test_substring_host_spoofing_is_blocked(): void
    {

        $this->assertFalse(UrlGuard::isSafeToFetch('http://127.0.0.1/docs.google.com'));
        $this->assertFalse(UrlGuard::isSafeToFetch('http://169.254.169.254/?docs.google.com'));
        $this->assertFalse(UrlGuard::isGoogleHost('http://evil.com/docs.google.com'));
    }

    public function test_public_ip_detection(): void
    {
        $this->assertTrue(UrlGuard::isPublicIp('8.8.8.8'));
        $this->assertFalse(UrlGuard::isPublicIp('127.0.0.1'));
        $this->assertFalse(UrlGuard::isPublicIp('10.0.0.5'));
        $this->assertFalse(UrlGuard::isPublicIp('192.168.1.1'));
        $this->assertFalse(UrlGuard::isPublicIp('169.254.169.254'));
    }

    public static function safeUrls(): array
    {
        return [
            ['https://docs.google.com/document/d/abc123/edit'],
            ['https://docs.google.com/spreadsheets/d/abc123/edit'],
            ['https://drive.google.com/file/d/abc123/view'],
            ['https://accounts.google.com/signin'],
            ['https://www.notion.so/Some-Page-123'],
            ['https://team.notion.site/page'],
        ];
    }

    public static function unsafeUrls(): array
    {
        return [
            ['http://127.0.0.1'],
            ['http://169.254.169.254/latest/meta-data/'],
            ['http://localhost:6379'],
            ['http://10.0.0.1/internal'],
            ['http://192.168.1.10'],
            ['https://evil.com'],
            ['ftp://docs.google.com/file'],
            ['file:///etc/passwd'],
            ['https://docs.google.com.evil.com/doc'],
            ['not-a-url'],
        ];
    }
}
