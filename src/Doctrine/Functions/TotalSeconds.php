<?php

namespace App\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * "total_seconds" "(" ArithmeticPrimary "," ArithmeticPrimary ")"
 * First argument should be start datetime
 * Second argument should be end datetime
 */
class TotalSeconds extends FunctionNode
{
    /**
     * @var Node
     */
    public $date1;

    /**
     * @var Node
     */
    public $date2;

    /**
     * @override
     * @inheritdoc
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $platformName = $sqlWalker->getConnection()->getDatabasePlatform()->getName();

        $start = $this->date1->dispatch($sqlWalker);
        $end = $this->date2->dispatch($sqlWalker);

        switch ($platformName) {
            case 'sqlite':
                // SUM(julianday(end) - julianday(start)) * 86400
                // julianday is fractional, so multiply by seconds in day
                return "(ROUND(SUM(julianday($end) - julianday($start)) * 86400))";
            case 'postgresql':
                // extract(epoch FROM SUM(end - start))
                return "extract(epoch FROM SUM($end - $start))";
            case 'mysql':
                // SUM(TIMESTAMPDIFF(SECOND, start, end))
                return "SUM(TIMESTAMPDIFF(SECOND, $start, $end))";
            default:
                throw new \Exception("Unsupported database '{$platformName}'");
        }
    }

    /**
     * @override
     * @inheritdoc
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->date1 = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->date2 = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
