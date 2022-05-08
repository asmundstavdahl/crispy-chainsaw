<?php

declare(strict_types=1);

namespace AsmundStavdahl\Php2Js;

use PhpParser\ParserFactory;
use PhpParser\Node;

class Php2Js
{
    public static function convert(string $filepath): string
    {
        $source = file_get_contents($filepath);

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $ast = $parser->parse($source);

        $jsCode = implode(
            "\n\n",
            array_map([self::class, 'nodeToJs'], $ast)
        );

        return $jsCode;
    }

    private static function nodeToJs(Node $node, int $indentation = 0): string
    {
        $method = "nodeToJs_{$node->getType()}";

        if (!method_exists(self::class, $method)) {
            echo "Not implemented: {$method}\n<br><br\n>";

            exit(print_r($node, true));
        }

        return self::{$method}($node);
    }

    private static function nodeToJs_Stmt_Expression(Node\Stmt\Expression $node): string
    {
        return self::nodeToJs($node->expr);
    }

    private static function nodeToJs_Expr_MethodCall(Node\Expr\MethodCall $node): string
    {
        return self::nodeToJs($node->var)
            . '.'
            . $node->name->name
            . '(' . self::argsToJs($node->args) . ')';
    }

    private static function nodeToJs_Arg(Node\Arg $node): string
    {
        return self::nodeToJs($node->value);
    }

    private static function nodeToJs_Scalar_String(Node\Scalar\String_ $node): string
    {
        switch ($node->getAttribute('kind')) {
            case Node\Scalar\String_::KIND_SINGLE_QUOTED:

            default:
                return "'" . str_replace("'", "\\'", $node->value) . "'";
                break;
        }
    }

    private static function nodeToJs_Expr_ArrowFunction(Node\Expr\ArrowFunction $node): string
    {
        return '(' . self::paramsToJs($node->params) . ') => ' . self::nodeToJs($node->expr);
    }

    private static function nodeToJs_Expr_Closure(Node\Expr\Closure $node): string
    {
        return 'function(' . self::paramsToJs($node->params) . "){\n"
            . self::stmtsToJs($node->stmts)
            . "\n}";
    }

    private static function nodeToJs_Param(Node\Param $node): string
    {
        return $node->var->name;
    }

    private static function nodeToJs_Expr_Variable(Node\Expr\Variable $node): string
    {
        return $node->name;
    }

    private static function nodeToJs_Stmt_Namespace(Node\Stmt\Namespace_ $node): string
    {
        return self::stmtsToJs($node->stmts);
    }

    private static function nodeToJs_Expr_FuncCall(Node\Expr\FuncCall $node): string
    {
        return $node->name . '(' . self::argsToJs($node->args) . ')';
    }

    private static function stmtsToJs(array $nodes): string
    {
        $stmtsNodeJs = array_map([self::class, 'nodeToJs'], $nodes);

        return implode("\n", $stmtsNodeJs);
    }

    private static function argsToJs(array $nodes): string
    {
        $argsNodeJs = array_map([self::class, 'nodeToJs'], $nodes);

        return implode(', ', $argsNodeJs);
    }

    private static function paramsToJs(array $nodes): string
    {
        return self::argsToJs($nodes);
    }

    private static function nodeToJs_Expr_ArrayDimFetch(Node\Expr\ArrayDimFetch $node): string
    {
        return self::nodeToJs($node->var) . '[' . self::nodeToJs($node->dim) . ']';
    }

    private static function nodeToJs_Expr_PropertyFetch(Node\Expr\PropertyFetch $node): string
    {
        return self::nodeToJs($node->var) . '.' . self::nodeToJs($node->name);
    }

    private static function nodeToJs_Identifier(Node\Identifier $node): string
    {
        return $node->name;
    }

    private static function nodeToJs_Expr_Assign(Node\Expr\Assign $node): string
    {
        return self::nodeToJs($node->var) . ' = ' . self::nodeToJs($node->expr);
    }

    private static function nodeToJs_Expr_Array(Node\Expr\Array_ $node): string
    {
        $arrayItems = array_map(fn ($n) => self::nodeToJs($n), $node->items);


        return '[' . implode(', ', $arrayItems) . ']';
    }

    private static function nodeToJs_Expr_ArrayItem(Node\Expr\ArrayItem $node): string
    {
        $key = $node->key !== null ? self::nodeToJs($node->key) . ': ' : '';

        return $key . self::nodeToJs($node->value);
    }

    private static function nodeToJs_Scalar_DNumber(Node\Scalar\DNumber $node): string
    {
        return (string) $node->value;
    }

    private static function nodeToJs_Scalar_LNumber(Node\Scalar\LNumber $node): string
    {
        return (string) $node->value;
    }

    private static function nodeToJs_Expr_ConstFetch(Node\Expr\ConstFetch $node): string
    {
        return self::nodeToJs($node->name);
    }

    private static function nodeToJs_Name(Node\Name $node): string
    {
        if (count($node->parts) !== 1) {
            throw new \Exception("nodeToJs not implemented for Node\\Name of part count != 1");
        }

        return $node->parts[0];
    }

    private static function nodeToJs_Stmt_Class(Node\Stmt\Class_ $node): string
    {
        return 'class ' . self::nodeToJs($node->namespacedName ?? $node->name)
            . "{\n"
            . self::stmtsToJs($node->stmts)
            . "\n}";
    }

    private static function nodeToJs_Expr_New(Node\Expr\New_ $node): string
    {
        return 'new ' . self::nodeToJs($node->class) . '(' . self::argsToJs($node->args) . ')';
    }

    private static function nodeToJs_Stmt_Property(Node\Stmt\Property $node): string
    {
        return '/* TODO: impl nodeToJs_Stmt_Property */';
    }

    private static function nodeToJs_Stmt_ClassMethod(Node\Stmt\ClassMethod $node): string
    {
        return ''; // TODO: impl
    }
}
