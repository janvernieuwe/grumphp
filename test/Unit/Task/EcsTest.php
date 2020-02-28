<?php

declare(strict_types=1);

namespace GrumPHPTest\Uni\Task;

use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use GrumPHP\Task\Ecs;
use GrumPHP\Task\TaskInterface;
use GrumPHP\Test\Task\AbstractExternalTaskTestCase;

class EcsTest extends AbstractExternalTaskTestCase
{
    protected function provideTask(): TaskInterface
    {
        return new Ecs(
            $this->processBuilder->reveal(),
            $this->formatter->reveal()
        );
    }

    public function provideConfigurableOptions(): iterable
    {
        yield 'defaults' => [
            [],
            [
                'whitelist_patterns' => [],
                'clear-cache' => false,
                'no-progress-bar' => true,
                'config' => null,
                'level' => null,
                'triggered_by' => ['php'],
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
                $this->mockProcessBuilder('ecs', $process = $this->mockProcess(1));
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
                $this->mockProcessBuilder('ecs', $this->mockProcess(0));
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
    }

    public function provideExternalTaskRuns(): iterable
    {
        yield 'defaults' => [
            [],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'ecs',
            [
                'check',
                '--no-progress-bar',
                '--ansi',
                '--no-interaction',
            ]
        ];

        yield 'whitelist' => [
            [
                'whitelist_patterns' => ['src/', 'test/'],
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'ecs',
            [
                'check',
                'src/',
                'test/',
                '--no-progress-bar',
                '--ansi',
                '--no-interaction',
            ]
        ];
        yield 'clear-cache' => [
            [
                'clear-cache' => true,
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'ecs',
            [
                'check',
                '--clear-cache',
                '--no-progress-bar',
                '--ansi',
                '--no-interaction',
            ]
        ];
        yield 'progress-bar' => [
            [
                'no-progress-bar' => false,
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'ecs',
            [
                'check',
                '--ansi',
                '--no-interaction',
            ]
        ];
        yield 'config' => [
            [
                'config' => 'configfile',
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'ecs',
            [
                'check',
                '--config=configfile',
                '--no-progress-bar',
                '--ansi',
                '--no-interaction',
            ]
        ];
        yield 'level' => [
            [
                'level' => 'PSR-2',
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'ecs',
            [
                'check',
                '--level=PSR-2',
                '--no-progress-bar',
                '--ansi',
                '--no-interaction',
            ]
        ];
    }
}
