<?php

namespace LeMike\PhpSpec\WpExtension\CodeGenerator\Generator;

use LeMike\PhpSpec\WpExtension\Locator\WordPress\WpResource;
use PhpSpec\Console\IO;
use PhpSpec\CodeGenerator\TemplateRenderer;
use PhpSpec\CodeGenerator\Generator\GeneratorInterface;
use PhpSpec\Util\Filesystem;
use PhpSpec\Locator\ResourceInterface;

class SpecificationGenerator implements GeneratorInterface
{
    private $io;
    private $templates;
    private $filesystem;

    public function __construct(IO $io, TemplateRenderer $templates, Filesystem $filesystem = null)
    {
        $this->io         = $io;
        $this->templates  = $templates;
        $this->filesystem = $filesystem ?: new Filesystem;
    }

    public function supports(ResourceInterface $resource, $generation, array $data)
    {
        return 'specification' === $generation && $resource instanceof WpResource;
    }

    public function generate(ResourceInterface $resource, array $data = array())
    {
        $filepath = $resource->getSpecFilename();
        if ($this->filesystem->pathExists($filepath)) {
            $message = sprintf('File "%s" already exists. Overwrite?', basename($filepath));
            if (!$this->io->askConfirmation($message, false)) {
                return;
            }

            $this->io->writeln();
        }

        $path = dirname($filepath);
        if (!$this->filesystem->isDirectory($path)) {
            $this->filesystem->makeDirectory($path);
        }

	    $values = array(
		    '%filepath%'  => $filepath,
		    '%name%'      => $resource->getSpecName(),
		    '%namespace%' => $resource->getSpecNamespace(),
		    '%subject%'   => $resource->getSrcClassname()
	    );

        if (!$content = $this->templates->render('wp_class', $values)) {
            $content = $this->templates->renderString(
                file_get_contents(__DIR__ . '/templates/specification.template'), $values
            );
        }

        $this->filesystem->putFileContents($filepath, $content);
        $this->io->writeln(sprintf(
            "<info>Specification for class <value>%s</value> created in <value>'%s'</value>.</info>\n",
            $resource->getSrcClassname(), $filepath
        ));
    }

    public function getPriority()
    {
        return 42;
    }
}
