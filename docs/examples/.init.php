<?php

use Oliverde8\Component\PhpEtl\Builder\Factories\ChainSplitFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Extract\CsvExtractFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Grouping\SimpleGroupingFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Loader\CsvFileWriterFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Loader\JsonFileWriterFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\FilterDataFactory;
use Oliverde8\Component\PhpEtl\Builder\Factories\Transformer\RuleTransformFactory;
use Oliverde8\Component\PhpEtl\ChainBuilder;
use Oliverde8\Component\PhpEtl\ChainOperation\ChainSplitOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Extract\CsvExtractOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Grouping\SimpleGroupingOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Loader\FileWriterOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\FilterDataOperation;
use Oliverde8\Component\PhpEtl\ChainOperation\Transformer\RuleTransformOperation;
use Oliverde8\Component\PhpEtl\ChainProcessor;
use Oliverde8\Component\PhpEtl\ExecutionContextFactory;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . "/../../vendor/autoload.php";

function getChainProcessor($fileName): ChainProcessor
{
    $fileName = str_replace(".php", ".yml", $fileName);

    $ruleApplier = new \Oliverde8\Component\RuleEngine\RuleApplier(
        new \Psr\Log\NullLogger(),
        [
            new \Oliverde8\Component\RuleEngine\Rules\Get(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\Implode(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\StrToLower(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\StrToUpper(new \Psr\Log\NullLogger()),
            new \Oliverde8\Component\RuleEngine\Rules\ExpressionLanguage(new \Psr\Log\NullLogger()),
        ]
    );

    $builder = new ChainBuilder(new ExecutionContextFactory());
    $builder->registerFactory(new RuleTransformFactory('rule-engine-transformer', RuleTransformOperation::class, $ruleApplier));
    $builder->registerFactory(new FilterDataFactory('filter', FilterDataOperation::class, $ruleApplier));
    $builder->registerFactory(new SimpleGroupingFactory('simple-grouping', SimpleGroupingOperation::class));
    $builder->registerFactory(new ChainSplitFactory('split', ChainSplitOperation::class, $builder));
    $builder->registerFactory(new CsvFileWriterFactory('csv-write', FileWriterOperation::class));
    $builder->registerFactory(new JsonFileWriterFactory('json-write', FileWriterOperation::class));
    $builder->registerFactory(new CsvExtractFactory('csv-read', CsvExtractOperation::class));

    return $builder->buildChainProcessor(
        Yaml::parse(file_get_contents($fileName))['chain']
    );
}