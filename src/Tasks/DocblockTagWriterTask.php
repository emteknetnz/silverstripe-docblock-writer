<?php

namespace SilverStripe\DocblockWriter\Tasks;

use Exception;
use ReflectionClass;
use PhpParser\Error;
use PhpParser\Lexer;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Config_ForClass;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use Symfony\Component\Process\Process;

/**
 * Adds class level docblock @method tags to DataObjects and Extensions for ORM private static proerties
 * `has_one`, `one_many` and `many_many`.
 *
 * Usage: vendor/bin/sake dev/tasks/dev/tasks/SilverStripe-DocblockWriter-Tasks-DocblockTagWriterTask <path>
 */
class DocblockTagWriterTask extends BuildTask
{
    public function run($request)
    {
        if (!Director::isDev()) {
            echo "This task can only be run in dev mode\n";
            die;
        }
        // tractorcow, colymba
        $vendors = ['silverstripe', 'dnadesign', 'symbiote', 'cwp', 'tractorcow', 'colymba', 'bringyourownideas'];
        foreach ($vendors as $vendor) {
            if (!file_exists("/var/www/vendor/$vendor")) {
                continue;
            }
            foreach (scandir("/var/www/vendor/$vendor") as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                // $pathFilter = $this->getPathFilter($request);
                $pathFilter = "/var/www/vendor/$vendor/$file";
                foreach ($this->getProcessableFiles($pathFilter) as $file) {
                    $path = $file['path'];
                    $className = $file['className'];
                    $contents = file_get_contents($path);
                    $newDocblock = $this->createNewDocblock($className, $contents, $path);
                    $newContents = $this->addNewDocblockToContents($newDocblock, $contents, $path);
                    if ($newContents === $contents) {
                        continue;
                    }
                    file_put_contents($path, $newContents);
                    echo "Wrote to $path\n";
                }
            }
        }
        // git
    }

    private function addNewDocblockToContents(
        string $newDocblock,
        string $contents,
        string $path
    ): string {
        if (empty($newDocblock)) {
            return $contents;
        }
        $currentDocblock = $this->getCurrentDocblock($contents, $path);
        if ($currentDocblock) {
            // Replace old docblock if exists
            $contents = str_replace($currentDocblock, $newDocblock, $contents);
        } else {
            // Add in new docblock if one does not yet exist
            $class = $this->getClass($contents, $path);
            $pos = $class->getStartFilePos();
            $contents = substr($contents, 0, $pos) . $newDocblock . "\n" . substr($contents, $pos);
        }
        return $contents;
    }

    private function cleanNewDocblockLines(array $lines): array
    {
        // Sort $lines - move any @deprecated to the bottom
        usort($lines, function ($a, $b) {
            $tmpA = str_replace(' * ', '', $a);
            $tagA = explode(' ', $tmpA)[0];
            return $tagA == '@deprecated' ? 1 : 0;
        });
        // Remove any empty lines
        $lines = array_filter($lines);
        // If the last line has a @deprecated tag, then ensure there's an empty docblock line above it
        if (str_contains(end($lines), '@deprecated')) {
            $line = array_pop($lines);
            $lines[] = ' *';
            $lines[] = $line;
        }
        // Reset array index
        $lines = array_values($lines);
        // Ensure the most consecutive empty docblock lines is one
        $tmp = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if ($line === ' *' && isset($lines[$i + 1]) && $lines[$i + 1] === ' *') {
                continue;
            }
            $tmp[] = $line;
        }
        $lines = $tmp;
        // Remove the first line if it's an empty docblock line
        if (reset($lines) === ' *') {
            array_shift($lines);
        }
        // Add PHPCS exclusions if line length is greater that 120 (PSR-2 rule)
        $lineCount = count($lines);
        for ($i = 0; $i < $lineCount; $i++) {
            $line = $lines[$i];
            if (strlen($line) <= 120) {
                continue;
            }
            // Add extra lines to temporarily disable phpcs
            array_splice($lines, $i + 1, 0, [' * @codingStandardsIgnoreEnd']);
            array_splice($lines, $i, 0, [' * @codingStandardsIgnoreStart']);
            $lineCount += 2;
            // Increment $i to prevent infinite loop
            $i++;
        }
        return $lines;
    }

    private function cleanRelationClass(string $relationClass): string
    {
        // Remove any suffixed relation identifier
        $relationClass = preg_replace('#\.[a-zA-Z0-9]+$#', '', $relationClass);
        // Use short class name
        $classInfo = new ClassInfo();
        $relationClass = $classInfo->shortName($relationClass);
        return $relationClass;
    }

    private function createNewDocblock(string $className, string $contents, string $path): string
    {
        $currentDocblock = $this->getCurrentDocblock($contents, $path);
        $newDocblockLines = $this->getNewDocblockLines($currentDocblock);
        $newDocblockMethods = $this->getNewDocblockMethods($className, $contents);
        $newDocblockLines = array_merge($newDocblockLines, $newDocblockMethods);
        $newDocblockLines = $this->cleanNewDocblockLines($newDocblockLines);
        if (empty($newDocblockLines)) {
            return '';
        }
        $newDocblock = "/**\n" . implode("\n", $newDocblockLines) . "\n */";
        return $newDocblock;
    }

    private function getAst(string $contents): array
    {
        $lexer = new Lexer([
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startFilePos',
                'endFilePos'
            ]
        ]);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer);
        try {
            $contents = str_replace('declare(strict_types=1);', '', $contents);
            $ast = $parser->parse($contents);
        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            die;
        }
        return $ast;
    }

    private function getClass(string $contents, string $path): Class_
    {
        $ast = $this->getAst($contents);
        $classes = $this->getClasses($ast);
        if (count($classes) !== 1) {
            throw new Exception("Expected 1 class in $path, got " . count($classes));
        }
        $class = $classes[0];
        return $class;
    }

    /**
     * @return Class_[]
     */
    private function getClasses(array $ast): array
    {
        $ret = [];
        $a = ($ast[0] ?? null) instanceof Namespace_ ? $ast[0]->stmts : $ast;
        $ret = array_merge($ret, array_filter($a, fn($v) => $v instanceof Class_));
        // SapphireTest and other file with dual classes
        $i = array_filter($a, fn($v) => $v instanceof If_);
        foreach ($i as $if) {
            foreach ($if->stmts ?? [] as $v) {
                if ($v instanceof Class_) {
                    $ret[] = $v;
                }
            }
        }
        return $ret;
    }

    private function getCurrentDocblock(string $contents, string $path): string
    {
        $class = $this->getClass($contents, $path);
        $comment = $class->getComments()[0] ?? null;
        $currentDocblock = $comment ? $comment->getText() : '';
        return $currentDocblock;
    }

    private function getImportedClassNameMap(string $contents): array
    {
        $ret = [];
        $classInfo = new ClassInfo();
        preg_match_all("#^use ([^ ]+);$#m", $contents, $matches);
        for ($i = 0; $i < count($matches[0]); $i++) {
            $fqcn = $matches[1][$i];
            $ret[$fqcn] = $classInfo->shortName($fqcn);
        }
        return $ret;
    }

    private function getNewDocblockLines(string $currentDocblock): array
    {
        $tmp = $currentDocblock;
        $tmp = str_replace(["/**\n", " */"], '', trim($tmp));
        // Remove existing @method tags and disable phpcs lines from docblock
        $tmp = preg_replace("# \* @method.*\n#", '', $tmp);
        $tmp = str_replace(' * @codingStandardsIgnoreStart', '', $tmp);
        $tmp = str_replace(' * @codingStandardsIgnoreEnd', '', $tmp);
        // Compile $lines
        $lines = explode("\n", $tmp);
        if (count($lines) === 1 && empty($lines[0])) {
            $lines = [];
        }
        return $lines;
    }

    private function getNewDocblockMethods(string $className, string $contents): array
    {
        $methods = [];
        // fqcn => shortname
        $importedClassNameMap = $this->getImportedClassNameMap($contents);
        // Read relation config and add it to $methods array
        // using reflection rather than instantiating dataobject
        // ReflectionProperty
        $hasOne = $this->getProperty($className, 'has_one');
        $belongsTo = $this->getProperty($className, 'belongs_to');
        $hasMany = $this->getProperty($className, 'has_many');
        $manyMany = $this->getProperty($className, 'many_many');
        $belongsManyMany = $this->getProperty($className, 'belongs_many_many');
        foreach ($hasOne as $relationName => $relationClass) {
            if (is_array($relationClass)) {
                throw new Exception('has_one fancy relation not supported yet');
            }
            $relationClass = $this->cleanRelationClass($relationClass);
            $methods[] = " * @method $relationClass $relationName()";
        }
        foreach ($belongsTo as $relationName => $relationClass) {
            if (is_array($relationClass)) {
                throw new Exception('unknown fancy belongs_to relation encountered');
            }
            $relationClass = $this->cleanRelationClass($relationClass);
            $methods[] = " * @method $relationClass $relationName()";
        }
        foreach ($hasMany as $relationName => $relationClass) {
            if (is_array($relationClass)) {
                throw new Exception('unknown fancy has_many relation encountered');
            }
            $relationClass = $this->cleanRelationClass($relationClass);
            $relationType = 'SilverStripe\ORM\HasManyList';
            $relationType = $importedClassNameMap[$relationType] ?? $relationType;
            $methods[] = " * @method $relationType<$relationClass> $relationName()";
        }
        foreach ($manyMany as $relationName => $relationClass) {
            if (is_array($relationClass)) {
                if (!array_key_exists('through', $relationClass)) {
                    throw new Exception('unknown non "through" many_many fancy relation encountered');
                }
                /** @var Config_ForClass $throughConfig */
                $throughConfig = $relationClass['through']::config();
                $throughHasOne = $throughConfig->get('has_one', Config::UNINHERITED);
                $relationClass = $throughHasOne[$relationClass['to']];
            }
            $relationClass = $this->cleanRelationClass($relationClass);
            $relationType = 'SilverStripe\ORM\ManyManyList';
            $relationType = $importedClassNameMap[$relationType] ?? $relationType;
            $methods[] = " * @method $relationType<$relationClass> $relationName()";
        }
        foreach ($belongsManyMany as $relationName => $relationClass) {
            if (is_array($relationClass)) {
                throw new Exception('unknown fancy has_many relation encountered');
            }
            $relationClass = $this->cleanRelationClass($relationClass);
            $relationType = 'SilverStripe\ORM\ManyManyList';
            $relationType = $importedClassNameMap[$relationType] ?? $relationType;
            $methods[] = " * @method $relationType<$relationClass> $relationName()";
        }
        // Sort @methods
        usort($methods, function ($a, $b) {
            $tmpA = str_replace(' * ', '', $a);
            $tmpB = str_replace(' * ', '', $b);
            $relationNameA = explode(' ', $tmpA)[2];
            $relationNameB = explode(' ', $tmpB)[2];
            return $relationNameA <=> $relationNameB;
        });
        return $methods;
    }

    private function getPathFilter(HTTPRequest $request): string
    {
        $args = $request->getVars()['args'] ?? [];
        if (empty($args)) {
            $task = 'dev/tasks/SilverStripe-DocblockWriter-Tasks-DocblockTagWriterTask';
            echo "Usage: vendor/bin/sake $task <path>\n";
            die;
        }
        $path = $args[0];
        // Absolute path
        if (strpos($path, BASE_PATH) === 0) {
            return $path;
        }
        // Relative path
        return Controller::join_links(BASE_PATH, $path);
    }

    private function getProcessableFiles(string $pathFilter): array
    {
        $files = [];
        $classInfo = new ClassInfo();
        $classNamees = array_merge(
            $classInfo->getValidSubClasses(DataObject::class),
            $classInfo->getValidSubClasses(Extension::class),
        );
        foreach ($classNamees as $className) {
            $path = (new ReflectionClass($className))->getFileName();
            if (strpos($path, $pathFilter) !== 0) {
                continue;
            }
            $files[] = [
                'path' => $path,
                'className' => $className,
            ];
        }
        return $files;
    }

    private function getProperty(string $className, string $property): array
    {
        $properties = (new ReflectionClass($className))->getProperties();
        /** @var ReflectionProperty $prop */
        $prop = array_values(array_filter($properties, function ($p) use ($property) {
            /** @var ReflectionProperty $p */
            return $p->getName() === $property;
        }))[0] ?? null;
        if (!$prop) {
            return [];
        }
        $prop->setAccessible(true);
        // Use new rather than ::create() because Extension subclasses are not injectable
        return $prop->getValue(new $className());
    }
}
