<?php


namespace Anboo\RabbitmqBundle\Command;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Swagger\Annotations as SWG;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Anboo\RabbitmqBundle\DBAL\Types\EnumInterface;

/**
 * Class LoadMessagesWithSwgPropertyCommand
 */
class LoadMessagesWithSwgPropertyCommand extends Command
{
    protected static $defaultName = 'webslon:load:messages:swg:props';

    /**
     * @var AnnotationReader
     */
    private $annotationReader;
    /**
     * @var Finder
     */
    private $finder;
    /**
     * @var string
     */
    private $projectDir;
    private $translateStoreDirDefault;
    private $domain = 'propertys';

    /**
     * LoadReturnReasonCommand constructor.
     *
     * @param string|null      $name
     * @param Reader $annotationReader
     */
    public function __construct(string $name = null, Reader $annotationReader, $projectDir)
    {
        $this->annotationReader = $annotationReader;
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
            ->setDescription('Load translate message with annotation @Swg\Property()')
            ->addOption('domain', 'dm', InputOption::VALUE_OPTIONAL, 'Domain translation service, default: "enum"', $this->domain)
            ->addOption('classStorageDir', 'dr', InputOption::VALUE_OPTIONAL, 'Stored directory classes for parse and create translation data', [$this->translateStoreDirDefault])
            ->addOption('pathToSave', 'pathSave', InputOption::VALUE_OPTIONAL, 'Path to dir to save file', $this->projectDir.'/translations')
            ->addOption('appendToClassName', 'append', InputOption::VALUE_OPTIONAL, 'Append class name to key translations, result: OrderStatus.PICKING_STARTED', false)
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

        $classStorageDir = $input->getOption('classStorageDir');
        $translateStoreDir = $input->getOption('pathToSave');
        $appendToClassName = $input->getOption('appendToClassName');
        $domain = $input->getOption('domain');

        $data =[];
        $mapClasses = [];
        $classes = $this->extractClasses([rtrim($this->projectDir, '/').'/'.$classStorageDir]);
        $pathFile = $translateStoreDir.DIRECTORY_SEPARATOR.$domain.'.en.yml';

        /** @var \ReflectionClass $class */
        foreach ($classes as $class) {
            try {
                $shortNameBaseClass = $mapClasses[$class->getShortName()] ?? $class->getShortName();
                $data[$shortNameBaseClass] = [];
                /** @var EnumInterface $className */
                foreach ($class->getProperties() as $property) {
                    if ($annotationProperty = $this->annotationReader->getPropertyAnnotation($property, SWG\Property::class)) {
                        $nameProperty = $mapClasses[$property->getName()] ?? $property->getName();
                        if ($annotationProperty->ref) {
                            if ($appendToClassName) {
                                $data[$shortNameBaseClass][$nameProperty] = $this->parseAnnotation($annotationProperty, true);
                            } else {
                                $data[$nameProperty] = $this->parseAnnotation($annotationProperty, false);
                            }
                        } else {
                            if ($appendToClassName) {
                                $data[$shortNameBaseClass][$nameProperty] = $this->clearTitleProp($annotationProperty->title ?? $annotationProperty->description);
                            } else {
                                $data[$nameProperty] = $this->clearTitleProp($annotationProperty->title ?? $annotationProperty->description);
                            }
                        }
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

        if (true === file_exists($pathFile)) { // file exist, compare values
            $currentValuesYaml = Yaml::parseFile($pathFile);
            foreach ($currentValuesYaml as $k => $v) {
                if (array_key_exists($k, $data)) {
                    if ($currentValuesYaml[$k] === $data[$k] && is_scalar($v)) {
                        unset($data[$k]);
                        continue;
                    } else {
                        if (is_array($v)) {
                            foreach ($v as $key => $vv) {
                                if ((isset($data[$k][$key]) && isset($currentValuesYaml[$k][$key])) && ($data[$k][$key] === $currentValuesYaml[$k][$key])) {
                                    unset($data[$k]);
                                    continue;
                                }
                            }
                        }
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

        $io->success('##### FINISHED COMMAND...');
    }


    private function parseAnnotation ($annotationProperty, $appendClassName)
    {
         $classChild = new \ReflectionClass($annotationProperty->ref->type);
         $shortNameChildClass = $mapClasses[$classChild->getShortName()] ?? $classChild->getShortName();
         $items = [];
         foreach ($classChild->getProperties() as $childProp) {
             $nameChildProperty = $mapClasses[$childProp->getName()] ?? $childProp->getName();
             if ($annotationChildProperty = $this->annotationReader->getPropertyAnnotation($childProp, SWG\Property::class)) {
                 $items[$nameChildProperty] = $this->clearTitleProp($annotationChildProperty->title ?? $annotationChildProperty->description);
             }
         }

         return $items;
    }
    /**
     * @param string $title
     *
     * @return mixed
     */
    private function clearTitleProp(?string $title): ?string
    {
        if (!$title) {
            return '';
        }
        $replace = [
            'В_ВЕРХНЕМ_РЕГИСТРЕ', '"', '\''
        ];
        if (($pos = mb_stripos($title, ',')) !== false) {
            $title = mb_substr($title, 0, $pos);
        }
        if (($pos = mb_stripos($title, '(')) !== false) {
            $title = mb_substr($title, 0, $pos);
        }

        return str_replace($replace, '', $title);
    }
    /**
     * @param string $format
     * @param array $classStorageDir
     *
     * @return array|null
     * @throws \ReflectionException
     */
    public function extractClasses(array $classStorageDir, $format = 'reflection'): ?array
    {
        $isInFinder = false;
        foreach ($classStorageDir as $dir) {
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