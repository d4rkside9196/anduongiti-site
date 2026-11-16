<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BrowserDatabasePreparationTest extends TestCase
{
    private string $databaseName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseName = 'anduongiti_browser_prep_'.getmypid();
    }

    protected function tearDown(): void
    {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '123456');
        $pdo->exec("DROP DATABASE IF EXISTS {$this->databaseName}");

        parent::tearDown();
    }

    public function test_disposable_mysql_database_uses_environment_credentials_without_touching_development_data(): void
    {
        $password = 'Disposable-Browser!2026';
        $developmentPdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=anduongiti', 'root', '123456');
        $originalHash = $developmentPdo->query("SELECT password FROM users WHERE email = 'admin@anduongiti.com'")->fetchColumn();

        $process = new Process(['bash', 'scripts/prepare-browser-db.sh'], base_path(), [
            'ADMIN_EMAIL' => 'admin@anduongiti.com',
            'ADMIN_PASSWORD' => $password,
            'BROWSER_DB_DATABASE' => $this->databaseName,
        ]);
        $process->setTimeout(120);
        $process->mustRun();

        $browserPdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname='.$this->databaseName, 'root', '123456');
        $browserHash = $browserPdo->query("SELECT password FROM users WHERE email = 'admin@anduongiti.com'")->fetchColumn();

        $this->assertTrue(Hash::check($password, $browserHash), $process->getOutput().$process->getErrorOutput());
        $this->assertFalse(Hash::check('anduong2026', $browserHash));
        $this->assertSame(
            $originalHash,
            $developmentPdo->query("SELECT password FROM users WHERE email = 'admin@anduongiti.com'")->fetchColumn(),
            'The development MySQL admin must not be modified by browser setup.',
        );
    }
}
