<?php


namespace Anboo\RabbitmqBundle\Command;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class LoadMessagesEnumValueWithConstantCommand
 */
class LoadMessagesEnumValueWithConstantCommand extends Command
{
    protected static $defaultName = 'anboo:load:messages:enum:constant';

    /**
     * @var Finder
     */
    private $finder;
    /**
     * @var string
     */
    private $projectDir;
    private $translateStoreDirDefault;
    private $domain = 'enum';
    private $translator;
    /**
     * LoadReturnReasonCommand constructor.
     *
     * @param string|null      $name
     * @param Reader $annotationReader
     */
    public function __construct(string $name = null, TranslatorInterface $translator, $projectDir)
    {
        $this->translator = $translator;
        $this->finder = new Finder();
        $this->projectDir = $projectDir;
        $this->translateStoreDirDefault = $this->projectDir.'/src/Enum';
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Load translate message for ENUM code')
            ->addOption('domain', 'dm', InputOption::VALUE_OPTIONAL, 'Domain translation service, default: "enum"', $this->domain)
            ->addOption('enumDir', 'dr', InputOption::VALUE_OPTIONAL, 'Storage directory class Enum', [$this->translateStoreDirDefault])
            ->addOption('pathToSave', 'pathSave', InputOption::VALUE_OPTIONAL, 'Path to dir to save file', 'translations')
            ->addOption('appendToClassName', 'append', InputOption::VALUE_OPTIONAL, 'Append class name to key translations, result: '.
            "\nPaymentHandlerEnum:
                >SBERBANK: Сбербанк
                >VTB: ВТБ
                >PAYMENT_BRICK: \'Payment Brick\'
                >CASH: Наличные
                >\'NULL\': \'Не выбрано...\'", false)
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('##### START COMMAND...');
        $enumDir = $this->projectDir . $input->getOption('enumDir');
        $domain = $input->getOption('domain');
        $pathToSave = rtrim($this->projectDir,'/').'/'.$input->getOption('pathToSave');
        $appendTo = $input->getOption('appendToClassName');
        $data = $mapClasses = [];
        $classes = $this->extractClasses([$enumDir]);

        /** @var \ReflectionClass $class */
        foreach ($classes as $class) {
            try {
                $shortNameBaseClass = $mapClasses[$class->getShortName()] ?? $class->getShortName();
                $className = $class->getName();
                foreach ($class->getConstants() as $constant) {
                    if ($appendTo) {
                        $data[$shortNameBaseClass][$constant] = $this->translator->trans($className::choices()[$constant], [], 'enum');
                    } else {
                        $data[$constant] = $this->translator->trans($className::choices()[$constant], [], 'enum');
                    }
                }
            } catch (\RuntimeException $e) {
                continue;
            } catch (\Error $e) {
                continue;
            } catch (\Exception $e) {
                continue;
            }
        }
        $io = new SymfonyStyle($input, $output);
        $pathFile = rtrim($pathToSave, '/').'/'.$domain.'.ru.yml';
        echo $pathFile;

        if (true === file_exists($pathFile)) { // file exist, compare values
            $currentValuesYaml = Yaml::parseFile($pathFile);
            foreach ($currentValuesYaml as $k => $v) {
                if (array_key_exists($k, $data)) {
                    if ($currentValuesYaml[$k] === $data[$k]) {
                        unset($data[$k]);
                        continue;
                    }
                }
            }
            if ($data) {
                $yaml = Yaml::dump($data, 3);
                file_put_contents($pathFile, $yaml, FILE_APPEND);
            }
        } else { // new file
            $yaml = Yaml::dump($data, 3);
            file_put_contents($pathFile, $yaml);
        }
        $io->success('##### FINISHED...');
    }

    /**
     * @param string $format
     *
     * @return array|null
     * @throws \ReflectionException
     */
    public function extractClasses($enumDirs, $format = 'reflection'): ?array
    {
        $isInFinder = false;
        foreach ($enumDirs as $dir) {
            try {
                $this->finder->in($dir);
                $isInFinder = true;
            } catch (\Throwable $exception) {

            }
        }
        if (!$isInFinder) {
            return [];
        }
        if (!$this->finder->hasResults()) {
            return [];
        }
        $reflections = [];
        foreach ($this->finder as $file) {
            if (!is_file($file) || (stripos($file,'.php') === false)) {
                continue;
            }
            $filename = str_replace('.php', '', $file->getFilename());
            $classWithNamespace = $this->getFullNamespace(realpath($file->getRealPath())) . '\\' . $filename;
            $reflectionClass = new \ReflectionClass($classWithNamespace);
            if ($format === 'reflection') {
                $reflections[] = $reflectionClass;
            } else {
                $reflections[] = $reflectionClass->getShortName();
            }
        }

        return $reflections;
    }

    /**
     * @param string $phpFile
     *
     * @return mixed
     */
    private function getFullNamespace($phpFile)
    {
        $lines = preg_grep('/^namespace /', file($phpFile));
        $namespaceLine = array_shift($lines);

        return trim(str_replace(['namespace',';'], '', $namespaceLine));
    }
}