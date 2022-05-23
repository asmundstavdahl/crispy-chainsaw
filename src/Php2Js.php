<?php

declare(strict_types=1);

namespace AsmundStavdahl\Php2Js;

use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr;

class Php2Js
{
    private static ?string $source;

    public static function convert(string $filepath): string
    {
        $source = file_get_contents($filepath) ?: '';

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        $ast = $parser->parse($source);

        self::$source = $source;
        $jsCode = implode(
            "\n\n",
            array_map([self::class, 'jsOf'], $ast ?? [])
        );
        self::$source = null;

        return $jsCode;
    }

    private static function jsOf(Node|string|null $node, int $indentation = 0): string
    {
        if ($node instanceof Node) {
            switch (get_class($node))
            {
                case Stmt\Expression::class:
                    return self::jsOf($node->expr);
                case Node\Arg::class:
                    return self::jsOf($node->value);
                case Expr\Variable::class:
                case Node\Expr\ConstFetch::class:
                    return self::jsOf($node->name);
                case Node\Identifier::class:
                    return self::translateSpecialName($node->name);
                case Node\Param::class:
                    return self::jsOf($node->var);
                case Node\Scalar\DNumber::class:
                case Node\Scalar\LNumber::class:
                    return (string) $node->value;
                default:
                    return (fn($m) => self::{$m}($node))("jsOf_{$node->getType()}");
            }
        }

        return $node ?? 'null';
    }

    private static function jsOf_Expr_MethodCall(Node\Expr\MethodCall $node): string
    {
        return self::jsOf($node->var)
            . '.'
            . self::jsOf($node->name)
            . '(' . self::argsToJs($node->args) . ')';
    }

    /**
     * @param Node\Scalar\String_ $node
     * @return string
     */
    private static function jsOf_Scalar_String(Node\Scalar\String_ $node): string
    {
        return match ($node->getAttribute('kind')) {
            # Node\Scalar\String_::KIND_SINGLE_QUOTED
            default
                => "'" . str_replace("'", "\\'", $node->value) . "'"
        };
    }

    private static function jsOf_Expr_ArrowFunction(Node\Expr\ArrowFunction $node): string
    {
        return '(' . self::paramsToJs($node->params) . ') => ' . self::jsOf($node->expr);
    }

    private static function jsOf_Expr_Closure(Node\Expr\Closure $node): string
    {
        return 'function(' . self::paramsToJs($node->params) . "){\n"
            . self::stmtsToJs($node->stmts)
            . "\n}";
    }

    private static function jsOf_Stmt_Namespace(Node\Stmt\Namespace_ $node): string
    {
        return self::stmtsToJs($node->stmts);
    }

    private static function jsOf_Expr_FuncCall(Node\Expr\FuncCall $node): string
    {
        return self::jsOf($node->name) . '(' . self::argsToJs($node->args) . ')';
    }

    private static function stmtsToJs(array $nodes): string
    {
        $stmtsNodeJs = array_map([self::class, 'jsOf'], $nodes);

        return implode("\n", $stmtsNodeJs);
    }

    private static function argsToJs(array $nodes): string
    {
        $argsNodeJs = array_map([self::class, 'jsOf'], $nodes);

        return implode(', ', $argsNodeJs);
    }

    private static function paramsToJs(array $nodes): string
    {
        return self::argsToJs($nodes);
    }

    private static function jsOf_Expr_ArrayDimFetch(Node\Expr\ArrayDimFetch $node): string
    {
        if ($node->dim === null) {
            throw new \Exception("dim of var '" . self::jsOf($node->var) . "' is null, not supported");
        }

        return self::jsOf($node->var)
        . '['
        . self::jsOf($node->dim)
        . ']';
    }

    private static function jsOf_Expr_PropertyFetch(Node\Expr\PropertyFetch $node): string
    {
        return self::jsOf($node->var) . '.' . self::jsOf($node->name);
    }

    private static function jsOf_Identifier(Node\Identifier $node): string
    {
        return $node->name;
    }

    private static function jsOf_Expr_Assign(Node\Expr\Assign $node): string
    {
        return self::jsOf($node->var) . ' = ' . self::jsOf($node->expr);
    }

    private static function jsOf_Expr_Array(Node\Expr\Array_ $node): string
    {
        $arrayItems = array_map(
            fn ($n) =>
            self::jsOf($n ?? 'null'),
            $node->items
        );


        return '[' . implode(', ', $arrayItems) . ']';
    }

    private static function jsOf_Expr_ArrayItem(Node\Expr\ArrayItem $node): string
    {
        $key = $node->key !== null ? self::jsOf($node->key) . ': ' : '';

        return $key . self::jsOf($node->value);
    }

    private static function jsOf_Name(Node\Name $node): string
    {
        if (count($node->parts) !== 1) {
            throw new \Exception("jsOf not implemented for Node\\Name of part count != 1");
        }

        return $node->parts[0];
    }

    private static function jsOf_Stmt_Class(Node\Stmt\Class_ $node): string
    {
        // @phpstan-ignore-next-line
        return 'class ' . self::jsOf($node->namespacedName ?? $node->name)
            . "{\n"
            . self::stmtsToJs($node->stmts)
            . "\n}";
    }

    private static function jsOf_Expr_New(Node\Expr\New_ $node): string
    {
        return '(new ' . self::jsOf($node->class) . '(' . self::argsToJs($node->args) . '))';
    }

    private static function jsOf_Stmt_Property(Node\Stmt\Property $node): string
    {
        $staticPrefix = $node->isStatic() ? 'static ' : '';

        return implode(
            "\n",
            array_map(
                fn($prop) => $staticPrefix . self::jsOf($prop),
                $node->props
            )
        );
    }

    private static function jsOf_Stmt_PropertyProperty(Node\Stmt\PropertyProperty $node): string
    {
        return self::jsOf($node->name) . ' = ' . self::jsOf($node->default);
    }

    private static function jsOf_VarLikeIdentifier(Node\VarLikeIdentifier $node): string
    {
        return $node->name;
    }

    private static function jsOf_Stmt_ClassMethod(Node\Stmt\ClassMethod $node): string
    {
        $staticPrefix = $node->isStatic() ? 'static ' : '';

        return $staticPrefix . self::jsOf($node->name) . '(' . self::paramsToJs($node->params) . ") {\n"
            . self::stmtsToJs($node->stmts ?? [])
            . "\n}";
    }

    private static function jsOf_Stmt_Return(Stmt\Return_ $node): string
    {
        return 'return ' . self::jsOf($node->expr);
    }

    private static function jsOf_Expr_AssignOp_Plus(Expr\AssignOp\Plus $node): string
    {
        return self::jsOf($node->var) . ' += ' . self::jsOf($node->expr);
    }

    private static function jsOf_Expr_BinaryOp_Concat(Expr\BinaryOp\Concat $node): string
    {
        return self::jsOf($node->left) . ' + ' . self::jsOf($node->right);
    }

    private static function dumpNode(Node $node): string
    {
        return \json_encode($node, JSON_PRETTY_PRINT) ?: 'NO JSON (dumpNode)';
    }

    private static function getPhpOf(Node $node): string
    {
        return \implode(" /**/ ",
            \array_slice(
                explode("\n", self::$source ?? ''),
                $node->getStartLine() - 1,
                $node->getEndLine() - 1,
            )
        );
    }

    private static function translateSpecialName(string $name): string
    {
        return match ($name)
        {
            "__construct" => "constructor",
            default => $name
        };
    }
}
