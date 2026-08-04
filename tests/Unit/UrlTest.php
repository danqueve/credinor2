<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    private array $envBackup;
    private array $serverBackup;

    protected function setUp(): void
    {
        $this->envBackup = $_ENV;
        $this->serverBackup = $_SERVER;
        Url::reset();
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        $_SERVER = $this->serverBackup;
        Url::reset();
    }

    public function testUsaAppUrlDelEnvCuandoEstaDefinida(): void
    {
        $_ENV['APP_URL'] = 'https://credinor.credinort.uno';

        $this->assertSame('https://credinor.credinort.uno', Url::base());
        $this->assertSame('https://credinor.credinort.uno/creditos', Url::to('/creditos'));
    }

    public function testQuitaLaBarraFinalDeAppUrl(): void
    {
        $_ENV['APP_URL'] = 'https://credinor.credinort.uno/';

        $this->assertSame('https://credinor.credinort.uno', Url::base());
    }

    public function testAutoDetectaEnLocalhostBajoSubdirectorioPublic(): void
    {
        $_ENV['APP_URL'] = '';
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/credinor2/public/index.php';
        unset($_SERVER['HTTPS']);

        $this->assertSame('http://localhost/credinor2/public', Url::base());
        $this->assertSame('http://localhost/credinor2/public/creditos', Url::to('/creditos'));
    }

    public function testDetectaHttpsPorHeaderDeProxy(): void
    {
        $_ENV['APP_URL'] = '';
        $_SERVER['HTTP_HOST']   = 'credinor.credinort.uno';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $this->assertSame('https', Url::scheme());
    }

    public function testToSinArgumentosDevuelveLaBase(): void
    {
        $_ENV['APP_URL'] = 'http://localhost/credinor2/public';

        $this->assertSame('http://localhost/credinor2/public', Url::to());
    }

    public function testToNormalizaBarraInicialDelPath(): void
    {
        $_ENV['APP_URL'] = 'http://localhost/credinor2/public';

        $this->assertSame('http://localhost/credinor2/public/clientes', Url::to('clientes'));
        $this->assertSame('http://localhost/credinor2/public/clientes', Url::to('/clientes'));
    }
}
