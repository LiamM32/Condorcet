<?php
declare(strict_types=1);

namespace CondorcetPHP\Condorcet\Dev\CondorcetDocumentationGenerator;

use CondorcetPHP\Condorcet\Dev\CondorcetDocumentationGenerator\CondorcetDocAttributes\{Description, Example, FunctionParameter, FunctionReturn, PublicAPI, Related};
use HaydenPierce\ClassFinder\ClassFinder;

class Generate
{

    // Static - Translators

    public static function makeFilename (\ReflectionMethod $method) : string
    {
        return  self::getModifiersName($method).
                " ".
                str_replace("\\", "_", self::simpleClass($method->class))."--".$method->name.
                ".md";
    }

    public static function simpleClass (string $fullClassName) : string
    {
        return str_replace('CondorcetPHP\\Condorcet\\','',$fullClassName);
    }

    public static function speakBool ($c) : string
    {
    if ($c === true || $c === 'true') : return 'true'; endif;
    if ($c === false || $c === 'false') : return 'false'; endif;
    if ($c === null || $c === 'null') : return 'null'; endif;
    if (is_array($c)) : return '['.implode(',', $c).']'; endif;

    return (string) $c;
    }

    public static function getTypeAsString (?\ReflectionType $rf_rt) : ?string
    {
        if ( $rf_rt !== null ) :
            return (string) $rf_rt;
        endif;

        return $rf_rt;
    }

    public static function getModifiersName (\ReflectionMethod $method) : string
    {
        return implode(' ', \Reflection::getModifierNames($method->getModifiers()));
    }

    // Static - Builder

    public static function cleverRelated (string $name) : string
    {
    $infos = explode('::', $name);
    $infos[0] = str_replace('static ', '', $infos[0]);

    $url = '../'.$infos[0].' Class/public '.str_replace('::', '--', $name) . '.md' ;
    $url = str_replace(' ', '%20', $url);

    return "[".$name."](".$url.")";
    }

    public static function computeRepresentationAsForIndex (\ReflectionMethod $method) : string
    {
        return  self::getModifiersName($method).
                " ".
                self::simpleClass($method->class).
                (($method->isStatic())?"::":'->').
                $method->name.
                " (".( ($method->getNumberOfParameters() > 0) ? '...' : '').")";
    }

    public static function computeRepresentationAsPHP (\ReflectionMethod $method) : string
    {
        $option = false;
        $str = '(';
        $i = 0;


        if ($method->getNumberOfParameters() > 0) :
            foreach ($method->getParameters() as $value) :
                $str .= " ";
                $str .= ($value->isOptional() && !$option) ? "[" : "";
                $str .= ($i > 0) ? ", " : "";
                $str .= self::getTypeAsString($value->getType());
                $str .= " ";
                $str .= $value->getName();
                $str .= ($value->isDefaultValueAvailable()) ? " = ".self::speakBool($value->getDefaultValue()) : "";

                ($value->isOptional() && !$option) ? $option = true : null;
                $i++;
            endforeach;
        endif;

        if ($option) :
            $str .= "]";
        endif;

        $str .= " )";

        return  "```php\n".
                self::getModifiersName($method).' '.self::simpleClass($method->class).(($method->isStatic())?"::":'->').$method->name." ".$str. ( (self::getTypeAsString($method->getReturnType()) !== null) ? ": ".self::getTypeAsString($method->getReturnType()) : "" ).
                "\n```";
    }


    // Script
    public function __construct(string $path)
    {
        $start_time = microtime(true);

        $pathDirectory = $path.\DIRECTORY_SEPARATOR;

        //
        $index  = [];
        $FullClassList = ClassFinder::getClassesInNamespace('CondorcetPHP\Condorcet\\', ClassFinder::RECURSIVE_MODE);
        $FullClassList = \array_filter($FullClassList, function (string $value) { return (strpos($value, 'Condorcet\Test') === FALSE) && (strpos($value, 'Condorcet\Dev') === FALSE); });

        $inDoc = 0;
        $non_inDoc = 0;

        foreach ($FullClassList as $FullClass) :
            $methods = (new \ReflectionClass($FullClass))->getMethods(\ReflectionMethod::IS_PUBLIC);
            $shortClass = self::simpleClass($FullClass);

            foreach ($methods as $oneMethod) :
                if ( !isset($index[$shortClass][$oneMethod->name]) && !$oneMethod->isInternal() ) :
                    $non_inDoc++;

                    if (!empty($oneMethod->getAttributes(PublicAPI::class)) && $oneMethod->getNumberOfParameters() > 0) :
                        foreach ($oneMethod->getParameters() as $oneParameter) :
                            if (empty($oneParameter->getAttributes(FunctionParameter::class))) :
                                var_dump('Method Has Public API attribute but parameter $'.$oneParameter->getName().' is undocumented '.$oneMethod->getDeclaringClass()->getName().'->'.$oneMethod->getName());
                            endif;
                        endforeach;
                    endif;

                    $index[$shortClass][$oneMethod->name]['ReflectionMethod'] = $oneMethod;
                    $index[$shortClass][$oneMethod->name]['class'][] = $shortClass;

                else :
                    $inDoc++;

                    if (empty($oneMethod->getAttributes(PublicAPI::class)) && $oneMethod->getDeclaringClass()->getNamespaceName() !== "") :
                        var_dump('Method not has API attribute: '.$oneMethod->getDeclaringClass()->getName().'->'.$oneMethod->getName());
                    endif;

                    if (empty($oneMethod->getAttributes(Description::class)) && $oneMethod->getDeclaringClass()->getNamespaceName() !== "") :
                        var_dump('Description Attribute is empty: '.$oneMethod->getDeclaringClass()->getName().'->'.$oneMethod->getName());
                    endif;
                endif;

                // Write Markdown
                if (!empty($apiAttribute = $oneMethod->getAttributes(PublicAPI::class)) && (empty($apiAttribute[0]->getArguments()) || in_array(self::simpleClass($oneMethod->class),$apiAttribute[0]->getArguments(), true)) ) :
                    $path = $pathDirectory . str_replace("\\", "_", self::simpleClass($oneMethod->class)) . " Class/";

                    if (!is_dir($path)) :
                        mkdir($path);
                    endif;

                    file_put_contents($path.self::makeFilename($oneMethod), $this->createMarkdownContent($oneMethod, $index[$shortClass][$oneMethod->name] ?? null));
                endif;
            endforeach;
        endforeach;

        $full_methods_list = [];

        $total_methods = 0;
        $total_nonInternal_methods = 0;

        foreach ($FullClassList as $FullClass) :
            $methods = (new \ReflectionClass($FullClass))->getMethods();
            $shortClass = str_replace('CondorcetPHP\Condorcet\\', '', $FullClass);

            foreach ($methods as $oneMethod) :

                $full_methods_list[$shortClass][] = [   'FullClass' => $FullClass,
                                                        'shortClass' => $shortClass,
                                                        'name' => $oneMethod->name,
                                                        'static' => $oneMethod->isStatic(),
                                                        'visibility_public' => $oneMethod->isPublic(),
                                                        'visibility_protected' => $oneMethod->isProtected(),
                                                        'visibility_private' => $oneMethod->isPrivate(),
                                                        'ReflectionMethod' => $oneMethod,
                                                        'ReflectionClass' => $oneMethod->getDeclaringClass(),
                                                    ];

                $total_methods++;

                if (!$oneMethod->isInternal()) :
                    $total_nonInternal_methods++;
                endif;

            endforeach;
        endforeach;


        print "Public methods in doc: ".$inDoc." / ".($inDoc + $non_inDoc)." | Total non-internal methods count: ".$total_nonInternal_methods." | Number of Class: ".count($FullClassList)." | Number of Methods including internals: ".$total_methods."\n";

        // Add Index
        uksort($index,'strnatcmp');

        $file_content =  "> **[Presentation](../README.md) | [Manual](https://github.com/julien-boudry/Condorcet/wiki) | Methods References | [Tests](../Tests)**\n\n".

                        "# Public API Index _(Not yet exhaustive, not yet....)*_\n".
                        "_Not including technical public methods which ones are used for very advanced use between components (typically if you extend Coondorcet or build your own modules)._\n\n".

                        "_*: I try to update and complete the documentation. See also [the manual](https://github.com/julien-boudry/Condorcet/wiki), [the tests](../Tests) also produce many examples. And create issues for questions or fixing documentation!_\n\n";


        $file_content .= $this->makeIndex($index);

        $file_content .= "\n\n\n";

        uksort($full_methods_list,'strnatcmp');
        $file_content .=    "# Full Class & Methods References\n".
                            "_Including above methods from public API_\n\n";

        $file_content .= $this->makeProfundis($full_methods_list);


        // Write file
        file_put_contents($pathDirectory."README.md", $file_content);


        echo 'YAH ! <br>' . (microtime(true) - $start_time) .'s';

    }


    // Build Methods

    protected function createMarkdownContent (\ReflectionMethod $method, array $entry) : string
    {
        // Header
        $md =   "## ".self::getModifiersName($method)." ". self::simpleClass($method->class)."::".$method->name."\n\n".
                "### Description    \n\n".
                self::computeRepresentationAsPHP($method)."\n\n".
                $method->getAttributes(Description::class)[0]->getArguments()[0]."\n    ";

        // Input
        if ($method->getNumberOfParameters() > 0) :
            foreach ($method->getParameters() as $key => $value ) :

                if (!empty($attributes = $value->getAttributes(FunctionParameter::class))) :
                    $pt = $attributes[0]->newInstance()->text;
                elseif (isset($entry['input'][$value->getName()]['text'])) :
                     $pt = $entry['input'][$value->getName()]['text'];
                else :
                    $pt = "";
                endif;

                $md .=  "\n\n".
                        "##### **".$value->getName().":** *".self::getTypeAsString($value->getType())."*   \n".
                        $pt."    \n";
            endforeach;
        endif;


        // Return Value

        if (!empty($method->getAttributes(FunctionReturn::class))) :
            $md .= "\n\n".
                    "### Return value:   \n\n".
                    "*(".self::getTypeAsString($method->getReturnType()).")* ".$method->getAttributes(FunctionReturn::class)[0]->getArguments()[0]."\n\n";
        endif;

        // Related methods

        if(!empty($method->getAttributes(Related::class))) :

            $md .=  "\n".
                    "---------------------------------------\n\n".
                    "### Related method(s)      \n\n";

            foreach ($method->getAttributes(Related::class) as $RelatedAttribute) :
                foreach ($RelatedAttribute->newInstance()->relatedList as $value) :

                    if ($value === self::simpleClass($method->class).'::'.$method->name) :
                        continue;
                    endif;

                    $md .= "* ".self::cleverRelated($value)."    \n";
                endforeach;
            endforeach;

        endif;

        if(!empty($method->getAttributes(Example::class))) :

            $md .=  "\n".
                    "---------------------------------------\n\n".
                    "### Examples and explanation\n\n";

            foreach ($method->getAttributes(Example::class) as $ExampleAttribute) :
                $ExampleAttribute = $ExampleAttribute->newInstance();

                $md .= "* **[".$ExampleAttribute->name."](".$ExampleAttribute->link.")**    \n";
            endforeach;

        endif;

        return $md;
    }

    protected function makeIndex (array $index) : string
    {
        $file_content = '';

        $testPublicAttribute = function (\ReflectionMethod $reflectionMethod): bool {
            return !(empty($apiAttribute = $reflectionMethod->getAttributes(PublicAPI::class)) || (!empty($apiAttribute[0]->getArguments()) && !in_array(self::simpleClass($reflectionMethod->class),$apiAttribute[0]->getArguments(), true)));
        };

        foreach ($index as $class => $methods) :

            usort($methods, function (array $a, array $b) {
                if ($a['ReflectionMethod']->isStatic() === $b['ReflectionMethod']->isStatic()) :
                    return strnatcmp($a['ReflectionMethod']->name,$b['ReflectionMethod']->name);
                elseif ($a['ReflectionMethod']->isStatic() && !$b['ReflectionMethod']->isStatic()) :
                    return -1;
                else :
                    return 1;
                endif;
            });

            $ReflectionClass = new \ReflectionClass('CondorcetPHP\Condorcet\\'.$class);
            $classWillBePublic = false;

            if ($ReflectionClass->getAttributes(PublicAPI::class)) :
                $classWillBePublic = true;
            else :
                foreach ($methods as $oneMethod) :
                    if ($testPublicAttribute($oneMethod['ReflectionMethod'])) :
                        $classWillBePublic = true;
                        break;
                    endif;
                endforeach;
            endif;

            if ($classWillBePublic) :
                $isEnum = \enum_exists(($enumCases = $ReflectionClass)->name);

                $file_content .= "\n";
                $file_content .= '### CondorcetPHP\Condorcet\\'.$class." ".((!$isEnum) ? "Class" : "Enum")."  \n\n";

                if  ($isEnum) :
                    $file_content .= $this->makeEnumeCases(new \ReflectionEnum($enumCases->name), false);
                    $file_content .= "\n";
                endif;
            endif;


            foreach ($methods as $oneMethod) :
                if (!$testPublicAttribute($oneMethod['ReflectionMethod']) || !$oneMethod['ReflectionMethod']->isUserDefined()) :
                    continue;
                else :
                    $url = str_replace("\\","_",self::simpleClass($oneMethod['ReflectionMethod']->class)).' Class/'.self::getModifiersName($oneMethod['ReflectionMethod'])." ". str_replace("\\","_",self::simpleClass($oneMethod['ReflectionMethod']->class)."--". $oneMethod['ReflectionMethod']->name) . '.md' ;
                    $url = str_replace(' ', '%20', $url);

                    $file_content .= "* [".self::computeRepresentationAsForIndex($oneMethod['ReflectionMethod'])."](".$url.")";

                    if (isset($oneMethod['ReflectionMethod']) && $oneMethod['ReflectionMethod']->hasReturnType()) :
                        $file_content .= ': '.self::getTypeAsString($oneMethod['ReflectionMethod']->getReturnType());
                    endif;


                    $file_content .= "  \n";
                endif;
            endforeach;

        endforeach;

        return $file_content;
    }

    protected function makeEnumeCases (\ReflectionEnum $enumReflection, bool $shortName = false): string
    {
        $cases = $enumReflection->getCases();

        $r = '';

        foreach ($cases as $oneCase) :
            $name = ($shortName) ? $enumReflection->getShortName() : self::simpleClass($enumReflection->getName());
            $r .= '* case '.$name.'::'.$oneCase->getName()."\n";
        endforeach;

        return $r;
    }

    protected function makeProfundis (array $index) : string
    {
        $file_content = '';

        foreach ($index as $class => &$methods) :

            usort($methods, function (array $a, array $b) {
                if ($a['static'] === $b['static']) :
                    if ( $a['visibility_public'] && !$b['visibility_public'] )  :
                        return -1;
                    elseif ( !$a['visibility_public'] && $b['visibility_public'] ) :
                        return 1;
                    else :
                        if ($a['visibility_protected'] && !$b['visibility_protected']) :
                            return -1;
                        elseif (!$a['visibility_protected'] && $b['visibility_protected']) :
                            return 1;
                        else :
                            return strnatcmp($a['name'],$b['name']);
                        endif;
                    endif;
                elseif ($a['static'] && !$b['static']) :
                    return -1;
                else :
                    return 1;
                endif;
            });

            $ReflectionClass = new \ReflectionClass('CondorcetPHP\Condorcet\\'.$class);

            $file_content .= "\n";
            $file_content .= '#### ';
            $file_content .= ($ReflectionClass->isAbstract()) ? 'Abstract ' : '';
            $file_content .= 'CondorcetPHP\Condorcet\\'.$class.' ';

            $file_content .= ($p = $ReflectionClass->getParentClass()) ? 'extends '.$p->name.' ' : '';

            $interfaces = implode(', ', $ReflectionClass->getInterfaceNames());
            $file_content .= (!empty($interfaces)) ? 'implements '.$interfaces : '';

            $file_content .= "  \n";
            $file_content .= "```php\n";

            $isEnum = \enum_exists(($enumCases = $ReflectionClass)->name);

            if  ($isEnum) :
                $file_content .= $this->makeEnumeCases(new \ReflectionEnum($enumCases->name), true);
                $file_content .= "\n";
            endif;


            foreach ($methods as $oneMethod) :
                if ($oneMethod['ReflectionMethod']->isUserDefined()) :
                    $parameters = $oneMethod['ReflectionMethod']->getParameters();
                    $parameters_string = '';

                    $i = 0;
                    foreach ($parameters as $oneP) :
                        $parameters_string .= (++$i > 1) ? ', ' : '';

                        if ($oneP->getType() !== null) :
                            $parameters_string .= self::getTypeAsString($oneP->getType()) . ' ';
                        endif;
                        $parameters_string .= '$'.$oneP->name;

                        if ($oneP->isDefaultValueAvailable()) :
                            $parameters_string .= ' = '.self::speakBool($oneP->getDefaultValue());
                        endif;
                    endforeach;

                    $representation = ($oneMethod['visibility_public']) ? 'public ' : '';
                    $representation .= ($oneMethod['visibility_protected']) ? 'protected ' : '';
                    $representation .= ($oneMethod['visibility_private']) ? 'private ' : '';

                    $representation .=  ($oneMethod['static']) ? 'static ' : '';
                    $representation .=  $oneMethod['name'] . ' ('.$parameters_string.')';

                    if ($oneMethod['ReflectionMethod']->hasReturnType()) :
                        $representation .= ': '.self::getTypeAsString($oneMethod['ReflectionMethod']->getReturnType());
                    endif;

                    $file_content .= "* ".$representation."  \n";
                endif;
            endforeach;

                $file_content .= "```\n";

        endforeach;

        return $file_content;
    }

}
