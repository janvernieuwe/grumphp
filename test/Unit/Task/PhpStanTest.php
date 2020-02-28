<?php

declare(strict_types=1);

namespace GrumPHPTest\Uni\Task;

use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use GrumPHP\Task\PhpStan;
use GrumPHP\Task\TaskInterface;
use GrumPHP\Test\Task\AbstractExternalTaskTestCase;

class PhpStanTest extends AbstractExternalTaskTestCase
{
    protected function provideTask(): TaskInterface
    {
        return new PhpStan(
            $this->processBuilder->reveal(),
            $this->formatter->reveal()
        );
    }

    public function provideConfigurableOptions(): iterable
    {
        yield 'defaults' => [
            [],
            [
                'autoload_file' => null,
                'configuration' => null,
                'level' => 0,
                'ignore_patterns' => [],
                'force_patterns' => [],
                'triggered_by' => ['php'],
                'memory_limit' => null
            ]
        ];
    }

    public function provideRunContexts(): iterable
    {
        yield 'run-context' => [
            true,
            $this->mockContext(RunContext::class)
        ];

        yield 'pre-commit-context' => [
            true,
            $this->mockContext(GitPreCommitContext::class)
        ];

        yield 'other' => [
            false,
            $this->mockContext()
        ];
    }

    public function provideFailsOnStuff(): iterable
    {
        yield 'exitCode1' => [
            [],
            $this->mockContext(RunContext::class, ['hello.php']),
            function () {
                $this->mockProcessBuilder('phpstan', $process = $this->mockProcess(1));
                $this->formatter->format($process)->willReturn('nope');
            },
            'nope'
        ];
    }

    public function providePassesOnStuff(): iterable
    {
        yield 'exitCode0' => [
            [],
            $this->mockContext(RunContext::class, ['hello.php']),
            function () {
                $this->mockProcessBuilder('phpstan', $this->mockProcess(0));
            }
        ];

        yield 'no-php-files-but-with-force-patterns' => [
            [
                'force_patterns' => ['file.txt'],
            ],
            $this->mockContext(RunContext::class, ['file.txt']),
            function () {
                $this->mockProcessBuilder('phpstan', $this->mockProcess(0));
            }
        ];
    }

    public function provideSkipsOnStuff(): iterable
    {
        yield 'no-files' => [
            [],
            $this->mockContext(RunContext::class),
            function () {}
        ];
        yield 'no-files-after-triggered-by' => [
            [],
            $this->mockContext(RunContext::class, ['notaphpfile.txt']),
            function () {}
        ];
        yield 'no-files-after-ignore-patterns' => [
            [
                'ignore_patterns' => ['test/'],
            ],
            $this->mockContext(RunContext::class, ['test/file.php']),
            function () {}
        ];
    }

    public function provideExternalTaskRuns(): iterable
    {
        yield 'defaults' => [
            [],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'phpstan',
            [
                'analyse',
                '--level=0',
                '--no-ansi',
                '--no-interaction',
                '--no-progress',
                'hello.php',
                'hello2.php',
            ]
        ];
        yield 'autoload' => [
            [
                'autoload_file' => 'autoload.php'
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'phpstan',
            [
                'analyse',
                '--autoload-file=autoload.php',
                '--level=0',
                '--no-ansi',
                '--no-interaction',
                '--no-progress',
                'hello.php',
                'hello2.php',
            ]
        ];
        yield 'configuration' => [
            [
                'configuration' => 'configurationfile'
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'phpstan',
            [
                'analyse',
                '--configuration=configurationfile',
                '--level=0',
                '--no-ansi',
                '--no-interaction',
                '--no-progress',
                'hello.php',
                'hello2.php',
            ]
        ];
        yield 'memory-limit' => [
            [
                'memory_limit' => '250MB'
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'phpstan',
            [
                'analyse',
                '--memory-limit=250MB',
                '--level=0',
                '--no-ansi',
                '--no-interaction',
                '--no-progress',
                'hello.php',
                'hello2.php',
            ]
        ];
        yield 'level' => [
            [
                'level' => 9001,
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'phpstan',
            [
                'analyse',
                '--level=9001',
                '--no-ansi',
                '--no-interaction',
                '--no-progress',
                'hello.php',
                'hello2.php',
            ]
        ];

        /*
         *         $arguments->addOptionalArgument('--autoload-file=%s', $config['autoload_file']);
        $arguments->addOptionalArgument('--configuration=%s', $config['configuration']);
        $arguments->addOptionalArgument('--memory-limit=%s', $config['memory_limit']);
        $arguments->addOptionalMixedArgument('--level=%s', $config['level']);
         */
    }
}
