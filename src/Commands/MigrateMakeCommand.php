<?php declare(strict_types=1);

namespace Alexeykhr\ClickhouseMigrations\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Alexeykhr\ClickhouseMigrations\StubFactory;
use Alexeykhr\ClickhouseMigrations\Concerns\MigrationPath;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Alexeykhr\ClickhouseMigrations\Migrations\MigrationCreator;
use Alexeykhr\ClickhouseMigrations\Exceptions\ClickhouseStubException;

class MigrateMakeCommand extends Command
{
    use MigrationPath;

    /**
     * @inheritDoc
     */
    protected $signature = 'make:clickhouse-migration {name : The name of the migration}
                {--table= : The table to migrate}
                {--path= : Path to Clickhouse directory with migrations}
                {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}';

    /**
     * @inheritDoc
     */
    protected $description = 'Create a new ClickHouse migration file';

    /**
     * @var MigrationCreator
     */
    protected $creator;

    /**
     * @var Composer
     */
    protected $composer;

    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    /**
     * Execute the console command
     *
     * @return void
     * @throws FileNotFoundException
     * @throws ClickhouseStubException
     */
    public function handle(): void
    {
        // Depending on the received parameters, we use the appropriate stub
        // to generate the migration
        $this->applyStub();

        $path = $this->creator->create(
            $this->getNameArgument(),
            $this->getMigrationPath(),
            ['table' => $this->getTableOption()]
        );

        $this->line($path
            ? "<info>Migration created</info> {$path}."
            : '<error>Migration file not created.</error>');

        $this->composer->dumpAutoloads();
    }

    /**
     * Use stub file to generate migration
     *
     * @return void
     * @throws ClickhouseStubException
     */
    protected function applyStub(): void
    {
        if ($this->getTableOption()) {
            $this->creator->setStub(StubFactory::create('table'));
        }
    }

    /**
     * @return string
     */
    protected function getNameArgument(): string
    {
        return Str::snake(trim($this->input->getArgument('name')));
    }

    /**
     * @return string|null
     */
    protected function getTableOption(): ?string
    {
        return $this->option('table');
    }
}
