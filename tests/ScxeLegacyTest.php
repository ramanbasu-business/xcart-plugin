<?php

use PHPUnit\Framework\TestCase;

class TestableScxeEncoder extends scxe_encoder
{
    private bool $loginResult;

    public function __construct(bool $loginResult = true)
    {
        parent::__construct();
        $this->loginResult = $loginResult;
    }

    public function dbLogin($email, $password): bool
    {
        return $this->loginResult;
    }
}

class TestableScxeJob extends scxe_job
{
    public array $queries = [];

    public function db_execute($query)
    {
        $this->queries[] = $query;
        return true;
    }

    public function db_select($sql)
    {
        $this->queries[] = $sql;

        if (stripos($sql, 'max( id )') !== false) {
            return [[42]];
        }

        return [['id' => 7, 'status' => 0, 'module' => 'inventory']];
    }
}

class ScxeLegacyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['QUERY_STRING'] = '';
    }

    public function testValidateLoginAcceptsValidCredentials(): void
    {
        $_SERVER['QUERY_STRING'] = 'u=admin@example.com&p=CorrectHorseBatteryStaple';

        $encoder = new TestableScxeEncoder(true);
        $result = $encoder->validateLogin();

        $this->assertSame([
            'u' => 'admin@example.com',
            'p' => 'CorrectHorseBatteryStaple',
        ], $result);
    }

    public function testValidateLoginRejectsInvalidEmail(): void
    {
        $_SERVER['QUERY_STRING'] = 'u=not-an-email&p=CorrectHorseBatteryStaple';

        $encoder = new TestableScxeEncoder(true);

        ob_start();
        $result = $encoder->validateLogin();
        $output = ob_get_clean();

        $this->assertNull($result);
        $this->assertStringContainsString('Invalid user', $output);
    }

    public function testUpdateJobStatusClampsOutOfRangeValues(): void
    {
        $job = new TestableScxeJob();

        $job->updateJobStatus(999999, 99);

        $this->assertStringContainsString('UPDATE job SET status=2 WHERE id=999999', $job->queries[0]);
    }

    public function testInsertJobSanitizesFieldsAndReturnsLastId(): void
    {
        $job = new TestableScxeJob();

        $id = $job->insertJob(
            '2024-01-01 09:00:00',
            '2024-01-01 09:02:00',
            '../../bad<file>.xml',
            99,
            'log<name>.txt',
            'module!name'
        );

        $query = $job->queries[0];

        $this->assertSame(42, $id);
        $this->assertStringNotContainsString('<', $query);
        $this->assertStringNotContainsString('!', $query);
        $this->assertStringContainsString('modulename', $query);
        $this->assertStringContainsString("'99'", $query);
    }

    public function testGetOneSubmittedJobUsesSanitizedModule(): void
    {
        $job = new TestableScxeJob();

        $row = $job->getOneSubmittedJob('module!name');

        $this->assertSame(['id' => 7, 'status' => 0, 'module' => 'inventory'], $row);
        $this->assertStringContainsString("module='modulename'", $job->queries[0]);
    }
}
