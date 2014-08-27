<?php

class XdgTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return \XdgBaseDir\Xdg
     */
    public function getXdg()
    {
        return \XdgBaseDir\Xdg::createFromEnvironment();
    }

    public function testGetHomeDir()
    {
         putenv('HOME=/fake-dir');
         $this->assertEquals('/fake-dir', $this->getXdg()->getHomeDir());
    }

    public function testGetFallbackHomeDir()
    {
        putenv('HOME=');
        putenv('HOMEDRIVE=C:');
        putenv('HOMEPATH=fake-dir');
        $this->assertEquals('C:/fake-dir', $this->getXdg()->getHomeDir());
    }

    public function testXdgPutCache()
    {
        $env = [
            'XDG_DATA_HOME' => 'tmp/',
            'XDG_CONFIG_HOME' => 'tmp/',
            'XDG_CACHE_HOME' => 'tmp/',
        ];
        $xdg = \XdgBaseDir\Xdg::createFromArray($env);

        $this->assertEquals('tmp/', $xdg->getHomeCacheDir());
    }

    public function testXdgPutData()
    {
        $env = ['XDG_DATA_HOME' => 'tmp/'];
        $xdg = \XdgBaseDir\Xdg::createFromArray($env);

        $this->assertEquals('tmp/', $xdg->getHomeDataDir());
    }

    public function testXdgPutConfig()
    {
        $env = ['XDG_CONFIG_HOME' => 'tmp/'];
        $xdg = \XdgBaseDir\Xdg::createFromArray($env);

        $this->assertEquals('tmp/', $xdg->getHomeConfigDir());
    }

    public function testXdgDataDirsShouldIncludeHomeDataDir()
    {
        $env = [
            'XDG_DATA_HOME' => 'tmp/',
            'XDG_CONFIG_HOME' => 'tmp/'
        ];
        $xdg = \XdgBaseDir\Xdg::createFromArray($env);

        $this->assertArrayHasKey('tmp/', array_flip($xdg->getDataDirs()));
    }

    public function testXdgConfigDirsShouldIncludeHomeConfigDir()
    {
        $env = ['XDG_CONFIG_HOME' => 'tmp/'];
        $xdg = \XdgBaseDir\Xdg::createFromArray($env);

        $this->assertArrayHasKey('tmp/', array_flip($xdg->getConfigDirs()));
    }

    /**
     * If XDG_RUNTIME_DIR is set, it should be returned
     */
    public function testGetRuntimeDir()
    {
        $env = ['XDG_RUNTIME_DIR' => '/tmp/'];
        $xdg = \XdgBaseDir\Xdg::createFromArray($env);

        $runtimeDir =$xdg->getRuntimeDir();
        $this->assertEquals(is_dir($runtimeDir), true);
    }

    /**
     * In strict mode, an exception should be shown if XDG_RUNTIME_DIR does not exist
     *
     * @expectedException \RuntimeException
     */
    public function testGetRuntimeDirShouldThrowException()
    {
        $env = ['XDG_RUNTIME_DIR' => ''];
        $xdg = \XdgBaseDir\Xdg::createFromArray($env);

        $xdg->getRuntimeDir(true);
    }

    /**
     * In fallback mode a directory should be created
     * @filesystem
     */
    public function testGetRuntimeDirShouldCreateDirectory()
    {
        $env = ['XDG_RUNTIME_DIR' => ''];
        $xdg = \XdgBaseDir\Xdg::createFromArray($env);

        $dir = $xdg->getRuntimeDir(false);
        $permission = decoct(fileperms($dir) & 0777);
        $this->assertEquals(700, $permission);
    }

    /**
     * Ensure, that the fallback directories are created with correct permission
     * @filesystem
     */
    public function testGetRuntimeShouldDeleteDirsWithWrongPermission()
    {
        $runtimeDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . XdgBaseDir\Xdg::RUNTIME_DIR_FALLBACK . getenv('USER');

        rmdir($runtimeDir);
        mkdir($runtimeDir, 0764, true);

        // Permission should be wrong now
        $permission = decoct(fileperms($runtimeDir) & 0777);
        $this->assertEquals(764, $permission);

        $env = ['XDG_RUNTIME_DIR' => ''];
        $xdg = \XdgBaseDir\Xdg::createFromArray($env);
        $dir = $xdg->getRuntimeDir(false);

        // Permission should be fixed
        $permission = decoct(fileperms($dir) & 0777);
        $this->assertEquals(700, $permission);
    }
}
