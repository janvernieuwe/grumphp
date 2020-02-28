<?php

declare(strict_types=1);

namespace GrumPHPTest\Uni\Task;

use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use GrumPHP\Task\PhpunitBridge;
use GrumPHP\Task\TaskInterface;
use GrumPHP\Test\Task\AbstractExternalTaskTestCase;

class PhpunitBridgeTest extends AbstractExternalTaskTestCase
{
    protected function provideTask(): TaskInterface
    {
        return new PhpunitBridge(
            $this->processBuilder->reveal(),
            $this->formatter->reveal()
        );
    }

    public function provideConfigurableOptions(): iterable
    {
        yield 'defaults' => [
            [],
            [
                'config_file' => null,
                'testsuite' => null,
                'group' => [],
                'always_execute' => false,
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
                $this->mockProcessBuilder('simple-phpunit', $process = $this->mockProcess(1));
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
                $this->mockProcessBuilder('simple-phpunit', $this->mockProcess(0));
            }
        ];
        yield 'no-files-but-always-execute' => [
            [
                'always_execute' => true,
            ],
            $this->mockContext(RunContext::class, []),
            function () {
                $this->mockProcessBuilder('simple-phpunit', $this->mockProcess(0));
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
    }

    public function provideExternalTaskRuns(): iterable
    {
        yield 'defaults' => [
            [],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'simple-phpunit',
            []
        ];
        yield 'config-file' => [
            [
                'config_file' => 'config.xml',
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'simple-phpunit',
            [
                '--configuration=config.xml',
            ]
        ];
        yield 'testsuite' => [
            [
                'testsuite' => 'suite',
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'simple-phpunit',
            [
                '--testsuite=suite',
            ]
        ];
        yield 'group' => [
            [
                'group' => ['group1','group2',],
            ],
            $this->mockContext(RunContext::class, ['hello.php', 'hello2.php']),
            'simple-phpunit',
            [
                '--group=group1,group2',
            ]
        ];
    }
}
