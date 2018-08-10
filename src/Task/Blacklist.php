<?php

namespace GrumPHP\Task;

use GrumPHP\Collection\FilesCollection;
use GrumPHP\Configuration\GrumPHP;
use GrumPHP\Formatter\ProcessFormatterInterface;
use GrumPHP\IO\IOInterface;
use GrumPHP\Process\ProcessBuilder;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\Finder\Finder;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Git Blacklist Task
 */
class Blacklist extends AbstractExternalTask
{
    /**
     * @var IOInterface
     */
    private $IO;

    /**
     * Blacklist constructor.
     *
     * @param GrumPHP                   $grumPHP
     * @param ProcessBuilder            $processBuilder
     * @param ProcessFormatterInterface $formatter
     * @param IOInterface               $IO
     */
    public function __construct(
        GrumPHP $grumPHP,
        ProcessBuilder $processBuilder,
        ProcessFormatterInterface $formatter,
        IOInterface $IO
    ) {
        $this->IO = $IO;
        parent::__construct($grumPHP, $processBuilder, $formatter);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'blacklist';
    }

    /**
     * @return OptionsResolver
     */
    public function getConfigurableOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'keywords'           => [],
            'whitelist_patterns' => [],
            'triggered_by'       => ['php'],
            'regexp_type'        => 'G',
            'paths'              => [],
        ]);

        $resolver->addAllowedTypes('keywords', ['array']);
        $resolver->addAllowedTypes('paths', ['array']);
        $resolver->addAllowedTypes('whitelist_patterns', ['array']);
        $resolver->addAllowedTypes('triggered_by', ['array']);
        $resolver->addAllowedTypes('regexp_type', ['string']);
        $resolver->setAllowedValues('regexp_type', ['G', 'E', 'P']);

        return $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function canRunInContext(ContextInterface $context)
    {
        return ($context instanceof GitPreCommitContext || $context instanceof RunContext);
    }

    /**
     * {@inheritdoc}
     */
    public function run(ContextInterface $context)
    {
        $config = $this->getConfiguration();
        $whitelistPatterns = $config['whitelist_patterns'];
        $extensions = $config['triggered_by'];

        $finder = new Finder();
        $files = $finder->files()->in($config['paths'])->exclude($whitelistPatterns)->name('*.php');
        $files = iterator_to_array($files);
        $files = new FilesCollection($files);

        $arguments = $this->processBuilder->createArgumentsForCommand('git');
        $arguments->add('grep');
        $arguments->add('--cached');
        $arguments->add('-n');
        $arguments->add('--break');
        $arguments->add('--heading');
        $arguments->addOptionalArgument('--color', $this->IO->isDecorated());
        $arguments->addOptionalArgument('-%s', $config['regexp_type']);
        $arguments->addArgumentArrayWithSeparatedValue('-e', $config['keywords']);
        $arguments->addFiles($files);

        $process = $this->processBuilder->buildProcess($arguments);
        $process->run();

        if ($process->isSuccessful()) {
            return TaskResult::createFailed($this, $context, sprintf(
                'You have blacklisted keywords in your commit:%s%s',
                PHP_EOL,
                $this->formatter->format($process)
            ));
        }

        return TaskResult::createPassed($this, $context);
    }
}
